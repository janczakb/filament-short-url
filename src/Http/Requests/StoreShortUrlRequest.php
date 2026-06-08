<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Requests;

use Bjanczak\FilamentShortUrl\Http\Requests\Concerns\ShortUrlApiAttributes;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Bjanczak\FilamentShortUrl\Rules\OutboundUrl;
use Bjanczak\FilamentShortUrl\Rules\SafeUrl;
use Bjanczak\FilamentShortUrl\Services\SafeBrowsingService;
use Bjanczak\FilamentShortUrl\Support\CustomDomainValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShortUrlRequest extends FormRequest
{
    use ShortUrlApiAttributes;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $safeBrowsing = app(SafeBrowsingService::class);
        $safeUrlRule = app(SafeUrl::class);

        $countries = __('filament-short-url::countries');
        $countryRule = is_array($countries) && ! empty($countries)
            ? 'in:'.implode(',', array_merge(array_keys($countries), array_map('strtolower', array_keys($countries))))
            : 'string|max:10';

        $languages = __('filament-short-url::languages');
        $languageRule = is_array($languages) && ! empty($languages)
            ? 'in:'.implode(',', array_merge(array_keys($languages), array_map('strtoupper', array_keys($languages))))
            : 'string|max:10';

        $safeBrowsingRule = $safeUrlRule;

        $isLegacyRules = is_array($this->input('targeting_rules')) && isset($this->input('targeting_rules')['type']);

        $targetingRules = [];
        if ($isLegacyRules) {
            $targetingRules = [
                'targeting_rules' => 'nullable|array',
                'targeting_rules.type' => 'required_with:targeting_rules|string|in:none,device,geo,language,rotation',
                'targeting_rules.device' => 'nullable|array',
                'targeting_rules.device.mobile' => ['nullable', 'url', 'max:2048', $safeBrowsingRule],
                'targeting_rules.device.tablet' => ['nullable', 'url', 'max:2048', $safeBrowsingRule],
                'targeting_rules.device.desktop' => ['nullable', 'url', 'max:2048', $safeBrowsingRule],
                'targeting_rules.device.ios' => ['nullable', 'url', 'max:2048', $safeBrowsingRule],
                'targeting_rules.device.android' => ['nullable', 'url', 'max:2048', $safeBrowsingRule],
                'targeting_rules.geo' => 'nullable|array',
                'targeting_rules.geo.*.country_code' => 'required_with:targeting_rules.geo|distinct:ignore_case|'.$countryRule,
                'targeting_rules.geo.*.url' => ['required_with:targeting_rules.geo', 'url', 'max:2048', $safeBrowsingRule],
                'targeting_rules.language' => 'nullable|array',
                'targeting_rules.language.*.language_code' => 'required_with:targeting_rules.language|distinct:ignore_case|'.$languageRule,
                'targeting_rules.language.*.url' => ['required_with:targeting_rules.language', 'url', 'max:2048', $safeBrowsingRule],
                'targeting_rules.rotation' => 'nullable|array',
                'targeting_rules.rotation.*.url' => ['required_with:targeting_rules.rotation', 'url', 'max:2048', $safeBrowsingRule],
                'targeting_rules.rotation.*.weight' => 'required_with:targeting_rules.rotation|integer|min:1|max:1000',
            ];
        } else {
            $targetingRules = [
                'targeting_rules' => [
                    'nullable',
                    'array',
                    'max:10',
                    function (string $attribute, $value, \Closure $fail) use ($safeBrowsing) {
                        if (! is_array($value)) {
                            return;
                        }
                        foreach ($value as $index => $rule) {
                            if (! is_array($rule)) {
                                $fail("Targeting rule at index {$index} must be an array.");

                                continue;
                            }
                            $allowedKeys = ['match', 'destination_type', 'url', 'variants', 'filters'];
                            $invalidKeys = array_diff(array_keys($rule), $allowedKeys);
                            if (! empty($invalidKeys)) {
                                $fail("Invalid keys in targeting rule at index {$index}: ".implode(', ', $invalidKeys));
                            }

                            $destType = $rule['destination_type'] ?? 'single';
                            if ($destType === 'split') {
                                if (empty($rule['variants']) || ! is_array($rule['variants'])) {
                                    $fail("Targeting rule at index {$index} requires a non-empty 'variants' array for split destination type.");
                                } else {
                                    $variants = $rule['variants'];
                                    $variantsCount = count($variants);
                                    if ($variantsCount < 2 || $variantsCount > 5) {
                                        $fail("Targeting rule at index {$index} must have between 2 and 5 variants.");

                                        continue;
                                    }

                                    $variantSum = 0;
                                    foreach ($variants as $vIndex => $variant) {
                                        if (! is_array($variant)) {
                                            $fail("Variant at index {$vIndex} of targeting rule {$index} must be an array.");

                                            continue;
                                        }

                                        // Guard against random keys in variant
                                        $allowedVariantKeys = ['label', 'url', 'weight'];
                                        $invalidVariantKeys = array_diff(array_keys($variant), $allowedVariantKeys);
                                        if (! empty($invalidVariantKeys)) {
                                            $fail("Invalid keys in variant at index {$vIndex} of targeting rule {$index}: ".implode(', ', $invalidVariantKeys));
                                        }

                                        if (empty($variant['url']) || ! filter_var($variant['url'], FILTER_VALIDATE_URL)) {
                                            $fail("Variant at index {$vIndex} of targeting rule {$index} requires a valid 'url'.");
                                        } else {
                                            if (! $safeBrowsing->isSafe($variant['url'])) {
                                                $fail("Variant at index {$vIndex} of targeting rule {$index} URL has been flagged by Google Safe Browsing as unsafe.");
                                            }
                                        }
                                        if (empty($variant['label']) || ! is_string($variant['label'])) {
                                            $fail("Variant at index {$vIndex} of targeting rule {$index} requires a string 'label'.");
                                        }
                                        if (! isset($variant['weight']) || ! is_numeric($variant['weight'])) {
                                            $fail("Variant at index {$vIndex} of targeting rule {$index} requires a numeric 'weight'.");
                                        } else {
                                            $weight = $variant['weight'];
                                            if (floor($weight) != $weight) {
                                                $fail("Variant at index {$vIndex} of targeting rule {$index} weight must be an integer.");
                                            } else {
                                                $weight = (int) $weight;
                                                if ($weight < 0 || $weight > 100) {
                                                    $fail("Variant at index {$vIndex} of targeting rule {$index} weight must be between 0 and 100.");
                                                }
                                                $variantSum += $weight;
                                            }
                                        }
                                    }

                                    if ($variantSum !== 100) {
                                        $fail("Suma udziałów w ruchu dla targeting rule {$index} musi wynosić dokładnie 100%. Obecna suma: {$variantSum}%.");
                                    }
                                }
                            } else {
                                if (empty($rule['url']) || ! filter_var($rule['url'], FILTER_VALIDATE_URL)) {
                                    $fail("Targeting rule at index {$index} requires a valid 'url' for single destination type.");
                                } else {
                                    if (! $safeBrowsing->isSafe($rule['url'])) {
                                        $fail("Targeting rule at index {$index} URL has been flagged by Google Safe Browsing as unsafe.");
                                    }
                                }
                                if (! empty($rule['variants'])) {
                                    $fail("Targeting rule at index {$index} must not have 'variants' for single destination type.");
                                }
                            }
                        }
                    },
                ],
                'targeting_rules.*.match' => 'required_with:targeting_rules|string|in:or,and',
                'targeting_rules.*.destination_type' => 'nullable|string|in:single,split',
                'targeting_rules.*.url' => 'nullable|url|max:2048',
                'targeting_rules.*.variants' => 'nullable|array',
                'targeting_rules.*.filters' => [
                    'required_with:targeting_rules',
                    'array',
                    'min:1',
                    function (string $attribute, $value, \Closure $fail) {
                        if (! is_array($value)) {
                            return;
                        }
                        $types = collect($value)->pluck('type');
                        if ($types->duplicates()->isNotEmpty()) {
                            $fail('Each filter type (device, platform, country, language) can only be added once.');
                        }

                        foreach ($value as $index => $filter) {
                            if (! is_array($filter)) {
                                $fail("Filter at index {$index} must be an array.");

                                continue;
                            }

                            $allowedFilterKeys = ['type', 'data'];
                            $invalidFilterKeys = array_diff(array_keys($filter), $allowedFilterKeys);
                            if (! empty($invalidFilterKeys)) {
                                $fail("Invalid keys in filter at index {$index}: ".implode(', ', $invalidFilterKeys));

                                continue;
                            }

                            $type = $filter['type'] ?? null;
                            $data = $filter['data'] ?? null;

                            if (! in_array($type, ['device', 'platform', 'country', 'language'])) {
                                continue;
                            }

                            if (! is_array($data)) {
                                $fail("Filter data for type '{$type}' must be an array.");

                                continue;
                            }

                            $allowedKeys = match ($type) {
                                'device' => ['devices'],
                                'platform' => ['platforms'],
                                'country' => ['countries'],
                                'language' => ['languages'],
                            };

                            $invalidKeys = array_diff(array_keys($data), $allowedKeys);
                            if (! empty($invalidKeys)) {
                                $fail("Invalid keys in data for filter '{$type}': ".implode(', ', $invalidKeys));

                                continue;
                            }

                            $mainKey = $allowedKeys[0];
                            if (! isset($data[$mainKey]) || ! is_array($data[$mainKey]) || empty($data[$mainKey])) {
                                $fail("Filter '{$type}' requires a non-empty array named '{$mainKey}'.");

                                continue;
                            }
                        }
                    },
                ],
                'targeting_rules.*.filters.*.type' => 'required_with:targeting_rules|string|in:device,platform,country,language',
                'targeting_rules.*.filters.*.data' => 'required_with:targeting_rules|array',
                'targeting_rules.*.filters.*.data.devices' => 'nullable|array',
                'targeting_rules.*.filters.*.data.devices.*' => 'string|in:desktop,mobile,tablet',
                'targeting_rules.*.filters.*.data.platforms' => 'nullable|array',
                'targeting_rules.*.filters.*.data.platforms.*' => 'string|in:android,fire_os,ios,linux,mac,windows',
                'targeting_rules.*.filters.*.data.countries' => 'nullable|array',
                'targeting_rules.*.filters.*.data.countries.*' => 'string|'.$countryRule,
                'targeting_rules.*.filters.*.data.languages' => 'nullable|array',
                'targeting_rules.*.filters.*.data.languages.*' => 'string|'.$languageRule,
            ];
        }

        $destType = $this->input('destination_type', 'single');

        $rotationVariantsRules = ['array'];
        if ($destType === 'split') {
            $rotationVariantsRules[] = 'required';
            $rotationVariantsRules[] = 'min:2';
            $rotationVariantsRules[] = 'max:5';
        } else {
            $rotationVariantsRules[] = 'nullable';
            $rotationVariantsRules[] = 'max:0';
        }

        $rotationVariantsRules[] = function (string $attribute, $value, \Closure $fail) use ($destType, $safeBrowsing) {
            if (! is_array($value)) {
                return;
            }
            if ($destType === 'single') {
                if (! empty($value)) {
                    $fail('Rotation variants are not allowed for single destination type.');
                }

                return;
            }

            $sum = 0;
            foreach ($value as $index => $variant) {
                if (! is_array($variant)) {
                    $fail("Variant at index {$index} must be an array.");

                    continue;
                }

                $allowedKeys = ['label', 'url', 'weight'];
                $invalidKeys = array_diff(array_keys($variant), $allowedKeys);
                if (! empty($invalidKeys)) {
                    $fail("Invalid keys in variant at index {$index}: ".implode(', ', $invalidKeys));
                }

                $weight = $variant['weight'] ?? null;
                if ($weight === null || ! is_numeric($weight)) {
                    continue;
                }

                if (floor($weight) != $weight) {
                    $fail("Variant at index {$index} weight must be an integer.");
                } else {
                    $weight = (int) $weight;
                    if ($weight < 0 || $weight > 100) {
                        $fail("Variant at index {$index} weight must be between 0 and 100.");
                    }
                    $sum += $weight;
                }

                $url = $variant['url'] ?? null;
                if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                    if (! $safeBrowsing->isSafe($url)) {
                        $fail("Variant at index {$index} URL has been flagged by Google Safe Browsing as unsafe.");
                    }
                }
            }

            if ($sum !== 100) {
                $fail('Suma udziałów w ruchu musi wynosić dokładnie 100%. Obecna suma: '.$sum.'%.');
            }
        };

        $domainsCount = ShortUrlCustomDomain::where('is_active', true)
            ->where('is_verified', true)
            ->count();
        $defaultDisabled = (bool) config('filament-short-url.disable_default_domain', false);

        $isRequired = $defaultDisabled && $domainsCount > 1;
        $customDomainRule = [
            $isRequired ? 'required' : 'nullable',
            'integer',
            'exists:short_url_custom_domains,id,is_active,1,is_verified,1',
            CustomDomainValidator::ownershipClosure(CustomDomainValidator::ownerUserIdFromRequest($this)),
        ];

        $rules = [
            'destination_type' => 'sometimes|required|string|in:single,split',
            'destination_url' => array_merge(
                $destType === 'single' ? ['required'] : ['nullable'],
                ['url', 'max:2048', $safeBrowsingRule]
            ),
            'rotation_variants' => $rotationVariantsRules,
            'rotation_variants.*.label' => 'required_with:rotation_variants|string|max:100',
            'rotation_variants.*.url' => 'required_with:rotation_variants|url|max:2048',
            'rotation_variants.*.weight' => 'required_with:rotation_variants|integer|min:0|max:100',
            'custom_domain_id' => $customDomainRule,
            'url_key' => [
                'nullable',
                'string',
                'alpha_dash',
                'max:32',
                Rule::unique('short_urls', 'url_key')->where(function ($query) {
                    $domainScopeId = (int) ($this->input('custom_domain_id') ?? 0);

                    return $query->where('domain_scope_id', $domainScopeId);
                }),
            ],
            'notes' => 'nullable|string|max:255',
            'is_enabled' => 'sometimes|required|boolean',
            'redirect_status_code' => 'sometimes|required|integer|in:301,302',
            'single_use' => 'sometimes|required|boolean',
            'forward_query_params' => 'sometimes|required|boolean',
            'max_visits' => 'nullable|integer|min:1',
            'expiration_redirect_url' => ['nullable', 'url', 'max:255', $safeUrlRule],
            'activated_at' => 'nullable|date|after_or_equal:today',
            'expires_at' => 'nullable|date|after_or_equal:activated_at',
            'webhook_url' => ['nullable', 'url', 'max:2048', $safeUrlRule, app(OutboundUrl::class)],
        ];

        $rules = array_merge($rules, $targetingRules);

        return array_merge($rules, $this->apiAttributeRules(), [
            'external_id' => 'nullable|string|max:255|unique:short_urls,external_id',
            'password' => 'nullable|string|max:255',
            'show_warning_page' => 'sometimes|required|boolean',
            'auto_open_app_mobile' => 'sometimes|required|boolean',
            'ga_tracking_id' => 'nullable|string|max:50|regex:/^G-[A-Z0-9]+$/',
            'track_visits' => 'sometimes|required|boolean',
            'track_ip_address' => 'sometimes|required|boolean',
            'track_browser' => 'sometimes|required|boolean',
            'track_browser_version' => 'sometimes|required|boolean',
            'track_operating_system' => 'sometimes|required|boolean',
            'track_operating_system_version' => 'sometimes|required|boolean',
            'track_device_type' => 'sometimes|required|boolean',
            'track_referer_url' => 'sometimes|required|boolean',
            'track_browser_language' => 'sometimes|required|boolean',
            'pixels' => 'nullable|array',
            'pixels.*' => 'integer|exists:short_url_pixels,id',
        ]);
    }
}
