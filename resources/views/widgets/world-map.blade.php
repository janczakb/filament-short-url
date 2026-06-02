@php
    // Computes top 10 for the ranked sidebar
    $topCountries = collect($countryData)->sortDesc()->take(10);

    // Country name lookup (ISO-2 → English name)
    $countryNames = [
        'AF'=>'Afghanistan','AL'=>'Albania','DZ'=>'Algeria','AO'=>'Angola','AR'=>'Argentina','AU'=>'Australia',
        'AT'=>'Austria','AZ'=>'Azerbaijan','BD'=>'Bangladesh','BE'=>'Belgium','BF'=>'Burkina Faso','BY'=>'Belarus',
        'BJ'=>'Benin','BO'=>'Bolivia','BA'=>'Bosnia & Herz.','BW'=>'Botswana','BR'=>'Brazil','BN'=>'Brunei',
        'BG'=>'Bulgaria','KH'=>'Cambodia','CM'=>'Cameroon','CA'=>'Canada','CF'=>'C. African Rep.','TD'=>'Chad',
        'CL'=>'Chile','CN'=>'China','CO'=>'Colombia','CD'=>'DR Congo','CG'=>'Congo','CR'=>'Costa Rica',
        'HR'=>'Croatia','CU'=>'Cuba','CY'=>'Cyprus','CZ'=>'Czech Rep.','DK'=>'Denmark','DO'=>'Dominican Rep.',
        'EC'=>'Ecuador','EG'=>'Egypt','SV'=>'El Salvador','GQ'=>'Eq. Guinea','ER'=>'Eritrea','EE'=>'Estonia',
        'ET'=>'Ethiopia','FI'=>'Finland','FR'=>'France','GA'=>'Gabon','GM'=>'Gambia','GE'=>'Georgia',
        'DE'=>'Germany','GH'=>'Ghana','GR'=>'Greece','GT'=>'Guatemala','GN'=>'Guinea','GW'=>'Guinea-Bissau',
        'GY'=>'Guyana','HT'=>'Haiti','HN'=>'Honduras','HU'=>'Hungary','IN'=>'India','ID'=>'Indonesia',
        'IR'=>'Iran','IQ'=>'Iraq','IE'=>'Ireland','IL'=>'Israel','IT'=>'Italy','CI'=>'Ivory Coast',
        'JP'=>'Japan','JO'=>'Jordan','KZ'=>'Kazakhstan','KE'=>'Kenya','KP'=>'North Korea','KR'=>'South Korea',
        'KW'=>'Kuwait','KG'=>'Kyrgyzstan','LA'=>'Laos','LV'=>'Latvia','LB'=>'Lebanon','LS'=>'Lesotho',
        'LR'=>'Liberia','LY'=>'Libya','LT'=>'Lithuania','MK'=>'N. Macedonia','MG'=>'Madagascar','MW'=>'Malawi',
        'MY'=>'Malaysia','ML'=>'Mali','MR'=>'Mauritania','MX'=>'Mexico','MD'=>'Moldova','MN'=>'Mongolia',
        'ME'=>'Montenegro','MA'=>'Morocco','MZ'=>'Mozambique','MM'=>'Myanmar','NA'=>'Namibia','NP'=>'Nepal',
        'NL'=>'Netherlands','NZ'=>'New Zealand','NI'=>'Nicaragua','NE'=>'Niger','NG'=>'Nigeria','NO'=>'Norway',
        'OM'=>'Oman','PK'=>'Pakistan','PS'=>'Palestine','PA'=>'Panama','PG'=>'Papua N. Guinea','PY'=>'Paraguay',
        'PE'=>'Peru','PH'=>'Philippines','PL'=>'Poland','PT'=>'Portugal','RO'=>'Romania','RU'=>'Russia',
        'RW'=>'Rwanda','SA'=>'Saudi Arabia','SN'=>'Senegal','RS'=>'Serbia','SL'=>'Sierra Leone','SO'=>'Somalia',
        'ZA'=>'South Africa','SS'=>'South Sudan','ES'=>'Spain','LK'=>'Sri Lanka','SD'=>'Sudan','SR'=>'Suriname',
        'SZ'=>'Eswatini','SE'=>'Sweden','CH'=>'Switzerland','SY'=>'Syria','TW'=>'Taiwan','TJ'=>'Tajikistan',
        'TZ'=>'Tanzania','TH'=>'Thailand','TG'=>'Togo','TN'=>'Tunisia','TR'=>'Turkey','TM'=>'Turkmenistan',
        'UG'=>'Uganda','UA'=>'Ukraine','AE'=>'UAE','GB'=>'United Kingdom','US'=>'United States','UY'=>'Uruguay',
        'UZ'=>'Uzbekistan','VE'=>'Venezuela','VN'=>'Vietnam','YE'=>'Yemen','ZM'=>'Zambia','ZW'=>'Zimbabwe',
    ];
@endphp

