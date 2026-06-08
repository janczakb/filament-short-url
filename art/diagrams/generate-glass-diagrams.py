#!/usr/bin/env python3
"""Generate liquid-glass architecture SVG diagrams for README."""

from pathlib import Path

OUT = Path(__file__).parent

GLASS_HEAD = """<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 960 {height}" role="img" aria-label="{label}">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#1e1b4b"/>
      <stop offset="45%" stop-color="#0f172a"/>
      <stop offset="100%" stop-color="#164e63"/>
    </linearGradient>
    <radialGradient id="orb1" cx="20%" cy="15%" r="55%">
      <stop offset="0%" stop-color="#a855f7" stop-opacity="0.55"/>
      <stop offset="100%" stop-color="#a855f7" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="orb2" cx="85%" cy="25%" r="50%">
      <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.45"/>
      <stop offset="100%" stop-color="#38bdf8" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="orb3" cx="50%" cy="90%" r="45%">
      <stop offset="0%" stop-color="#2dd4bf" stop-opacity="0.35"/>
      <stop offset="100%" stop-color="#2dd4bf" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="shine" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.45"/>
      <stop offset="45%" stop-color="#ffffff" stop-opacity="0.08"/>
      <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
    </linearGradient>
    <linearGradient id="edge" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.05"/>
      <stop offset="50%" stop-color="#ffffff" stop-opacity="0.55"/>
      <stop offset="100%" stop-color="#ffffff" stop-opacity="0.05"/>
    </linearGradient>
    <filter id="blur80" x="-50%" y="-50%" width="200%" height="200%">
      <feGaussianBlur stdDeviation="80"/>
    </filter>
    <filter id="shadow" x="-40%" y="-40%" width="180%" height="180%">
      <feDropShadow dx="0" dy="16" stdDeviation="20" flood-color="#020617" flood-opacity="0.55"/>
    </filter>
    <marker id="arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
      <path d="M0,0 L8,4 L0,8 Z" fill="#e2e8f0" fill-opacity="0.9"/>
    </marker>
  </defs>
  <rect width="960" height="{height}" fill="url(#bg)"/>
  <ellipse cx="180" cy="80" rx="220" ry="160" fill="url(#orb1)" filter="url(#blur80)"/>
  <ellipse cx="820" cy="120" rx="200" ry="150" fill="url(#orb2)" filter="url(#blur80)"/>
  <ellipse cx="480" cy="{orb_y}" rx="260" ry="180" fill="url(#orb3)" filter="url(#blur80)"/>
  <text x="480" y="44" text-anchor="middle" font-family="system-ui,-apple-system,Segoe UI,sans-serif" font-size="28" font-weight="700" fill="#f8fafc" letter-spacing="-0.6">{title}</text>
  <text x="480" y="72" text-anchor="middle" font-family="system-ui,-apple-system,Segoe UI,sans-serif" font-size="14" fill="#cbd5e1" fill-opacity="0.85">{subtitle}</text>
"""

GLASS_CARD = """
  <g filter="url(#shadow)">
    <rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{rx}" fill="#ffffff" fill-opacity="0.11" stroke="#ffffff" stroke-opacity="0.38" stroke-width="1.2"/>
    <rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{rx}" fill="url(#shine)" pointer-events="none"/>
    {accent}{content}
  </g>"""

ACCENT_TOP = '<rect x="{x}" y="{y}" width="{w}" height="3" rx="1.5" fill="{color}" fill-opacity="0.85"/>\n    '

FOOT = """
</svg>
"""


def card(x, y, w, h, content, rx=26, accent=None):
    accent_html = ""
    if accent:
        accent_html = ACCENT_TOP.format(x=x, y=y, w=w, color=accent)
    return GLASS_CARD.format(x=x, y=y, w=w, h=h, rx=rx, accent=accent_html, content=content)


