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
    @if(($record->destination_type ?? 'single') === 'split')
        <span class="truncate max-w-[50ch] text-[#273144] dark:text-gray-300 text-[15px] leading-[16px] font-semibold italic">
            {{ __('filament-short-url::default.destination_type_split') }}
        </span>
    @else
        <span onclick="event.preventDefault(); event.stopPropagation(); window.open('{{ e($record->destination_url) }}', '_blank', 'noopener,noreferrer');" 
              class="truncate max-w-[50ch] hover:underline text-[#273144] dark:text-gray-350 text-[15px] leading-[16px] font-medium cursor-pointer" 
              title="{{ e($record->destination_url) }}">
            {{ $record->destination_url }}
        </span>
    @endif
</div>
