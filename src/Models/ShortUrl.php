<?php

namespace Bjanczak\FilamentShortUrl\Models;

use Bjanczak\FilamentShortUrl\Services\ShortUrlBuilder;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $destination_url
 * @property string $url_key
 * @property string|null $notes
 * @property bool $is_enabled
 * @property int $redirect_status_code
 * @property Carbon|null $activated_at
 * @property Carbon|null $deactivated_at
 * @property Carbon|null $expires_at
 * @property bool $single_use
 * @property bool $forward_query_params
 * @property bool $track_visits
 * @property bool $track_ip_address
 * @property bool $track_browser
 * @property bool $track_browser_version
 * @property bool $track_operating_system
 * @property bool $track_operating_system_version
 * @property bool $track_device_type
 * @property bool $track_referer_url
 * @property array|null $qr_options
 * @property string|null $ga_tracking_id
 * @property int $total_visits
 * @property int $unique_visits
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ShortUrl extends Model
{
    use HasFactory;

    protected $table = 'short_urls';

    protected $fillable = [
        'destination_url',
        'url_key',
        'notes',
        'is_enabled',
        'redirect_status_code',
        'activated_at',
        'deactivated_at',
        'expires_at',
        'single_use',
        'forward_query_params',
        'track_visits',
        'track_ip_address',
        'track_browser',
        'track_browser_version',
        'track_operating_system',
        'track_operating_system_version',
        'track_device_type',
        'track_referer_url',
        'qr_options',
        'ga_tracking_id',
        'total_visits',
        'unique_visits',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_enabled' => 'boolean',
        'single_use' => 'boolean',
        'forward_query_params' => 'boolean',
        'track_visits' => 'boolean',
        'track_ip_address' => 'boolean',
        'track_browser' => 'boolean',
        'track_browser_version' => 'boolean',
        'track_operating_system' => 'boolean',
        'track_operating_system_version' => 'boolean',
        'track_device_type' => 'boolean',
        'track_referer_url' => 'boolean',
        'qr_options' => 'array',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    public function visits(): HasMany
    {
        return $this->hasMany(ShortUrlVisit::class, 'short_url_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->enabled()
            ->where(fn (Builder $q) => $q
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now())
            );
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }

    // ─── Static Finders ──────────────────────────────────────────────────────

    /**
     * Find by URL key — cached for ultra-fast redirects.
     * Cache is invalidated automatically via model events on save/delete.
     */
    public static function findByKey(string $key): ?static
    {
        $ttl = config('filament-short-url.cache_ttl', 3600);

        if ($ttl <= 0) {
            return static::where('url_key', $key)->first();
        }

        return cache()->remember(
            "filament-short-url:{$key}",
            $ttl,
            fn () => static::where('url_key', $key)->first()
        );
    }

    /**
     * Bust the cache when the model is saved or deleted.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $m) => cache()->forget("filament-short-url:{$m->url_key}"));
        static::deleted(fn (self $m) => cache()->forget("filament-short-url:{$m->url_key}"));
    }

    /** @return Collection<int, static> */
    public static function findByDestinationUrl(string $url): Collection
    {
        return static::where('destination_url', $url)->get();
    }

    /**
     * Start building a ShortUrl programmatically.
     *
     * @example ShortUrl::destination('https://example.com')->withTracing(['utm_source' => 'linkedin'])->create();
     */
    public static function destination(string $url): ShortUrlBuilder
    {
        return app(ShortUrlService::class)->destination($url);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        // Not yet active
        if ($this->activated_at && $this->activated_at->isFuture()) {
            return false;
        }

        // Explicitly deactivated
        if ($this->deactivated_at && $this->deactivated_at->isPast()) {
            return false;
        }

        // TTL expiry
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function trackingEnabled(): bool
    {
        return $this->track_visits;
    }

    /**
     * @return array<string>
     */
    public function trackingFields(): array
    {
        if (! $this->track_visits) {
            return [];
        }

        return array_keys(array_filter([
            'ip_address' => $this->track_ip_address,
            'browser' => $this->track_browser,
            'browser_version' => $this->track_browser_version,
            'operating_system' => $this->track_operating_system,
            'operating_system_version' => $this->track_operating_system_version,
            'device_type' => $this->track_device_type,
            'referer_url' => $this->track_referer_url,
        ]));
    }

    /** @return array<string, mixed> */
    public function getQrOptions(): array
    {
        return array_merge(
            config('filament-short-url.qr_defaults', []),
            $this->qr_options ?? []
        );
    }

    public function getShortUrl(): string
    {
        $prefix = config('filament-short-url.route_prefix', 's');

        return rtrim(config('app.url'), '/')."/{$prefix}/{$this->url_key}";
    }

    /**
     * Atomically increment visit counters — single query when unique,
     * to avoid race conditions and two round-trips.
     */
    public function incrementVisits(bool $isUnique = false): void
    {
        $this->newQuery()
            ->where('id', $this->id)
            ->increment('total_visits', 1, $isUnique ? ['unique_visits' => DB::raw('unique_visits + 1')] : []);
    }

    /**
     * Get cached statistics for this short URL.
     *
     * @return array<string, mixed>
     */
    public function getCachedStats(): array
    {
        $cacheTtl = (int) config('filament-short-url.geo_ip.stats_cache_ttl', 300);
        $cacheKey = "short_url_stats_{$this->id}";

        return cache()->remember($cacheKey, $cacheTtl, function () {
            $visits = $this->visits();

            $totalVisits = (int) ($this->total_visits ?? 0);
            $uniqueVisits = (int) ($this->unique_visits ?? 0);
            $visitsToday = (clone $visits)->where('visited_at', '>=', today()->startOfDay())->count();
            $visitsThisWeek = (clone $visits)->where('visited_at', '>=', now()->startOfWeek())->count();
            $visitsThisMonth = (clone $visits)->where('visited_at', '>=', now()->startOfMonth())->count();

            // Visits by day — last 30 days
            $visitsByDayRaw = (clone $visits)
                ->where('visited_at', '>=', now()->subDays(29)->startOfDay())
                ->selectRaw('DATE(visited_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();

            $visitsByDay = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $visitsByDay[$date] = $visitsByDayRaw[$date] ?? 0;
            }

            // Top countries
            $visitsByCountry = (clone $visits)
                ->whereNotNull('country')
                ->selectRaw('country, country_code, COUNT(*) as count')
                ->groupBy('country', 'country_code')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->mapWithKeys(fn ($row) => [$row->country => $row->count])
                ->toArray();

            // Device types
            $visitsByDevice = (clone $visits)
                ->whereNotNull('device_type')
                ->selectRaw('device_type, COUNT(*) as count')
                ->groupBy('device_type')
                ->orderByDesc('count')
                ->pluck('count', 'device_type')
                ->toArray();

            // Browsers
            $visitsByBrowser = (clone $visits)
                ->whereNotNull('browser')
                ->selectRaw('browser, COUNT(*) as count')
                ->groupBy('browser')
                ->orderByDesc('count')
                ->limit(8)
                ->pluck('count', 'browser')
                ->toArray();

            // Operating systems
            $visitsByOs = (clone $visits)
                ->whereNotNull('operating_system')
                ->selectRaw('operating_system, COUNT(*) as count')
                ->groupBy('operating_system')
                ->orderByDesc('count')
                ->limit(8)
                ->pluck('count', 'operating_system')
                ->toArray();

            // Top referers
            $visitsByReferer = (clone $visits)
                ->whereNotNull('referer_url')
                ->selectRaw('referer_url, COUNT(*) as count')
                ->groupBy('referer_url')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'referer_url')
                ->toArray();

            return [
                'totalVisits' => $totalVisits,
                'uniqueVisits' => $uniqueVisits,
                'visitsToday' => $visitsToday,
                'visitsThisWeek' => $visitsThisWeek,
                'visitsThisMonth' => $visitsThisMonth,
                'visitsByDay' => $visitsByDay,
                'visitsByCountry' => $visitsByCountry,
                'visitsByDevice' => $visitsByDevice,
                'visitsByBrowser' => $visitsByBrowser,
                'visitsByOs' => $visitsByOs,
                'visitsByReferer' => $visitsByReferer,
            ];
        });
    }
}
