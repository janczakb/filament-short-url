/**
 * Baseline HTTP load test for the short URL redirect hot path.
 *
 * Requires k6: https://grafana.com/docs/k6/latest/set-up/install-k6/
 *
 * Example (Herd / local app):
 *   k6 run scripts/k6/redirect-baseline.js \
 *     -e BASE_URL=https://wyachts-super-app.test \
 *     -e URL_KEY=bench-key \
 *     -e VUS=20 \
 *     -e DURATION=1m
 *
 * Env:
 *   BASE_URL      — App origin (required), e.g. https://your-app.test
 *   URL_KEY       — Existing link key (required)
 *   ROUTE_PREFIX  — Default: s (from SHORT_URL_ROUTE_PREFIX)
 *   VUS           — Virtual users, default 10
 *   DURATION      — Scenario duration, default 30s
 *   P95_MS        — p95 threshold in ms, default 500
 *   SLEEP_MS      — Optional pause between iterations per VU
 *
 * Tip: create a dedicated link with track_visits=false for a cleaner redirect benchmark.
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const redirectDuration = new Trend('redirect_duration', true);
const redirectFailures = new Rate('redirect_failures');

const p95ThresholdMs = Number(__ENV.P95_MS || 500);

export const options = {
  scenarios: {
    redirect_baseline: {
      executor: 'constant-vus',
      vus: Number(__ENV.VUS || 10),
      duration: __ENV.DURATION || '30s',
    },
  },
  thresholds: {
    redirect_failures: ['rate<0.05'],
    redirect_duration: [`p(95)<${p95ThresholdMs}`],
    http_req_failed: ['rate<0.05'],
  },
};

export function setup() {
  const baseUrl = (__ENV.BASE_URL || '').replace(/\/$/, '');
  const routePrefix = __ENV.ROUTE_PREFIX || 's';
  const urlKey = __ENV.URL_KEY || '';

  if (!baseUrl) {
    throw new Error('BASE_URL is required (e.g. https://your-app.test)');
  }

  if (!urlKey) {
    throw new Error('URL_KEY is required — use an existing short link key');
  }

  const url = `${baseUrl}/${routePrefix}/${urlKey}`;

  const probe = http.get(url, { redirects: 0 });

  if (![301, 302, 307, 308].includes(probe.status)) {
    throw new Error(
      `Setup probe failed: GET ${url} returned ${probe.status} (expected 301/302). ` +
        'Create the link first or disable password/max_visits for the benchmark key.',
    );
  }

  return { url };
}

export default function (data) {
  const res = http.get(data.url, {
    redirects: 0,
    tags: { name: 'short_url_redirect' },
  });

  const ok = check(res, {
    'redirect status 301/302/307/308': (r) => [301, 302, 307, 308].includes(r.status),
  });

  redirectDuration.add(res.timings.duration);
  redirectFailures.add(!ok);

  if (__ENV.SLEEP_MS) {
    sleep(Number(__ENV.SLEEP_MS) / 1000);
  }
}
