<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Rules;

use Bjanczak\FilamentShortUrl\Services\SafeBrowsingService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SafeUrl implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        public SafeBrowsingService $safeBrowsing
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        if (! $this->safeBrowsing->isSafe($value)) {
            $fail(__('filament-short-url::default.safe_browsing_error'));
        }
    }
}
