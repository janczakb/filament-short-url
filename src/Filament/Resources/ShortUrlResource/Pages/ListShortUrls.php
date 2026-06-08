<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\PasswordOpenGraphGuard;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlGlobalOverview;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\HtmlString;

class ListShortUrls extends ManageRecords
{
    protected static string $resource = ShortUrlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-short-url::default.empty_state_action'))
                ->modalHeading(__('filament-short-url::default.empty_state_action'))
                ->icon('heroicon-o-plus')
                ->size('sm')
                ->color('primary')
                ->modalWidth('5xl')
                ->modalAutofocus(false)
                ->closeModalByClickingAway(false)
                ->stickyModalFooter()
                ->stickyModalHeader()
                ->createAnother(false)
                ->modalCancelAction(false)
                ->extraModalWindowAttributes(['class' => 'update-link-modal modal-fsl'])
                ->modalFooterActions(fn ($action) => [
                    $action->getModalSubmitAction(),
                ])
                ->mutateFormDataUsing(function (array $data, ShortUrlService $service): array {
                    if (empty($data['url_key'])) {
                        $data['url_key'] = $service->generateKey();
                    }

                    return PasswordOpenGraphGuard::sanitizeSaveData($data);
                })
                ->after(function (ShortUrl $record): void {
                    $id = json_encode($record->id);
                    $shortUrl = json_encode($record->getShortUrl());
                    $urlKey = json_encode($record->url_key);
                    $destHost = json_encode(parse_url($record->destination_url, PHP_URL_HOST) ?? '');

                    $this->js("
                        setTimeout(() => {
                            if (localStorage.getItem('fsu:hide-share-modal') !== '1') {
                                \$wire.mountAction('shareAfterCreate', {
                                    id: {$id},
                                    shortUrl: {$shortUrl},
                                    urlKey: {$urlKey},
                                    destHost: {$destHost}
                                });
                            }
                        }, 200);
                    ");
                }),

            // Hidden action — opened programmatically after create
            Action::make('shareAfterCreate')
                ->label('')
                ->extraAttributes(['class' => 'hidden'])
                ->modalHeading('')
                ->modalWidth('lg')
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->form(function (array $arguments): array {
                    $recordId = $arguments['id'] ?? null;
                    $shortUrl = $arguments['shortUrl'] ?? '';
                    $qrTargetUrl = $shortUrl ? ($shortUrl.'?source=qr') : '';
                    $destHost = $arguments['destHost'] ?? '';
                    $urlKey = $arguments['urlKey'] ?? 'qr-code';
                    $encoded = urlencode($shortUrl);
                    $eid = 'fsu_'.substr(md5($shortUrl), 0, 8);

                    $successTitle = __('filament-short-url::default.success_modal_title');
                    $successSubtitle = __('filament-short-url::default.success_modal_subtitle');
                    $successHelper = __('filament-short-url::default.success_modal_helper');
                    $downloadSvgText = __('filament-short-url::default.qr_download_svg');
                    $downloadPngText = __('filament-short-url::default.qr_download_png');
                    $closeButtonText = __('filament-short-url::default.close_button');
                    $copyLinkText = __('filament-short-url::default.action_copy');
                    $qrCodeText = __('filament-short-url::default.action_qr');
                    $openLinkText = __('filament-short-url::default.open_link');
                    $dontShowAgainText = __('filament-short-url::default.dont_show_again');

                    $record = $recordId ? ShortUrl::find($recordId) : null;
                    $qrDefaults = $record ? $record->getQrOptions() : config('filament-short-url.qr_defaults', []);
                    $isGrad = ($qrDefaults['gradient_enabled'] ?? false) || (($qrDefaults['color_mode'] ?? '') === 'gradient');
                    $dotStyle = $qrDefaults['dot_style'] ?? 'square';
                    $fgColor = $qrDefaults['foreground_color'] ?? '#000000';
                    $bgColor = ($qrDefaults['bg_transparent'] ?? false) ? 'rgba(0,0,0,0)' : ($qrDefaults['background_color'] ?? '#ffffff');

                    $dotsOptions = $isGrad ? [
                        'type' => $dotStyle,
                        'gradient' => [
                            'type' => $qrDefaults['gradient_type'] ?? 'linear',
                            'colorStops' => [
                                ['offset' => 0, 'color' => $qrDefaults['gradient_from'] ?? '#4f46e5'],
                                ['offset' => 1, 'color' => $qrDefaults['gradient_to'] ?? '#06b6d4'],
                            ],
                        ],
                    ] : [
                        'type' => $dotStyle,
                        'color' => $fgColor,
                    ];

                    $mainColor = $isGrad ? ($qrDefaults['gradient_from'] ?? '#4f46e5') : $fgColor;

                    $eyeConfigEnabled = $qrDefaults['eye_config_enabled'] ?? false;
                    $eyeSquareStyle = $qrDefaults['eye_square_style'] ?? ($dotStyle === 'dots' ? 'dot' : 'square');
                    $eyeDotStyle = $qrDefaults['eye_dot_style'] ?? ($dotStyle === 'dots' ? 'dot' : 'square');
                    $eyeColor = $qrDefaults['eye_color'] ?? $mainColor;

                    $cornersSquareOptions = $eyeConfigEnabled ? [
                        'type' => $eyeSquareStyle,
                        'color' => $eyeColor,
                    ] : [
                        'type' => $dotStyle === 'dots' ? 'dot' : 'square',
                        'color' => $mainColor,
                    ];

                    $cornersDotOptions = $eyeConfigEnabled ? [
                        'type' => $eyeDotStyle,
                        'color' => $eyeColor,
                    ] : [
                        'type' => $dotStyle === 'dots' ? 'dot' : 'square',
                        'color' => $mainColor,
                    ];

                    $logo = $qrDefaults['logo'] ?? null;
                    $logoSize = $qrDefaults['logo_size'] ?? 0.3;
                    $logoMargin = $qrDefaults['logo_margin'] ?? 9;
                    $logoHideBackground = $qrDefaults['logo_hide_background'] ?? true;
                    $logoShape = $qrDefaults['logo_shape'] ?? 'square';

                    $qrOptions = [
                        'type' => 'svg',
                        'width' => 200,
                        'height' => 200,
                        'margin' => $qrDefaults['margin'] ?? 1,
                        'dotsOptions' => $dotsOptions,
                        'backgroundOptions' => ['color' => $bgColor],
                        'cornersSquareOptions' => $cornersSquareOptions,
                        'cornersDotOptions' => $cornersDotOptions,
                        'image' => $logo ?: null,
                        'imageOptions' => [
                            'crossOrigin' => 'anonymous',
                            'hideBackgroundDots' => $logoHideBackground,
                            'imageSize' => $logoSize,
                            'margin' => $logoMargin,
                            'logoShape' => $logoShape,
                        ],
                        'qrOptions' => ['errorCorrectionLevel' => $logo ? 'H' : 'M'],
                    ];

                    $viewData = compact(
                        'shortUrl',
                        'qrTargetUrl',
                        'destHost',
                        'urlKey',
                        'eid',
                        'qrOptions',
                        'successTitle',
                        'successSubtitle',
                        'successHelper',
                        'downloadSvgText',
                        'downloadPngText',
                        'closeButtonText',
                        'copyLinkText',
                        'qrCodeText',
                        'openLinkText',
                        'dontShowAgainText'
                    );

                    return [
                        Forms\Components\Placeholder::make('share_modal_content')
                            ->hiddenLabel()
                            ->content(new HtmlString(view('filament-short-url::modals.share-after-create', $viewData)->render())),
                    ];
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ShortUrlGlobalOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make(__('filament-short-url::default.tab_active_links'))
                ->modifyQueryUsing(fn ($query) => $query->where('is_archived', false)),
            'archived' => Tab::make(__('filament-short-url::default.tab_archived_links'))
                ->modifyQueryUsing(fn ($query) => $query->where('is_archived', true)),
        ];
    }

    protected function paginateTableQuery(Builder $query): Paginator
    {
        $paginator = parent::paginateTableQuery($query);

        if ($paginator instanceof AbstractPaginator) {
            ShortUrl::preloadBufferedCountersForIds(
                $paginator->getCollection()->pluck('id')->all()
            );
        }

        return $paginator;
    }
}
