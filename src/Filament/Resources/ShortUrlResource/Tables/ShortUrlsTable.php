<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Tables;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ShortUrlsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    // ROW 1: Favicon + Short URL with copy capability on click of text or icon (vertically centered!)
                    TextColumn::make('url_key')
                        ->label(__('filament-short-url::default.col_short_url'))
                        ->getStateUsing(static function (ShortUrl $record): HtmlString {
                            $shortUrl = e($record->getShortUrl());
                            $copiedMsg = e(__('filament-short-url::default.qr_copied'));
                            $tooltipCopy = e(__('filament-short-url::default.action_copy'));
                            $destHost = e(parse_url($record->destination_url, PHP_URL_HOST));

                            return new HtmlString(<<<HTML
                                <div onclick="
                                    event.preventDefault();
                                    event.stopPropagation();
                                    const text = '{$shortUrl}';
                                    if (navigator.clipboard && window.isSecureContext) {
                                        navigator.clipboard.writeText(text);
                                    } else {
                                        const textCopyArea = document.createElement('textarea');
                                        textCopyArea.value = text;
                                        textCopyArea.style.position = 'fixed';
                                        textCopyArea.style.left = '-999999px';
                                        textCopyArea.style.top = '-999999px';
                                        document.body.appendChild(textCopyArea);
                                        textCopyArea.focus();
                                        textCopyArea.select();
                                        try {
                                            document.execCommand('copy');
                                        } catch (err) {}
                                        textCopyArea.remove();
                                    }
                                    if (typeof FilamentNotification !== 'undefined') {
                                        new FilamentNotification()
                                            .title('{$copiedMsg}')
                                            .success()
                                            .send();
                                    } else if (window.Alpine) {
                                        window.Alpine.store('filament-notifications')?.send({
                                            status: 'success',
                                            title: '{$copiedMsg}'
                                        });
                                    }
                                " class="flex items-center gap-3 cursor-pointer w-fit">
                                    <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                                        <img src="https://icons.duckduckgo.com/ip2/{$destHost}.ico" 
                                             class="w-full h-full object-contain" 
                                             onerror="this.src='https://heroicons.com/24/outline/link.svg'" />
                                    </div>
                                    <span class="text-[#2a5bd7] text-[16px] font-bold leading-6 break-all line-clamp-1">
                                        {$shortUrl}
                                    </span>
                                    <span title="{$tooltipCopy}" class="w-8 h-8 rounded-full flex items-center justify-center bg-[#f4f4f5] hover:bg-[#e4e4e7] dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors flex-shrink-0 focus:outline-none">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                </div>
HTML
                            );
                        })
                        ->searchable(query: fn ($query, string $search) => $query->where('url_key', 'like', "%{$search}%")),

                    // ROW 2: Sub-arrow + Destination URL
                    TextColumn::make('destination_url')
                        ->label(__('filament-short-url::default.col_destination_url'))
                        ->getStateUsing(fn ($record): HtmlString => new HtmlString('
                            <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 text-xs mt-2">
                                <svg class="w-3.5 h-3.5 flex-shrink-0 text-gray-400 dark:text-gray-500" 
                                     style="transform: scaleY(-1);" 
                                     xmlns="http://www.w3.org/2000/svg" 
                                     fill="none" 
                                     viewBox="0 0 24 24" 
                                     stroke-width="2.5" 
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 00-6 6v3" />
                                </svg>
                                <a href="'.e($record->destination_url).'" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="truncate max-w-[50ch] hover:underline text-[#273144] text-[15px] leading-[16px] font-medium" 
                                   title="'.e($record->destination_url).'">
                                    '.e($record->destination_url).'
                                </a>
                            </div>
                        '))
                        ->searchable(),

                    // ROW 3: Bottom Metadata (Total clicks / Unique clicks / Date added / Expiry / Redirect code)
                    TextColumn::make('metadata_badges')
                        ->getStateUsing(fn ($record): HtmlString => new HtmlString('
                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-3">
                                    <!-- Clicks Badge -->
                                    <span class="inline-flex items-center gap-1 bg-[#f4f4f5] dark:bg-gray-800 px-2 py-1 rounded text-[11px] font-medium text-gray-600 dark:text-gray-300">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        '.$record->total_visits.' '.__('filament-short-url::default.badge_clicks').'
                                    </span>

                                    <!-- Unique Clicks Badge -->
                                    <span class="inline-flex items-center gap-1 bg-[#f4f4f5] dark:bg-gray-800 px-2 py-1 rounded text-[11px] font-medium text-gray-600 dark:text-gray-300">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        '.$record->unique_visits.' '.__('filament-short-url::default.badge_unique').'
                                    </span>

                                    <!-- QR Scans Badge -->
                                    <span class="inline-flex items-center gap-1 bg-[#f4f4f5] dark:bg-gray-800 px-2 py-1 rounded text-[11px] font-medium text-gray-600 dark:text-gray-300">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12h.008v.008H15V12Zm0 3h.008v.008H15V15Zm0 3h.008v.008H15V18Zm3-3h.008v.008H18V15Zm0 3h.008v.008H18V18Zm3-3h.008v.008H21V15Zm0 3h.008v.008H21V18Zm0-6h.008v.008H21V12Zm-3 0h.008v.008H18V12Z" />
                                        </svg>
                                        '.$record->qr_scans.' '.__('filament-short-url::default.badge_qr_scans').'
                                    </span>

                                    <!-- Date Added Badge -->
                                    <span class="inline-flex items-center gap-1 bg-[#f4f4f5] dark:bg-gray-800 px-2 py-1 rounded text-[11px] font-medium text-gray-600 dark:text-gray-300">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        '.$record->created_at->translatedFormat('M d, Y').'
                                    </span>

                                    <!-- Expiry / Single Use Badge -->
                                    <span class="inline-flex items-center gap-1 bg-[#f4f4f5] dark:bg-gray-800 px-2 py-1 rounded text-[11px] font-medium text-gray-600 dark:text-gray-300">
                                        '.($record->expires_at ? '
                                            <svg class="w-3.5 h-3.5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            '.__('filament-short-url::default.badge_expires', ['date' => $record->expires_at->translatedFormat('M d, Y')]).'
                                        ' : '
                                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            '.__('filament-short-url::default.badge_no_expiry').'
                                        ').'
                                    </span>

                                    <!-- Redirect Type Badge -->
                                    <span class="inline-flex items-center gap-1 bg-[#f4f4f5] dark:bg-gray-800 px-2 py-1 rounded text-[11px] font-medium text-gray-600 dark:text-gray-300">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        '.__('filament-short-url::default.badge_redirect', ['code' => $record->redirect_status_code]).'
                                    </span>
                                </div>
                            ')),
                ]),
            ])
            ->filters([
                TernaryFilter::make('is_enabled')
                    ->label(__('filament-short-url::default.col_status'))
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                TernaryFilter::make('track_visits')
                    ->label(__('filament-short-url::default.track_visits'))
                    ->trueLabel('Tracking enabled')
                    ->falseLabel('Tracking disabled'),

                SelectFilter::make('single_use')
                    ->label(__('filament-short-url::default.single_use'))
                    ->options([
                        '0' => 'Multi-use',
                        '1' => 'Single-use',
                    ]),
            ])
            ->recordClasses(fn (): string => 'short-url-card')
            ->actions([
                Action::make('share')
                    ->label(__('filament-short-url::default.action_share'))
                    ->icon('heroicon-o-share')
                    ->color('gray')
                    ->iconButton()
                    ->tooltip(__('filament-short-url::default.action_share'))
                    ->modalHeading(__('filament-short-url::default.share_title'))
                    ->modalWidth('md')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->form([
                        Forms\Components\Placeholder::make('platforms')
                            ->label(__('filament-short-url::default.share_description'))
                            ->content(fn (ShortUrl $record): HtmlString => new HtmlString('
                                <div class="flex items-center gap-6 overflow-x-auto pb-4 pt-1 scroll-smooth" style="scrollbar-width: thin; -ms-overflow-style: none;">
                                    <!-- Messenger -->
                                    <a href="fb-messenger://share/?link='.urlencode($record->getShortUrl()).'" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group flex-shrink-0 cursor-pointer">
                                        <div class="w-11 h-11 rounded-full bg-gradient-to-tr from-[#006aff] via-[#00b2ff] to-[#00d6ff] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
                                            <svg class="w-5.5 h-5.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.14 2 11.25c0 2.9 1.45 5.48 3.73 7.08v3.67c0 .24.23.4.43.27l4.07-2.3c.57.16 1.17.25 1.77.25 5.52 0 10-4.14 10-9.25S17.52 2 12 2zm1.09 11.95l-2.43-2.6-4.73 2.6 5.19-5.52 2.47 2.63 4.7-2.63-5.2 5.52z"/>
                                            </svg>
                                        </div>
                                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Messenger</span>
                                    </a>
                                    <!-- Facebook -->
                                    <a href="https://www.facebook.com/sharer/sharer.php?u='.urlencode($record->getShortUrl()).'" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group flex-shrink-0 cursor-pointer">
                                        <div class="w-11 h-11 rounded-full bg-[#1877f2] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
                                            <svg class="w-5.5 h-5.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                            </svg>
                                        </div>
                                        <span class="text-[11px] font-medium text-gray-550 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Facebook</span>
                                    </a>
                                    <!-- WhatsApp -->
                                    <a href="https://api.whatsapp.com/send?text='.urlencode($record->getShortUrl()).'" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group flex-shrink-0 cursor-pointer">
                                        <div class="w-11 h-11 rounded-full bg-[#25d366] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
                                            <svg class="w-5.5 h-5.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.458 5.704 1.459h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                        </div>
                                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">WhatsApp</span>
                                    </a>
                                    <!-- Twitter/X -->
                                    <a href="https://twitter.com/intent/tweet?url='.urlencode($record->getShortUrl()).'" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group flex-shrink-0 cursor-pointer">
                                        <div class="w-11 h-11 rounded-full bg-[#0f1419] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                            </svg>
                                        </div>
                                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Twitter (X)</span>
                                    </a>
                                    <!-- LinkedIn -->
                                    <a href="https://www.linkedin.com/sharing/share-offsite/?url='.urlencode($record->getShortUrl()).'" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group flex-shrink-0 cursor-pointer">
                                        <div class="w-11 h-11 rounded-full bg-[#0077b5] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
                                            <svg class="w-5.5 h-5.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.779-1.75-1.75s.784-1.75 1.75-1.75 1.75.779 1.75 1.75-.784 1.75-1.75 1.75zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                            </svg>
                                        </div>
                                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">LinkedIn</span>
                                    </a>
                                    <!-- Email -->
                                    <a href="mailto:?body='.urlencode($record->getShortUrl()).'" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group flex-shrink-0 cursor-pointer">
                                        <div class="w-11 h-11 rounded-full bg-[#6b7280] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
                                            <svg class="w-5.5 h-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Email</span>
                                    </a>
                                </div>
                            ')),

                        Forms\Components\Placeholder::make('copy_field')
                            ->label('')
                            ->content(fn (ShortUrl $record): HtmlString => new HtmlString('
                                <div class="flex items-center gap-2 relative mt-2">
                                    <input type="text" 
                                           readonly 
                                           value="'.e($record->getShortUrl()).'" 
                                           id="share_link_input_'.$record->id.'"
                                           class="flex-1 min-w-0 block w-full px-3.5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 text-gray-900 dark:text-gray-100 text-sm focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-400 dark:focus:border-primary-400 focus:outline-none">
                                    
                                    <button onclick="
                                        const input = document.getElementById(\'share_link_input_'.$record->id.'\');
                                        input.select();
                                        navigator.clipboard.writeText(input.value);
                                        if (typeof FilamentNotification !== \'undefined\') {
                                            new FilamentNotification()
                                                .title(\''.e(__('filament-short-url::default.share_copied')).'\')
                                                .success()
                                                .send();
                                        } else if (typeof Alpine !== \'undefined\') {
                                            Alpine.store(\'filament-notifications\')?.send({
                                                status: \'success\',
                                                title: \''.e(__('filament-short-url::default.share_copied')).'\'
                                            });
                                        }
                                    " 
                                    class="flex-shrink-0 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-gray-900 hover:bg-gray-800 dark:bg-gray-100 dark:hover:bg-white text-white dark:text-gray-950 font-semibold text-sm rounded-lg shadow-sm hover:shadow transition duration-200">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <span>'.e(__('filament-short-url::default.share_copy')).'</span>
                                    </button>
                                </div>
                            ')),
                    ]),

                Action::make('qrCode')
                    ->label(__('filament-short-url::default.action_qr') ?? 'QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->color('gray')
                    ->iconButton()
                    ->tooltip(__('filament-short-url::default.action_qr') ?? 'QR Code')
                    ->modalWidth('md')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->form(function (ShortUrl $record): array {
                        $shortUrl = $record->getShortUrl();
                        $qrTargetUrl = $shortUrl.'?source=qr';
                        $destHost = parse_url($record->destination_url, PHP_URL_HOST) ?? '';
                        $urlKey = $record->url_key;
                        $eid = 'fsu_'.substr(md5($shortUrl), 0, 8);

                        $qrHelperText = __('filament-short-url::default.qr_modal_helper') ?? 'Scan, copy, or download your custom QR code.';
                        $downloadSvgText = __('filament-short-url::default.qr_download_svg') ?? 'Download SVG';
                        $downloadPngText = __('filament-short-url::default.qr_download_png') ?? 'Download PNG';
                        $closeButtonText = __('filament-short-url::default.close_button') ?? 'Close';
                        $copyLinkText = __('filament-short-url::default.action_copy') ?? 'Copy link';
                        $openLinkText = __('filament-short-url::default.open_link') ?? 'Open link';

                        $qrDefaults = $record->getQrOptions();
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

                        $qrOptionsJson = json_encode([
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
                        ]);

                        $escapedQrOptions = e($qrOptionsJson);

                        $html = <<<HTML
<div data-qr-options="{$escapedQrOptions}" x-data="{
    shortUrl: '{$shortUrl}',
    qrTargetUrl: '{$qrTargetUrl}',
    urlKey: '{$urlKey}',
    eid: '{$eid}',
    init() {
        this.\$nextTick(() => {
            this.loadScript().then(() => {
                const canvas = document.getElementById(this.eid + '_qr_canvas');
                if (canvas && !canvas.innerHTML) {
                    const opts = JSON.parse(this.\$el.getAttribute('data-qr-options'));
                    opts.data = this.qrTargetUrl;
                    
                    const fixSvg = () => {
                        const svg = canvas.querySelector('svg');
                        if (svg) {
                            const w = svg.getAttribute('width') || opts.width || 200;
                            const h = svg.getAttribute('height') || opts.height || 200;
                            svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
                            svg.style.width = '100%';
                            svg.style.height = '100%';
                        }
                    };

                    if (opts.image && opts.imageOptions) {
                        const img = new Image();
                        img.onload = () => {
                            const cv = document.createElement('canvas');
                            cv.width = 1200;
                            cv.height = 1200;
                            const cx = cv.getContext('2d');
                            cx.imageSmoothingEnabled = true;
                            cx.imageSmoothingQuality = 'high';

                            const isCircle = opts.imageOptions.logoShape === 'circle';
                            const targetDim = 1200 - (parseFloat(opts.imageOptions.margin || 0) * 20);

                            cx.save();
                            if (isCircle) {
                                cx.beginPath();
                                cx.arc(600, 600, targetDim / 2, 0, 2 * Math.PI);
                                cx.clip();

                                const scale = Math.max(targetDim / img.width, targetDim / img.height);
                                const w = img.width * scale;
                                const h = img.height * scale;
                                const x = (1200 - w) / 2;
                                const y = (1200 - h) / 2;

                                cx.drawImage(img, x, y, w, h);
                            } else {
                                cx.beginPath();
                                const offset = (1200 - targetDim) / 2;
                                const radius = 144 * (targetDim / 1200);

                                if (typeof cx.roundRect === 'function') {
                                    cx.roundRect(offset, offset, targetDim, targetDim, radius);
                                } else {
                                    cx.moveTo(offset + radius, offset);
                                    cx.lineTo(offset + targetDim - radius, offset);
                                    cx.quadraticCurveTo(offset + targetDim, offset, offset + targetDim, offset + radius);
                                    cx.lineTo(offset + targetDim, offset + targetDim - radius);
                                    cx.quadraticCurveTo(offset + targetDim, offset + targetDim, offset + targetDim - radius, offset + targetDim);
                                    cx.lineTo(offset + radius, offset + targetDim);
                                    cx.quadraticCurveTo(offset, offset + targetDim, offset, offset + targetDim - radius);
                                    cx.lineTo(offset, offset + radius);
                                    cx.quadraticCurveTo(offset, offset, offset + radius, offset);
                                    cx.closePath();
                                }
                                cx.clip();

                                const scale = Math.max(targetDim / img.width, targetDim / img.height);
                                const w = img.width * scale;
                                const h = img.height * scale;
                                const x = (1200 - w) / 2;
                                const y = (1200 - h) / 2;

                                cx.drawImage(img, x, y, w, h);
                            }
                            cx.restore();

                            opts.image = cv.toDataURL('image/png');
                            opts.imageOptions.margin = 0;


                            window['qr_' + this.eid] = new window.QRCodeStyling(opts);
                            window['qr_' + this.eid].append(canvas);
                            fixSvg();
                        };
                        img.src = opts.image;
                    } else {
                        window['qr_' + this.eid] = new window.QRCodeStyling(opts);
                        window['qr_' + this.eid].append(canvas);
                        fixSvg();
                    }
                }
            });
        });
    },
    loadScript() {
        return new Promise((resolve) => {
            if (window.QRCodeStyling) return resolve();
            const s = document.createElement('script');
            s.src = 'https://unpkg.com/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js';
            s.onload = () => resolve();
            s.onerror = () => {
                const s2 = document.createElement('script');
                s2.src = 'https://cdn.jsdelivr.net/npm/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js';
                s2.onload = () => resolve();
                document.head.appendChild(s2);
            };
            document.head.appendChild(s);
        });
    }
}">

<!-- Close Button (x) -->
<button type="button" x-on:click="event.preventDefault(); event.stopPropagation(); const f=\$el.closest('.fi-modal-window'); const m=f?(f.getAttribute('x-on:keydown.window.escape')||'').match(/'(fi-[^']*)'/):null; if(m){\$dispatch('close-modal',{id:m[1]})}else{\$wire.unmountAction()}"
        style="position:absolute;top:16px;right:16px;z-index:50;width:28px;height:28px;border-radius:50%;border:none;background:#f3f4f6;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6b7280;transition:color .15s,background-color .15s"
        onmouseover="this.style.color='#374151';this.style.backgroundColor='#e5e7eb'"
        onmouseout="this.style.color='#6b7280';this.style.backgroundColor='#f3f4f6'"
        title="{$closeButtonText}">
    <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
    </svg>
</button>

<div style="text-align:center;padding:4px 0 8px">
    <p style="font-size:13px;color:#9ca3af;margin:0">{$qrHelperText}</p>
</div>

<!-- URL pill -->
<div style="display:flex;align-items:center;gap:10px;background:#EFF6FF;border-radius:999px;padding:10px 14px;margin:18px 0 0">
    <div style="width:30px;height:30px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid #e5e7eb">
        <img src="https://icons.duckduckgo.com/ip2/{$destHost}.ico" style="width:16px;height:16px;object-fit:contain" onerror="this.style.display='none'">
    </div>
    <span style="flex:1;font-size:14px;font-weight:600;color:#1d4ed8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{$shortUrl}</span>
    <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
        <button id="{$eid}_copy" type="button"
            onclick="
                event.preventDefault();
                event.stopPropagation();
                const u='{$shortUrl}';
                if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(u);}
                else{const t=document.createElement('textarea');t.value=u;t.style.cssText='position:fixed;left:-9999px';document.body.appendChild(t);t.select();document.execCommand('copy');t.remove();}
                const b=document.getElementById('{$eid}_copy');
                const prev=b.innerHTML;
                b.innerHTML='<svg style=\'width:16px;height:16px;color:#16a34a\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'2\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M5 13l4 4L19 7\'/></svg>';
                setTimeout(()=>b.innerHTML=prev,1800);
            "
            style="width:30px;height:30px;border-radius:7px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s"
            onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'" title="{$copyLinkText}">
            <svg style="width:16px;height:16px;color:#6b7280" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
        </button>
        <a href="{$shortUrl}" target="_blank" rel="noopener noreferrer"
           style="width:30px;height:30px;border-radius:7px;border:1px solid #e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:background .15s"
           onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'" title="{$openLinkText}">
            <svg style="width:15px;height:15px;color:#6b7280" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
    </div>
</div>

<!-- QR Code Container (Visible immediately) -->
<div id="{$eid}_qr_container" style="display:block;margin:16px 0 0;text-align:center;background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:16px">
    <div style="display:flex;justify-content:center;margin-bottom:12px">
        <div id="{$eid}_qr_canvas" style="background:#fff;padding:8px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.05)"></div>
    </div>
    <div style="display:flex;justify-content:center;gap:8px">
        <button type="button" onclick="event.preventDefault();event.stopPropagation();window['qr_{$eid}']?.download({ name: '{$urlKey}-qr', extension: 'svg' })" style="font-size:12px;font-weight:600;color:#374151;border:1.5px solid #e5e7eb;background:#fff;padding:6px 12px;border-radius:8px;cursor:pointer;transition:background .15s" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'">{$downloadSvgText}</button>
        <button type="button" onclick="event.preventDefault();event.stopPropagation();window['qr_{$eid}']?.download({ name: '{$urlKey}-qr', extension: 'png' })" style="font-size:12px;font-weight:600;color:#374151;border:1.5px solid #e5e7eb;background:#fff;padding:6px 12px;border-radius:8px;cursor:pointer;transition:background .15s" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'">{$downloadPngText}</button>
    </div>
</div>

</div>
HTML;

                        return [
                            Forms\Components\Placeholder::make('qr_modal_content')
                                ->hiddenLabel()
                                ->content(new HtmlString($html)),
                        ];
                    }),

                Action::make('stats')
                    ->label(__('filament-short-url::default.action_stats'))
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->iconButton()
                    ->tooltip(__('filament-short-url::default.action_stats'))
                    ->url(fn (ShortUrl $record): string => ShortUrlResource::getUrl('stats', ['record' => $record])),

                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->iconButton()
                    ->tooltip(__('filament-short-url::default.action_edit')),

                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->color('gray')
                    ->iconButton()
                    ->tooltip(__('filament-short-url::default.action_delete')),
            ])
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('enable')
                        ->label(__('filament-short-url::default.action_enable_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('disable')
                        ->label(__('filament-short-url::default.action_disable_selected'))
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => false]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