def t(x, y, text, size=12, weight="400", fill="#e2e8f0", anchor="start", mono=False):
    fam = "ui-monospace,Menlo,monospace" if mono else "system-ui,-apple-system,Segoe UI,sans-serif"
    w = f' font-weight="{weight}"' if weight != "400" else ""
    return f'<text x="{x}" y="{y}" text-anchor="{anchor}" font-family="{fam}" font-size="{size}" fill="{fill}"{w}>{text}</text>'


def line(x1, y1, x2, y2):
    return f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="#e2e8f0" stroke-opacity="0.35" stroke-width="2" marker-end="url(#arrow)"/>'


def pill(x, y, w, h, text, stroke="#ffffff"):
    return f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{h//2}" fill="#ffffff" fill-opacity="0.08" stroke="{stroke}" stroke-opacity="0.35"/><text x="{x+w/2}" y="{y+h/2+4}" text-anchor="middle" font-family="system-ui,sans-serif" font-size="10" fill="#f1f5f9">{text}</text>'


def diagram_01():
    h, oy = 600, 520
    body = GLASS_HEAD.format(height=h, label="System overview", title="System overview", subtitle="Filament panel, public routes, core services, persistence", orb_y=oy)
    body += card(48, 100, 272, 220, "\n    ".join([
        t(72, 136, "Filament admin", 16, "700"),
        t(72, 158, "Panel layer", 11, fill="#94a3b8"),
        t(72, 188, "ShortUrlResource", 12),
        t(72, 210, "Settings, domains, QR", 12),
        t(72, 232, "Analytics and live feed", 12),
        pill(72, 258, 170, 24, "scope_links_to_user"),
    ]), accent="#c084fc")
    body += card(344, 100, 272, 220, "\n    ".join([
        t(368, 136, "Public HTTP", 16, "700"),
        t(368, 158, "Edge of Laravel app", 11, fill="#94a3b8"),
        t(368, 188, "GET /s/[key]", 11, mono=True, fill="#bae6fd"),
        t(368, 210, "GET /s-auth/[key]", 11, mono=True, fill="#bae6fd"),
        t(368, 232, "GET /api/short-url/*", 11, mono=True, fill="#bae6fd"),
        t(368, 254, "/[key] on custom domain", 11, mono=True, fill="#bae6fd"),
        pill(368, 278, 92, 22, "STATELESS", stroke="#38bdf8"),
    ]), accent="#38bdf8")
    body += card(640, 100, 272, 220, "\n    ".join([
        t(664, 136, "Package core", 16, "700"),
        t(664, 158, "Runtime services", 11, fill="#94a3b8"),
        t(664, 188, "RedirectHandler", 12),
        t(664, 210, "TrackShortUrlVisitJob", 12),
        t(664, 232, "SendWebhookJob (HMAC)", 12),
        t(664, 254, "HasStats and rollups", 12),
    ]), accent="#2dd4bf")
    body += line(184, 320, 184, 352) + line(480, 320, 480, 352) + line(776, 320, 776, 352)
    body += card(120, 360, 720, 130, "\n    ".join([
        t(480, 396, "Persistence layer", 17, "700", anchor="middle"),
        t(480, 422, "short_urls, visits, daily_stats, cache, optional Redis buffer", 12, anchor="middle", fill="#94a3b8"),
        pill(200, 448, 130, 24, "SQL DB"),
        pill(350, 448, 110, 24, "Redis"),
        pill(480, 448, 130, 24, "Safe Browsing"),
        pill(630, 448, 110, 24, "Link cache"),
    ]), accent="#fb923c", rx=28)
    body += card(48, 512, 864, 56, t(480, 546, "Self-hosted Laravel plugin, not edge SaaS", 13, anchor="middle", fill="#e9d5ff"), rx=20)
    return body + FOOT


def diagram_02():
    h, oy = 680, 600
    body = GLASS_HEAD.format(height=h, label="Redirect lifecycle", title="Redirect lifecycle", subtitle="GET /s/[key] from click to destination", orb_y=oy)
    steps = [
        ("Visitor click", "Start of request", "#818cf8"),
        ("Throttle only", "No web middleware, no session cookie", "#fbbf24"),
        ("findByKey(host)", "Cache hit or single DB resolve", "#a78bfa"),
        ("Safe Browsing cache", "isSafeCached(destination) when enabled", "#f87171"),
        ("max_visits lock", "lockForUpdate plus isActive()", "#34d399"),
    ]
    y = 96
    for i, (title, sub, color) in enumerate(steps):
        body += card(280, y, 400, 54, "\n    ".join([
            t(480, y + 24, title, 13, "600", anchor="middle"),
            t(480, y + 42, sub, 11, anchor="middle", fill="#94a3b8"),
        ]), accent=color, rx=18)
        if i < len(steps) - 1:
            body += line(480, y + 54, 480, y + 72)
        y += 72
    body += line(480, y, 480, y + 20)
    body += '<polygon points="480,{0} 410,{1} 550,{1}" fill="#ffffff" fill-opacity="0.12" stroke="#ffffff" stroke-opacity="0.35"/>'.format(y + 20, y + 58)
    body += t(480, y + 48, "Response path", 12, "600", anchor="middle")
    y2 = y + 72
    branches = [
        (48, "Password", "/s-auth/[key] + web", "#fb923c"),
        (344, "Interstitial", "warning, pixels, cloak, OG", "#facc15"),
        (640, "Simple 302", "TrackJob then redirect", "#4ade80"),
    ]
    for x, title, sub, color in branches:
        body += card(x, y2, 272, 88, "\n    ".join([
            t(x + 136, y2 + 28, title, 13, "700", anchor="middle"),
            t(x + 136, y2 + 48, sub, 10, anchor="middle", fill="#94a3b8"),
        ]), accent=color, rx=20)
    body += card(120, y2 + 108, 720, 48, t(480, y2 + 136, "TrackShortUrlVisitJob: GeoIP, VPN, visit, webhook, GA4 (sync default or async queue)", 11, anchor="middle", fill="#cbd5e1"), rx=16)
    return body + FOOT


def diagram_03():
    h, oy = 460, 400
    body = GLASS_HEAD.format(height=h, label="Stateless vs stateful", title="Stateless vs stateful routes", subtitle="Lean clicks vs session-backed password unlock", orb_y=oy)
    for x, title, badge, lines, accent in [
        (48, "Every redirect click", "STATELESS", ["GET /s/[key]", "GET /[key] on custom domain", "Middleware: throttle:120,1", "No cookies, no CSRF"], "#34d399"),
        (496, "Password unlock only", "SESSION", ["GET /s-auth/[key]", "GET /auth/[key] on custom domain", "Middleware: web + throttle", "Unlock remembered per session"], "#60a5fa"),
    ]:
        content = [t(x + 28, 132, title, 17, "700"), pill(x + 28, 148, 88, 22, badge)]
        cy = 188
        for ln in lines:
            content.append(t(x + 28, cy, ln, 12, mono=("/" in ln)))
            cy += 22
        content.append(pill(x + 80, cy + 8, 200, 24, "Fixed in v5.2" if x == 48 else "By design"))
        body += card(x, 96, 416, 280, "\n    ".join(content), accent=accent, rx=28)
    body += card(48, 396, 864, 44, t(480, 424, "Old audit: web on /s/[key] sent cookies on every click. Republish config if middleware still includes web.", 11, anchor="middle", fill="#fecaca"), rx=16, accent="#f87171")
    return body + FOOT


def diagram_04():
    h, oy = 540, 480
    body = GLASS_HEAD.format(height=h, label="Custom domain routing", title="Custom domains and domain_scope_id", subtitle="Same slug on different domains without collision", orb_y=oy)
    body += card(64, 96, 380, 150, "\n    ".join([
        t(254, 132, "Default app domain", 16, "700", anchor="middle"),
        t(254, 160, "app.test/s/promo", 12, anchor="middle", mono=True, fill="#ddd6fe"),
        pill(154, 178, 200, 26, "domain_scope_id = 0"),
    ]), accent="#c084fc", rx=24)
    body += card(516, 96, 380, 150, "\n    ".join([
        t(706, 132, "Verified custom domain", 16, "700", anchor="middle"),
        t(706, 160, "links.brand.com/promo", 12, anchor="middle", mono=True, fill="#bae6fd"),
        pill(586, 178, 240, 26, "scope = custom_domain_id"),
    ]), accent="#38bdf8", rx=24)
    body += line(254, 246, 254, 276) + line(706, 246, 706, 276)
    body += card(180, 276, 600, 62, "\n    ".join([
        t(480, 304, "Composite unique: (url_key, domain_scope_id)", 13, "700", anchor="middle", fill="#a7f3d0"),
        t(480, 324, "GET /links/exists?url_key=promo&amp;custom_domain_id=3", 11, anchor="middle", mono=True, fill="#94a3b8"),
    ]), accent="#34d399", rx=20)
    body += card(80, 360, 800, 120, "\n    ".join([
        t(480, 392, "Ownership enforcement", 16, "700", anchor="middle", fill="#fed7aa"),
        t(480, 418, "Filament domains scoped by user_id", 12, anchor="middle"),
        t(480, 442, "API: CustomDomainValidator blocks foreign domain IDs", 12, anchor="middle"),
        pill(320, 458, 320, 24, "Fixed: exists scope + ownership (v5.2)"),
    ]), accent="#fb923c", rx=24)
    return body + FOOT


def diagram_05():
    h, oy = 460, 400
    body = GLASS_HEAD.format(height=h, label="Targeting and A/B", title="Smart targeting and A/B split", subtitle="RedirectUrlResolver evaluates rules top to bottom", orb_y=oy)
    body += card(360, 92, 240, 46, t(480, 122, "GET /s/[key]", 13, "600", anchor="middle", mono=True), rx=23, accent="#a78bfa")
    body += line(480, 138, 480, 158)
    body += card(300, 158, 360, 56, "\n    ".join([
        t(480, 184, "RedirectUrlResolver", 13, "600", anchor="middle"),
        t(480, 204, "UA, Geo, language, rules", 11, anchor="middle", fill="#94a3b8"),
    ]), rx=18, accent="#38bdf8")
    body += line(480, 214, 480, 238)
    body += '<polygon points="480,238 400,272 560,272" fill="#ffffff" fill-opacity="0.12" stroke="#ffffff" stroke-opacity="0.35"/>'
    body += t(480, 262, "First matching rule?", 12, "600", anchor="middle")
    body += line(400, 272, 160, 310) + line(560, 272, 800, 310)
    for x, title, lines, color in [
        (48, "Rule matched", ["Use rule URL", "Nested A/B in rule"], "#4ade80"),
        (344, "Root A/B", ["Weighted 2-5 URLs", "Variant on visit row"], "#facc15"),
        (640, "No match", ["destination_url", "UTM merge + query forward"], "#f87171"),
    ]:
        body += card(x, 310, 272, 100, "\n    ".join([t(x + 136, 338, title, 12, "700", anchor="middle")] + [t(x + 136, 358 + i * 18, ln, 10, anchor="middle", fill="#94a3b8") for i, ln in enumerate(lines)]), accent=color, rx=20)
    return body + FOOT


def diagram_06():
    h, oy = 500, 440
    body = GLASS_HEAD.format(height=h, label="Webhooks and HMAC", title="Webhooks and HMAC signing", subtitle="Global events, per-link URL override, SSRF-safe dispatch", orb_y=oy)
    body += card(60, 96, 360, 140, "\n    ".join([
        t(240, 128, "Global webhook", 15, "700", anchor="middle"),
        t(240, 152, "Settings: URL, enabled, events", 11, anchor="middle", fill="#94a3b8"),
        t(240, 174, "visited | created | expired | limit", 10, anchor="middle", mono=True),
        t(240, 196, "Signing secret required (v5.2)", 11, anchor="middle", fill="#ddd6fe"),
        pill(120, 208, 240, 22, "Events are global, not per-link"),
    ]), accent="#c084fc", rx=22)
    body += card(540, 96, 360, 140, "\n    ".join([
        t(720, 128, "Per-link webhook URL", 15, "700", anchor="middle"),
        t(720, 152, "short_urls.webhook_url override", 11, anchor="middle", fill="#94a3b8"),
        t(720, 174, "SSRF re-check in SendWebhookJob", 11, anchor="middle"),
        t(720, 196, "allow_redirects=false", 10, anchor="middle", mono=True),
    ]), accent="#38bdf8", rx=22)
    body += line(480, 236, 480, 262)
    body += card(200, 262, 560, 54, "\n    ".join([
        t(480, 286, "SendWebhookJob", 14, "700", anchor="middle"),
        t(480, 304, "Header: X-Short-Url-Signature sha256=... (HMAC body)", 10, anchor="middle", mono=True, fill="#94a3b8"),
    ]), accent="#34d399", rx=18)
    body += card(80, 336, 800, 120, "\n    ".join([
        t(480, 368, "Audit fixes (v5.2)", 15, "700", anchor="middle", fill="#fed7aa"),
        t(480, 394, "Before: optional secret allowed unsigned webhooks", 12, anchor="middle"),
        t(480, 418, "After: Settings rejects enable without secret", 12, anchor="middle"),
        t(480, 442, "Safe Browsing on save + redirect | lock_url_key server-side", 11, anchor="middle", fill="#94a3b8"),
    ]), accent="#fb923c", rx=24)
    return body + FOOT


def diagram_07():
    h, oy = 540, 480
    body = GLASS_HEAD.format(height=h, label="Stats and queue", title="Stats pipeline and queue modes", subtitle="Visits, aggregation cron, panel vs API stats gap", orb_y=oy)
    body += t(480, 98, "Visit tracking", 15, "700", anchor="middle")
    boxes = [(60, "Redirect"), (280, "TrackJob"), (540, "visits table"), (740, "Buffer")]
    for i, (x, label) in enumerate(boxes):
        body += card(x, 112, 160 if x < 740 else 120, 48, t(x + (80 if x < 740 else 60), 142, label, 11, "600", anchor="middle"), rx=16, accent="#818cf8" if i == 1 else "#38bdf8")
        if i < len(boxes) - 1:
            nx = boxes[i + 1][0]
            body += line(x + (160 if x < 740 else 120), 136, nx, 136)
    body += card(120, 180, 340, 110, "\n    ".join([
        t(290, 210, "sync (default)", 15, "700", anchor="middle", fill="#6ee7b7"),
        t(290, 234, "Job runs inside redirect", 11, anchor="middle", fill="#94a3b8"),
        t(290, 256, "Works without queue worker", 11, anchor="middle", fill="#94a3b8"),
        t(290, 278, "GeoIP, webhook, GA4 add latency", 10, anchor="middle", fill="#64748b"),
    ]), accent="#34d399", rx=22)
    body += card(500, 180, 340, 110, "\n    ".join([
        t(670, 210, "redis / database", 15, "700", anchor="middle", fill="#93c5fd"),
        t(670, 234, "302 sent first", 11, anchor="middle", fill="#94a3b8"),
        t(670, 256, "Requires queue:work", 11, anchor="middle", fill="#94a3b8"),
        t(670, 278, "Settings - General", 10, anchor="middle", fill="#64748b"),
    ]), accent="#60a5fa", rx=22)
    body += t(480, 318, "Aggregation (schedule:run)", 14, "700", anchor="middle")
    body += card(140, 332, 200, 44, t(240, 360, "Raw visits 90d", 11, anchor="middle"), rx=14, accent="#f87171")
    body += line(340, 354, 400, 354)
    body += card(400, 332, 240, 44, t(520, 360, "AggregateAndPrune", 11, anchor="middle"), rx=14, accent="#fbbf24")
    body += line(640, 354, 700, 354)
    body += card(700, 332, 200, 44, t(800, 360, "daily_stats", 11, anchor="middle"), rx=14, accent="#4ade80")
    body += card(80, 400, 800, 90, "\n    ".join([
        t(480, 430, "Filament: FilteredStatsCollector + cross-dimensional rollups", 13, "600", anchor="middle"),
        t(480, 454, "API stats: date_from / date_to only (cross-filters = open gap)", 11, anchor="middle", fill="#94a3b8"),
        t(480, 476, "Long-range reports use rollups after raw prune", 10, anchor="middle", fill="#64748b"),
    ]), accent="#818cf8", rx=24)
    return body + FOOT


def diagram_08():
    h, oy = 560, 500
    body = GLASS_HEAD.format(height=h, label="Audit trust summary", title="v5.2 audit trust summary", subtitle="Promise vs old reality vs current status", orb_y=oy)
    rows = [
        ("Sub-15ms / stateless", "web on /s/[key]", "FIXED: throttle only", "#34d399"),
        ("Multi-tenant panel", "All admins see all links", "FIXED: LinkUserScope", "#34d399"),
        ("API exists", "Ignored domain scope", "FIXED: domain_scope_id", "#34d399"),
        ("Domain ownership", "Foreign domain ID", "FIXED: CustomDomainValidator", "#34d399"),
        ("Safe Browsing", "Save only", "FIXED: redirect cache", "#34d399"),
        ("Webhook HMAC", "Optional secret", "FIXED: required when on", "#34d399"),
        ("lock_url_key", "UI disabled only", "FIXED: LockedUrlKeyGuard", "#34d399"),
        ("Per-link webhook events", "Documented per-link", "OPEN: global list", "#fbbf24"),
        ("Default queue async", "README vs sync", "BY DESIGN: sync default", "#fbbf24"),
        ("Dub parity table", "Overstated", "FIXED: scoped compare", "#34d399"),
    ]
    y = 92
    body += card(48, y, 864, 36, "\n    ".join([
        t(180, y + 24, "Audit promise", 12, "700", anchor="middle"),
        t(440, y + 24, "Old reality", 12, "700", anchor="middle"),
        t(730, y + 24, "v5.2 status", 12, "700", anchor="middle"),
    ]), rx=14, accent="#ffffff")
    y += 48
    for promise, old, status, color in rows:
        body += card(48, y, 864, 34, "\n    ".join([
            t(64, y + 22, promise, 11),
            t(330, y + 22, old, 11, fill="#94a3b8"),
            t(600, y + 22, status, 11, "600", fill=color),
        ]), rx=10, accent=color if "FIXED" in status else "#fbbf24")
        y += 40
    body += card(48, y + 8, 864, 72, "\n    ".join([
        t(480, y + 36, "Product boundary: Laravel Filament plugin, not Dub clone", 13, "600", anchor="middle", fill="#e9d5ff"),
        t(480, y + 58, "Open: OpenAPI, API stats filters, workspaces, conversion, load tests", 11, anchor="middle", fill="#94a3b8"),
    ]), rx=20, accent="#818cf8")
    return body + FOOT


DIAGRAMS = [
    ("01-system-overview.svg", diagram_01),
    ("02-redirect-lifecycle.svg", diagram_02),
    ("03-stateless-vs-stateful.svg", diagram_03),
    ("04-custom-domain-routing.svg", diagram_04),
    ("05-targeting-and-ab.svg", diagram_05),
    ("06-webhooks-and-security.svg", diagram_06),
    ("07-stats-and-queue.svg", diagram_07),
    ("08-audit-trust-summary.svg", diagram_08),
]

if __name__ == "__main__":
    for name, fn in DIAGRAMS:
        path = OUT / name
        path.write_text(fn(), encoding="utf-8")
        print(f"Wrote {name}")