<x-filament-widgets::widget>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900/50">

        {{-- Header --}}
        <div class="mb-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500 dark:bg-indigo-950/50 dark:text-indigo-400">
                    <x-filament::icon icon="heroicon-o-globe-alt" class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                        {{ __('filament-short-url::default.world_map_title') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ number_format($totalClicks) }} {{ __('filament-short-url::default.world_map_total_clicks') }}
                        · {{ count($countryData) }} {{ __('filament-short-url::default.world_map_countries') }}
                    </p>
                </div>
            </div>

            {{-- Legend --}}
            @if(!empty($countryData))
                <div class="hidden items-center gap-2 sm:flex">
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.world_map_fewer') }}</span>
                    <div class="flex gap-0.5">
                        @foreach([10, 25, 45, 65, 85, 100] as $intensity)
                            <div class="h-4 w-4 rounded-sm" style="background: hsl(243 100% {{ max(30, 90 - $intensity * 0.55) }}% / {{ max(0.12, $intensity / 100) }});"></div>
                        @endforeach
                    </div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.world_map_more') }}</span>
                </div>
            @endif
        </div>

        @if(empty($countryData))
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                    <x-filament::icon icon="heroicon-o-globe-alt" class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('filament-short-url::default.world_map_no_data') }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.world_map_no_data_sub') }}</p>
            </div>
        @else
            {{-- Map + Sidebar layout --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">

                {{-- SVG Map --}}
                <div class="relative lg:col-span-3"
                     x-data="{
                        countryData: {{ json_encode($countryData) }},
                        normalized: {{ json_encode($normalized) }},
                        countryNames: {{ json_encode($countryNames) }},
                        tooltip: { show: false, country: '', count: 0, x: 0, y: 0 },
                        
                        init() {
                            this.$nextTick(() => {
                                const svg = this.$el.querySelector('#world-map');
                                if (!svg) return;

                                // 1. Annotate paths/groups inside the SVG dynamically
                                const elements = svg.querySelectorAll('[id]');
                                elements.forEach(el => {
                                    const code = el.id.toUpperCase();
                                    if (code.length !== 2) return;

                                    const count = this.countryData[code] || 0;
                                    if (count > 0) {
                                        const intensity = this.normalized[code] || 0;
                                        el.classList.add('world-map-country-active');
                                        el.style.setProperty('--intensity', intensity / 100);
                                    } else {
                                        el.classList.add('world-map-country-inactive');
                                    }
                                });

                                // 2. Render dynamic pulsing dots at the center of top 5 countries
                                const topActiveCodes = Object.keys(this.countryData).slice(0, 5);
                                topActiveCodes.forEach((code, index) => {
                                    const el = svg.querySelector(`#${code.toLowerCase()}`);
                                    if (el) {
                                        try {
                                            const bbox = el.getBBox();
                                            if (bbox && bbox.width > 0 && bbox.height > 0) {
                                                const cx = bbox.x + bbox.width / 2;
                                                const cy = bbox.y + bbox.height / 2;

                                                // Create pulsing halo circle
                                                const pulse = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                                                pulse.setAttribute('cx', cx);
                                                pulse.setAttribute('cy', cy);
                                                pulse.setAttribute('r', '3');
                                                pulse.setAttribute('class', 'world-map-pulse-dot');
                                                pulse.style.setProperty('--delay', `${index * 0.4}s`);

                                                // Create solid core circle
                                                const core = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                                                core.setAttribute('cx', cx);
                                                core.setAttribute('cy', cy);
                                                core.setAttribute('r', '3');
                                                core.setAttribute('class', 'world-map-core-dot');

                                                svg.appendChild(pulse);
                                                svg.appendChild(core);
                                            }
                                        } catch (e) {
                                            console.warn('Could not get bounding box for country ' + code, e);
                                        }
                                    }
                                });
                            });
                        },

                        handleMouseMove(event) {
                            const activeEl = event.target.closest('.world-map-country-active');
                            if (activeEl) {
                                const code = activeEl.id.toUpperCase();
                                const count = this.countryData[code] || 0;
                                const name = this.countryNames[code] || code;

                                this.tooltip.show = true;
                                this.tooltip.country = name;
                                this.tooltip.count = count;

                                const rect = this.$el.getBoundingClientRect();
                                this.tooltip.x = event.clientX - rect.left;
                                this.tooltip.y = event.clientY - rect.top;
                            } else {
                                this.tooltip.show = false;
                            }
                        },

                        hideTooltip() {
                            this.tooltip.show = false;
                        }
                     }"
                     @mousemove="handleMouseMove($event)"
                     @mouseleave="hideTooltip()"
                >
                    {{-- Tooltip --}}
                    <div x-show="tooltip.show"
                         x-cloak
                         :style="`left: ${tooltip.x + 12}px; top: ${tooltip.y - 8}px`"
                         class="pointer-events-none absolute z-20 rounded-lg border border-gray-200 bg-white px-3 py-2 shadow-xl dark:border-gray-700 dark:bg-gray-800"
                         style="min-width: 130px; transition: left 0.05s ease, top 0.05s ease;"
                    >
                        <p class="text-xs font-semibold text-gray-800 dark:text-gray-200" x-text="tooltip.country"></p>
                        <p class="mt-0.5 text-xs text-indigo-600 dark:text-indigo-400">
                            <span x-text="tooltip.count.toLocaleString()"></span> clicks
                        </p>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-100 dark:border-gray-800/80">
                        {!! $svgContent !!}
                    </div>
                </div>

                {{-- Ranked Country Sidebar --}}
                <div class="flex flex-col gap-1">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('filament-short-url::default.world_map_top_countries') }}
                    </p>
                    @forelse($topCountries as $code => $count)
                        @php
                            $pct = $totalClicks > 0 ? round($count / $totalClicks * 100, 1) : 0;
                            $name = $countryNames[$code] ?? $code;
                            $barWidth = $maxCount > 0 ? round($count / $maxCount * 100) : 0;
                        @endphp
                        <div class="group rounded-lg px-2 py-1.5 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                            <div class="flex items-center gap-2">
                                <span class="w-5 text-center text-xs font-bold text-gray-400 dark:text-gray-500">
                                    {{ $loop->iteration }}
                                </span>
                                <span class="flex-1 truncate text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $name }}
                                </span>
                                <span class="shrink-0 font-mono text-xs font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($count) }}
                                </span>
                                <span class="w-9 shrink-0 text-right text-xs text-gray-400 dark:text-gray-500">
                                    {{ $pct }}%
                                </span>
                            </div>
                            <div class="mt-1 ml-7 h-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full rounded-full bg-indigo-500 transition-all duration-700"
                                     style="width: {{ $barWidth }}%">
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">
                            {{ __('filament-short-url::default.world_map_no_data') }}
                        </p>
                    @endforelse
                </div>

            </div>
        @endif
    </div>
