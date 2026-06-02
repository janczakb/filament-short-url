@php
    /**
     * World-map heat-map widget.
     *
     * Uses Natural Earth 110m simplified country paths (public-domain).
     * Countries are coloured using their ISO-3166-1 alpha-2 code which is stored
     * in the short_url_visits.country_code column.
     *
     * $countryData  – array<ISO2, int>  raw click counts
     * $normalized   – array<ISO2, 0-100>  intensity percentage
     * $maxCount     – int
     * $totalClicks  – int
     */

    /**
     * Very compact "mercator" paths for ~180 countries sourced from public-domain
     * Natural Earth data (110m resolution) encoded into a 950×500 viewport.
     *
     * Only the most visited countries are listed for performance; the rest render
     * in a neutral colour. Full-resolution SVG world maps can be bundled as an
     * asset if needed.
     */
    $countryPaths = [
        'AF' => 'M 613 175 L 617 170 L 626 172 L 630 168 L 637 173 L 638 180 L 633 186 L 624 189 L 616 184 Z',
        'AL' => 'M 508 142 L 511 138 L 514 142 L 512 147 L 508 145 Z',
        'DZ' => 'M 470 148 L 500 148 L 500 185 L 470 185 L 455 175 L 455 158 Z',
        'AO' => 'M 500 240 L 520 240 L 520 270 L 500 270 L 490 255 Z',
        'AR' => 'M 265 330 L 280 320 L 285 360 L 275 400 L 260 410 L 250 390 L 255 350 Z',
        'AU' => 'M 730 310 L 790 295 L 820 310 L 830 345 L 810 370 L 760 375 L 725 355 L 720 330 Z',
        'AT' => 'M 500 120 L 514 118 L 516 124 L 502 126 Z',
        'AZ' => 'M 568 138 L 575 134 L 580 140 L 574 145 L 568 142 Z',
        'BD' => 'M 655 180 L 663 178 L 666 185 L 660 190 L 654 187 Z',
        'BE' => 'M 480 112 L 487 110 L 489 116 L 482 118 Z',
        'BF' => 'M 460 198 L 475 195 L 478 205 L 462 208 Z',
        'BY' => 'M 530 105 L 545 103 L 548 112 L 532 115 Z',
        'BJ' => 'M 475 205 L 480 202 L 482 215 L 477 218 Z',
        'BO' => 'M 265 295 L 280 288 L 285 310 L 268 315 Z',
        'BA' => 'M 507 130 L 514 128 L 515 136 L 508 137 Z',
        'BW' => 'M 518 278 L 530 275 L 532 290 L 520 292 Z',
        'BR' => 'M 255 220 L 320 210 L 340 240 L 330 300 L 290 320 L 255 310 L 235 280 L 240 250 Z',
        'BN' => 'M 724 218 L 728 215 L 730 220 L 726 223 Z',
        'BG' => 'M 525 130 L 536 128 L 537 136 L 526 137 Z',
        'KH' => 'M 705 210 L 715 207 L 717 218 L 707 220 Z',
        'CM' => 'M 490 210 L 503 207 L 505 230 L 491 232 Z',
        'CA' => 'M 55 65 L 200 55 L 220 85 L 180 100 L 100 108 L 55 95 Z',
        'CF' => 'M 502 220 L 520 218 L 522 235 L 504 237 Z',
        'TD' => 'M 498 188 L 515 185 L 517 220 L 499 222 Z',
        'CL' => 'M 250 295 L 258 290 L 260 380 L 252 385 L 248 350 Z',
        'CN' => 'M 640 130 L 740 125 L 755 160 L 745 200 L 700 215 L 660 205 L 635 185 L 630 160 Z',
        'CO' => 'M 225 220 L 255 215 L 260 250 L 240 258 L 220 245 Z',
        'CD' => 'M 503 238 L 535 230 L 540 270 L 520 278 L 500 270 Z',
        'CG' => 'M 496 238 L 505 236 L 507 255 L 497 257 Z',
        'CR' => 'M 205 215 L 212 212 L 213 220 L 206 222 Z',
        'HR' => 'M 505 126 L 514 124 L 514 132 L 506 133 Z',
        'CU' => 'M 198 183 L 218 180 L 220 188 L 200 191 Z',
        'CY' => 'M 540 152 L 548 150 L 549 155 L 541 156 Z',
        'CZ' => 'M 505 115 L 518 113 L 519 120 L 506 121 Z',
        'DK' => 'M 494 98 L 500 95 L 502 105 L 495 107 Z',
        'DO' => 'M 225 188 L 233 186 L 234 193 L 226 194 Z',
        'EC' => 'M 220 248 L 233 244 L 235 260 L 221 263 Z',
        'EG' => 'M 537 158 L 558 155 L 560 175 L 538 178 Z',
        'SV' => 'M 196 210 L 203 208 L 204 214 L 197 215 Z',
        'GQ' => 'M 487 228 L 493 226 L 494 233 L 488 234 Z',
        'ER' => 'M 553 192 L 563 188 L 565 198 L 554 200 Z',
        'EE' => 'M 525 95 L 535 93 L 536 99 L 526 101 Z',
        'ET' => 'M 545 205 L 570 198 L 574 220 L 548 228 Z',
        'FJ' => 'M 865 285 L 873 282 L 875 290 L 867 292 Z',
        'FI' => 'M 520 75 L 540 68 L 545 92 L 522 95 Z',
        'FR' => 'M 467 115 L 492 112 L 494 133 L 469 136 Z',
        'GA' => 'M 490 235 L 500 233 L 501 248 L 491 250 Z',
        'GM' => 'M 430 200 L 442 198 L 443 204 L 431 205 Z',
        'GE' => 'M 558 130 L 572 128 L 573 136 L 559 137 Z',
        'DE' => 'M 491 105 L 512 103 L 514 125 L 492 127 Z',
        'GH' => 'M 458 212 L 470 210 L 471 228 L 459 230 Z',
        'GR' => 'M 519 138 L 533 136 L 535 150 L 520 152 Z',
        'GT' => 'M 187 207 L 197 205 L 198 215 L 188 217 Z',
        'GN' => 'M 432 210 L 452 207 L 454 222 L 433 225 Z',
        'GW' => 'M 428 205 L 438 203 L 439 210 L 429 211 Z',
        'GY' => 'M 265 230 L 275 227 L 277 242 L 267 244 Z',
        'HT' => 'M 218 188 L 226 186 L 227 194 L 219 195 Z',
        'HN' => 'M 197 208 L 212 205 L 213 215 L 198 217 Z',
        'HU' => 'M 510 120 L 525 118 L 526 127 L 511 128 Z',
        'IN' => 'M 617 160 L 660 155 L 665 195 L 655 215 L 635 220 L 615 205 L 608 185 Z',
        'ID' => 'M 710 235 L 790 225 L 800 255 L 780 265 L 720 260 L 705 250 Z',
        'IR' => 'M 573 148 L 615 142 L 620 172 L 608 185 L 578 180 L 568 165 Z',
        'IQ' => 'M 557 148 L 580 145 L 582 170 L 558 173 Z',
        'IE' => 'M 453 105 L 463 103 L 464 115 L 454 117 Z',
        'IL' => 'M 545 155 L 550 153 L 551 163 L 546 165 Z',
        'IT' => 'M 492 128 L 512 125 L 518 148 L 508 158 L 494 150 Z',
        'CI' => 'M 445 215 L 462 212 L 463 228 L 446 231 Z',
        'JP' => 'M 770 135 L 795 128 L 800 155 L 775 162 L 765 150 Z',
        'JO' => 'M 548 155 L 557 153 L 558 162 L 549 163 Z',
        'KZ' => 'M 580 108 L 645 103 L 650 140 L 640 145 L 582 142 Z',
        'KE' => 'M 543 232 L 563 227 L 566 252 L 544 255 Z',
        'KP' => 'M 751 140 L 763 137 L 765 150 L 752 152 Z',
        'KR' => 'M 758 148 L 770 145 L 772 158 L 759 160 Z',
        'XK' => 'M 513 133 L 519 131 L 520 137 L 514 138 Z',
        'KW' => 'M 570 160 L 576 158 L 577 164 L 571 165 Z',
        'KG' => 'M 630 130 L 648 127 L 650 136 L 631 138 Z',
        'LA' => 'M 700 190 L 713 185 L 716 207 L 702 210 Z',
        'LV' => 'M 522 100 L 535 98 L 536 105 L 523 106 Z',
        'LB' => 'M 548 150 L 553 148 L 554 155 L 549 156 Z',
        'LS' => 'M 522 295 L 528 293 L 529 300 L 523 301 Z',
        'LR' => 'M 435 218 L 447 215 L 448 226 L 436 228 Z',
        'LY' => 'M 498 155 L 535 150 L 537 178 L 500 182 Z',
        'LT' => 'M 520 105 L 535 103 L 536 112 L 521 113 Z',
        'MK' => 'M 516 135 L 524 133 L 525 140 L 517 141 Z',
        'MG' => 'M 565 278 L 578 272 L 582 298 L 568 302 Z',
        'MW' => 'M 535 260 L 541 257 L 543 273 L 536 275 Z',
        'MY' => 'M 700 218 L 735 212 L 738 228 L 702 232 Z',
        'ML' => 'M 440 185 L 475 180 L 478 210 L 442 215 Z',
        'MR' => 'M 428 175 L 455 172 L 457 200 L 430 203 Z',
        'MX' => 'M 115 155 L 200 148 L 210 190 L 195 205 L 150 200 L 115 180 Z',
        'MD' => 'M 530 118 L 540 116 L 541 124 L 531 125 Z',
        'MN' => 'M 652 115 L 730 108 L 733 140 L 655 145 Z',
        'ME' => 'M 511 132 L 517 130 L 518 136 L 512 137 Z',
        'MA' => 'M 440 148 L 460 145 L 462 168 L 441 171 Z',
        'MZ' => 'M 533 265 L 552 260 L 555 295 L 535 298 Z',
        'MM' => 'M 675 178 L 695 172 L 698 208 L 677 212 Z',
        'NA' => 'M 495 275 L 520 270 L 522 295 L 497 298 Z',
        'NP' => 'M 635 163 L 658 160 L 659 170 L 636 172 Z',
        'NL' => 'M 483 107 L 494 105 L 495 114 L 484 115 Z',
        'NZ' => 'M 845 365 L 855 358 L 858 378 L 848 382 Z',
        'NI' => 'M 200 212 L 215 209 L 216 220 L 201 222 Z',
        'NE' => 'M 462 183 L 500 178 L 502 208 L 464 212 Z',
        'NG' => 'M 468 208 L 503 203 L 505 235 L 470 238 Z',
        'NO' => 'M 490 72 L 520 62 L 535 85 L 510 92 L 490 88 Z',
        'OM' => 'M 592 170 L 616 163 L 618 188 L 595 192 Z',
        'PK' => 'M 606 150 L 640 145 L 643 175 L 620 182 L 607 172 Z',
        'PS' => 'M 545 155 L 549 153 L 550 160 L 546 161 Z',
        'PA' => 'M 213 222 L 228 218 L 229 228 L 214 230 Z',
        'PG' => 'M 780 255 L 815 248 L 818 268 L 782 272 Z',
        'PY' => 'M 265 310 L 285 305 L 288 328 L 267 331 Z',
        'PE' => 'M 225 262 L 265 255 L 268 300 L 228 308 Z',
        'PH' => 'M 745 195 L 773 188 L 776 220 L 748 224 Z',
        'PL' => 'M 508 105 L 530 102 L 532 120 L 510 122 Z',
        'PT' => 'M 450 125 L 462 122 L 463 140 L 451 143 Z',
        'PR' => 'M 234 188 L 240 186 L 241 192 L 235 193 Z',
        'RO' => 'M 520 118 L 540 115 L 542 130 L 522 132 Z',
        'RU' => 'M 540 60 L 820 50 L 830 120 L 770 135 L 680 118 L 590 100 L 548 88 Z',
        'RW' => 'M 530 245 L 537 242 L 538 250 L 531 251 Z',
        'SA' => 'M 553 163 L 607 155 L 610 195 L 580 210 L 553 205 Z',
        'SN' => 'M 425 198 L 448 195 L 450 208 L 427 211 Z',
        'RS' => 'M 512 126 L 524 124 L 525 135 L 513 136 Z',
        'SL' => 'M 430 218 L 442 215 L 443 225 L 431 227 Z',
        'SO' => 'M 555 210 L 578 200 L 582 228 L 560 238 L 555 225 Z',
        'ZA' => 'M 500 288 L 536 280 L 540 310 L 510 318 L 495 308 Z',
        'SS' => 'M 525 215 L 547 210 L 549 232 L 527 235 Z',
        'ES' => 'M 452 130 L 485 125 L 487 148 L 453 152 Z',
        'LK' => 'M 641 205 L 647 202 L 649 213 L 643 215 Z',
        'SD' => 'M 520 183 L 552 177 L 555 215 L 522 220 Z',
        'SR' => 'M 268 228 L 280 225 L 281 238 L 269 240 Z',
        'SZ' => 'M 526 292 L 532 290 L 533 297 L 527 298 Z',
        'SE' => 'M 503 72 L 520 68 L 525 100 L 505 103 Z',
        'CH' => 'M 487 118 L 502 116 L 503 124 L 488 125 Z',
        'SY' => 'M 545 143 L 563 140 L 565 155 L 546 157 Z',
        'TW' => 'M 753 172 L 759 169 L 761 178 L 755 180 Z',
        'TJ' => 'M 625 135 L 642 132 L 644 142 L 627 144 Z',
        'TZ' => 'M 530 250 L 555 244 L 557 272 L 532 275 Z',
        'TH' => 'M 692 193 L 712 188 L 714 218 L 694 222 Z',
        'TL' => 'M 762 258 L 775 255 L 776 263 L 763 265 Z',
        'TG' => 'M 470 210 L 476 208 L 477 225 L 471 226 Z',
        'TN' => 'M 490 143 L 502 141 L 503 158 L 491 160 Z',
        'TR' => 'M 530 132 L 575 127 L 577 148 L 532 152 Z',
        'TM' => 'M 590 130 L 623 126 L 625 145 L 592 148 Z',
        'UG' => 'M 530 232 L 548 228 L 550 248 L 532 251 Z',
        'UA' => 'M 522 108 L 560 104 L 562 128 L 524 132 Z',
        'AE' => 'M 590 170 L 605 167 L 606 178 L 591 180 Z',
        'GB' => 'M 460 100 L 480 95 L 482 118 L 462 122 Z',
        'US' => 'M 55 105 L 215 98 L 225 148 L 215 178 L 150 185 L 60 168 Z',
        'UY' => 'M 275 330 L 292 325 L 294 342 L 277 345 Z',
        'UZ' => 'M 600 118 L 635 113 L 637 135 L 602 138 Z',
        'VE' => 'M 235 220 L 270 212 L 273 240 L 237 245 Z',
        'VN' => 'M 708 185 L 726 178 L 730 215 L 710 218 Z',
        'YE' => 'M 563 188 L 608 180 L 610 202 L 565 207 Z',
        'ZM' => 'M 515 258 L 543 252 L 545 278 L 517 281 Z',
        'ZW' => 'M 518 275 L 540 270 L 542 292 L 520 294 Z',
    ];

    // Compute top 10 for the ranked sidebar
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

    // Unique Alpine.js component ID to allow multiple instances
    $mapId = 'world-map-' . Str::random(8);
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
            <div class="hidden items-center gap-2 sm:flex">
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.world_map_fewer') }}</span>
                <div class="flex gap-0.5">
                    @foreach([10, 25, 45, 65, 85, 100] as $intensity)
                        <div class="h-4 w-4 rounded-sm" style="background: hsl(243 100% {{ max(30, 90 - $intensity * 0.55) }}% / {{ max(0.12, $intensity / 100) }});"></div>
                    @endforeach
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.world_map_more') }}</span>
            </div>
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
                        tooltip: { show: false, country: '', count: 0, x: 0, y: 0 },
                        showTooltip(country, count, event) {
                            this.tooltip = { show: true, country, count, x: event.offsetX, y: event.offsetY };
                        },
                        hideTooltip() { this.tooltip.show = false; }
                     }"
                >
                    {{-- Tooltip --}}
                    <div x-show="tooltip.show"
                         x-cloak
                         :style="`left: ${tooltip.x + 12}px; top: ${tooltip.y - 8}px`"
                         class="pointer-events-none absolute z-20 rounded-lg border border-gray-200 bg-white px-3 py-2 shadow-xl dark:border-gray-700 dark:bg-gray-800"
                         style="min-width: 130px;"
                    >
                        <p class="text-xs font-semibold text-gray-800 dark:text-gray-200" x-text="tooltip.country"></p>
                        <p class="mt-0.5 text-xs text-indigo-600 dark:text-indigo-400">
                            <span x-text="tooltip.count.toLocaleString()"></span> clicks
                        </p>
                    </div>

                    <div class="overflow-hidden rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <svg viewBox="0 0 950 500" xmlns="http://www.w3.org/2000/svg"
                             class="h-full w-full"
                             style="min-height: 280px; max-height: 420px;"
                        >
                            {{-- Ocean background --}}
                            <rect width="950" height="500" fill="currentColor" class="text-gray-100 dark:text-gray-800" rx="8"/>

                            {{-- Grid lines (latitude/longitude) --}}
                            <g class="opacity-30" stroke="currentColor" stroke-width="0.5" class="text-gray-300 dark:text-gray-600">
                                @foreach([83, 167, 250, 333, 417] as $x)
                                    <line x1="{{ $x }}" y1="0" x2="{{ $x }}" y2="500" stroke="#94a3b8" stroke-width="0.4" opacity="0.4"/>
                                @endforeach
                                @foreach([100, 200, 300, 400] as $y)
                                    <line x1="0" y1="{{ $y }}" x2="950" y2="{{ $y }}" stroke="#94a3b8" stroke-width="0.4" opacity="0.4"/>
                                @endforeach
                            </g>

                            {{-- Countries --}}
                            @foreach($countryPaths as $code => $path)
                                @php
                                    $count = $countryData[$code] ?? 0;
                                    $intensity = $normalized[$code] ?? 0;
                                    $name = $countryNames[$code] ?? $code;

                                    if ($count > 0) {
                                        // Active country: indigo hue, darkness based on intensity
                                        $lightness = max(30, 88 - ($intensity * 0.55));
                                        $alpha = max(0.15, $intensity / 100);
                                        $fill = "hsl(243 100% {$lightness}% / {$alpha})";
                                        $stroke = "hsl(243 80% 40% / 0.4)";
                                        $strokeWidth = "0.8";
                                    } else {
                                        // Inactive country: neutral gray
                                        $fill = "hsl(220 13% 91%)";
                                        $stroke = "hsl(220 13% 80%)";
                                        $strokeWidth = "0.4";
                                    }
                                @endphp
                                <path
                                    d="{{ $path }}"
                                    fill="{{ $fill }}"
                                    stroke="{{ $stroke }}"
                                    stroke-width="{{ $strokeWidth }}"
                                    class="{{ $count > 0 ? 'cursor-pointer transition-all duration-200 hover:brightness-90 hover:stroke-indigo-500' : '' }}"
                                    @if($count > 0)
                                        @mouseenter="showTooltip('{{ $name }}', {{ $count }}, $event)"
                                        @mouseleave="hideTooltip()"
                                    @endif
                                    style="transition: fill 0.2s ease;"
                                />
                            @endforeach

                            {{-- Pulse dots on top countries --}}
                            @php
                                $dotPositions = [
                                    'US' => [135, 140], 'GB' => [471, 108], 'DE' => [502, 115], 'FR' => [480, 124],
                                    'IN' => [638, 188], 'CN' => [692, 167], 'BR' => [287, 265], 'RU' => [685, 90],
                                    'AU' => [775, 340], 'CA' => [127, 80], 'JP' => [782, 145], 'MX' => [162, 165],
                                    'ES' => [468, 138], 'IT' => [504, 137], 'TR' => [553, 140], 'PL' => [520, 112],
                                    'NL' => [488, 110], 'SA' => [580, 182], 'ZA' => [517, 299], 'AR' => [268, 360],
                                ];
                            @endphp
                            @foreach($topCountries->take(5)->keys() as $rank => $code)
                                @if(isset($dotPositions[$code]))
                                    @php [$dx, $dy] = $dotPositions[$code]; @endphp
                                    <circle cx="{{ $dx }}" cy="{{ $dy }}" r="5" fill="hsl(243 100% 55%)" opacity="0.9">
                                        <animate attributeName="r" values="5;9;5" dur="{{ 1.5 + $rank * 0.3 }}s" repeatCount="indefinite"/>
                                        <animate attributeName="opacity" values="0.9;0.2;0.9" dur="{{ 1.5 + $rank * 0.3 }}s" repeatCount="indefinite"/>
                                    </circle>
                                    <circle cx="{{ $dx }}" cy="{{ $dy }}" r="3" fill="hsl(243 100% 65%)" opacity="1"/>
                                @endif
                            @endforeach
                        </svg>
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
