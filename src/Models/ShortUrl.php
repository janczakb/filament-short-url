<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Models;

use App\Models\User;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlGlobalOverview;
use Bjanczak\FilamentShortUrl\Http\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Jobs\SendWebhookJob;
use Bjanczak\FilamentShortUrl\Services\ShortUrlBuilder;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    use Concerns\HasStats;
    use Concerns\HasTargeting;
    use HasFactory;

    protected $table = 'short_urls';

    protected $fillable = [
        'user_id',
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
        'destination_type',
        'rotation_variants',
        'custom_domain_id',
        'folder_id',
        'is_archived',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'user_id' => 'integer',
        'custom_domain_id' => 'integer',
        'folder_id' => 'integer',
        'is_enabled' => 'boolean',
        'is_archived' => 'boolean',
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
        'rotation_variants' => 'array',
        'max_visits' => 'integer',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'expires_at' => 'datetime',
        'qr_scans' => 'integer',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ShortUrlFolder::class, 'folder_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ShortUrlTag::class, 'short_url_tag', 'short_url_id', 'tag_id');
    }

    public function customDomain(): BelongsTo
    {
        return $this->belongsTo(ShortUrlCustomDomain::class, 'custom_domain_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('filament-short-url.user.model', User::class),
            'user_id'
        );
    }

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
    public static function findByKey(string $key, ?string $host = null): ?static
    {
        $host ??= request()?->getHost();
        $mainDomain = parse_url(config('app.url'), PHP_URL_HOST);

        $ttl = config('filament-short-url.cache_ttl', 3600);

        $query = static::where('url_key', $key)->with(['pixels', 'customDomain']);

        if ($host && strcasecmp($host, $mainDomain) !== 0) {
            // Shared cache key with ShortUrlRedirectController \u2014 the controller warms this cache
            // before calling findByKey(), so this lookup is typically a free cache hit.
            $customDomain = cache()->remember(
                "filament-short-url:custom-domain:{$host}",
                300,
                fn () => ShortUrlCustomDomain::where('domain', $host)
                    ->where('is_active', true)
                    ->first()
            );

            if ($customDomain) {
                $query->where('custom_domain_id', $customDomain->id);
            } else {
                return null;
            }
        } else {
            $query->whereNull('custom_domain_id');
        }

        if ($ttl <= 0) {
            return $query->first();
        }

        $cacheKey = "filament-short-url:{$key}:".($host ?? 'default');

        return cache()->remember(
            $cacheKey,
            $ttl,
            fn () => $query->first()
        );
    }

    /**
     * Bust the redirect cache when the model is saved or deleted.
     * Also invalidate the global overview link-count cache (forever cache)
     * on creation or deletion — the count changes only in those cases.
     */
    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (auth()->check() && empty($m->user_id)) {
                $m->user_id = auth()->id();
            }
        });

        static::saving(function (self $m) {
            if ($m->single_use || $m->max_visits !== null || $m->expires_at !== null) {
                if ($m->single_use) {
                    $m->max_visits = null;
                }
                $m->redirect_status_code = 302; // Force temporary redirect to prevent browser caching of limited/expiring URLs
            }

            if ($m->activated_at === null && $m->expires_at === null && $m->deactivated_at === null) {
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
            cache()->forget("filament-short-url:visits:{$m->id}");

            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            $hosts = ['default'];
            if ($appHost) {
                $hosts[] = $appHost;
            }

            // Current domain
            if ($m->custom_domain_id) {
                $domain = ShortUrlCustomDomain::find($m->custom_domain_id);
                if ($domain) {
                    $hosts[] = $domain->domain;
                }
            }

            // If custom domain changed, clear old domain cache too
            if ($m->wasChanged('custom_domain_id')) {
                $oldDomainId = $m->getOriginal('custom_domain_id');
                if ($oldDomainId) {
                    $oldDomain = ShortUrlCustomDomain::find($oldDomainId);
                    if ($oldDomain) {
                        $hosts[] = $oldDomain->domain;
                    }
                }
            }

            // Forget all redirect cache keys for this url_key
            foreach ($hosts as $host) {
                cache()->forget("filament-short-url:{$m->url_key}:{$host}");
            }

            if ($m->wasChanged('url_key')) {
                $oldKey = $m->getOriginal('url_key');
                if ($oldKey) {
                    foreach ($hosts as $host) {
                        cache()->forget("filament-short-url:{$oldKey}:{$host}");
                    }
                }
            }

            // Bust the forever-cached link count on creation or deletion — count changes only then.
            // Using wasRecentlyCreated avoids the double-forget that the separate created() event caused.
            if ($m->wasRecentlyCreated) {
                cache()->forget(ShortUrlGlobalOverview::LINKS_CACHE_KEY);
                $m->dispatchWebhook('created');
            }
        });

        static::deleted(function (self $m) {
            cache()->forget("filament-short-url:visits:{$m->id}");
            cache()->forget(ShortUrlGlobalOverview::LINKS_CACHE_KEY);

            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            $hosts = ['default'];
            if ($appHost) {
                $hosts[] = $appHost;
            }

            if ($m->custom_domain_id) {
                $domain = ShortUrlCustomDomain::find($m->custom_domain_id);
                if ($domain) {
                    $hosts[] = $domain->domain;
                }
            }

            foreach ($hosts as $host) {
                cache()->forget("filament-short-url:{$m->url_key}:{$host}");
            }

            if (! empty($m->qr_logo)) {
                Storage::disk('public')->delete($m->qr_logo);
            }
        });

    }

    /** @return Collection<int, static> */
    public static function findByDestinationUrl(string $url): Collection
    {
        $builder = static::where('destination_url', $url);

        return $builder->get();
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

    public function isActive(): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        // For single-use links the cached model may say is_enabled=true while the DB has
        // already flipped it to false (another request beat us to it). We must re-read from
        // the DB here because the redirect controller dispatches the tracking job (L174)
        // BEFORE performing the atomic disable (L226). A stale cache hit would cause:
        //   1. A phantom visit to be logged for an already-consumed single-use link.
        //   2. Potentially serving content (warning page, pixel page) to the second visitor
        //      before they hit the atomic-update 410 check.
        // The extra DB query is a justified cost: it only fires for single-use links, and
        // only when the cached model still shows is_enabled=true.
        if ($this->single_use) {
            $realEnabled = DB::table($this->table)->where('id', $this->id)->value('is_enabled');
            if (! $realEnabled) {
                return false;
            }
        }

        if ($this->activated_at && $this->activated_at->isFuture()) {
            return false;
        }

        if ($this->deactivated_at && $this->deactivated_at->isPast()) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->max_visits !== null && $this->getRealTimeTotalVisits() >= $this->max_visits) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function trackingEnabled(): bool
    {
        return $this->track_visits;
    }

    /** @return array<int, string> */
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
        $baseUrl = config('app.url');

        if ($this->custom_domain_id && $this->customDomain) {
            // Use https for custom domains in production; fall back to the app.url scheme
            // in other environments (local dev, staging). This prevents branded links from
            // using http:// when the developer hasn't yet updated their app.url to https.
            $appScheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            $scheme = app()->isProduction() ? 'https' : $appScheme;
            $baseUrl = $scheme.'://'.$this->customDomain->domain;
        }

        if (! empty($prefix)) {
            return rtrim($baseUrl, '/').'/'.trim($prefix, '/').'/'.$this->url_key;
        }

        return rtrim($baseUrl, '/').'/'.$this->url_key;
    }

    /**
     * Dispatch webhook if global or per-link webhook is active for the specified event.
     */
    public function dispatchWebhook(string $event, array $extraPayload = []): void
    {
        $targetUrl = $this->webhook_url;
        $globalUrl = config('filament-short-url.global_webhook_url');
        $events = config('filament-short-url.webhook_events', []);

        $webhooksToDispatch = [];
        if (! empty($targetUrl)) {
            $webhooksToDispatch[] = $targetUrl;
        }
        if (! empty($globalUrl) && in_array($event, $events)) {
            $webhooksToDispatch[] = $globalUrl;
        }

        if (empty($webhooksToDispatch)) {
            return;
        }

        $shortUrlData = ($event === 'created')
            ? (new ShortUrlResource($this))->resolve()
            : [
                'id' => $this->id,
                'destination_url' => $this->destination_url,
                'url_key' => $this->url_key,
                'short_url' => $this->getShortUrl(),
                'total_visits' => (int) $this->getRealTimeTotalVisits(),
                'unique_visits' => (int) $this->unique_visits,
            ];

        $payload = array_merge([
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'short_url' => $shortUrlData,
        ], $extraPayload);

        $connection = config('filament-short-url.queue_connection', 'sync');

        foreach (array_unique($webhooksToDispatch) as $url) {
            try {
                $job = new SendWebhookJob(
                    url: $url,
                    event: $event,
                    payload: $payload
                );

                if ($connection) {
                    $job->onConnection($connection);
                } else {
                    $job->onConnection('sync');
                }

                dispatch($job);
            } catch (\Throwable $e) {
                Log::error("[FilamentShortUrl] {$event} webhook dispatch failed", [
                    'url' => $url,
                    'url_key' => $this->url_key,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
