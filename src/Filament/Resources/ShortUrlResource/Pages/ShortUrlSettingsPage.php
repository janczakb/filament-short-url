<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs\AdvancedTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs\DeepLinkingTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs\DeveloperTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs\Ga4Tab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs\GeneralTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs\GeoIpTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs\QrDefaultsTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs\TrackingDefaultsTab;
use Bjanczak\FilamentShortUrl\FilamentShortUrlPlugin;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

class ShortUrlSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $slug = 'short-url-settings';

    protected string $view = 'filament-short-url::settings';

    public static function getNavigationLabel(): string
    {
        return __('filament-short-url::default.settings_nav_label');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        try {
            return ShortUrlResource::getNavigationGroup();
        } catch (\Throwable) {
            return __('filament-short-url::default.navigation_group');
        }
    }

    public static function getNavigationSort(): ?int
    {
        try {
            return ShortUrlResource::getNavigationSort() + 3;
        } catch (\Throwable) {
            return 53;
        }
    }

    public static function canAccess(array $parameters = []): bool
    {
        try {
            $plugin = FilamentShortUrlPlugin::get();

            if ($callback = $plugin->getAuthorizeSettingsUsing()) {
                return (bool) app()->call($callback);
            }
        } catch (\Throwable) {
            // Ignore if plugin is not registered yet in some contexts
        }

        // Fallback: Check if there's a Model Policy with `manageSettings` method
        if (Gate::getPolicyFor(ShortUrl::class) &&
            method_exists(Gate::getPolicyFor(ShortUrl::class), 'manageSettings')) {
            return Gate::allows('manageSettings', ShortUrl::class);
        }

        // Default fallback: Check if the user is authorized to view the resource in general
        return ShortUrlResource::canViewAny();
    }

    public ?array $data = [];

    public function mount(ShortUrlSettingsManager $mgr): void
    {
        $aasa = $mgr->get('aasa_json');
        if (! empty($aasa)) {
            $decoded = json_decode($aasa, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $aasa = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        $assetlinks = $mgr->get('assetlinks_json');
        if (! empty($assetlinks)) {
            $decoded = json_decode($assetlinks, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $assetlinks = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        $this->form->fill([
            'route_prefix' => $mgr->get('route_prefix', 's'),
            'lock_url_key' => $mgr->get('lock_url_key', false),
            'disable_default_domain' => $mgr->get('disable_default_domain', false),
            'redirect_status_code' => $mgr->get('redirect_status_code', 302),
            'key_length' => $mgr->get('key_length', 6),
            'cache_ttl' => $mgr->get('cache_ttl', 3600),
            'geo_ip_enabled' => $mgr->get('geo_ip_enabled', true),
            'geo_ip_driver' => $mgr->get('geo_ip_driver', 'headers'),
            'geo_ip_cache_ttl' => $mgr->get('geo_ip_cache_ttl', 86400),
            'geo_ip_timeout' => $mgr->get('geo_ip_timeout', 3),
            'maxmind_database_path' => $mgr->get('maxmind_database_path', storage_path('geoip/GeoLite2-Country.mmdb')),
            'geo_ip_stats_cache_ttl' => $mgr->get('geo_ip_stats_cache_ttl', 300),
            'queue_connection' => $mgr->get('queue_connection', 'sync'),
            'queue_name' => $mgr->get('queue_name', 'default'),
            'ga4_api_secret' => $mgr->get('ga4_api_secret'),
            'ga4_firebase_app_id' => $mgr->get('ga4_firebase_app_id'),
            'counter_buffering_enabled' => $mgr->get('counter_buffering_enabled', false),
            'trust_cdn_headers' => $mgr->get('trust_cdn_headers', false),
            'pruning_enabled' => $mgr->get('pruning_enabled', true),
            'pruning_retention_days' => $mgr->get('pruning_retention_days', 90),
            'rate_limiting_enabled' => $mgr->get('rate_limiting_enabled', false),
            'rate_limiting_max_attempts' => $mgr->get('rate_limiting_max_attempts', 60),
            'rate_limiting_decay_seconds' => $mgr->get('rate_limiting_decay_seconds', 60),
            'tracking_enabled' => $mgr->get('tracking_enabled', true),
            'tracking_anonymize_ips' => $mgr->get('tracking_anonymize_ips', false),
            'tracking_fields_ip_address' => $mgr->get('tracking_fields_ip_address', true),
            'tracking_fields_browser' => $mgr->get('tracking_fields_browser', true),
            'tracking_fields_browser_version' => $mgr->get('tracking_fields_browser_version', true),
            'tracking_fields_operating_system' => $mgr->get('tracking_fields_operating_system', true),
            'tracking_fields_operating_system_version' => $mgr->get('tracking_fields_operating_system_version', true),
            'tracking_fields_referer_url' => $mgr->get('tracking_fields_referer_url', true),
            'tracking_fields_device_type' => $mgr->get('tracking_fields_device_type', true),
            'tracking_fields_browser_language' => $mgr->get('tracking_fields_browser_language', true),
            'qr_size' => $mgr->get('qr_size', 300),
            'qr_margin' => $mgr->get('qr_margin', 1),
            'qr_dot_style' => $mgr->get('qr_dot_style', 'square'),
            'qr_foreground_color' => $mgr->get('qr_foreground_color', '#000000'),
            'qr_background_color' => $mgr->get('qr_background_color', '#ffffff'),
            'qr_gradient_enabled' => $mgr->get('qr_gradient_enabled', false),
            'qr_gradient_from' => $mgr->get('qr_gradient_from', '#4f46e5'),
            'qr_gradient_to' => $mgr->get('qr_gradient_to', '#06b6d4'),
            'qr_gradient_type' => $mgr->get('qr_gradient_type', 'linear'),
            'global_webhook_url' => $mgr->get('global_webhook_url'),
            'webhook_events' => $mgr->get('webhook_events', ['visited']),
            'global_webhook_enabled' => $mgr->get('global_webhook_enabled', false),
            'api_keys' => $mgr->get('api_keys', []),
            'api_enabled' => $mgr->get('api_enabled', false),
            'site_name' => $mgr->get('site_name'),
            // Security v2.0
            'vpn_detection_enabled' => $mgr->get('vpn_detection_enabled', false),
            'vpn_detection_driver' => $mgr->get('vpn_detection_driver', 'ip-api'),
            'vpnapi_key' => $mgr->get('vpnapi_key'),
            'vpn_block_action' => $mgr->get('vpn_block_action', 'flag_only'),
            'safe_browsing_enabled' => $mgr->get('safe_browsing_enabled', false),
            'google_safe_browsing_api_key' => $mgr->get('google_safe_browsing_api_key'),
            // Deep Linking v2.1
            'deep_linking_enabled' => $mgr->get('deep_linking_enabled', false),
            'aasa_json' => $aasa,
            'assetlinks_json' => $assetlinks,
            // Webhook signing secret
            'webhook_signing_secret' => $mgr->get('webhook_signing_secret'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ShortUrlSettings')
                    ->persistTabInQueryString()
                    ->tabs([
                        GeneralTab::make(),
                        GeoIpTab::make(),
                        Ga4Tab::make(),
                        AdvancedTab::make(),
                        TrackingDefaultsTab::make(),
                        QrDefaultsTab::make(),
                        DeveloperTab::make(),
                        DeepLinkingTab::make(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(ShortUrlSettingsManager $mgr): void
    {
        $data = $this->form->getState();

        $mgr->set($data);

        // Clear deep linking cache when settings are saved!
        cache()->forget('fsu:deep-linking:aasa');
        cache()->forget('fsu:deep-linking:assetlinks');

        Notification::make()
            ->title(__('filament-short-url::default.settings_saved'))
            ->success()
            ->send();

        if ($newKeys = session()->get('fsu_new_api_keys')) {
            foreach ($newKeys as $newKey) {
                Notification::make()
                    ->title(__('filament-short-url::default.api_key_generated'))
                    ->body(new HtmlString('<strong>'.$newKey['name'].'</strong>: '.__('filament-short-url::default.api_key_warning')."<br/><code style='background:#e4e4e7;color:#18181b;padding:6px;border-radius:4px;user-select:all;display:block;margin-top:6px;font-family:monospace;font-weight:bold;'>".$newKey['plain'].'</code>'))
                    ->warning()
                    ->persistent()
                    ->send();
            }

            // Re-mount settings form state to reflect the new masked key names in the UI
            $this->mount($mgr);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('filament-short-url::default.stats_btn_back'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->size('sm')
                ->url(ShortUrlResource::getUrl()),
        ];
    }

    public function getTitle(): string
    {
        return __('filament-short-url::default.settings_nav_label');
    }
}
