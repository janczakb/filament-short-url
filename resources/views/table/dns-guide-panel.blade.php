@php
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
        $serverIp = '76.76.21.21'; // Dummy IP for demonstration
    }

    // Extract subdomain name
    $domainParts = explode('.', $record->domain);
    $isSubdomain = count($domainParts) > 2;
    $subdomainName = $isSubdomain ? $domainParts[0] : '@';
@endphp

<div x-data="{ activeTab: 'A' }" class="p-6 bg-neutral-50/50 dark:bg-neutral-950/20 border-t border-neutral-100 dark:border-neutral-800 text-left">
    <!-- Tabs Menu -->
    <div class="-ml-1.5 border-b border-neutral-200 dark:border-neutral-800">
        <div class="flex text-sm">
            <!-- Tab A -->
            <div class="relative">
                <button @click="activeTab = 'A'" type="button" class="p-4 transition-colors duration-75 text-neutral-400 hover:text-neutral-700 dark:text-neutral-500 dark:hover:text-neutral-300 font-medium focus:outline-none cursor-pointer" :class="activeTab === 'A' ? 'text-neutral-900 dark:text-white font-semibold' : ''">
                    {{ __('filament-short-url::default.dns_tab_a') }}
                </button>
                <div x-show="activeTab === 'A'" class="absolute bottom-0 w-full px-1.5 text-neutral-900 dark:text-white">
                    <div class="h-0.5 rounded-t-full bg-current"></div>
                </div>
            </div>
            <!-- Tab CNAME -->
            <div class="relative">
                <button @click="activeTab = 'CNAME'" type="button" class="p-4 transition-colors duration-75 text-neutral-400 hover:text-neutral-700 dark:text-neutral-500 dark:hover:text-neutral-300 font-medium focus:outline-none cursor-pointer" :class="activeTab === 'CNAME' ? 'text-neutral-900 dark:text-white font-semibold' : ''">
                    {{ __('filament-short-url::default.dns_tab_cname') }}
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
            <p x-show="activeTab === 'A'" class="prose-sm prose-code:rounded-md prose-code:bg-neutral-100 dark:prose-code:bg-neutral-800 prose-code:p-1 prose-code:text-[.8125rem] prose-code:font-medium prose-code:font-mono prose-code:text-neutral-900 dark:prose-code:text-neutral-150 max-w-none">
                {!! __('filament-short-url::default.dns_instructions_apex', ['domain' => '<code>' . e($record->domain) . '</code>']) !!}
            </p>
            <p x-show="activeTab === 'CNAME'" x-cloak class="prose-sm prose-code:rounded-md prose-code:bg-neutral-100 dark:prose-code:bg-neutral-800 prose-code:p-1 prose-code:text-[.8125rem] prose-code:font-medium prose-code:font-mono prose-code:text-neutral-900 dark:prose-code:text-neutral-150 max-w-none">
                {!! __('filament-short-url::default.dns_instructions_subdomain', ['domain' => '<code>' . e($record->domain) . '</code>']) !!}
            </p>
        </div>

        <!-- Parameters Table (CSS Grid format) -->
        <div class="scrollbar-hide grid items-end gap-x-10 gap-y-1 overflow-x-auto rounded-lg bg-neutral-100/80 dark:bg-neutral-950/40 p-4 text-sm grid-cols-[repeat(4,max-content)]">
            <!-- Table Headers -->
            <p class="font-medium text-neutral-950 dark:text-white">{{ __('filament-short-url::default.dns_table_type') }}</p>
            <p class="font-medium text-neutral-950 dark:text-white">{{ __('filament-short-url::default.dns_table_name') }}</p>
            <p class="font-medium text-neutral-950 dark:text-white">{{ __('filament-short-url::default.dns_table_value') }}</p>
            <p class="font-medium text-neutral-950 dark:text-white">{{ __('filament-short-url::default.dns_table_ttl') }}</p>

            <!-- Tab A content -->
            <div x-show="activeTab === 'A'" class="contents">
                <p class="font-mono text-neutral-600 dark:text-neutral-400">A</p>
                <p class="font-mono text-neutral-600 dark:text-neutral-400">@</p>
                <p class="flex items-end gap-1 font-mono text-neutral-900 dark:text-white" x-data="{ copied: false }">
                    <span>{{ $serverIp }}</span>
                    <button @click="navigator.clipboard.writeText('{{ $serverIp }}'); copied = true; setTimeout(() => copied = false, 2000)" class="relative group rounded-full p-1.5 transition-all duration-75 bg-transparent hover:bg-neutral-100 dark:hover:bg-neutral-800 active:bg-neutral-200 dark:active:bg-neutral-700 -mb-0.5 cursor-pointer focus:outline-none" type="button">
                        <span class="sr-only">Copy</span>
                        <svg x-show="!copied" fill="none" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="14" height="14" class="h-3.5 w-3.5 text-neutral-400 dark:text-neutral-500">
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
                <p class="flex items-end gap-1 font-mono text-neutral-900 dark:text-white" x-data="{ copied: false }">
                    <span>{{ $appHost }}</span>
                    <button @click="navigator.clipboard.writeText('{{ $appHost }}'); copied = true; setTimeout(() => copied = false, 2000)" class="relative group rounded-full p-1.5 transition-all duration-75 bg-transparent hover:bg-neutral-100 dark:hover:bg-neutral-800 active:bg-neutral-200 dark:active:bg-neutral-700 -mb-0.5 cursor-pointer focus:outline-none" type="button">
                        <span class="sr-only">Copy</span>
                        <svg x-show="!copied" fill="none" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="14" height="14" class="h-3.5 w-3.5 text-neutral-400 dark:text-neutral-500">
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

        <!-- Warning Alert Block -->
        <div class="mt-4 flex items-center gap-2 rounded-lg p-3 bg-orange-50 dark:bg-orange-950/20 text-orange-600 dark:text-orange-400 border border-orange-100/30 dark:border-orange-900/30">
            <svg height="18" width="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0">
                <g fill="currentColor">
                    <circle cx="9" cy="9" fill="none" r="7.25" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></circle>
                    <line fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" x1="9" x2="9" y1="12.819" y2="8.25"></line>
                    <path d="M9,6.75c-.552,0-1-.449-1-1s.448-1,1-1,1,.449,1,1-.448,1-1,1Z" fill="currentColor" stroke="none"></path>
                </g>
            </svg>
            <p class="prose-sm prose-code:rounded-md prose-code:bg-neutral-100 dark:prose-code:bg-neutral-800 prose-code:p-1 prose-code:text-[.8125rem] prose-code:font-medium prose-code:font-mono prose-code:text-neutral-900 dark:prose-code:text-neutral-150 max-w-none">
                {{ __('filament-short-url::default.dns_warning_ttl') }}
            </p>
        </div>
    </div>
</div>
