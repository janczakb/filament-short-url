<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Models;

use App\Models\User;
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
        $bust = static function (self $m): void {
            cache()->forget("filament-short-url:custom-domain:{$m->domain}");
        };

        static::saved($bust);
        static::deleted($bust);
    }

    /**
     * Dynamically resolve DNS records for this custom domain to verify if it points
     * back to the host application server (via CNAME or A record).
     */
    public function verifyDns(): bool
    {
        $domain = trim(strtolower($this->domain));
        if (empty($domain)) {
            return false;
        }

        // Get target host domain dynamically from configuration
        $hostDomain = parse_url(config('app.url'), PHP_URL_HOST);
        if (empty($hostDomain)) {
            $hostDomain = request()?->getHost();
        }

        if (empty($hostDomain)) {
            return false;
        }

        $hostDomain = trim(strtolower($hostDomain));

        // 1. Resolve host server IP dynamically
        $hostIp = null;
        try {
            if (function_exists('dns_get_record')) {
                $records = @dns_get_record($hostDomain, DNS_A);
                if (is_array($records) && ! empty($records)) {
                    $hostIp = $records[0]['ip'] ?? null;
                }
            }

            if (empty($hostIp)) {
                $output = shell_exec('dig +short +time=1 +tries=1 A '.escapeshellarg($hostDomain));
                if ($output) {
                    $ips = array_filter(array_map('trim', explode("\n", trim($output))));
                    $hostIp = reset($ips) ?: null;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        if (empty($hostIp)) {
            $hostIp = $_SERVER['SERVER_ADDR'] ?? null;
        }

        $isVerified = false;

        // 2. Resolve domain A records
        $aIps = [];
        try {
            if (function_exists('dns_get_record')) {
                $records = @dns_get_record($domain, DNS_A);
                if (is_array($records)) {
                    $aIps = array_filter(array_column($records, 'ip'));
                }
            }

            if (empty($aIps)) {
                $output = shell_exec('dig +short +time=1 +tries=1 A '.escapeshellarg($domain));
                if ($output) {
                    $aIps = array_filter(array_map('trim', explode("\n", trim($output))));
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        if (! empty($aIps) && $hostIp && in_array($hostIp, $aIps)) {
            $isVerified = true;
        }

        // 3. Check CNAME record
        if (! $isVerified) {
            $cnameTarget = null;
            try {
                if (function_exists('dns_get_record')) {
                    $records = @dns_get_record($domain, DNS_CNAME);
                    if (is_array($records) && ! empty($records)) {
                        $cnameTarget = trim(strtolower(rtrim($records[0]['target'] ?? '', '.')));
                    }
                }

                if (empty($cnameTarget)) {
                    $output = shell_exec('dig +short +time=1 +tries=1 CNAME '.escapeshellarg($domain));
                    if ($output) {
                        $cnameTarget = trim(strtolower(rtrim(trim($output), '.')));
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }

            if ($cnameTarget && strcasecmp($cnameTarget, $hostDomain) === 0) {
                $isVerified = true;
            }
        }

        if ($this->exists && $this->is_verified !== $isVerified) {
            $this->update(['is_verified' => $isVerified]);
        }

        return $isVerified;
    }
}
