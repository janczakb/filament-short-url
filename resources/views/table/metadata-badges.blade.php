@php
    // Get the user relationship config
    $userModel = config('filament-short-url.user.model', \App\Models\User::class);
    $nameColumn = config('filament-short-url.user.name_column', 'name');
    $emailColumn = config('filament-short-url.user.email_column', 'email');
    $avatarColumn = config('filament-short-url.user.avatar_column', 'avatar_url');

    $user = $record->user;
    $userName = null;
    $userEmail = null;
    $avatarUrl = null;

    if ($user) {
        $userName = $user->{$nameColumn} ?? null;
        $userEmail = $user->{$emailColumn} ?? null;

        // Try to get avatar URL
        if ($avatarColumn && method_exists($user, $avatarColumn)) {
            $avatarUrl = $user->{$avatarColumn}();
        } elseif ($avatarColumn && isset($user->{$avatarColumn})) {
            $avatarUrl = $user->{$avatarColumn};
        } elseif ($user instanceof \Filament\Models\Contracts\HasAvatar) {
            $avatarUrl = $user->getFilamentAvatarUrl();
        } elseif (method_exists($user, 'getFilamentAvatarUrl')) {
            $avatarUrl = $user->getFilamentAvatarUrl();
        }

        // Fallback to Gravatar if no avatar URL found
        if (empty($avatarUrl) && !empty($userEmail)) {
            $avatarUrl = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($userEmail))) . '?d=mp&s=80';
        }
    }

    // Relative Date Calculations for "1h", "2d" etc.
    $diff = $record->created_at->diff(now());
    if ($diff->y > 0) {
        $shortTime = $diff->y . 'y';
    } elseif ($diff->m > 0) {
        $shortTime = $diff->m . 'mo';
    } elseif ($diff->d > 0) {
        $shortTime = $diff->d . 'd';
    } elseif ($diff->h > 0) {
        $shortTime = $diff->h . 'h';
    } elseif ($diff->i > 0) {
        $shortTime = $diff->i . 'm';
    } else {
        $shortTime = max(1, $diff->s) . 's';
    }

    // Detailed relative format for hover: "1 hour, 52 minutes, 16 seconds ago"
    $parts = [];
    if ($diff->y > 0) {
        $parts[] = $diff->y . ' ' . str('year')->plural($diff->y);
    }
    if ($diff->m > 0) {
        $parts[] = $diff->m . ' ' . str('month')->plural($diff->m);
    }
    if ($diff->d > 0) {
        $parts[] = $diff->d . ' ' . str('day')->plural($diff->d);
    }
    if ($diff->h > 0) {
        $parts[] = $diff->h . ' ' . str('hour')->plural($diff->h);
    }
    if ($diff->i > 0) {
        $parts[] = $diff->i . ' ' . str('minute')->plural($diff->i);
    }
    if ($diff->s > 0) {
        $parts[] = $diff->s . ' ' . str('second')->plural($diff->s);
    }

    // Slice to top 3 units for a readable detailed string
    $detailedParts = array_slice($parts, 0, 3);
    $detailedRelative = !empty($detailedParts) 
        ? implode(', ', $detailedParts) . ' ago' 
        : 'just now';

    // Timezone offset badge (e.g. GMT+2)
    $offsetHours = $record->created_at->offsetHours;
    $timezoneBadge = 'GMT' . ($offsetHours >= 0 ? '+' : '') . $offsetHours;

    // Absolute date (e.g. Jun 5, 2026, 1:09:29 AM)
    $absoluteDate = $record->created_at->format('M j, Y, g:i:s A');
@endphp

