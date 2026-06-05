@php
    $record = $getRecord();
    $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost();
    
    // Dynamically resolve target IP for A Record
    $serverIp = null;
    try {
        $serverIp = gethostbyname($appHost);
        if ($serverIp === $appHost) {
            $serverIp = $_SERVER['SERVER_ADDR'] ?? null;
        }
    } catch (\Throwable $e) {
        $serverIp = $_SERVER['SERVER_ADDR'] ?? null;
    }
    if (!$serverIp || $serverIp === '127.0.0.1') {
        $serverIp = '76.76.21.21'; // Dummy IP for demonstration if local env has loopback
    }

    // Extract subdomain name
    $domainParts = explode('.', $record->domain);
    $isSubdomain = count($domainParts) > 2;
    $subdomainName = $isSubdomain ? $domainParts[0] : '@';

    $shortUrlsCount = $record->short_urls_count ?? 0;
    $clicksCount = $record->total_clicks ?? 0;
@endphp

<div x-data="{ open: false, activeTab: 'A' }" class="w-full text-left">
    <!-- Header / Collapsed Row -->
    <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Left Section: Icon & Info -->
        <div class="flex items-center gap-3.5 min-w-0">
            <!-- Globe Icon -->
            <div class="flex items-center justify-center w-10 h-10 rounded-full border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-950/40 flex-shrink-0">
                <svg class="w-5 h-5 text-neutral-500 dark:text-neutral-450" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" />
                </svg>
            </div>
            
            <div class="min-w-0">
                <!-- Domain and Primary Badge -->
                <div class="flex items-center gap-2">
                    <span class="text-[15px] font-bold text-neutral-800 dark:text-neutral-200 truncate leading-tight">
                        {{ $record->domain }}
                    </span>
                    @if($record->is_active)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50/80 dark:bg-blue-950/30 text-blue-650 dark:text-blue-400 border border-blue-100/60 dark:border-blue-900/40">
                        Primary
                    </span>
                    @endif
                </div>
                <!-- Subtitle -->
                <div class="text-[12px] text-neutral-450 dark:text-neutral-500 mt-1 flex items-center gap-1.5">
                    <span>↳</span>
                    @if($shortUrlsCount === 0)
                        <span>No redirect configured</span>
                    @else
                        <span>Mapped to {{ $shortUrlsCount }} short {{ Str::plural('link', $shortUrlsCount) }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Middle Section: Clicks & Verification Status -->
        <div class="flex items-center gap-3 sm:ml-auto">
            <!-- Clicks Badge -->
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl border border-neutral-200/60 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/50 text-[12px] font-semibold text-neutral-700 dark:text-neutral-300">
                <svg class="w-3.5 h-3.5 text-neutral-450 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.303.197-1.593 1.593M21.75 12H19.5m-.197 5.303-1.593-1.593M12 21.75V19.5m-5.303-.197 1.593-1.593M2.25 12H4.5m.197-5.303 1.593 1.593" />
                </svg>
                {{ $clicksCount }} clicks
            </span>

            <!-- Status Badge -->
            @if($record->is_verified)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-150 dark:border-emerald-900/40 text-[12px] font-semibold text-emerald-650 dark:text-emerald-450">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                </svg>
                Valid
            </span>
            @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-red-50 dark:bg-red-950/20 border border-red-150 dark:border-red-900/40 text-[12px] font-semibold text-red-650 dark:text-red-400">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                Invalid
            </span>
            @endif
        </div>

        <!-- Right Section: Actions Buttons -->
        <div class="flex items-center gap-2">
            <!-- Settings Toggle Button with red indicator when invalid -->
            <button @click="open = !open" type="button" class="relative inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-950 transition-all shadow-sm focus:outline-none cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.936 6.936 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.33-.183.58-.495.643-.869L9.594 3.94Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                <!-- Live Red Dot Indicator -->
                @if(!$record->is_verified)
                <span class="absolute top-0.5 right-0.5 flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                </span>
                @endif
                <!-- Chevron -->
                <svg class="w-3 h-3 text-neutral-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            <!-- Actions Dropdown -->
            <div x-data="{ dropdownOpen: false }" class="relative">
                <button @click="dropdownOpen = !dropdownOpen" type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-950 transition-all shadow-sm focus:outline-none cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                    </svg>
                </button>
                <div x-show="dropdownOpen" @click.away="dropdownOpen = false" x-cloak class="absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-neutral-850 border border-neutral-200/80 dark:border-neutral-800 z-50 py-1.5 text-left transition-all">
                    <!-- Verify DNS / Refresh -->
                    <button @click="$wire.callTableAction('verify', {{ $record->id }}); dropdownOpen = false" type="button" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-900 focus:outline-none cursor-pointer">
                        <svg class="w-4 h-4 text-neutral-450 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Verify / Refresh DNS
                    </button>
                    <!-- Edit Action -->
                    <button @click="$wire.mountTableAction('edit', {{ $record->id }}); dropdownOpen = false" type="button" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-900 focus:outline-none cursor-pointer">
                        <svg class="w-4 h-4 text-neutral-450 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                        </svg>
                        Edit Domain
                    </button>
                    <hr class="border-neutral-100 dark:border-neutral-800 my-1">
                    <!-- Delete Action -->
                    <button @click="$wire.mountTableAction('delete', {{ $record->id }}); dropdownOpen = false" type="button" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-655 dark:text-red-400 hover:bg-neutral-50 dark:hover:bg-neutral-900 focus:outline-none cursor-pointer">
                        <svg class="w-4 h-4 text-red-450 dark:text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        Delete Domain
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Expanded DNS Panel -->
    <div x-show="open" x-collapse x-cloak class="border-t border-neutral-100 dark:border-neutral-800/80 px-6 py-5 bg-neutral-50/20 dark:bg-neutral-950/5">
        <div class="pt-2">
            <!-- Tabs Menu -->
            <div class="-ml-1.5 border-b border-neutral-200 dark:border-neutral-800">
                <div class="flex text-sm">
                    <!-- Tab A -->
                    <div class="relative">
                        <button @click="activeTab = 'A'" type="button" class="p-4 transition-colors duration-75 text-neutral-400 hover:text-neutral-700 dark:text-neutral-500 dark:hover:text-neutral-300 font-medium focus:outline-none cursor-pointer" :class="activeTab === 'A' ? 'text-neutral-900 dark:text-white font-semibold' : ''">
                            A Record (recommended)
                        </button>
                        <div x-show="activeTab === 'A'" class="absolute bottom-0 w-full px-1.5 text-neutral-900 dark:text-white">
                            <div class="h-0.5 rounded-t-full bg-current"></div>
                        </div>
                    </div>
                    <!-- Tab CNAME -->
                    <div class="relative">
                        <button @click="activeTab = 'CNAME'" type="button" class="p-4 transition-colors duration-75 text-neutral-400 hover:text-neutral-700 dark:text-neutral-500 dark:hover:text-neutral-300 font-medium focus:outline-none cursor-pointer" :class="activeTab === 'CNAME' ? 'text-neutral-900 dark:text-white font-semibold' : ''">
                            CNAME Record
                        </button>
                        <div x-show="activeTab === 'CNAME'" x-cloak class="absolute bottom-0 w-full px-1.5 text-neutral-900 dark:text-white">
                            <div class="h-0.5 rounded-t-full bg-current"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="mt-3 text-left text-neutral-600">
                <!-- Instruction Paragraph -->
                <div class="my-5">
                    <p class="prose-sm prose-code:rounded-md prose-code:bg-neutral-100 dark:prose-code:bg-neutral-800 prose-code:p-1 prose-code:text-[.8125rem] prose-code:font-medium prose-code:font-mono prose-code:text-neutral-900 dark:prose-code:text-neutral-150 max-w-none">
                        To configure your <span x-text="activeTab === 'A' ? 'apex domain' : 'subdomain'"></span> <code>{{ $record->domain }}</code>, set the following <span x-text="activeTab === 'A' ? 'A record' : 'CNAME record'"></span> on your DNS provider:
                    </p>
                </div>

                <!-- Parameters Table (CSS Grid format) -->
                <div class="scrollbar-hide grid items-end gap-x-10 gap-y-1 overflow-x-auto rounded-lg bg-neutral-100/80 dark:bg-neutral-950/40 p-4 text-sm grid-cols-[repeat(4,max-content)]">
                    <!-- Table Headers -->
                    <p class="font-medium text-neutral-950 dark:text-white">Type</p>
                    <p class="font-medium text-neutral-950 dark:text-white">Name</p>
                    <p class="font-medium text-neutral-950 dark:text-white">Value</p>
                    <p class="font-medium text-neutral-950 dark:text-white">TTL</p>

                    <!-- Tab A content -->
                    <div x-show="activeTab === 'A'" class="contents">
                        <p class="font-mono text-neutral-600 dark:text-neutral-400">A</p>
                        <p class="font-mono text-neutral-600 dark:text-neutral-400">@</p>
                        <p class="flex items-center gap-1 font-mono text-neutral-900 dark:text-white" x-data="{ copied: false }">
                            <span>{{ $serverIp }}</span>
                            <button @click="navigator.clipboard.writeText('{{ $serverIp }}'); copied = true; setTimeout(() => copied = false, 2000)" class="relative group rounded-full p-1.5 transition-all duration-75 bg-transparent hover:bg-neutral-200 dark:hover:bg-neutral-800 active:bg-neutral-300 dark:active:bg-neutral-700 -mb-0.5 cursor-pointer focus:outline-none" type="button">
                                <span class="sr-only">Copy</span>
                                <svg x-show="!copied" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="14" height="14" class="h-3.5 w-3.5 text-neutral-450 dark:text-neutral-500">
                                    <path d="M8 17.929H6c-1.105 0-2-.912-2-2.036V5.036C4 3.91 4.895 3 6 3h8c1.105 0 2 .911 2 2.036v1.866m-6 .17h8c1.105 0 2 .91 2 2.035v10.857C20 21.09 19.105 22 18 22h-8c-1.105 0-2-.911-2-2.036V9.107c0-1.124.895-2.036 2-2.036z"></path>
                                </svg>
                                <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </button>
                        </p>
                        <p class="font-mono text-neutral-600 dark:text-neutral-400">86400</p>
                    </div>

                    <!-- Tab CNAME content -->
                    <div x-show="activeTab === 'CNAME'" x-cloak class="contents">
                        <p class="font-mono text-neutral-600 dark:text-neutral-400">CNAME</p>
                        <p class="font-mono text-neutral-600 dark:text-neutral-400">{{ $subdomainName }}</p>
                        <p class="flex items-center gap-1 font-mono text-neutral-900 dark:text-white" x-data="{ copied: false }">
                            <span>{{ $appHost }}</span>
                            <button @click="navigator.clipboard.writeText('{{ $appHost }}'); copied = true; setTimeout(() => copied = false, 2000)" class="relative group rounded-full p-1.5 transition-all duration-75 bg-transparent hover:bg-neutral-200 dark:hover:bg-neutral-800 active:bg-neutral-300 dark:active:bg-neutral-700 -mb-0.5 cursor-pointer focus:outline-none" type="button">
                                <span class="sr-only">Copy</span>
                                <svg x-show="!copied" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="14" height="14" class="h-3.5 w-3.5 text-neutral-450 dark:text-neutral-500">
                                    <path d="M8 17.929H6c-1.105 0-2-.912-2-2.036V5.036C4 3.91 4.895 3 6 3h8c1.105 0 2 .911 2 2.036v1.866m-6 .17h8c1.105 0 2 .91 2 2.035v10.857C20 21.09 19.105 22 18 22h-8c-1.105 0-2-.911-2-2.036V9.107c0-1.124.895-2.036 2-2.036z"></path>
                                </svg>
                                <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </button>
                        </p>
                        <p class="font-mono text-neutral-600 dark:text-neutral-400">86400</p>
                    </div>
                </div>

                <!-- Info Alert Block -->
                <div class="mt-4 flex items-center gap-2 rounded-lg p-3 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-650 dark:text-indigo-400 border border-indigo-100/30 dark:border-indigo-900/30">
                    <svg height="18" width="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0">
                        <g fill="currentColor">
                            <circle cx="9" cy="9" fill="none" r="7.25" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></circle>
                            <line fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" x1="9" x2="9" y1="12.819" y2="8.25"></line>
                            <path d="M9,6.75c-.552,0-1-.449-1-1s.448-1,1-1,1,.449,1,1-.448,1-1,1Z" fill="currentColor" stroke="none"></path>
                        </g>
                    </svg>
                    <p class="prose-sm prose-code:rounded-md prose-code:bg-neutral-100 dark:prose-code:bg-neutral-800 prose-code:p-1 prose-code:text-[.8125rem] prose-code:font-medium prose-code:font-mono prose-code:text-neutral-900 dark:prose-code:text-neutral-150 max-w-none">
                        If a TTL value of 86400 is not available, choose the highest available value. Domain propagation may take up to 12 hours.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