</x-filament-widgets::widget>

<style>
    /* Responsive World Map SVG */
    #world-map {
        width: 100% !important;
        height: auto !important;
        display: block;
        background-color: #f8fafc; /* Slate-50 (light mode ocean) */
        transition: background-color 0.3s ease;
    }

    .dark #world-map {
        background-color: #0b0f19; /* Custom premium dark blue-gray (dark mode ocean) */
    }

    /* Transition for SVG map paths */
    #world-map path, #world-map g {
        transition: fill 0.3s ease, stroke 0.3s ease, stroke-width 0.2s ease;
    }

    /* Inactive countries */
    .world-map-country-inactive {
        fill: #f1f5f9 !important; /* Slate-100 */
        stroke: #cbd5e1 !important; /* Slate-300 */
        stroke-width: 0.4px !important;
    }

    .dark .world-map-country-inactive {
        fill: #1e293b !important; /* Slate-800 */
        stroke: #334155 !important; /* Slate-700 */
    }

    /* Active countries heatmap */
    .world-map-country-active {
        /* Light mode: higher intensity = darker, richer indigo-700; lower intensity = light indigo-100 */
        fill: hsla(243, 85%, calc(90% - (var(--intensity) * 45%)), calc(0.25 + var(--intensity) * 0.75)) !important;
        stroke: rgba(99, 102, 241, 0.4) !important; /* Indigo-500 with opacity */
        stroke-width: 0.8px !important;
    }

    .world-map-country-active:hover {
        stroke: #6366f1 !important; /* Indigo-500 highlight */
        stroke-width: 1.2px !important;
        filter: brightness(0.95);
        cursor: pointer;
    }

    .dark .world-map-country-active {
        /* Dark mode: higher intensity = glowing indigo-400; lower intensity = deep indigo-900 */
        fill: hsla(243, 90%, calc(25% + (var(--intensity) * 40%)), calc(0.3 + var(--intensity) * 0.7)) !important;
        stroke: rgba(129, 140, 248, 0.5) !important; /* Indigo-400 with opacity */
    }

    .dark .world-map-country-active:hover {
        stroke: #818cf8 !important; /* Indigo-400 highlight */
        stroke-width: 1.2px !important;
        filter: brightness(1.1);
    }

    /* Pulse Dots CSS */
    .world-map-pulse-dot {
        fill: #6366f1;
        transform-box: fill-box;
        transform-origin: center;
        animation: world-map-pulse 2.2s infinite ease-in-out;
        animation-delay: var(--delay, 0s);
        pointer-events: none;
    }

    .dark .world-map-pulse-dot {
        fill: #818cf8;
    }

    .world-map-core-dot {
        fill: #4f46e5;
        pointer-events: none;
    }

    .dark .world-map-core-dot {
        fill: #6366f1;
    }

    @keyframes world-map-pulse {
        0% {
            r: 3px;
            opacity: 0.9;
        }
        50% {
            r: 9px;
            opacity: 0.25;
        }
        100% {
            r: 3px;
            opacity: 0.9;
        }
    }
</style>
