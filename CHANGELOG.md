# Changelog

All notable changes to **janczakb/filament-short-url** are documented here.

Recent releases (v5.x) are summarized in [README.md](README.md#changelog). This file contains the full history for older versions and the complete **v5.2.0** release notes (including enterprise hardening follow-up, signed 2026-06-08).

---

## v5.2.3

> **Production Redis mode, stats scaling for high traffic, and Settings diagnostics.**

### Settings → Redis & queue diagnostics

- **Redis connection in Settings** — When **Queue Connection = `redis`**, configure host, port, password, database, and key prefix in **Settings → General**. Values are stored in `short_url_settings` and **override** `database.redis` + `queue.connections.redis` at runtime (independent of `CACHE_STORE`).
- **Test Redis connection** — PING + pipelined `INCR`/`SADD` probe (same Redis primitives as visit counters). Uses unsaved form values via preview overrides.
- **Test queue worker** — Dispatches `VerifyQueueWorkerJob` on the selected connection/queue and waits up to 12 s for a worker to process it; reports the exact `php artisan queue:work {connection} --queue={name}` command on failure.
- **Unified queue callout** — Single info box per async driver with a **dynamic** worker command from current Settings (no duplicate boxes; hidden entirely for `sync`).
- **`withPreviewSettings()`** — Settings test buttons apply form state temporarily without saving.

### Stats scaling (`queue_connection = redis`)

- **`StatsScalingProfile`** — Central profile: auto counter buffering, dedicated Redis counters, Redis today buffer, SQL micro-cache for sync mode.
- **`PluginRedisConnection`** — Resolves Redis from `queue.connections.redis` (phpredis/Predis), not from `CACHE_STORE`.
- **`VisitCounterBuffer`** — Unified buffered totals/uniques: dedicated Redis path when Settings queue is `redis`, Laravel cache path for sync + manual toggle.
- **`TodayStatsBuffer`** — Pipelined Redis counters for today's summary and hourly charts; merged live in `HasStatsCache` without busting historical stats cache on every click.
- **`StatsVisitRecorder`** — Hot-path stats side effects extracted from the visit job; **`TrackShortUrlVisitJob`** no longer clears full stats cache per visit.
- **`SyncBufferedCountersCommand`** — Refactored to flush via `VisitCounterBuffer` (Redis dirty-set + cache path).
- **Live feed** — `LiveFeedBroadcaster` prefers plugin queue Redis over cache-store Redis (unchanged priority, now shares infra with counters/stats when queue is `redis`).

### Config & migrations

- **`config/filament-short-url.php`** — New `redis.*` defaults (`REDIS_HOST`, `REDIS_PORT`, …) used as fallbacks before Settings overrides.
- **Service provider** — All package migrations registered in `hasMigrations()` including `2026_06_10_000001` and `2026_06_11_000001`.

### Tests

- **`tests/Feature/StatsScalingTest.php`** — Redis queue profile, today buffer, counter buffering.
- **`tests/Feature/RedisSettingsTest.php`** — Config overrides, masked password preservation, sync worker test.
- **`tests/Unit/PluginRedisConnectionTesterTest.php`** — Redis health-check service.

---

## v5.2.2

> **Enterprise hardening, airtight visit counters, trait refactor, and i18n audit.**

### Security

- **XSS** — `@json()` in pixel interstitial and Alpine stats widgets (replaces `addslashes()`).
- **Atomic `max_visits`** — `VisitSlotReservation::reserveAtomicSlot()` with fast-path SQL and pessimistic lock near cap.
- **Single-use bots** — BotDetector gate before consuming single-use links.
- **Buffer double-count** — `revertBufferedIncrements()` before DB fallback in `HasVisitCounters`.
- **API tenancy** — `owner_user_id` required on API keys when `scope_links_to_user` is enabled.
- **IDOR** — `ResourceOwnershipValidator` for folder/tag/pixel on API writes.
- **Redirect pipeline** — Limits before app interstitial; caps enforced when `track_visits=false`.

### Performance

- **`max_visits_pessimistic_remaining`** config (default 5).
- **`LiveFeedBroadcaster`** — Redis source priority: plugin **Settings → Queue Connection = redis** first, then `CACHE_STORE=redis`; pub/sub push with PhpRedis, SSE poll fallback otherwise.
- **`StatsCacheHelper`** — `Cache::lock` on stats miss.
- **`preloadBufferedCountersForIds()`** — Batch buffer reads in list tables.

### Quality

- **Public stats** — Uniform 404; public-safe response subset.
- **Octane** — A/B variant on request attributes.
- **Filtered uniques** — Rollup merge in `FilteredStatsCollector`.
- **`findByKey()`** — `domain_scope_id` index path.

### Maintainability

- **`HasStats` facade** — `HasVisitCounters`, `HasStatsCache`, `HasStatsQueries`, `HasSecurityStats`.
- **i18n** — API, redirect, and visitor-facing strings use translation keys (EN + PL).

### Tests

- **`tests/Feature/WorldClassHardeningTest.php`** — Comprehensive audit regression suite.

---

## v5.2.1

> **Audit roadmap follow-up** — Authorization policy, API stats parity, SSE live feed, load baselines, and hardening tests.

### Authorization

- **`ShortUrlPolicy`** — `view`, `update`, `delete`, `restore`, `forceDelete`, and `manageSettings` aligned with `scope_links_to_user` / `user_id`. Auto-registered in `FilamentShortUrlServiceProvider` when the host has no custom policy.
- **Stats page** — `ViewShortUrlStats` calls `authorize('view', $record)`.

### REST API

- **Filtered stats** — `GET /links/{idOrKey}/stats` passes query filters to `FilteredStatsCollector` (same dimensions as the Filament stats dashboard).
- **`ApiStatsFilterParser`** — Parses `CrossDimensionalStatsEngine::FILTER_KEYS` plus aliases `country` → `country_code`, `device` → `device_type`.
- **Response meta** — Returns `meta.date_from`, `meta.date_to`, and `meta.filters`.

### Live feed (SSE)

- **`ShortUrlLiveFeedStreamController`** — Authenticated SSE endpoint at `GET /short-url/live-feed/{shortUrl}/stream`.
- **Widget** — Alpine `EventSource` + `ShortUrlLiveFeedWidget::onStreamUpdate()` replaces Livewire polling.
- **Config** — `live_feed.sse_interval_seconds` (default 3), `live_feed.sse_max_duration_seconds` (default 120).

### Load & stress baselines

- **`php artisan short-url:stress-redirect {key}`** — In-process redirect benchmark (avg / min / p95 / max ms).
- **`scripts/k6/redirect-baseline.js`** — k6 HTTP load script with setup probe, configurable VUs/duration, and p95 thresholds.

### Tests

- **`tests/Feature/RoadmapHardeningTest.php`** — Policy scoping, API stats filters, webhook HMAC E2E via `Http::fake()`, `max_visits` burst guard, SSE stream authorization, stress command smoke test.

---

## v5.2.0

> **Main release theme: SEO & Social + integration hardening** — Open Graph metadata, link cloaking, search indexing control, expanded REST API, production security fixes, and **enterprise hardening** (GA4 MP, Geo-IP, custom domain DNS gate).

### Stats & security hardening (audit follow-up)

- **Filtered stats optimizer** — `FilteredStatsCollector` replaces 15+ raw GROUP BY queries with daily JSON rollups + single summary + cursor pass.
- **Cross-dimensional daily rollups** — `CrossDimensionalStatsEngine` stores single- and two-filter slices in `short_url_daily_stats` (`cross_dimensional_stats`, `cross_filter_pairs`, `filter_qr_counts`); aggregation uses one cursor pass instead of 11 GROUP BY queries; re-aggregation backfills NULL cross columns.
- **Stats cache helper** — `StatsCacheHelper` wraps remember/forget with driver-agnostic fallbacks (file, database, Redis, Memcached, array).
- **Security widget cache** — `getSecurityBreakdownStats()` with daily `all_visits_count` / `bot_visits_count` / `proxy_visits_count` rollups.
- **World map** — Always routes through `getCachedStats()`.
- **VPN fail-closed** — When `block_with_403`, detection timeout/error blocks access.
- **Public stats password** — Accepted only via POST body or `Authorization` header (not query string).
- **Custom domain legacy URLs** — `/s/{key}` → 301 → `/{key}` on verified custom domains.

### SEO, Open Graph & Link Cloaking

- **New SEO & Social tab** — `og_title`, `og_description`, `og_image`, `do_index`, `is_cloaked`, live social preview sidebar.
- **Custom Open Graph meta** — Per-link OG/Twitter tags for Facebook, LinkedIn, X, Slack, WhatsApp, and other preview bots.
- **Search Engine Indexing (`do_index`)** — Per-link toggle; when off, all responses emit `noindex, nofollow`.
- **Link Cloaking (`is_cloaked`)** — Iframe embedding on your short-link domain for humans; standalone OG page for crawlers.
- **OG meta scraper** — SSRF-safe streaming fetch (`GET /short-url/scrape-meta`).
- **OG image pipeline** — WebP conversion, temp storage, promotion to permanent disk on save.
- **Iframeable pre-check** — `POST /short-url/check-iframeable` with redirect-chain validation.

### REST API

- **Bulk create** — `POST /api/short-url/links/bulk` (max 100).
- **Upsert** — `PUT /api/short-url/links/upsert` by `external_id` or `url_key` + `destination_url`.
- **Helpers** — `GET /links/exists`, `/links/random`, `/links/info`.
- **CSV export** — `GET /links/{idOrKey}/visits/export`.
- **Tags & Folders CRUD** — `/api/short-url/tags`, `/api/short-url/folders`.
- **Per-key owner scoping** — Optional `owner_user_id` on API keys.
- **Link-level UTM** — `utm_*` and `ref` stored on the link and merged on redirect.
- **Expanded serializer** — `external_id`, UTM fields, tags, folder, archive, public stats flags.
- **Visits, bulk delete/update** — Paginated visits log, bulk delete by IDs/keys, bulk patch.

### Public Stats

- **Shareable endpoint** — `GET /short-url/public-stats/{url_key}` with optional password (`public_stats_enabled`, `public_stats_password`).

### Security & Reliability

- **Password hashing** — Link passwords hashed at rest; legacy plain-text re-hashed on save.
- **DNS-aware SSRF guard** — Webhooks, OG scraper, iframe checker block hostnames resolving to private IPs.
- **`OutboundUrl` rule** — Shared validation for outbound URLs.
- **Safe Browsing fail-closed** — Unreachable API rejects URLs when checking is enabled.
- **Global webhook toggle** — `global_webhook_enabled` must be on for global webhooks to fire.
- **`log-error` hardened** — Auth + throttle + validation.
- **Bot hardening** — Bot visit exclusion, optional click dedup, Googlebot IP verify, `?bot=1` secret.
- **Single-use fix** — No visit recorded after single-use consumption.
- **Redis counter fix** — Buffered stats sync with Redis cache driver.

### Settings GUI

- **Analytics & Bot Detection** — Click deduplication, Googlebot verify, bot debug secret in **Settings → Advanced**.

### Database (safe upgrade)

- **Migration `2026_06_08_000001`** — Adds nullable `external_id`, `utm_*`, `ref`, `public_stats_*`, and index on `short_url_visits (short_url_id, ip_hash)`. No existing data modified.
- **Migration `2026_06_09_000001`** — Adds `domain_scope_id`, composite unique `(url_key, domain_scope_id)`, FK on `custom_domain_id` (`ON DELETE SET NULL`), admin/aggregation indexes, and JSON stats columns on `short_url_daily_stats`. Existing rows backfilled (`domain_scope_id = custom_domain_id` where set, else `0`).
- **Cross-database** — No `->after()` hints, no DB-specific `ENUM`; runs on SQLite, MySQL, and PostgreSQL.

### Performance & reliability audit

- **`domain_scope_id` + composite unique** — Same slug on default domain and custom domains without collision.
- **Custom domain routing** — Root-level URLs on verified domains; reserved segments blocked (`api`, `admin`, `auth`, …).
- **`getRealTimeTotalVisits()`** — Merges buffered counters for accurate `max_visits` / single-use checks with cached redirect models.
- **HasStats / aggregation** — Incremental daily aggregation, bot/proxy filters, chunked prune, Redis dirty-set fix, scoped buffer reads, unique metric via `COUNT(DISTINCT ip_hash)`.
- **`SyncBufferedCountersCommand`** — Atomic batch flush; temp keys cleaned after commit.
- **`VerifyCustomDomainsCommand`** — Scheduled DNS re-verification for custom domains.
- **API** — Transactions on bulk/upsert, `ShortUrlCacheInvalidator`, scoped `exists`, hashed-only API keys, public stats throttle.
- **Security** — `AuthenticateShortUrlApi` drops plaintext legacy keys; public stats password from query/body/Authorization.
- **Settings** — VPN cache TTL + timeout in GUI; settings cache 3600s; queue default remains **`sync`** (opt-in `database`/`redis`).
- **Support classes** — `HostNormalizer`, `ShortUrlCacheInvalidator`, `LinkUtmMerger`, `ApiLinkScope`, `VisitCsvExporter`.
- **Verification doc** — Full itemized checklist in **[AUDIT_IMPLEMENTATION.md](AUDIT_IMPLEMENTATION.md)**.

### Refactoring

- **`ShortUrlRedirectHandler`**, **`RedirectUrlResolver`**, **`OgMetaPresenter`**, **`StatsSqlHelper`**, **`SafeUrl` rule**.

### Enterprise hardening (follow-up — 2026-06-08)

> **Signed:** Bartek Janczak — post-audit production hardening before v5.2.0 tag.

#### GA4 Measurement Protocol (enterprise)

- **`Ga4MeasurementProtocolService`** — Extracted from `TrackShortUrlVisitJob`; singleton registered in service provider.
- **Full MP payload** — `timestamp_micros`, `session_id`, `engagement_time_msec`, standard events `page_view` + `click`, custom `short_url_visit`.
- **Measurement ID validation** — Rejects invalid `ga_tracking_id` format before HTTP; logs and skips send.
- **Settings Test connection** — Requires real `G-XXXXXXXXXX` (or Firebase App ID); validates via GA4 debug collector with the configured secret (not placeholder `G-XXXXXXXXXX`).
- **Privacy-safe `client_id`** — Deterministic SHA-256 hash of IP + User Agent (unchanged semantics, now in dedicated service).

#### Geo-IP foolproofing

- **Headers driver auto-trust** — Saving `geo_ip_driver = headers` forces `trust_cdn_headers = true` in DB and runtime config.
- **Offline fallback chain** — When CDN headers are missing, Headers driver falls back to MaxMind, then ip-api (instead of returning empty geo).
- **ip-api rate limit** — Laravel `RateLimiter` guard: max 40 lookups/minute (`fsu_geo_ip_api:{YmdHi}`).
- **Settings UI** — GeoIP tab info callout explains auto-trust and fallback behaviour.

#### Custom domains (DNS gate)

- **Multi-record verification** — All A records resolved; multiple CNAME targets; IP match via `array_intersect`; CNAME chain to `app.url` host.
- **Activation enforcement** — `custom_domains.enforce_dns_on_activate` (default `true`, env `SHORT_URL_CUSTOM_DOMAIN_ENFORCE_DNS`); saving with `is_active=true` without DNS → auto-deactivate.
- **Removed `SERVER_ADDR` fallback** — Verification relies on DNS resolution against `app.url` only.

#### API & settings

- **Upsert + `lock_url_key`** — `UpsertShortUrlRequest` blocks changing `url_key` and `custom_domain_id` when lock is enabled (closes panel/API parity gap).
- **`ShortUrlSettingsManager`** — `last_aggregation_date` in system keys; secret preservation on masked save; `trust_cdn_headers` derived when driver is headers.

#### Stats & reliability audit (P0–P2)

- **Redis dirty-set prefix** — `SyncBufferedCountersCommand` uses store prefix for dirty ID set.
- **Webhook SSRF** — `SendWebhookJob`: `allow_redirects => false`, rejects 3xx responses.
- **Buffer fallback** — `HasStats::incrementVisits()` DB fallback when dirty-ID tracking fails.
- **Unique visits** — Human clicks from daily rollups; no DISTINCT override inflation.
- **Cross-filter stats** — Two-filter pairs in aggregation; gap fill in `FilteredStatsCollector`.
- **Per-link webhooks** — Respects `webhook_events` on link model.
- **Octane** — `GeoIpService::flush()`, `ShortUrl::flushBufferedCounterMemory()` on request terminate.
- **Security** — Redirect HTML XSS fix; public stats rate limit; visit logs default to stats-counted scope.
- **Tests** — `EnterpriseHardeningTest.php`, `StatsAuditHardeningTest.php`; run `php artisan test packages/filament-short-url/tests/ --compact` in the host app.

#### Critical audit hardening (follow-up — 2026-06-08)

- **Stateless redirect default** — Main `/s/{key}` route uses `throttle:120,1` only (no `web` middleware). Password flow stays on `/s-auth/{key}` with explicit `web`.
- **Filament `lock_url_key` server-side** — `LockedUrlKeyGuard` sanitizes Filament save data and blocks model persistence when key/domain change is attempted (parity with API).
- **Multi-tenant Filament scope** — `LinkUserScope` filters `ShortUrlResource` by `user_id` when `scope_links_to_user` is enabled (default).
- **Custom domain ownership in API** — `CustomDomainValidator` ensures API keys/users can only assign domains they own.
- **Scoped `exists` API** — `custom_domain_id` + `domain_scope_id` in response; checks per domain scope.
- **Safe Browsing on redirect** — `SafeBrowsingService::isSafeCached()` re-checks destination at redirect time (configurable TTL).
- **Webhook signing required** — Global webhooks require `webhook_signing_secret` when `webhook_signing_required` is true (default); jobs skip unsigned dispatch.
- **Hot path optimizations** — Single domain resolution passed to `findByKey()`; pixels lazy-loaded; `max_visits` enforced under row lock before tracking.
- **Docs** — README claims corrected (no sub-15ms; dub.co parity table scoped; production checklist; sync queue default documented).
- **Tests** — `SecurityHardeningFixesTest.php`, `LockedUrlKeyGuardTest.php`.

---

## v4.0.0

- **Custom Domains Branding** — Let users connect branded custom domains with real-time CNAME/A record DNS verification and automatic prefix-free root routing.
- **Cache Leak Fix** — Solved a critical caching bug where single-use links could be visited multiple times during the cache TTL by passing the host-specific key suffix to cache invalidations.
- **Aggregator Performance Tuning** — Restructured daily aggregation SQL queries to filter only active URL IDs, leveraging composite indexes and running native DB aggregations.
- **Logo Upload Authorization** — Secured the logo upload API with resource-level permission and policy checks.
- **DB-Free Registration Phase** — Deferred settings overrides to the service provider boot phase to prevent database errors during offline configuration caching.
- **Conditional Redirection Fallback** — Added configurations to optionally disable global fallback route registration.

## v3.5.0

- **User Attribution** — Short URLs now record the creator. Their avatar and hover card (name + email) are displayed in the list table.
- **Relative Date Badges** — Creation dates show compact relative strings (`2h`, `5d`, `3mo`). Hover to see the precise date and timezone.
- **Keyboard Shortcuts** — Hover any table row and press `E` (edit), `Q` (QR code), `I` (share), `S` (statistics), or `X` (delete).
- **Unified Action Dropdown** — All per-row actions consolidated under a single 3-dot button with keyboard shortcut badges.
- **Row Click → Statistics** — Clicking a table row navigates directly to the link's Statistics page.
- **A/B Split Testing** — Weighted traffic rotation in root-level links and nested targeting rules, with drag-based sliders and a one-click "Balance weights" action.
- **Variant Analytics** — Tracks which A/B variant was served and shows a "Variant Clicks Distribution" chart in the analytics dashboard.
- **Query Optimization** — Composite DB index on the visits table; queries filter by indexed `country_code`; Eloquent hydration bypassed with `toBase()->get()` for large sets.
- **GDPR IP Anonymization** — Opt-in IP masking (IPv4/IPv6) with SHA-256 hashing for unique visitor identification, managed via Settings.
- **Google Safe Browsing in REST API** — Safety checks now apply to all incoming URLs in the API (destination, A/B variants, targeting rules).
- **Settings URL Cleanup** — Tab query parameters are now human-readable (e.g. `?tab=qr` instead of `?tab=qr-defaults::data::tab`).
- **Analytics Enhancements** — QR Scan Conversion Rate card with period-over-period delta, browser/OS version subtexts, A/B click-share vs. weight comparison.

## v3.3.0

- **Advanced Multi-Filter Targeting Engine** — Replaced the legacy single-strategy selection panel with a highly flexible rule engine supporting custom `AND` / `OR` logic matching.
- **Granular Filter Categories** — Added support for filtering by devices (Desktop, Mobile, Tablet), platform operating systems (Windows, macOS, Linux, iOS, Android, Fire OS), countries (multiselect with search), and browser languages (multiselect with search).
- **Client-Side Flag Icons** — Integrated high-quality country flag icons dynamically loaded from a trusted CDN (`flagcdn.com`) inside both the Filament form targeting dropdown and the analytics country statistics widget.
- **Redirection Performance Boost** — Bypassed full user agent parsing (which involves regular expression scanning for versions) during redirections by introducing specialized fast-path `getDeviceType` and `getOs` helper functions.
- **Dynamic Legacy Data Adapter** — Added automatic on-the-fly hydration and upgrade of legacy database records to the new rule format when loaded in the Filament form.

## v3.2.0

- **Expanded Developer REST API** — Added new endpoints to inspect single short URLs (`GET /api/short-url/links/{idOrKey}`), fetch real-time click metrics (`GET /api/short-url/links/{idOrKey}/stats`), and fully update links programmatically (`PUT/PATCH /api/short-url/links/{idOrKey}`). Enabled flexible lookup dynamically by ID or URL key.
- **REST API Throttling & Rate Limiting** — Added route middleware throttling for the Developer REST API. Current default: `throttle:120,1` (configurable via `filament-short-url.middleware`); per-key limits default to 60 req/min.
- **Unified Validation System** — Cleaned up and unified API and admin panel form validation, supporting advanced fields like granular logging parameters (`track_visits`, `track_browser_language`, etc.), custom GA4 tracking IDs (`ga_tracking_id`), and auto-open deep linking options (`auto_open_app_mobile`).
- **Media Controller Separation** — Refactored internal logo uploads and serving routes out of the public REST API controller into a dedicated `ShortUrlLogoController` (Single Responsibility compliance).
- **Alpine.js Webhook Payload Preview** — Replaced the Filament package CodeEditor component with a custom, high-reliability dark-mode HTML/CSS component featuring live code highlighting and copy-to-clipboard functionality powered by Alpine.js.

## v3.1.0

- **Database-Backed & Cached Settings** — Relocated user configuration from local JSON files to the database (`short_url_settings` table) with automatic caching and zero-downtime migration of legacy settings.
- **High-Performance Aggregations** — Completely refactored the statistics aggregator command to run optimized GROUP BY queries directly in the database, reducing memory usage (OOM protection) to near zero.
- **Secure Hashed API Keys** — API keys are now stored securely as SHA-256 hashes, verified using constant-time comparisons (`hash_equals()`), and displayed only once upon generation.
- **HMAC Signed Webhooks** — Webhook payloads are now optionally signed with a configured secret key and verified via the `X-ShortUrl-Signature` header.
- **Privacy-Safe GA4 Client IDs** — Replaced random UUIDs with deterministic client IDs based on hashed visitor IP and User Agent, ensuring session integrity in Google Analytics without storing raw visitor data.
- **Browser Cache Prevention for Limited Links** — Force temporary `302` redirects automatically for single-use, max-visit, or expiring links to prevent browsers from caching redirects and bypassing tracking logic.
- **Proxy Detection Optimization** — Implemented an aggressive 800ms timeout for external proxy checkers and reduced cache time for transient rate-limit failures to 60 seconds.

## v3.0.0

- **Native App Linking (Mobile Auto-Open)** — Automatically match and redirect mobile visitors directly inside 24+ native mobile apps (such as WhatsApp, YouTube, TikTok, Instagram, Spotify, etc.) using custom schemes, complete with a glassmorphic redirect page and a live interactive matching preview widget.
- **Global Deep Linking (Universal Links & App Links)** — Easily serve iOS `apple-app-site-association` and Android `.well-known/assetlinks.json` configuration files directly from your root domain to support OS-level native integration (disabled by default, managed via Settings).
- **Central Retargeting Pixel Registry** — Introduced a premium Many-to-Many pixel management registry. Define pixels centrally (Meta Pixel, Google Tag, LinkedIn Insight, TikTok Pixel, Pinterest Tag) and easily associate them with short links via the Filament panel or the REST API.
- **Standalone Settings Page** — Relocated the Settings interface from a resource header sub-action to a standalone sidebar navigation page under the default plugin group.
- **Retargeting Pixel API** — The REST API now fully exposes the `pixels` relationship array parameter for registering and linking retargeting pixels programmatically.
- **Enhanced Browser Language Redirection** — Robust double-pass language targeting logic matching exact locales first (e.g. `en-US`, `zh-CN`) and falling back to base language codes (e.g. `en`, `zh`).
- **Custom Branded Expiry Pages** — Replaced raw 410 HTTP errors with a beautiful, fully localized, dark-mode compatible HTML expiry page displaying the Site Name, expired link details, and a homepage button.

## v2.0.0

- **Interactive QR Code Designer Branding Logo** — Upload custom brand logos inside the QR designer canvas in Filament. Configure logo sizing, margins, shapes (square/circle), and toggle dot backing removal to prevent dots overlapping with the logo.
- **Dedicated QR Code Scan Tracking** — Differentiates visitor clicks from physical QR code scans by dynamically appending source tags (`?source=qr`). Added a new database tracking column (`is_qr_scan` on visits, `qr_scans` on short URLs, and `qr_visits_count` on daily stats). Displays a dedicated scans counter badge in the Filament list table.
- **Browser Language Detection & Statistics** — Captures visitor browser preferred language headers (`browser_language` field) and aggregates them into the daily stats table. Displays a new "Top Languages" widget breakdown in the link statistics dashboard.
- **High-Traffic Performance Safeguards & Robust Rollbacks** — Atomic database transactions for buffered counter updates with fail-safe rollback that restores cache values in case of DB connection failures. Prevents N+1 queries by preloading request-wide counters in a single batch cache lookup.
- **Support for Empty Route Prefix (Root-Level URLs)** — Enhanced `getShortUrl()` to support empty route prefixes without generating double slashes (e.g. `domain.com/abc123` instead of `domain.com//abc123`).

## v1.7.0

- **Role-based Settings Access Control** — New `authorizeSettingsUsing(Closure)` method on the plugin to restrict who can access the Settings page. Supports any callable returning a `bool`. Also auto-detects a `manageSettings` method on a registered `ShortUrl` policy. The Settings button in the table header is hidden automatically when access is denied.

## v1.6.0

- **Google Safe Browsing Integration** — Automatic safety checks against Google's API during link creation or modification. Includes bypass settings, asynchronous checking option, and alert badges.
- **VPN / Proxy / Bot Filtering** — Detect and filter out VPN/proxy traffic and Tor nodes using external proxy detection APIs to keep traffic analytics clean.
- **Visitor World Map Widget** — Live interactive SVG world map showing clicks distribution per country, custom intensity highlighting, and hover details.

## v1.5.1

- **REST API On/Off Toggle** — Enable or disable the entire developer REST API from Settings → API & Webhooks without touching code. Returns `503 Service Unavailable` when disabled.

## v1.5.0

- **Social Retargeting Pixels** — Attach Meta Pixel, Google Tag (GA4/Ads), and LinkedIn Insight Tag to any short URL. A premium glassmorphic interstitial page executes pixel scripts in the visitor's browser before forwarding them to the destination. Enables building remarketing audiences even on external domains.
- **Developer REST API** — Full `GET /api/short-url/links`, `POST /api/short-url/links`, and `DELETE /api/short-url/links/{id}` endpoints with API Key authentication (managed via Settings UI). Supports creating links with all available attributes including pixels and webhooks.
- **Webhook System** — Real-time HTTP POST notifications on `visited`, `created`, `expired`, and `limit_reached` events. Configurable per-link or globally. Dispatched asynchronously via `SendWebhookJob` with 3-attempt retry policy and 10-second backoff.
- **Settings: API & Webhooks Tab** — New settings tab to manage global webhook URL, monitored event types, and developer API key management with name labels and per-key activation toggles.

## v1.4.0

- **Validity Date Ranges (From-To)** — Set activation dates (`activated_at` and `expires_at`) to control exactly when a short link is active.
- **Custom Visit Limit Counters** — Define a custom maximum visit limit (`max_visits`) after which a link automatically expires (e.g., active for 3 hits).
- **Custom Expiration Fallbacks** — Redirect expired/inactive visitors to a custom `expiration_redirect_url` rather than showing a static 410 Gone error page.
- **Fluent Builder APIs** — Fluent method additions (`activatedAt()`, `deactivatedAt()`, `maxVisits()`, `expirationRedirectUrl()`) in the developer query builder.

## v1.3.0

- **Automatic Scheduler Registration** — Zero-configuration task registration within the ServiceProvider booted phase (dynamically honors Settings toggles).
- **Interactive Settings Validators** — Adds real-time "Test connection" verify action for GA4 Measurement Protocol and "Verify file" action for MaxMind database paths.
- **Robust Table Row Copy Action** — High-reliability, conflict-free click-to-copy in table rows with built-in fallback helper for non-HTTPS (secure context) browser environments.

## v1.2.0

- **Password-protected links** — Session-based unlock flow with a styled prompt page.
- **Redirect warning pages** — Interstitial security screen before external redirects.
- **Smart targeting** — Device-based, Country/Geo-based, and A/B weighted rotation rules per link.
- **Rate limiting** — Configurable per-IP redirect throttling with 429 responses.
- **Daily stats aggregation** — Nightly `short-url:aggregate-and-prune` command for scalable log management.
- **Extended Settings GUI** — New "Performance & Security" tab for aggregation and rate limiting configuration.
- **Polish translations** — Full `pl` locale support for all new features.
