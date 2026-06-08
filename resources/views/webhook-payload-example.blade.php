@php
    use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\WebhookPayloadExample;

    $rawJson = $rawJson ?? WebhookPayloadExample::visitedEventSampleJson();
@endphp

<div class="marketing-webhook-payload-wrap">
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
        class="marketing-webhook-payload-code"
    >
        <button
            type="button"
            x-on:click="copy"
            class="marketing-webhook-payload-copy"
            title="{{ __('filament-short-url::default.webhook_payload_copy') }}"
        >
            <svg x-show="!copied" style="height: 1rem; width: 1rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
            </svg>
            <svg x-show="copied" x-cloak style="height: 1rem; width: 1rem; color: #34d399;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </button>

        <pre class="marketing-webhook-payload-pre"><code class="marketing-webhook-payload-code-inner">{{ $rawJson }}</code></pre>
    </div>
</div>
