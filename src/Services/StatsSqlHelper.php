<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatsSqlHelper
{
    /**
     * Normalize a calendar day for index-friendly comparisons on short_url_daily_stats.date.
     */
    public static function normalizeDailyStatsDate(string $date): string
    {
        return Carbon::parse($date)->toDateString();
    }

    /**
     * Apply calendar-day comparisons on short_url_daily_stats.date.
     *
     * Uses half-open ranges (date >= from, date < to+1day) so comparisons stay
     * index-friendly and work whether the column stores Y-m-d or Y-m-d H:i:s (SQLite).
     */
    public static function applyDailyStatsDateBefore(Builder|EloquentBuilder $query, string $date): void
    {
        $query->where('date', '<', self::normalizeDailyStatsDate($date));
    }

    public static function applyDailyStatsDateRange(Builder|EloquentBuilder $query, ?string $from, ?string $to): void
    {
        if ($from !== null) {
            $query->where('date', '>=', self::normalizeDailyStatsDate($from));
        }

        if ($to !== null) {
            $query->where('date', '<', Carbon::parse($to)->addDay()->toDateString());
        }
    }

    public static function applyDailyStatsDateEquals(Builder|EloquentBuilder $query, string $date): void
    {
        $day = self::normalizeDailyStatsDate($date);
        $query->where('date', '>=', $day)
            ->where('date', '<', Carbon::parse($day)->addDay()->toDateString());
    }

    /**
     * SQL expression for grouping visits by calendar day.
     */
    public static function visitedAtDateExpression(?string $driver = null): string
    {
        $driver ??= DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', visited_at)",
            'pgsql' => "to_char(visited_at, 'YYYY-MM-DD')",
            'sqlsrv' => 'CAST(visited_at AS DATE)',
            default => "DATE_FORMAT(visited_at, '%Y-%m-%d')",
        };
    }

    /**
     * SQL expression for grouping visits by calendar month.
     */
    public static function visitedAtMonthExpression(?string $driver = null): string
    {
        $driver ??= DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m', visited_at)",
            'pgsql' => "to_char(visited_at, 'YYYY-MM')",
            'sqlsrv' => "FORMAT(visited_at, 'yyyy-MM')",
            default => "DATE_FORMAT(visited_at, '%Y-%m')",
        };
    }
}
