<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Models;

use App\Models\User;
use Bjanczak\FilamentShortUrl\Support\HostNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $domain
 * @property bool $is_verified
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ShortUrlCustomDomain extends Model
{
    protected $table = 'short_url_custom_domains';

    protected $fillable = [
        'domain',
        'is_verified',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the owner of this custom domain.
     */
    public function user(): BelongsTo
    {
        $userModel = config('filament-short-url.user.model', User::class);

        return $this->belongsTo($userModel, 'user_id');
    }

    /**
     * Get short URLs mapped to this custom domain.
     */
    public function shortUrls(): HasMany
    {
        return $this->hasMany(ShortUrl::class, 'custom_domain_id');
    }

    /**
     * Invalidate the redirect-layer custom-domain cache on any state change.
     * This ensures that verifying, deactivating, or deleting a domain is reflected
     * immediately without waiting for the 5-minute cache TTL to expire.
     */
    protected static function booted(): void
    {
        static::saving(function (self $m): void {
            $m->domain = HostNormalizer::normalize($m->domain) ?? trim(strtolower($m->domain));

            if (! config('filament-short-url.custom_domains.enforce_dns_on_activate', true)) {
                return;
            }

            if ($m->is_active && ($m->isDirty('is_active') || $m->isDirty('domain') || ! $m->is_verified)) {
                $verified = $m->evaluateDnsVerification();
                $m->is_verified = $verified;

                if (! $verified) {
                    $m->is_active = false;
                }
            }
        });

        static::saved(function (self $m): void {
            $normalizedDomain = HostNormalizer::normalize($m->domain) ?? $m->domain;
            cache()->forget("filament-short-url:custom-domain:{$normalizedDomain}");

            $domainChanged = $m->wasChanged('domain');
            $verifiedChanged = $m->wasChanged('is_verified');
            if ($domainChanged) {
                $oldDomain = $m->getOriginal('domain');
                if ($oldDomain) {
                    $oldNormalized = HostNormalizer::normalize($oldDomain) ?? $oldDomain;
                    cache()->forget("filament-short-url:custom-domain:{$oldNormalized}");
                }
            }

            // Invalidate the redirect cache keys for all short URLs mapped to this domain
            // if the domain name changes or its active status is toggled.
            if ($domainChanged || $m->wasChanged('is_active') || $verifiedChanged) {
                $hosts = [$normalizedDomain];
                if ($domainChanged && isset($oldDomain)) {
                    $hosts[] = HostNormalizer::normalize($oldDomain) ?? $oldDomain;
                }

                $m->shortUrls()
                    ->select('url_key')
                    ->chunk(100, function ($shortUrls) use ($hosts): void {
                        foreach ($shortUrls as $url) {
                            foreach ($hosts as $host) {
                                cache()->forget("filament-short-url:{$url->url_key}:{$host}");
                            }
                        }
                    });
            }
        });

        static::deleted(function (self $m): void {
            $normalizedDomain = HostNormalizer::normalize($m->domain) ?? $m->domain;
            cache()->forget("filament-short-url:custom-domain:{$normalizedDomain}");

            // Clear redirect caches for all URLs mapped to this deleted domain
            $m->shortUrls()
                ->select('url_key')
                ->chunk(100, function ($shortUrls): void {
                    foreach ($shortUrls as $url) {
                        cache()->forget("filament-short-url:{$url->url_key}:{$normalizedDomain}");
                    }
                });
        });
    }

    public static function resolveForHost(string $host): ?self
    {
        $normalizedHost = HostNormalizer::normalize($host);

        if (! $normalizedHost) {
            return null;
        }

        return static::where('domain', $normalizedHost)
            ->where('is_active', true)
            ->where('is_verified', true)
            ->first();
    }

    /**
     * Evaluate DNS without persisting — used during save hooks and manual checks.
     */
    public function evaluateDnsVerification(): bool
    {
        $domain = HostNormalizer::normalize($this->domain) ?? trim(strtolower($this->domain));
        if ($domain === '') {
            return false;
        }

        $hostDomain = HostNormalizer::normalize(parse_url(config('app.url'), PHP_URL_HOST));
        if (empty($hostDomain)) {
            $hostDomain = HostNormalizer::normalize(request()?->getHost());
        }

        if (empty($hostDomain)) {
            return false;
        }

        $hostIps = $this->resolveHostIps($hostDomain);
        $domainIps = $this->resolveHostIps($domain);

        if (! empty($hostIps) && ! empty($domainIps) && ! empty(array_intersect($hostIps, $domainIps))) {
            return true;
        }

        foreach ($this->resolveCnameTargets($domain) as $cnameTarget) {
            $normalizedTarget = HostNormalizer::normalize($cnameTarget) ?? strtolower($cnameTarget);

            if ($normalizedTarget === $hostDomain) {
                return true;
            }

            if (HostNormalizer::normalize('www.'.$normalizedTarget) === $hostDomain) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dynamically resolve DNS records for this custom domain to verify if it points
     * back to the host application server (via CNAME or A record).
     */
    public function verifyDns(): bool
    {
        $isVerified = $this->evaluateDnsVerification();

        if ($this->exists && $this->is_verified !== $isVerified) {
            $this->update(['is_verified' => $isVerified]);
        }

        return $isVerified;
    }

    /**
     * @return list<string>
     */
    private function resolveHostIps(string $host): array
    {
        $host = HostNormalizer::normalize($host) ?? strtolower(trim($host));
        $ips = [];

        try {
            if (function_exists('dns_get_record')) {
                $records = @dns_get_record($host, DNS_A);
                if (is_array($records)) {
                    foreach ($records as $record) {
                        if (! empty($record['ip'])) {
                            $ips[] = $record['ip'];
                        }
                    }
                }
            }

            if (empty($ips)) {
                $resolved = @gethostbynamel($host);
                if (is_array($resolved)) {
                    $ips = array_merge($ips, $resolved);
                } elseif ($ip = @gethostbyname($host)) {
                    if ($ip !== $host) {
                        $ips[] = $ip;
                    }
                }
            }

            if (empty($ips)) {
                $output = @shell_exec('dig +short +time=1 +tries=1 A '.escapeshellarg($host));
                if (is_string($output) && $output !== '') {
                    $ips = array_merge($ips, array_filter(array_map('trim', explode("\n", trim($output)))));
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        return array_values(array_unique(array_filter($ips)));
    }

    /**
     * @return list<string>
     */
    private function resolveCnameTargets(string $domain): array
    {
        $targets = [];

        try {
            if (function_exists('dns_get_record')) {
                $records = @dns_get_record($domain, DNS_CNAME);
                if (is_array($records)) {
                    foreach ($records as $record) {
                        if (! empty($record['target'])) {
                            $targets[] = trim(strtolower(rtrim($record['target'], '.')));
                        }
                    }
                }
            }

            if (empty($targets)) {
                $output = @shell_exec('dig +short +time=1 +tries=1 CNAME '.escapeshellarg($domain));
                if (is_string($output) && trim($output) !== '') {
                    $targets[] = trim(strtolower(rtrim(trim($output), '.')));
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        return array_values(array_unique(array_filter($targets)));
    }
}
