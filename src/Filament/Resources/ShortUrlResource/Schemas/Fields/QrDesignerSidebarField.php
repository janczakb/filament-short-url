<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Fields;

use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Bjanczak\FilamentShortUrl\Services\OgImageProcessor;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTempStorage;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\RawJs;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class QrDesignerSidebarField
{
    /**
     * Build the sidebar ViewField with the embedded designQr action.
     */
    public static function make(): ViewField
    {
        return ViewField::make('sidebar_qr_preview')
            ->label(__('filament-short-url::default.action_qr'))
            ->view('filament-short-url::sidebar.qr-preview')
            ->viewData(function (ViewField $component, ?Get $get = null, $record = null) {
                $record = $record ?? ($component ? $component->getRecord() : null);

                $liveOptionsData = null;
                $liveLogo = null;

                if ($component && method_exists($component, 'getContainer')) {
                    $state = $component->getContainer()->getState();
                    $liveOptionsData = $state['qr_options'] ?? null;
                    $liveLogo = $state['qr_logo'] ?? null;
                }

                $hasNoLiveOptions = empty($liveOptionsData) || $liveOptionsData === '{}' || $liveOptionsData === [];

                if ($hasNoLiveOptions && $get) {
                    $liveOptionsData = $get('qr_options');
                    $liveLogo = $get('qr_logo');
                }

                $hasNoLiveOptions = empty($liveOptionsData) || $liveOptionsData === '{}' || $liveOptionsData === [];

                if ($hasNoLiveOptions && $component && method_exists($component, 'getLivewire')) {
                    $livewire = $component->getLivewire();
                    if (isset($livewire->data) && is_array($livewire->data)) {
                        $liveOptionsData = $livewire->data['qr_options'] ?? null;
                        $liveLogo = $livewire->data['qr_logo'] ?? null;
                    }
                }

                $liveOptions = [];
                if (is_string($liveOptionsData)) {
                    $liveOptions = json_decode($liveOptionsData, true) ?: [];
                } elseif (is_array($liveOptionsData)) {
                    $liveOptions = $liveOptionsData;
                }

                $opts = ! empty($liveOptions)
                    ? $liveOptions
                    : ($record ? $record->getQrOptions() : config('filament-short-url.qr_defaults', []));

                $currentLogo = $liveLogo !== null
                    ? $liveLogo
                    : ($record ? $record->qr_logo : '');

                return [
                    'domains' => ShortUrlCustomDomain::where('is_active', true)
                        ->where('is_verified', true)
                        ->pluck('domain', 'id')
                        ->toArray(),
                    'defaultDomain' => parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost',
                    'opts' => $opts,
                    'currentLogo' => $currentLogo,
                    'record' => $record,
                ];
            })
            ->registerActions([
                self::buildDesignQrAction(),
            ])
            ->dehydrated(false)
            ->columnSpanFull();
    }

    /**
     * Build the "Design QR" action with its full schema and submission logic.
     */
    public static function buildDesignQrAction(): Action
    {
        return Action::make('designQr')
            ->icon('heroicon-o-pencil')
            ->iconButton()
            ->color('gray')
            ->extraModalWindowAttributes([
                'class' => 'modal-fsl',
            ])
            ->extraAttributes([
                'class' => '!w-8 !h-8 !p-0 rounded-lg bg-white/90 dark:bg-neutral-900/90 border border-neutral-200 dark:border-neutral-700 shadow-sm flex items-center justify-center hover:bg-white dark:hover:bg-neutral-800 transition cursor-pointer text-neutral-500 dark:text-neutral-400 [&_svg]:!w-4 [&_svg]:!h-4',
                'style' => 'position: absolute !important; top: 8px !important; right: 8px !important; z-index: 10 !important; margin: 0 !important; width: 32px !important; height: 32px !important; padding: 0 !important;',
            ])
            ->stickyModalFooter()
            ->stickyModalHeader()
            ->modalFooterActions(fn ($action) => [
                $action->getModalSubmitAction(),
            ])
            ->modalHeading(__('filament-short-url::default.tab_qr_design'))
            ->fillForm(function (ViewField $component, ?Get $get = null) {
                $parentOpts = [];
                $parentLogo = null;
                if ($component && method_exists($component, 'getContainer')) {
                    $state = $component->getContainer()->getState();
                    $parentOpts = $state['qr_options'] ?? [];
                    $parentLogo = $state['qr_logo'] ?? null;
                }
                if (empty($parentOpts) && $get) {
                    $parentOpts = $get('qr_options') ?? [];
                    $parentLogo = $get('qr_logo');
                }
                if (is_string($parentOpts)) {
                    $parentOpts = json_decode($parentOpts, true) ?: [];
                }

                $defaults = config('filament-short-url.qr_defaults', []);

                return [
                    'dot_style' => $parentOpts['dot_style'] ?? $defaults['dot_style'] ?? 'square',
                    'color_mode' => $parentOpts['color_mode'] ?? ($defaults['gradient_enabled'] ?? false ? 'gradient' : 'solid'),
                    'foreground_color' => $parentOpts['foreground_color'] ?? $defaults['foreground_color'] ?? '#000000',
                    'gradient_from' => $parentOpts['gradient_from'] ?? $defaults['gradient_from'] ?? '#4f46e5',
                    'gradient_to' => $parentOpts['gradient_to'] ?? $defaults['gradient_to'] ?? '#06b6d4',
                    'gradient_type' => $parentOpts['gradient_type'] ?? $defaults['gradient_type'] ?? 'linear',
                    'bg_transparent' => $parentOpts['bg_transparent'] ?? false,
                    'background_color' => $parentOpts['background_color'] ?? $defaults['background_color'] ?? '#ffffff',
                    'eye_config_enabled' => $parentOpts['eye_config_enabled'] ?? false,
                    'eye_square_style' => $parentOpts['eye_square_style'] ?? 'square',
                    'eye_dot_style' => $parentOpts['eye_dot_style'] ?? 'square',
                    'eye_color' => $parentOpts['eye_color'] ?? '#000000',
                    'logo_file' => $parentLogo ? [$parentLogo] : [],
                    'logo_shape' => $parentOpts['logo_shape'] ?? 'square',
                    'logo_size' => $parentOpts['logo_size'] ?? 0.55,
                    'logo_margin' => $parentOpts['logo_margin'] ?? 8,
                    'logo_hide_background' => true,
                ];
            })
            ->schema([
                Grid::make([
                    'default' => 1,
                    'lg' => 12,
                ])
                    ->schema([
                        Group::make([
                            Section::make(__('filament-short-url::default.qr_label_dots_background'))
                                ->icon('heroicon-o-swatch')
                                ->id('qr-section-dots')
                                ->collapsible()
                                ->compact()
                                ->schema([
                                    ToggleButtons::make('dot_style')
                                        ->label(__('filament-short-url::default.qr_label_style'))
                                        ->options([
                                            'square' => __('filament-short-url::default.qr_option_square'),
                                            'dots' => __('filament-short-url::default.qr_option_dots'),
                                            'rounded' => __('filament-short-url::default.qr_option_rounded'),
                                            'classy' => __('filament-short-url::default.qr_option_classy'),
                                            'classy-rounded' => __('filament-short-url::default.qr_option_classy_rounded'),
                                            'extra-rounded' => __('filament-short-url::default.qr_option_extra_rounded'),
                                        ])
                                        ->icons([
                                            'square' => 'fsu-qr-dots-square',
                                            'dots' => 'fsu-qr-dots-dots',
                                            'rounded' => 'fsu-qr-dots-rounded',
                                            'classy' => 'fsu-qr-dots-classy',
                                            'classy-rounded' => 'fsu-qr-dots-classy-rounded',
                                            'extra-rounded' => 'fsu-qr-dots-extra-rounded',
                                        ])
                                        ->tooltips([
                                            'square' => __('filament-short-url::default.qr_option_square'),
                                            'dots' => __('filament-short-url::default.qr_option_dots'),
                                            'rounded' => __('filament-short-url::default.qr_option_rounded'),
                                            'classy' => __('filament-short-url::default.qr_option_classy'),
                                            'classy-rounded' => __('filament-short-url::default.qr_option_classy_rounded'),
                                            'extra-rounded' => __('filament-short-url::default.qr_option_extra_rounded'),
                                        ])
                                        ->extraAttributes([
                                            'class' => '[&_svg]:w-[25px] [&_svg]:h-[25px]',
                                        ])
                                        ->hiddenButtonLabels()
                                        ->grouped()
                                        ->required()
                                        ->live(),

                                    ToggleButtons::make('color_mode')
                                        ->hiddenLabel()
                                        ->options([
                                            'solid' => __('filament-short-url::default.qr_label_single_color'),
                                            'gradient' => __('filament-short-url::default.qr_label_gradient'),
                                        ])
                                        ->icons([
                                            'solid' => 'heroicon-o-paint-brush',
                                            'gradient' => 'heroicon-o-sparkles',
                                        ])
                                        ->grouped()
                                        ->required()
                                        ->live(),

                                    Grid::make(12)
                                        ->schema([
                                            ColorPicker::make('foreground_color')
                                                ->label(__('filament-short-url::default.qr_fg_color'))
                                                ->required()
                                                ->live()
                                                ->columnSpan(5),

                                            ViewField::make('foreground_color_presets')
                                                ->label('')
                                                ->view('filament-short-url::forms.components.color-presets-only')
                                                ->columnSpan(7)
                                                ->extraAttributes(['class' => 'flex items-end pb-1.5']),
                                        ])
                                        ->visible(fn (Get $get) => $get('color_mode') === 'solid'),

                                    Grid::make(2)
                                        ->schema([
                                            ColorPicker::make('gradient_from')
                                                ->label(__('filament-short-url::default.qr_label_from'))
                                                ->required()
                                                ->live(),
                                            ColorPicker::make('gradient_to')
                                                ->label(__('filament-short-url::default.qr_label_to'))
                                                ->required()
                                                ->live(),
                                        ])
                                        ->visible(fn (Get $get) => $get('color_mode') === 'gradient'),

                                    ToggleButtons::make('gradient_type')
                                        ->label(__('filament-short-url::default.qr_label_gradient_type'))
                                        ->options([
                                            'linear' => __('filament-short-url::default.qr_gradient_linear'),
                                            'radial' => __('filament-short-url::default.qr_gradient_radial'),
                                        ])
                                        ->icons([
                                            'linear' => 'heroicon-o-bars-3',
                                            'radial' => 'heroicon-o-sun',
                                        ])
                                        ->grouped()
                                        ->required()
                                        ->live()
                                        ->visible(fn (Get $get) => $get('color_mode') === 'gradient'),

                                    Toggle::make('bg_transparent')
                                        ->label(__('filament-short-url::default.qr_label_transparent'))
                                        ->live(),

                                    Grid::make(12)
                                        ->schema([
                                            ColorPicker::make('background_color')
                                                ->label(__('filament-short-url::default.qr_bg_color'))
                                                ->required()
                                                ->live()
                                                ->columnSpan(5),

                                            ViewField::make('background_color_presets')
                                                ->label('')
                                                ->view('filament-short-url::forms.components.color-presets-only')
                                                ->columnSpan(7)
                                                ->extraAttributes(['class' => 'flex items-end pb-1.5']),
                                        ])
                                        ->visible(fn (Get $get) => ! $get('bg_transparent')),
                                ]),

                            Section::make(__('filament-short-url::default.qr_label_eye_config'))
                                ->icon('heroicon-o-eye')
                                ->id('qr-section-eyes')
                                ->collapsible()
                                ->collapsed()
                                ->compact()
                                ->schema([
                                    Toggle::make('eye_config_enabled')
                                        ->label(__('filament-short-url::default.qr_label_custom_eye_config'))
                                        ->live(),

                                    ToggleButtons::make('eye_square_style')
                                        ->label(__('filament-short-url::default.qr_label_eye_square_style'))
                                        ->options([
                                            '' => __('filament-short-url::default.qr_option_none'),
                                            'square' => __('filament-short-url::default.qr_option_square'),
                                            'dot' => __('filament-short-url::default.qr_option_dot'),
                                            'extra-rounded' => __('filament-short-url::default.qr_option_extra_rounded'),
                                        ])
                                        ->icons([
                                            '' => 'fsu-qr-eye-square-none',
                                            'square' => 'fsu-qr-eye-square-square',
                                            'dot' => 'fsu-qr-eye-square-dot',
                                            'extra-rounded' => 'fsu-qr-eye-square-extra-rounded',
                                        ])
                                        ->tooltips([
                                            '' => __('filament-short-url::default.qr_option_none'),
                                            'square' => __('filament-short-url::default.qr_option_square'),
                                            'dot' => __('filament-short-url::default.qr_option_dot'),
                                            'extra-rounded' => __('filament-short-url::default.qr_option_extra_rounded'),
                                        ])
                                        ->extraAttributes([
                                            'class' => '[&_svg]:w-[25px] [&_svg]:h-[25px]',
                                        ])
                                        ->hiddenButtonLabels()
                                        ->grouped()
                                        ->live()
                                        ->visible(fn (Get $get) => $get('eye_config_enabled')),

                                    ToggleButtons::make('eye_dot_style')
                                        ->label(__('filament-short-url::default.qr_label_eye_dot_style'))
                                        ->options([
                                            '' => __('filament-short-url::default.qr_option_none'),
                                            'square' => __('filament-short-url::default.qr_option_square'),
                                            'dot' => __('filament-short-url::default.qr_option_dot'),
                                        ])
                                        ->icons([
                                            '' => 'fsu-qr-eye-dot-none',
                                            'square' => 'fsu-qr-eye-dot-square',
                                            'dot' => 'fsu-qr-eye-dot-dot',
                                        ])
                                        ->extraAttributes([
                                            'class' => '[&_svg]:w-[25px] [&_svg]:h-[25px]',
                                        ])
                                        ->tooltips([
                                            '' => __('filament-short-url::default.qr_option_none'),
                                            'square' => __('filament-short-url::default.qr_option_square'),
                                            'dot' => __('filament-short-url::default.qr_option_dot'),
                                        ])
                                        ->hiddenButtonLabels()
                                        ->grouped()
                                        ->live()
                                        ->visible(fn (Get $get) => $get('eye_config_enabled')),

                                    Grid::make(12)
                                        ->schema([
                                            ColorPicker::make('eye_color')
                                                ->label(__('filament-short-url::default.qr_label_eye_color'))
                                                ->required()
                                                ->live()
                                                ->columnSpan(5),

                                            ViewField::make('eye_color_presets')
                                                ->label('')
                                                ->view('filament-short-url::forms.components.color-presets-only')
                                                ->columnSpan(7)
                                                ->extraAttributes(['class' => 'flex items-end pb-1.5']),
                                        ])
                                        ->visible(fn (Get $get) => $get('eye_config_enabled')),
                                ]),

                            Section::make(__('filament-short-url::default.qr_label_logo_overlay'))
                                ->icon('heroicon-o-photo')
                                ->id('qr-section-logo')
                                ->collapsible()
                                ->collapsed()
                                ->compact()
                                ->schema([
                                    FileUpload::make('logo_file')
                                        ->label(__('filament-short-url::default.qr_label_logo_overlay'))
                                        ->avatar()
                                        ->image()
                                        ->directory(fn (ShortUrlTempStorage $temp): string => $temp->bucketDirectory())
                                        ->visibility('public')
                                        ->rule(Rule::dimensions()->minWidth(100)->minHeight(100))
                                        ->automaticallyCropImagesToAspectRatio('1:1')
                                        ->automaticallyResizeImagesMode('cover')
                                        ->automaticallyResizeImagesToWidth('1000')
                                        ->automaticallyResizeImagesToHeight('1000')
                                        ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, OgImageProcessor $processor): string {
                                            $storedPath = $processor->storeWebpFromPath($file->getRealPath(), ShortUrlTempStorage::ROOT);

                                            if ($storedPath === null) {
                                                return $file->storePubliclyAs(
                                                    app(ShortUrlTempStorage::class)->bucketDirectory(),
                                                    $file->getClientOriginalName(),
                                                    'public',
                                                );
                                            }

                                            return $storedPath;
                                        })
                                        ->live(),

                                    ToggleButtons::make('logo_shape')
                                        ->label(__('filament-short-url::default.qr_label_logo_shape'))
                                        ->options([
                                            'square' => __('filament-short-url::default.qr_option_square'),
                                            'circle' => __('filament-short-url::default.qr_option_circle'),
                                        ])
                                        ->icons([
                                            'square' => 'heroicon-o-stop',
                                            'circle' => 'heroicon-o-minus-circle',
                                        ])
                                        ->grouped()
                                        ->visible(fn (Get $get) => ! empty($get('logo_file')))
                                        ->required()
                                        ->live(),

                                    Slider::make('logo_size')
                                        ->label(__('filament-short-url::default.qr_label_logo_size'))
                                        ->range(minValue: 0.4, maxValue: 0.6)
                                        ->step(0.05)
                                        ->tooltips(RawJs::make(<<<'JS'
                                            `${Math.round($value * 100)}%`
                                            JS))
                                        ->fillTrack()
                                        ->visible(fn (Get $get) => ! empty($get('logo_file')))
                                        ->live(),

                                    Slider::make('logo_margin')
                                        ->label(__('filament-short-url::default.qr_label_logo_margin'))
                                        ->range(minValue: 1, maxValue: 20)
                                        ->step(1)
                                        ->tooltips(RawJs::make(<<<'JS'
                                            `${$value}px`
                                            JS))
                                        ->fillTrack()
                                        ->visible(fn (Get $get) => ! empty($get('logo_file')))
                                        ->live(),
                                ]),
                        ])
                            ->columnSpan(7),

                        ViewField::make('designer_preview')
                            ->view('filament-short-url::qr-preview-canvas')
                            ->columnSpan(5)
                            ->dehydrated(false)
                            ->viewData(function (ViewField $component) {
                                $record = $component->getRecord();
                                $shortUrl = $record
                                    ? ($record->getShortUrl().'?source=qr')
                                    : (config('app.url').'/s/preview?source=qr');

                                return [
                                    'shortUrl' => $shortUrl,
                                ];
                            }),
                    ]),
            ])
            ->modalWidth('5xl')
            ->modalSubmitActionLabel(__('filament-short-url::default.qr_save_design'))
            ->action(function (array $data, Set $set, Component $livewire): void {
                $opts = [
                    'margin' => 1,
                    'dot_style' => $data['dot_style'] ?? 'square',
                    'color_mode' => $data['color_mode'] ?? 'solid',
                    'foreground_color' => $data['foreground_color'] ?? '#000000',
                    'gradient_from' => $data['gradient_from'] ?? '#4f46e5',
                    'gradient_to' => $data['gradient_to'] ?? '#06b6d4',
                    'gradient_type' => $data['gradient_type'] ?? 'linear',
                    'bg_transparent' => (bool) ($data['bg_transparent'] ?? false),
                    'background_color' => $data['background_color'] ?? '#ffffff',
                    'eye_config_enabled' => (bool) ($data['eye_config_enabled'] ?? false),
                    'eye_square_style' => $data['eye_square_style'] ?? 'square',
                    'eye_dot_style' => $data['eye_dot_style'] ?? 'square',
                    'eye_color' => $data['eye_color'] ?? '#000000',
                    'logo_shape' => $data['logo_shape'] ?? 'square',
                    'logo_size' => (float) ($data['logo_size'] ?? 0.55),
                    'logo_margin' => (int) ($data['logo_margin'] ?? 8),
                    'logo_hide_background' => true,
                ];

                $logo = null;
                if (! empty($data['logo_file'])) {
                    // Keep the tmp path as-is here. The ShortUrl model's saving observer
                    // will move the file from short-urls/tmp/ to short-urls/logos/ when
                    // the parent Create/Edit Link form is ultimately submitted and saved.
                    $logo = is_array($data['logo_file']) ? reset($data['logo_file']) : $data['logo_file'];
                }

                $optionsJson = json_encode($opts) ?: '{}';

                $set('qr_options', $optionsJson);
                $set('qr_logo', $logo);

                $livewire->dispatch('qr-design-updated', [
                    'options' => $opts,
                    'logo' => $logo,
                ]);
            });
    }
}
