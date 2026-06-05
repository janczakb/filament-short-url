@php
    $rawJson = '{
  "event": "visited",
  "timestamp": "2026-06-04T12:00:00+02:00",
  "short_url": {
    "id": 12,
    "destination_url": "https://example.com/some-page",
    "url_key": "promo26",
    "short_url": "https://yoursite.com/s/promo26",
    "total_visits": 150,
    "unique_visits": 120
  },
  "visit": {
    "id": 345,
    "visited_at": "2026-06-04T12:00:00+02:00",
    "device_type": "mobile",
    "browser": "Chrome",
    "browser_version": "120.0",
    "operating_system": "Android",
    "operating_system_version": "14",
    "country": "Poland",
    "country_code": "PL",
    "city": "Warsaw",
    "referer_url": "https://t.co/",
    "referer_host": "t.co",
    "utm_source": "twitter",
    "utm_medium": "social",
    "utm_campaign": "summer_sale",
    "utm_term": null,
    "utm_content": "banner_ad",
    "is_qr_scan": false,
    "browser_language": "pl"
  }
}';
@endphp

<div class="space-y-2 mt-4">
    <label class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
        {{ __('filament-short-url::default.webhook_show_payload') }}
    </label>

    <div 
        x-data="{ 
            copied: false,
            rawJson: @js($rawJson),
            copy() {
                navigator.clipboard.writeText(this.rawJson);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            }
        }" 
        style="position: relative; overflow: hidden; border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.1); background-color: #18181b; padding: 1.25rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);"
    >
        <!-- Copy Button in Top Right -->
        <button 
            type="button"
            x-on:click="copy"
            x-on:mouseenter="$el.style.backgroundColor='rgba(255, 255, 255, 0.15)'; $el.style.color='#ffffff';"
            x-on:mouseleave="$el.style.backgroundColor='rgba(255, 255, 255, 0.08)'; $el.style.color='#a1a1aa';"
            style="position: absolute; top: 1rem; right: 1rem; display: flex; align-items: center; justify-content: center; height: 2rem; width: 2rem; border-radius: 0.5rem; background-color: rgba(255, 255, 255, 0.08); color: #a1a1aa; border: none; cursor: pointer; transition: all 0.2s;"
            title="Copy payload to clipboard"
        >
            <!-- Copy Icon -->
            <svg x-show="!copied" style="height: 1rem; width: 1rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
            </svg>
            <!-- Check Icon -->
            <svg x-show="copied" x-cloak style="height: 1rem; width: 1rem; color: #34d399;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </button>

        <!-- Syntax Highlighted Payload -->
        <pre style="margin: 0; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 13px; line-height: 1.6; color: #d4d4d8; overflow-x: auto; white-space: pre-wrap; word-break: break-all; padding-right: 2.5rem;"><code style="font-family: inherit; font-size: inherit; color: inherit;">{
  <span style="color: #f43f5e;">"event"</span>: <span style="color: #eab308;">"visited"</span>,
  <span style="color: #f43f5e;">"timestamp"</span>: <span style="color: #eab308;">"2026-06-04T12:00:00+02:00"</span>,
  <span style="color: #f43f5e;">"short_url"</span>: {
    <span style="color: #f43f5e;">"id"</span>: <span style="color: #c084fc;">12</span>,
    <span style="color: #f43f5e;">"destination_url"</span>: <span style="color: #eab308;">"https://example.com/some-page"</span>,
    <span style="color: #f43f5e;">"url_key"</span>: <span style="color: #eab308;">"promo26"</span>,
    <span style="color: #f43f5e;">"short_url"</span>: <span style="color: #eab308;">"https://yoursite.com/s/promo26"</span>,
    <span style="color: #f43f5e;">"total_visits"</span>: <span style="color: #c084fc;">150</span>,
    <span style="color: #f43f5e;">"unique_visits"</span>: <span style="color: #c084fc;">120</span>
  },
  <span style="color: #f43f5e;">"visit"</span>: {
    <span style="color: #f43f5e;">"id"</span>: <span style="color: #c084fc;">345</span>,
    <span style="color: #f43f5e;">"visited_at"</span>: <span style="color: #eab308;">"2026-06-04T12:00:00+02:00"</span>,
    <span style="color: #f43f5e;">"device_type"</span>: <span style="color: #eab308;">"mobile"</span>,
    <span style="color: #f43f5e;">"browser"</span>: <span style="color: #eab308;">"Chrome"</span>,
    <span style="color: #f43f5e;">"browser_version"</span>: <span style="color: #eab308;">"120.0"</span>,
    <span style="color: #f43f5e;">"operating_system"</span>: <span style="color: #eab308;">"Android"</span>,
    <span style="color: #f43f5e;">"operating_system_version"</span>: <span style="color: #eab308;">"14"</span>,
    <span style="color: #f43f5e;">"country"</span>: <span style="color: #eab308;">"Poland"</span>,
    <span style="color: #f43f5e;">"country_code"</span>: <span style="color: #eab308;">"PL"</span>,
    <span style="color: #f43f5e;">"city"</span>: <span style="color: #eab308;">"Warsaw"</span>,
    <span style="color: #f43f5e;">"referer_url"</span>: <span style="color: #eab308;">"https://t.co/"</span>,
    <span style="color: #f43f5e;">"referer_host"</span>: <span style="color: #eab308;">"t.co"</span>,
    <span style="color: #f43f5e;">"utm_source"</span>: <span style="color: #eab308;">"twitter"</span>,
    <span style="color: #f43f5e;">"utm_medium"</span>: <span style="color: #eab308;">"social"</span>,
    <span style="color: #f43f5e;">"utm_campaign"</span>: <span style="color: #eab308;">"summer_sale"</span>,
    <span style="color: #f43f5e;">"utm_term"</span>: <span style="color: #60a5fa;">null</span>,
    <span style="color: #f43f5e;">"utm_content"</span>: <span style="color: #eab308;">"banner_ad"</span>,
    <span style="color: #f43f5e;">"is_qr_scan"</span>: <span style="color: #60a5fa;">false</span>,
    <span style="color: #f43f5e;">"browser_language"</span>: <span style="color: #eab308;">"pl"</span>
  }
}</code></pre>
    </div>
</div>
