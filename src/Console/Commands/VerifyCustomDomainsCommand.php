<?php

namespace Bjanczak\FilamentShortUrl\Console\Commands;

use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Illuminate\Console\Command;

class VerifyCustomDomainsCommand extends Command
{
    /** @var string */
    protected $signature = 'short-url:verify-custom-domains';

    /** @var string */
    protected $description = 'Re-verify DNS for active custom domains';

    public function handle(): int
    {
        $activeDomains = ShortUrlCustomDomain::query()
            ->where('is_active', true)
            ->get();

        if ($activeDomains->isEmpty()) {
            $this->info('No active custom domains to verify.');

            return self::SUCCESS;
        }

        $verified = 0;
        $failed = 0;

        foreach ($activeDomains as $domain) {
            $isValid = false;

            try {
                $isValid = $domain->verifyDns();
            } catch (\Throwable) {
                $isValid = false;
            }

            if (! $isValid && $domain->is_verified) {
                $domain->update(['is_verified' => false]);
            }

            if ($isValid) {
                $verified++;
            } else {
                $failed++;
            }
        }

        $this->info("DNS verification finished. Verified: {$verified}, failed: {$failed}.");

        return self::SUCCESS;
    }
}
