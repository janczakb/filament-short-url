<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Models;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlGlobalOverview;
use Bjanczak\FilamentShortUrl\Jobs\IncrementVisitJob;
use Bjanczak\FilamentShortUrl\Services\ClientIpExtractor;
use Bjanczak\FilamentShortUrl\Services\GeoIpService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlBuilder;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\UserAgentParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

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
        'track_browser_language',
        'qr_options',
        'ga_tracking_id',
        'password',
        'show_warning_page',
        'targeting_rules',
        'total_visits',
        'unique_visits',
        'max_visits',
        'expiration_redirect_url',
        'webhook_url',
        'qr_logo',
        'qr_scans',
        'auto_open_app_mobile',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_enabled' => 'boolean',
        'single_use' => 'boolean',
        'forward_query_params' => 'boolean',
        'auto_open_app_mobile' => 'boolean',
        'track_visits' => 'boolean',
        'track_ip_address' => 'boolean',
        'track_browser' => 'boolean',
        'track_browser_version' => 'boolean',
        'track_operating_system' => 'boolean',
        'track_operating_system_version' => 'boolean',
        'track_device_type' => 'boolean',
        'track_referer_url' => 'boolean',
        'track_browser_language' => 'boolean',
        'qr_options' => 'array',
        'show_warning_page' => 'boolean',
        'targeting_rules' => 'array',
        'max_visits' => 'integer',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'expires_at' => 'datetime',
        'qr_scans' => 'integer',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    public function visits(): HasMany
    {
        return $this->hasMany(ShortUrlVisit::class, 'short_url_id');
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(ShortUrlDailyStats::class, 'short_url_id');
    }

    public function pixels(): BelongsToMany
    {
        return $this->belongsToMany(ShortUrlPixel::class, 'short_url_pixel', 'short_url_id', 'pixel_id');
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
                ->whereNull('activated_at')
                ->orWhere('activated_at', '<=', now())
            )
            ->where(fn (Builder $q) => $q
                ->whereNull('deactivated_at')
                ->orWhere('deactivated_at', '>', now())
            )
            ->where(fn (Builder $q) => $q
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now())
            )
            ->where(fn (Builder $q) => $q
                ->whereNull('max_visits')
                ->orWhereRaw('total_visits < max_visits')
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
            return static::where('url_key', $key)->with('pixels')->first();
        }

        return cache()->remember(
            "filament-short-url:{$key}",
            $ttl,
            fn () => static::where('url_key', $key)->with('pixels')->first()
        );
    }

    /**
     * Bust the redirect cache when the model is saved or deleted.
     * Also invalidate the global overview link-count cache (forever cache)
     * on creation or deletion — the count changes only in those cases.
     */
    protected static function booted(): void
    {
        static::saving(function (self $m) {
            if ($m->single_use || $m->max_visits !== null || $m->expires_at !== null) {
                if ($m->single_use) {
                    $m->max_visits = null;
                }
                $m->redirect_status_code = 302; // Force temporary redirect to prevent browser caching of limited/expiring URLs
            }

            if ($m->activated_at === null && $m->expires_at === null) {
                $m->expiration_redirect_url = null;
            }

            if (empty($m->webhook_url)) {
                $m->webhook_url = null;
            }

            if ($m->isDirty('qr_logo') && ! empty($m->qr_logo)) {
                if (str_starts_with($m->qr_logo, 'short-urls/tmp/')) {
                    $tmpPath = $m->qr_logo;
                    $filename = basename($tmpPath);
                    $newPath = 'short-urls/logos/'.$filename;

                    $disk = Storage::disk('public');
                    if ($disk->exists($tmpPath)) {
                        $disk->move($tmpPath, $newPath);
                        $m->qr_logo = $newPath;
                    }
                }
            }
        });

        static::updating(function (self $m) {
            if ($m->isDirty('qr_logo')) {
                $oldLogo = $m->getOriginal('qr_logo');
                if (! empty($oldLogo)) {
                    Storage::disk('public')->delete($oldLogo);
                }
            }
        });

        static::saved(function (self $m) {
            cache()->forget("filament-short-url:{$m->url_key}");
            cache()->forget("filament-short-url:visits:{$m->id}");

            if ($m->wasChanged('url_key')) {
                $oldKey = $m->getOriginal('url_key');
                if ($oldKey) {
                    cache()->forget("filament-short-url:{$oldKey}");
                }
            }
        });
        static::deleted(function (self $m) {
            cache()->forget("filament-short-url:{$m->url_key}");
            cache()->forget("filament-short-url:visits:{$m->id}");
            cache()->forget(ShortUrlGlobalOverview::LINKS_CACHE_KEY);

            if (! empty($m->qr_logo)) {
                Storage::disk('public')->delete($m->qr_logo);
            }
        });

        // Bust the forever-cached link counts displayed in the global overview widget.
        static::created(fn () => cache()->forget(ShortUrlGlobalOverview::LINKS_CACHE_KEY));
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

    public function getRealTimeTotalVisits(): int
    {
        if (config('filament-short-url.counter_buffering.enabled', false)) {
            $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
            $buffered = (int) cache()->get("{$prefix}total:{$this->id}", 0);

            return $this->total_visits + $buffered;
        }

        // Use real-time visit count in cache to keep the cached model instance updated
        $cacheKey = "filament-short-url:visits:{$this->id}";

        return (int) cache()->remember($cacheKey, 3600, fn () => $this->total_visits);
    }

    public function isActive(): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if ($this->single_use) {
            $realEnabled = DB::table($this->table)->where('id', $this->id)->value('is_enabled');
            if (! $realEnabled) {
                return false;
            }
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

        // Visit limit reached
        if (! $this->single_use && $this->max_visits !== null && $this->getRealTimeTotalVisits() >= $this->max_visits) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return true;
        }

        if (! $this->single_use && $this->max_visits !== null && $this->getRealTimeTotalVisits() >= $this->max_visits) {
            return true;
        }

        return false;
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
            'browser_language' => $this->track_browser_language,
        ]));
    }

    /** @return array<string, mixed> */
    public function getQrOptions(): array
    {
        $opts = array_merge(
            config('filament-short-url.qr_defaults', []),
            $this->qr_options ?? []
        );

        if (! empty($this->qr_logo)) {
            $opts['logo'] = route('short-url.logo', ['filename' => basename($this->qr_logo)]);
        }

        return $opts;
    }

    public function getShortUrl(): string
    {
        $prefix = config('filament-short-url.route_prefix');

        if (! empty($prefix)) {
            return rtrim(config('app.url'), '/').'/'.trim($prefix, '/').'/'.$this->url_key;
        }

        return rtrim(config('app.url'), '/').'/'.$this->url_key;
    }

    /**
     * Resolve the targeted destination URL based on request headers/context.
     */
    public function resolveDestinationUrl(Request $request): string
    {
        $rules = $this->targeting_rules;

        if (empty($rules)) {
            return $this->destination_url;
        }

        $type = $rules['type'] ?? 'none';

        if ($type === 'device') {
            $parser = app(UserAgentParser::class);
            $deviceType = $parser->parse($request->userAgent() ?? '')['device_type'];

            if ($deviceType === 'mobile') {
                return $rules['device']['mobile'] ?? $rules['device']['ios'] ?? $this->destination_url;
            }
            if ($deviceType === 'tablet') {
                return $rules['device']['tablet'] ?? $rules['device']['android'] ?? $this->destination_url;
            }

            return $rules['device']['desktop'] ?? $this->destination_url;
        }

        if ($type === 'geo') {
            $countryCode = ClientIpExtractor::getCountryCode($request);
            if (! $countryCode) {
                // Try resolving via GeoIpService
                $ip = ClientIpExtractor::getIp($request);
                $geo = app(GeoIpService::class)->resolve($ip);
                $countryCode = $geo['country_code'] ?? null;
            }

            if ($countryCode) {
                $countryCode = strtoupper(trim($countryCode));
                foreach ($rules['geo'] ?? [] as $rule) {
                    if (strtoupper($rule['country_code'] ?? '') === $countryCode) {
                        return $rule['url'] ?? $this->destination_url;
                    }
                }
            }
        }

        if ($type === 'language') {
            $acceptedLanguages = $request->getLanguages();

            // Pass 1: Exact match (e.g. "en-us" matches "en-us" rule, or "pl" matches "pl" rule)
            foreach ($acceptedLanguages as $acceptedLanguage) {
                $acceptedLanguage = strtolower(trim(str_replace('_', '-', $acceptedLanguage)));
                if (empty($acceptedLanguage)) {
                    continue;
                }

                foreach ($rules['language'] ?? [] as $rule) {
                    $ruleLang = strtolower(trim(str_replace('_', '-', $rule['language_code'] ?? '')));
                    if ($ruleLang === $acceptedLanguage) {
                        return $rule['url'] ?? $this->destination_url;
                    }
                }
            }

            // Pass 2: Primary language fallback match (e.g. "en-us" matches general "en" rule)
            foreach ($acceptedLanguages as $acceptedLanguage) {
                $acceptedLanguage = strtolower(trim(str_replace('_', '-', $acceptedLanguage)));
                if (empty($acceptedLanguage)) {
                    continue;
                }

                $parts = explode('-', $acceptedLanguage);
                $primaryLang = strtolower(trim($parts[0]));

                foreach ($rules['language'] ?? [] as $rule) {
                    $ruleLang = strtolower(trim(str_replace('_', '-', $rule['language_code'] ?? '')));
                    if ($ruleLang === $primaryLang) {
                        return $rule['url'] ?? $this->destination_url;
                    }
                }
            }
        }

        if ($type === 'rotation') {
            $items = $rules['rotation'] ?? [];
            if (! empty($items)) {
                $totalWeight = array_sum(array_column($items, 'weight'));
                if ($totalWeight > 0) {
                    $rand = mt_rand(1, $totalWeight);
                    $currentWeight = 0;
                    foreach ($items as $item) {
                        $currentWeight += (int) ($item['weight'] ?? 0);
                        if ($rand <= $currentWeight) {
                            return $item['url'] ?? $this->destination_url;
                        }
                    }
                }
            }
        }

        return $this->destination_url;
    }

    /**
     * Atomically increment visit counters — single query when unique,
     * to avoid race conditions and two round-trips. Supports write-back caching.
     */
    public function incrementVisits(bool $isUnique = false, bool $isQrScan = false): void
    {
        if (config('filament-short-url.counter_buffering.enabled', false)) {
            $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
            try {
                // Increment atomically in cache (works on Redis, Memcached, Database, File, etc.)
                cache()->increment("{$prefix}total:{$this->id}");

                if ($isUnique) {
                    cache()->increment("{$prefix}unique:{$this->id}");
                }

                if ($isQrScan) {
                    cache()->increment("{$prefix}qr:{$this->id}");
                }

                // Add to dirty IDs list atomically
                if (cache()->getDefaultDriver() === 'redis' && class_exists(Redis::class)) {
                    Redis::sadd("{$prefix}dirty_ids", $this->id);
                } else {
                    // Safe, concurrent-proof fallback for non-Redis stores using Cache locks
                    $lock = cache()->lock("{$prefix}dirty_ids_lock", 2);
                    $lock->get(function () use ($prefix) {
                        $dirtyIds = cache()->get("{$prefix}dirty_ids", []);
                        if (! is_array($dirtyIds)) {
                            $dirtyIds = [];
                        }
                        if (! in_array($this->id, $dirtyIds)) {
                            $dirtyIds[] = $this->id;
                            cache()->forever("{$prefix}dirty_ids", $dirtyIds);
                        }
                    });
                }

                return;
            } catch (\Throwable $e) {
                // Log and fall back to queue job below if caching backend fails
            }

            // Safe fallback: Dispatch async job so clicks are queued and not lost on cache clear
            $connection = config('filament-short-url.queue_connection', 'sync');
            dispatch((new IncrementVisitJob($this->id, $isUnique, $isQrScan))->onConnection($connection ?: 'sync'));

            return;
        }

        $updates = [];
        if ($isUnique) {
            $updates['unique_visits'] = DB::raw('unique_visits + 1');
        }
        if ($isQrScan) {
            $updates['qr_scans'] = DB::raw('qr_scans + 1');
        }

        $this->newQuery()
            ->where('id', $this->id)
            ->increment('total_visits', 1, $updates);

        // Keep the real-time cache count incremented
        $cacheKey = "filament-short-url:visits:{$this->id}";
        try {
            if (cache()->has($cacheKey)) {
                cache()->increment($cacheKey);
            } else {
                cache()->put($cacheKey, $this->total_visits + 1, 3600);
            }
        } catch (\Throwable $e) {
            // Ignore cache errors in increment to never disrupt redirection
        }
    }

    /**
     * Get cached statistics for this short URL.
     *
     * @return array<string, mixed>
     */
    public function getCachedStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFromClean = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : null;
        $dateToClean = $dateTo ? Carbon::parse($dateTo)->toDateString() : null;

        $cacheTtl = (int) config('filament-short-url.geo_ip.stats_cache_ttl', 300);
        $cacheKey = "short_url_stats_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all');

        return cache()->remember($cacheKey, $cacheTtl, function () use ($dateFromClean, $dateToClean) {
            $today = Carbon::today()->toDateString();

            // 1. Fetch daily stats (aggregated historical data)
            $dailyQuery = $this->dailyStats()->where('date', '<', $today);
            if ($dateFromClean) {
                $dailyQuery->where('date', '>=', $dateFromClean);
            }
            if ($dateToClean && $dateToClean < $today) {
                $dailyQuery->where('date', '<=', $dateToClean);
            }
            $dailyStatsRows = $dailyQuery->get();

            // 2. Fetch raw visits for today (if within date range)
            $includeToday = ($dateToClean === null || $dateToClean >= $today);
            if ($dateFromClean && $dateFromClean > $today) {
                $includeToday = false;
            }

            $rawVisits = [];
            if ($includeToday) {
                $rawVisits = $this->visits()->where('visited_at', '>=', $today.' 00:00:00')->get();
            }

            // Helper to merge associative stats arrays
            $mergeStats = function (array $base, ?array $additional): array {
                if (empty($additional)) {
                    return $base;
                }
                foreach ($additional as $key => $val) {
                    $base[$key] = ($base[$key] ?? 0) + $val;
                }

                return $base;
            };

            // Initialize metrics
            $totalVisits = 0;
            $uniqueVisitsCount = 0;
            $visitsToday = count($rawVisits);
            $visitsThisWeek = 0;
            $visitsThisMonth = 0;
            $qrScans = 0;

            $visitsByCountry = [];
            $visitsByCity = [];
            $visitsByDevice = [];
            $visitsByBrowser = [];
            $visitsByOs = [];
            $visitsByReferer = [];
            $utmSources = [];
            $utmMediums = [];
            $utmCampaigns = [];
            $visitsByLanguage = [];

            // Sum up daily stats
            $startOfWeek = now()->startOfWeek()->toDateString();
            $startOfMonth = now()->startOfMonth()->toDateString();

            foreach ($dailyStatsRows as $row) {
                $totalVisits += $row->visits_count;
                $uniqueVisitsCount += $row->unique_visits_count;
                $qrScans += $row->qr_visits_count ?? 0;

                $rowDate = $row->date->toDateString();
                if ($rowDate >= $startOfWeek) {
                    $visitsThisWeek += $row->visits_count;
                }
                if ($rowDate >= $startOfMonth) {
                    $visitsThisMonth += $row->visits_count;
                }

                $visitsByCountry = $mergeStats($visitsByCountry, $row->country_stats);
                $visitsByCity = $mergeStats($visitsByCity, $row->city_stats);
                $visitsByDevice = $mergeStats($visitsByDevice, $row->device_stats);
                $visitsByBrowser = $mergeStats($visitsByBrowser, $row->browser_stats);
                $visitsByOs = $mergeStats($visitsByOs, $row->os_stats);
                $visitsByReferer = $mergeStats($visitsByReferer, $row->referer_stats);
                $utmSources = $mergeStats($utmSources, $row->utm_source_stats);
                $utmMediums = $mergeStats($utmMediums, $row->utm_medium_stats);
                $utmCampaigns = $mergeStats($utmCampaigns, $row->utm_campaign_stats);
                $visitsByLanguage = $mergeStats($visitsByLanguage, $row->language_stats);
            }

            // Combine today's raw visits
            if ($includeToday) {
                $totalVisits += count($rawVisits);
                $uniqueVisitsCount += count(array_unique(array_filter($rawVisits->pluck('ip_hash')->toArray())));

                $visitsThisWeek += count($rawVisits);
                $visitsThisMonth += count($rawVisits);

                foreach ($rawVisits as $visit) {
                    if ($visit->is_qr_scan) {
                        $qrScans++;
                    }
                    if ($visit->browser_language) {
                        $visitsByLanguage[$visit->browser_language] = ($visitsByLanguage[$visit->browser_language] ?? 0) + 1;
                    }
                    if ($visit->country) {
                        $visitsByCountry[$visit->country] = ($visitsByCountry[$visit->country] ?? 0) + 1;
                    }
                    if ($visit->city) {
                        $cityKey = "{$visit->city} ({$visit->country_code})";
                        $visitsByCity[$cityKey] = ($visitsByCity[$cityKey] ?? 0) + 1;
                    }
                    if ($visit->device_type) {
                        $visitsByDevice[$visit->device_type] = ($visitsByDevice[$visit->device_type] ?? 0) + 1;
                    }
                    if ($visit->browser) {
                        $visitsByBrowser[$visit->browser] = ($visitsByBrowser[$visit->browser] ?? 0) + 1;
                    }
                    if ($visit->operating_system) {
                        $visitsByOs[$visit->operating_system] = ($visitsByOs[$visit->operating_system] ?? 0) + 1;
                    }
                    $refererHost = $visit->referer_host ?: 'Direct';
                    $visitsByReferer[$refererHost] = ($visitsByReferer[$refererHost] ?? 0) + 1;

                    if ($visit->utm_source) {
                        $utmSources[$visit->utm_source] = ($utmSources[$visit->utm_source] ?? 0) + 1;
                    }
                    if ($visit->utm_medium) {
                        $utmMediums[$visit->utm_medium] = ($utmMediums[$visit->utm_medium] ?? 0) + 1;
                    }
                    if ($visit->utm_campaign) {
                        $utmCampaigns[$visit->utm_campaign] = ($utmCampaigns[$visit->utm_campaign] ?? 0) + 1;
                    }
                }
            }

            // Build visitsByDay timeline
            $chartFrom = $dateFromClean ? Carbon::parse($dateFromClean) : now()->subDays(29)->startOfDay();
            $chartTo = $dateToClean ? Carbon::parse($dateToClean) : now()->endOfDay();
            $daysDiff = (int) $chartFrom->diffInDays($chartTo);

            $visitsByDay = [];
            if ($daysDiff > 90) {
                // Group by month
                foreach ($dailyStatsRows as $row) {
                    $m = $row->date->format('Y-m');
                    $visitsByDay[$m] = ($visitsByDay[$m] ?? 0) + $row->visits_count;
                }
                if ($includeToday) {
                    $mToday = Carbon::parse($today)->format('Y-m');
                    $visitsByDay[$mToday] = ($visitsByDay[$mToday] ?? 0) + count($rawVisits);
                }
            } else {
                // Initialize timeline with zeros
                for ($i = $daysDiff; $i >= 0; $i--) {
                    $d = (clone $chartTo)->subDays($i)->format('Y-m-d');
                    $visitsByDay[$d] = 0;
                }
                // Fill daily stats
                foreach ($dailyStatsRows as $row) {
                    $d = $row->date->format('Y-m-d');
                    if (isset($visitsByDay[$d])) {
                        $visitsByDay[$d] = $row->visits_count;
                    }
                }
                // Fill today
                if ($includeToday && isset($visitsByDay[$today])) {
                    $visitsByDay[$today] = count($rawVisits);
                }
            }

            // Sort distributions descending
            arsort($visitsByCountry);
            arsort($visitsByCity);
            arsort($visitsByDevice);
            arsort($visitsByBrowser);
            arsort($visitsByOs);
            arsort($visitsByReferer);
            arsort($utmSources);
            arsort($utmMediums);
            arsort($utmCampaigns);
            arsort($visitsByLanguage);

            return [
                'totalVisits' => $totalVisits,
                'uniqueVisits' => $uniqueVisitsCount,
                'visitsToday' => $visitsToday,
                'visitsThisWeek' => $visitsThisWeek,
                'visitsThisMonth' => $visitsThisMonth,
                'visitsByDay' => $visitsByDay,
                'visitsByCountry' => array_slice($visitsByCountry, 0, 10, true),
                'visitsByCity' => array_slice($visitsByCity, 0, 10, true),
                'visitsByDevice' => $visitsByDevice,
                'visitsByBrowser' => array_slice($visitsByBrowser, 0, 8, true),
                'visitsByOs' => array_slice($visitsByOs, 0, 8, true),
                'visitsByReferer' => array_slice($visitsByReferer, 0, 10, true),
                'utmSources' => array_slice($utmSources, 0, 8, true),
                'utmMediums' => array_slice($utmMediums, 0, 8, true),
                'utmCampaigns' => array_slice($utmCampaigns, 0, 8, true),
                'qrScans' => $qrScans,
                'visitsByLanguage' => array_slice($visitsByLanguage, 0, 10, true),
            ];
        });
    }

    /**
     * Cache properties to hold preloaded buffered visits for the current request.
     */
    protected static ?array $bufferedTotalVisits = null;

    protected static ?array $bufferedUniqueVisits = null;

    protected static ?array $bufferedQrScans = null;

    /**
     * Preload all buffered clicks in a single batch query for the entire request.
     * Prevents N+1 database queries even if database cache driver is used.
     */
    protected static function loadAllBufferedVisits(): void
    {
        if (static::$bufferedTotalVisits !== null) {
            return;
        }

        static::$bufferedTotalVisits = [];
        static::$bufferedUniqueVisits = [];
        static::$bufferedQrScans = [];

        if (! config('filament-short-url.counter_buffering.enabled', false)) {
            return;
        }

        $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
        $dirtyKey = "{$prefix}dirty_ids";

        // 1. Fetch the list of dirty IDs (URLs with pending buffered clicks) in one query
        $dirtyIds = [];
        try {
            if (cache()->getDefaultDriver() === 'redis' && class_exists(Redis::class)) {
                $dirtyIds = Redis::smembers($dirtyKey);
            } else {
                $dirtyIds = cache()->get($dirtyKey, []);
            }
        } catch (\Throwable) {
            // Fallback
        }

        if (empty($dirtyIds)) {
            return;
        }

        $dirtyIds = array_unique(array_filter((array) $dirtyIds));

        // 2. Build array of keys to fetch in a single cache store read
        $totalKeys = [];
        $uniqueKeys = [];
        $qrKeys = [];
        foreach ($dirtyIds as $id) {
            $totalKeys[$id] = "{$prefix}total:{$id}";
            $uniqueKeys[$id] = "{$prefix}unique:{$id}";
            $qrKeys[$id] = "{$prefix}qr:{$id}";
        }

        try {
            // Cache::many() is highly optimized (e.g. 1 database query for database store, or 1 MGET for Redis)
            $totals = cache()->many(array_values($totalKeys));
            $uniques = cache()->many(array_values($uniqueKeys));
            $qrs = cache()->many(array_values($qrKeys));

            foreach ($totalKeys as $id => $key) {
                static::$bufferedTotalVisits[$id] = (int) ($totals[$key] ?? 0);
            }
            foreach ($uniqueKeys as $id => $key) {
                static::$bufferedUniqueVisits[$id] = (int) ($uniques[$key] ?? 0);
            }
            foreach ($qrKeys as $id => $key) {
                static::$bufferedQrScans[$id] = (int) ($qrs[$key] ?? 0);
            }
        } catch (\Throwable) {
            // Fallback
        }
    }

    /**
     * Get the total visits count, merging the database value with any buffered clicks in cache.
     * Prevents database N+1 queries.
     */
    public function getTotalVisitsAttribute(): int
    {
        $dbValue = $this->attributes['total_visits'] ?? 0;

        if (! config('filament-short-url.counter_buffering.enabled', false)) {
            return $dbValue;
        }

        static::loadAllBufferedVisits();

        $buffered = static::$bufferedTotalVisits[$this->id] ?? 0;

        return $dbValue + $buffered;
    }

    /**
     * Get the unique visits count, merging the database value with any buffered clicks in cache.
     * Prevents database N+1 queries.
     */
    public function getUniqueVisitsAttribute(): int
    {
        $dbValue = $this->attributes['unique_visits'] ?? 0;

        if (! config('filament-short-url.counter_buffering.enabled', false)) {
            return $dbValue;
        }

        static::loadAllBufferedVisits();

        $buffered = static::$bufferedUniqueVisits[$this->id] ?? 0;

        return $dbValue + $buffered;
    }

    /**
     * Get the QR scans count, merging the database value with any buffered clicks in cache.
     * Prevents database N+1 queries.
     */
    public function getQrScansAttribute(): int
    {
        $dbValue = $this->attributes['qr_scans'] ?? 0;

        if (! config('filament-short-url.counter_buffering.enabled', false)) {
            return $dbValue;
        }

        static::loadAllBufferedVisits();

        $buffered = static::$bufferedQrScans[$this->id] ?? 0;

        return $dbValue + $buffered;
    }
}
