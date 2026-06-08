<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Requests;

use Bjanczak\FilamentShortUrl\Http\Support\ApiLinkScope;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Validation\Rule;

class UpsertShortUrlRequest extends StoreShortUrlRequest
{
    protected ?ShortUrl $existingModel = null;

    protected function prepareForValidation(): void
    {
        $this->existingModel = $this->resolveExisting();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $existing = $this->existingModel ?? $this->resolveExisting();

        if ($existing) {
            $rules['url_key'] = [
                'nullable',
                'string',
                'alpha_dash',
                'max:32',
                Rule::unique('short_urls', 'url_key')
                    ->ignore($existing->id)
                    ->where(function ($query) use ($existing) {
                        $domainScopeId = (int) ($this->input('custom_domain_id', $existing->custom_domain_id) ?? 0);

                        return $query->where('domain_scope_id', $domainScopeId);
                    }),
            ];

            if (config('filament-short-url.lock_url_key', false)) {
                $rules['url_key'][] = function (string $attribute, $value, \Closure $fail) use ($existing): void {
                    if ($value !== null && $value !== $existing->url_key) {
                        $fail(__('filament-short-url::default.url_key_locked_error'));
                    }
                };

                $rules['custom_domain_id'][] = function (string $attribute, $value, \Closure $fail) use ($existing): void {
                    $original = $existing->custom_domain_id !== null ? (int) $existing->custom_domain_id : null;
                    $newVal = $value !== null && $value !== '' ? (int) $value : null;

                    if ($newVal !== $original) {
                        $fail(__('filament-short-url::default.custom_domain_locked_error'));
                    }
                };
            }

            $rules['external_id'] = 'nullable|string|max:255|unique:short_urls,external_id,'.$existing->id;
        }

        return $rules;
    }

    public function getExistingModel(): ?ShortUrl
    {
        return $this->existingModel ?? $this->resolveExisting();
    }

    protected function resolveExisting(): ?ShortUrl
    {
        $query = ApiLinkScope::query($this);

        if ($this->filled('external_id')) {
            return (clone $query)->where('external_id', $this->input('external_id'))->first();
        }

        if ($this->filled('url_key') && $this->filled('destination_url')) {
            return (clone $query)
                ->where('url_key', $this->input('url_key'))
                ->where('destination_url', $this->input('destination_url'))
                ->first();
        }

        return null;
    }
}
