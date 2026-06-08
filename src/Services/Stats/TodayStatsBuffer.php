<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services\Stats;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Bjanczak\FilamentShortUrl\Services\Redis\PluginRedisConnection;
use Illuminate\Contracts\Redis\Connection as RedisConnectionContract;
use Illuminate\Support\Carbon;

/**
 * Hot-path today counters. Redis queue uses pipelined INCR/PFADD on the dedicated queue Redis connection.
 */
class TodayStatsBuffer
{
    private const string KEY_PREFIX = 'filament-short-url:stats:';

    public function __construct(
        private readonly StatsScalingProfile $profile,
        private readonly PluginRedisConnection $pluginRedis,
    ) {}

    /**
     * Record a human visit into today's live counters (no stats cache busting).
     */
    public function recordVisit(ShortUrl $shortUrl, ShortUrlVisit $visit, bool $isUnique): void
    {
        if (! $this->profile->usesRedisTodayBuffer()) {
            return;
        }

        $connection = $this->redisConnection();

        if ($connection === null) {
            return;
        }

        $prefix = $this->keyPrefix();
        $date = Carbon::parse($visit->visited_at)->toDateString();
        $hour = Carbon::parse($visit->visited_at)->format('Y-m-d H:00');
        $shortUrlId = (int) $shortUrl->id;

        $todayTotalKey = "{$prefix}today:{$shortUrlId}:{$date}:total";
        $todayQrKey = "{$prefix}today:{$shortUrlId}:{$date}:qr";
        $todayUniqueKey = "{$prefix}today:{$shortUrlId}:{$date}:unique";
        $hourTotalKey = "{$prefix}hourly:{$shortUrlId}:{$hour}:total";
        $hourUniqueKey = "{$prefix}hourly:{$shortUrlId}:{$hour}:unique";
        $hourQrKey = "{$prefix}hourly:{$shortUrlId}:{$hour}:qr";

        $connection->pipeline(function ($pipe) use (
            $todayTotalKey,
            $todayQrKey,
            $todayUniqueKey,
            $hourTotalKey,
            $hourUniqueKey,
            $hourQrKey,
            $visit,
            $isUnique,
        ): void {
            $pipe->incr($todayTotalKey);
            $pipe->incr($hourTotalKey);

            if ($visit->is_qr_scan) {
                $pipe->incr($todayQrKey);
                $pipe->incr($hourQrKey);
            }

            if ($isUnique && filled($visit->ip_hash)) {
                $pipe->pfadd($todayUniqueKey, [(string) $visit->ip_hash]);
                $pipe->pfadd($hourUniqueKey, [(string) $visit->ip_hash]);
            }
        });

        $ttl = max(86400, $this->profile->statsCacheTtl() * 4);
        $connection->expire($todayTotalKey, $ttl);
        $connection->expire($todayQrKey, $ttl);
        $connection->expire($todayUniqueKey, $ttl);
        $connection->expire($hourTotalKey, $ttl);
        $connection->expire($hourUniqueKey, $ttl);
        $connection->expire($hourQrKey, $ttl);
    }

    /**
     * @return array{
     *     totalVisits: int,
     *     uniqueVisits: int,
     *     qrScans: int,
     *     source: string,
     * }|null
     */
    public function getTodaySummary(int $shortUrlId, ?string $date = null): ?array
    {
        $date ??= Carbon::today()->toDateString();

        if (! $this->profile->usesRedisTodayBuffer()) {
            return null;
        }

        return $this->readRedisTodaySummary($shortUrlId, $date);
    }

    /**
     * @return array<string, int>
     */
    public function getHourlyTotals(int $shortUrlId, Carbon $start, Carbon $end, string $metric = 'total'): array
    {
        if (! $this->profile->usesRedisTodayBuffer()) {
            return [];
        }

        $connection = $this->redisConnection();

        if ($connection === null) {
            return [];
        }

        $prefix = $this->keyPrefix();
        $suffix = match ($metric) {
            'unique' => 'unique',
            'qr' => 'qr',
            default => 'total',
        };

        $totals = [];
        $current = $start->copy()->startOfHour();
        $endHour = $end->copy()->endOfHour();

        while ($current->lte($endHour)) {
            $bucket = $current->format('Y-m-d H:00');
            $key = "{$prefix}hourly:{$shortUrlId}:{$bucket}:{$suffix}";

            if ($metric === 'unique') {
                $totals[$bucket] = (int) $connection->pfcount($key);
            } else {
                $totals[$bucket] = (int) ($connection->get($key) ?: 0);
            }

            $current->addHour();
        }

        return $totals;
    }

    public function clearToday(int $shortUrlId, ?string $date = null): void
    {
        if (! $this->profile->usesRedisTodayBuffer()) {
            return;
        }

        $connection = $this->redisConnection();

        if ($connection === null) {
            return;
        }

        $date ??= Carbon::today()->toDateString();
        $prefix = $this->keyPrefix();
        $pattern = "{$prefix}*{$shortUrlId}:{$date}*";

        $this->deleteByPattern($connection, $pattern);
        $this->deleteByPattern($connection, "{$prefix}hourly:{$shortUrlId}:{$date}*");
    }

    /**
     * @return array{
     *     totalVisits: int,
     *     uniqueVisits: int,
     *     qrScans: int,
     *     source: string,
     * }|null
     */
    private function readRedisTodaySummary(int $shortUrlId, string $date): ?array
    {
        $connection = $this->redisConnection();

        if ($connection === null) {
            return null;
        }

        $prefix = $this->keyPrefix();

        $total = (int) ($connection->get("{$prefix}today:{$shortUrlId}:{$date}:total") ?: 0);
        $qr = (int) ($connection->get("{$prefix}today:{$shortUrlId}:{$date}:qr") ?: 0);
        $unique = (int) $connection->pfcount("{$prefix}today:{$shortUrlId}:{$date}:unique");

        if ($total === 0 && $qr === 0 && $unique === 0) {
            return null;
        }

        return [
            'totalVisits' => $total,
            'uniqueVisits' => $unique,
            'qrScans' => $qr,
            'source' => 'redis',
        ];
    }

    private function keyPrefix(): string
    {
        return $this->pluginRedis->prefix().self::KEY_PREFIX;
    }

    private function redisConnection(): ?RedisConnectionContract
    {
        return $this->pluginRedis->connection();
    }

    private function deleteByPattern(RedisConnectionContract $connection, string $pattern): void
    {
        $cursor = null;

        do {
            $result = $connection->scan($cursor, ['match' => $pattern, 'count' => 100]);

            if ($result === false) {
                break;
            }

            [$cursor, $keys] = $result;

            if (! empty($keys)) {
                $connection->del(...$keys);
            }
        } while ($cursor !== 0 && $cursor !== '0' && $cursor !== null);
    }
}
