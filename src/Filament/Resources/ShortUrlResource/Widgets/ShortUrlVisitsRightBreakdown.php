<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\Widget;

class ShortUrlVisitsRightBreakdown extends Widget
{
    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $view = 'filament-short-url::widgets.visits-right-breakdown';

    protected int|string|array $columnSpan = 'full';

    public function mount(?ShortUrl $record = null): void
    {
        $this->record = $record;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $languageNames = [
            'en' => 'English',
            'pl' => 'Polish',
            'de' => 'German',
            'es' => 'Spanish',
            'fr' => 'French',
            'it' => 'Italian',
            'ru' => 'Russian',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'pt' => 'Portuguese',
            'nl' => 'Dutch',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
            'cs' => 'Czech',
            'sv' => 'Swedish',
            'no' => 'Norwegian',
            'da' => 'Danish',
            'fi' => 'Finnish',
            'ro' => 'Romanian',
            'hu' => 'Hungarian',
            'sk' => 'Slovak',
            'bg' => 'Bulgarian',
            'hr' => 'Croatian',
            'sr' => 'Serbian',
            'sl' => 'Slovenian',
            'et' => 'Estonian',
            'lv' => 'Latvian',
            'lt' => 'Lithuanian',
            'el' => 'Greek',
        ];

        if (! $this->record) {
            return [
                'visitsByCountry' => [],
                'visitsByLanguage' => [],
                'languageNames' => $languageNames,
                'totalVisits' => 0,
            ];
        }

        $stats = $this->record->getCachedStats($this->dateFrom, $this->dateTo);

        return [
            'visitsByCountry' => $stats['visitsByCountry'] ?? [],
            'visitsByLanguage' => $stats['visitsByLanguage'] ?? [],
            'languageNames' => $languageNames,
            'totalVisits' => $stats['totalVisits'] ?? 0,
        ];
    }

    /**
     * Dynamically translate an ISO language code to the current application locale.
     */
    public static function getLanguageTranslation(string $langCode): string
    {
        $langCode = strtolower(trim($langCode));
        if (class_exists(\Locale::class)) {
            try {
                $name = \Locale::getDisplayLanguage($langCode, app()->getLocale());
                if ($name && $name !== $langCode) {
                    return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
                }
            } catch (\Throwable) {
                // Fallback
            }
        }

        $fallback = [
            'en' => 'English',
            'pl' => 'Polish',
            'de' => 'German',
            'es' => 'Spanish',
            'fr' => 'French',
            'it' => 'Italian',
            'ru' => 'Russian',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'pt' => 'Portuguese',
            'nl' => 'Dutch',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
            'cs' => 'Czech',
            'sv' => 'Swedish',
            'no' => 'Norwegian',
            'da' => 'Danish',
            'fi' => 'Finnish',
            'ro' => 'Romanian',
            'hu' => 'Hungarian',
            'sk' => 'Slovak',
            'bg' => 'Bulgarian',
            'hr' => 'Croatian',
            'sr' => 'Serbian',
            'sl' => 'Slovenian',
            'et' => 'Estonian',
            'lv' => 'Latvian',
            'lt' => 'Lithuanian',
            'el' => 'Greek',
        ];

        return $fallback[$langCode] ?? strtoupper($langCode);
    }

    /**
     * Dynamically translate an English country name to the current application locale.
     */
    public static function getCountryTranslation(string $englishName): string
    {
        $englishName = trim($englishName);

        try {
            $enCountries = trans('filament-short-url::countries', [], 'en');
            if (is_array($enCountries)) {
                $flipped = array_change_key_case(array_flip($enCountries), CASE_LOWER);
                $lookupKey = strtolower($englishName);
                $code = $flipped[$lookupKey] ?? null;

                if ($code) {
                    $translated = __('filament-short-url::countries.'.strtoupper($code));
                    if ($translated && $translated !== 'filament-short-url::countries.'.strtoupper($code)) {
                        return $translated;
                    }
                }
            }
        } catch (\Throwable) {
            // Fallback
        }

        return $englishName;
    }
}