<div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-3">
    <!-- Clicks Badge -->
    <span class="inline-flex items-center gap-1 bg-[#f4f4f5] dark:bg-gray-800 px-2 py-1 rounded text-[11px] font-medium text-gray-600 dark:text-gray-300">
        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        {{ number_format($record->total_visits) }} {{ __('filament-short-url::default.badge_clicks') }}
    </span>

    <!-- Unique Clicks Badge -->
    <span class="inline-flex items-center gap-1 bg-[#f4f4f5] dark:bg-gray-800 px-2 py-1 rounded text-[11px] font-medium text-gray-600 dark:text-gray-300">
        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        {{ number_format($record->unique_visits) }} {{ __('filament-short-url::default.badge_unique') }}
    </span>

    <!-- QR Scans Badge -->
    <span class="inline-flex items-center gap-1 bg-[#f4f4f5] dark:bg-gray-800 px-2 py-1 rounded text-[11px] font-medium text-gray-600 dark:text-gray-300">
        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12h.008v.008H15V12Zm0 3h.008v.008H15V15Zm0 3h.008v.008H15V18Zm3-3h.008v.008H18V15Zm0 3h.008v.008H18V18Zm3-3h.008v.008H21V15Zm0 3h.008v.008H21V18Zm0-6h.008v.008H21V12Zm-3 0h.008v.008H18V12Z" />
        </svg>
        {{ number_format($record->qr_scans) }} {{ __('filament-short-url::default.badge_qr_scans') }}
    </span>

    <!-- Date Added Badge (with Hover Card) -->
    <div class="relative inline-block" x-data="{ open: false }">
        <div @mouseenter="open = true" @mouseleave="open = false" 
             class="cursor-pointer inline-flex items-center gap-1 bg-[#f4f4f5] dark:bg-gray-800 px-2 py-1 rounded text-[11px] font-medium text-gray-600 dark:text-gray-300">
            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            {{ $shortTime }}
        </div>
        
        <!-- Popover Card for Date -->
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-1 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-1 scale-95"
             class="absolute z-[100] bottom-full left-1/2 -translate-x-1/2 mb-2 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 pointer-events-none overflow-hidden rounded-xl shadow-sm min-w-max"
             x-cloak>
            <div class="flex max-w-[360px] flex-col gap-2 px-2.5 py-2 text-left text-xs">
                <span class="text-neutral-500 dark:text-neutral-400 cursor-default">{{ $detailedRelative }}</span>
                <table>
                    <tbody>
                        <tr class="before:bg-bg-emphasis relative select-none before:absolute before:-inset-x-1 before:inset-y-0 before:rounded before:opacity-0 before:content-[''] hover:cursor-copy hover:before:opacity-60 active:before:opacity-100">
                            <td class="relative py-0.5">
                                <span class="text-neutral-500 dark:text-neutral-400 truncate bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 rounded px-1 font-mono text-[11px]" title="{{ config('app.timezone') }}">{{ $timezoneBadge }}</span>
                            </td>
                            <td class="text-neutral-800 dark:text-neutral-200 relative whitespace-nowrap py-0.5 pl-2">{{ $absoluteDate }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($user)
        <!-- User Avatar with Hover Card -->
        <div class="relative inline-block" x-data="{ open: false }">
            <div @mouseenter="open = true" @mouseleave="open = false" class="cursor-pointer flex items-center justify-center">
                <img src="{{ $avatarUrl }}" 
                     alt="{{ $userName }}" 
                     class="w-6 h-6 rounded-full border border-neutral-200 dark:border-neutral-700 bg-neutral-100 object-cover" />
            </div>
            
            <!-- Popover Card for User -->
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                 class="absolute z-[100] bottom-full left-1/2 -translate-x-1/2 mb-2 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 pointer-events-none overflow-hidden rounded-xl shadow-sm min-w-max"
                 x-cloak>
                <div class="w-full p-3 text-left">
                    <img alt="Avatar for {{ $userName }}" 
                         referrerpolicy="no-referrer" 
                         class="rounded-full border border-neutral-300 h-8 w-8 object-cover" 
                         draggable="false" 
                         src="{{ $avatarUrl }}">
                    <div class="mt-2 flex items-center gap-1.5">
                        <p class="text-sm font-semibold text-neutral-700 dark:text-neutral-200">{{ $userName }}</p>
                    </div>
                    <div class="flex flex-col gap-1 text-xs text-neutral-500 dark:text-neutral-400">
                        <p>{{ $userEmail }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Expiry / Single Use Badge -->
    @if ($record->expires_at)
        <span class="inline-flex items-center gap-1 bg-[#f4f4f5] dark:bg-gray-800 px-2 py-1 rounded text-[11px] font-medium text-gray-600 dark:text-gray-300">
            <svg class="w-3.5 h-3.5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ __('filament-short-url::default.badge_expires', ['date' => $record->expires_at->translatedFormat('M d, Y')]) }}
        </span>
    @endif

    {{-- Keyboard Shortcuts Handler (Alpine.js) --}}
    <div x-data="{ 
            hovered: false,
            init() {
                const card = this.$el.closest('.short-url-card');
                if (card) {
                    this.hovered = card.matches(':hover');
                    card.addEventListener('mouseenter', () => this.hovered = true);
                    card.addEventListener('mouseleave', () => this.hovered = false);
                }
            },
            handleKeyup(event) {
                if (!this.hovered) return;
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName) || document.activeElement.isContentEditable) return;
                if (document.activeElement.closest('.fi-modal-window') || document.activeElement.closest('.fi-modal')) return;
                
                const key = event.key.toLowerCase();
                if (event.ctrlKey || event.metaKey || event.altKey) return;
                
                if (key === 'e') {
                    this.$wire.mountTableAction('edit', '{{ $record->id }}');
                } else if (key === 'q') {
                    this.$wire.mountTableAction('qrCode', '{{ $record->id }}');
                } else if (key === 'i') {
                    this.$wire.mountTableAction('share', '{{ $record->id }}');
                } else if (key === 's') {
                    window.location.href = '{{ \Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource::getUrl('stats', ['record' => $record]) }}';
                } else if (key === 'x') {
                    this.$wire.mountTableAction('delete', '{{ $record->id }}');
                }
            }
         }"
         @keyup.window="handleKeyup($event)"
         class="hidden">
    </div>
</div>
