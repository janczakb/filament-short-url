<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services\Redis;

use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Contracts\Redis\Connection as RedisConnectionContract;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Redis;

/**
 * Health-check for queue.connections.redis — used from Settings UI before/after save.
 */
class PluginRedisConnectionTester
{
    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     client: ?string,
     *     redis_connection: ?string,
     *     key_prefix: ?string,
     *     latency_ms: ?float,
     * }
     */
    public function test(string $queueConnectionName = 'redis', ?string $queueName = null, array $previewSettings = []): array
    {
        if ($previewSettings !== []) {
            return app(ShortUrlSettingsManager::class)->withPreviewSettings($previewSettings, fn (): array => $this->runTest($queueConnectionName, $queueName));
        }

        return $this->runTest($queueConnectionName, $queueName);
    }

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     client: ?string,
     *     redis_connection: ?string,
     *     key_prefix: ?string,
     *     latency_ms: ?float,
     * }
     */
    private function runTest(string $queueConnectionName, ?string $queueName): array
    {
        $queueConfig = config('queue.connections.redis');

        if (! is_array($queueConfig) || ($queueConfig['driver'] ?? null) !== 'redis') {
            return [
                'ok' => false,
                'message' => 'queue.connections.redis is missing or not a redis driver in config/queue.php.',
                'client' => null,
                'redis_connection' => null,
                'key_prefix' => null,
                'latency_ms' => null,
            ];
        }

        $connectionName = (string) ($queueConfig['connection'] ?? 'default');

        try {
            $connection = Redis::connection($connectionName);
            $startedAt = microtime(true);

            $this->assertResponds($connection);

            $prefix = $this->detectKeyPrefix($connection);
            $probeKey = $prefix.'filament-short-url:health-check:'.uniqid('', true);

            $connection->pipeline(function ($pipe) use ($probeKey, $prefix): void {
                $pipe->incr($probeKey);
                $pipe->sadd($prefix.'filament-short-url:health-check:dirty', 'probe');
            });

            $value = (int) ($connection->get($probeKey) ?: 0);
            $connection->del($probeKey);
            $connection->srem($prefix.'filament-short-url:health-check:dirty', 'probe');

            if ($value !== 1) {
                return [
                    'ok' => false,
                    'message' => 'Redis responded but INCR/GET probe failed.',
                    'client' => $this->detectClient($connection),
                    'redis_connection' => $connectionName,
                    'key_prefix' => $prefix !== '' ? $prefix : null,
                    'latency_ms' => round((microtime(true) - $startedAt) * 1000, 1),
                ];
            }

            $latencyMs = round((microtime(true) - $startedAt) * 1000, 1);
            $client = $this->detectClient($connection);
            $queueName ??= (string) config('filament-short-url.queue_name', 'default');

            return [
                'ok' => true,
                'message' => trim(sprintf(
                    'Connected via %s on Redis connection "%s"%s (%s ms). INCR + SADD probe OK — same primitives as visit counters. Run worker: php artisan queue:work %s --queue=%s.',
                    $client,
                    $connectionName,
                    $prefix !== '' ? ', prefix: '.$prefix : '',
                    (string) $latencyMs,
                    $queueConnectionName,
                    $queueName,
                )),
                'client' => $client,
                'redis_connection' => $connectionName,
                'key_prefix' => $prefix !== '' ? $prefix : null,
                'latency_ms' => $latencyMs,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'client' => null,
                'redis_connection' => $connectionName,
                'key_prefix' => null,
                'latency_ms' => null,
            ];
        }
    }

    private function assertResponds(RedisConnectionContract $connection): void
    {
        if (method_exists($connection, 'ping')) {
            $response = $connection->ping();

            if ($response === true || $response === 'PONG' || $response === '+PONG') {
                return;
            }
        }

        $response = $connection->command('PING');

        if ($response === true || $response === 'PONG' || $response === '+PONG') {
            return;
        }

        throw new \RuntimeException('Redis PING did not return PONG.');
    }

    private function detectClient(RedisConnectionContract $connection): string
    {
        return $connection instanceof PhpRedisConnection ? 'phpredis' : 'predis';
    }

    private function detectKeyPrefix(RedisConnectionContract $connection): string
    {
        if ($connection instanceof PhpRedisConnection) {
            $options = $connection->client()->getOption(\Redis::OPT_PREFIX);

            return is_string($options) ? $options : '';
        }

        return (string) config('database.redis.options.prefix', '');
    }
}
