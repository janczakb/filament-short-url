<?php

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Support\Carbon;

class ShortUrlBuilder
{
    private array $data = [];

    public function __construct(
        private ShortUrlService $service,
        string $destinationUrl
    ) {
        $this->data['destination_url'] = $destinationUrl;
    }

    public function urlKey(string $key): static
    {
        $this->data['url_key'] = $key;

        return $this;
    }

    public function notes(string $notes): static
    {
        $this->data['notes'] = $notes;

        return $this;
    }

    public function singleUse(bool $singleUse = true): static
    {
        $this->data['single_use'] = $singleUse;

        return $this;
    }

    public function forwardQueryParams(bool $forward = true): static
    {
        $this->data['forward_query_params'] = $forward;

        return $this;
    }

    public function expiresAt(\DateTimeInterface|Carbon|null $date): static
    {
        $this->data['expires_at'] = $date ? Carbon::instance($date) : null;

        return $this;
    }

    public function activatedAt(\DateTimeInterface|Carbon|null $date): static
    {
        $this->data['activated_at'] = $date ? Carbon::instance($date) : null;

        return $this;
    }

    public function deactivatedAt(\DateTimeInterface|Carbon|null $date): static
    {
        $this->data['deactivated_at'] = $date ? Carbon::instance($date) : null;

        return $this;
    }

    public function maxVisits(?int $maxVisits): static
    {
        $this->data['max_visits'] = $maxVisits;

        return $this;
    }

    public function expirationRedirectUrl(?string $url): static
    {
        $this->data['expiration_redirect_url'] = $url;

        return $this;
    }

    public function trackVisits(bool $track = true): static
    {
        $this->data['track_visits'] = $track;

        return $this;
    }

    /**
     * Append tracing/UTM parameters directly to the destination URL.
     *
     * @param  array<string, string>  $tracing
     */
    public function withTracing(array $tracing): static
    {
        if (empty($tracing)) {
            return $this;
        }

        $destination = $this->data['destination_url'];
        $separator = str_contains($destination, '?') ? '&' : '?';

        // Filter out empty or null values to keep the URL clean
        $filtered = array_filter($tracing, fn ($value) => $value !== null && $value !== '');

        if (! empty($filtered)) {
            $this->data['destination_url'] = $destination.$separator.http_build_query($filtered);
        }

        return $this;
    }

    /**
     * Build and persist the ShortUrl record.
     */
    public function create(): ShortUrl
    {
        return $this->service->create($this->data);
    }
}
