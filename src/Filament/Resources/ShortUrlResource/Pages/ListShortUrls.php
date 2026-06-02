<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlGlobalOverview;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\HtmlString;

class ListShortUrls extends ManageRecords
{
    protected static string $resource = ShortUrlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('settings')
                ->label(__('filament-short-url::default.settings_nav_label'))
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('gray')
                ->size('sm')
                ->url(static::getResource()::getUrl('settings'))
                ->visible(fn () => ShortUrlSettingsPage::canAccess()),

            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->size('sm')
                ->color('primary')
                ->modalWidth('4xl')
                ->mutateFormDataUsing(function (array $data): array {
                    if (empty($data['url_key'])) {
                        $data['url_key'] = app(ShortUrlService::class)->generateKey();
                    }

                    return $data;
                })
                ->after(function (ShortUrl $record): void {
                    $this->mountAction('shareAfterCreate', [
                        'shortUrl' => $record->getShortUrl(),
                        'urlKey' => $record->url_key,
                        'destHost' => parse_url($record->destination_url, PHP_URL_HOST) ?? '',
                    ]);
                }),

            // Hidden action — opened programmatically after create
            Action::make('shareAfterCreate')
                ->label('')
                ->hidden()
                ->modalHeading('')
                ->modalWidth('lg')
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->form(function (array $arguments): array {
                    $shortUrl = $arguments['shortUrl'] ?? '';
                    $destHost = $arguments['destHost'] ?? '';
                    $encoded = urlencode($shortUrl);
                    $eid = 'fsu_'.substr(md5($shortUrl), 0, 8);

                    $html = <<<HTML
<div x-data x-init="if(localStorage.getItem('fsu:hide-share-modal')==='1'){ \$nextTick(()=>\$wire.unmountAction()) }">

<div style="text-align:center;padding:4px 0 8px">
    <p style="font-size:13px;color:#6b7280;margin:0 0 6px">Time to get some clicks 🎉</p>
    <h2 style="font-size:23px;font-weight:700;color:#111827;margin:0 0 6px;line-height:1.25">Your link &amp; QR code are ready!</h2>
    <p style="font-size:13px;color:#9ca3af;margin:0">Copy and share manually or choose a platform.</p>
</div>

<!-- URL pill -->
<div style="display:flex;align-items:center;gap:10px;background:#EFF6FF;border-radius:999px;padding:10px 14px;margin:18px 0 0">
    <div style="width:30px;height:30px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid #e5e7eb">
        <img src="https://icons.duckduckgo.com/ip2/{$destHost}.ico" style="width:16px;height:16px;object-fit:contain" onerror="this.style.display='none'">
    </div>
    <span style="flex:1;font-size:14px;font-weight:600;color:#1d4ed8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{$shortUrl}</span>
    <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
        <button id="{$eid}_copy"
            onclick="
                const u='{$shortUrl}';
                if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(u);}
                else{const t=document.createElement('textarea');t.value=u;t.style.cssText='position:fixed;left:-9999px';document.body.appendChild(t);t.select();document.execCommand('copy');t.remove();}
                const b=document.getElementById('{$eid}_copy');
                const prev=b.innerHTML;
                b.innerHTML='<svg style=\'width:16px;height:16px;color:#16a34a\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'2\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M5 13l4 4L19 7\'/></svg>';
                setTimeout(()=>b.innerHTML=prev,1800);
            "
            style="width:30px;height:30px;border-radius:7px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s"
            onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'" title="Copy link">
            <svg style="width:16px;height:16px;color:#6b7280" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
        </button>
        <a href="{$shortUrl}" target="_blank" rel="noopener noreferrer"
           style="width:30px;height:30px;border-radius:7px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:background .15s"
           onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'" title="Open link">
            <svg style="width:15px;height:15px;color:#6b7280" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
    </div>
</div>

<!-- Social sharing -->
<div style="display:flex;align-items:center;justify-content:center;gap:8px;margin:18px 0 0;flex-wrap:wrap">
    <a href="mailto:?body={$encoded}" target="_blank" rel="noopener" title="Email"
       style="width:52px;height:52px;border-radius:12px;border:1.5px solid #e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#d1d5db';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.07)'" onmouseout="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
        <svg style="width:22px;height:22px;color:#374151" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
    </a>
    <a href="https://www.linkedin.com/sharing/share-offsite/?url={$encoded}" target="_blank" rel="noopener" title="LinkedIn"
       style="width:52px;height:52px;border-radius:12px;border:1.5px solid #e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#d1d5db';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.07)'" onmouseout="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
        <svg style="width:22px;height:22px;color:#0077b5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.779-1.75-1.75s.784-1.75 1.75-1.75 1.75.779 1.75 1.75-.784 1.75-1.75 1.75zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
    </a>
    <a href="https://www.facebook.com/sharer/sharer.php?u={$encoded}" target="_blank" rel="noopener" title="Facebook"
       style="width:52px;height:52px;border-radius:12px;border:1.5px solid #e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#d1d5db';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.07)'" onmouseout="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
        <svg style="width:22px;height:22px;color:#1877f2" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
    </a>
    <a href="https://twitter.com/intent/tweet?url={$encoded}" target="_blank" rel="noopener" title="X (Twitter)"
       style="width:52px;height:52px;border-radius:12px;border:1.5px solid #e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#d1d5db';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.07)'" onmouseout="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
        <svg style="width:19px;height:19px;color:#0f1419" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
    </a>
    <a href="https://api.whatsapp.com/send?text={$encoded}" target="_blank" rel="noopener" title="WhatsApp"
       style="width:52px;height:52px;border-radius:12px;border:1.5px solid #e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#d1d5db';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.07)'" onmouseout="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
        <svg style="width:22px;height:22px;color:#25d366" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.458 5.704 1.459h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </a>
    <a href="https://t.me/share/url?url={$encoded}" target="_blank" rel="noopener" title="Telegram"
       style="width:52px;height:52px;border-radius:12px;border:1.5px solid #e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#d1d5db';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.07)'" onmouseout="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
        <svg style="width:22px;height:22px;color:#229ED9" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
    </a>
</div>

<!-- Don't show again -->
<div style="display:flex;align-items:center;gap:8px;margin:20px 0 2px;justify-content:center">
    <input type="checkbox" id="{$eid}_hide"
           onchange="localStorage.setItem('fsu:hide-share-modal', this.checked ? '1' : '0')"
           style="width:15px;height:15px;cursor:pointer;accent-color:#374151;flex-shrink:0">
    <label for="{$eid}_hide" style="font-size:13px;color:#6b7280;cursor:pointer;user-select:none">
        Don't show me the sharing options after creating a link
    </label>
</div>

</div>
HTML;

                    return [
                        Forms\Components\Placeholder::make('share_modal_content')
                            ->label('')
                            ->content(new HtmlString($html)),
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
}
