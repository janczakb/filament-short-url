<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Console\Commands;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

class StressRedirectCommand extends Command
{
    /** @var string */
    protected $signature = 'short-url:stress-redirect
                            {key : The short URL key to hit}
                            {--requests=50 : Number of in-process redirect requests}
                            {--warmup=0 : Warmup requests excluded from stats}';

    /** @var string */
    protected $description = 'Baseline redirect hot-path timing (in-process, no external HTTP)';

    public function handle(Kernel $kernel): int
    {
        $key = (string) $this->argument('key');
        $totalRequests = max(1, (int) $this->option('requests'));
        $warmupRequests = max(0, min($totalRequests, (int) $this->option('warmup')));

        if (! ShortUrl::query()->where('url_key', $key)->exists()) {
            $this->error("Short URL key [{$key}] was not found.");

            return self::FAILURE;
        }

        $prefix = config('filament-short-url.route_prefix', 's');
        $path = '/'.$prefix.'/'.$key;
        $durations = [];

        for ($i = 0; $i < $totalRequests; $i++) {
            $startedAt = microtime(true);

            $request = Request::create($path, 'GET');
            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);

            $elapsedMs = (microtime(true) - $startedAt) * 1000;

            if ($i >= $warmupRequests) {
                $durations[] = $elapsedMs;
            }
        }

        sort($durations);
        $count = count($durations);
        $sum = array_sum($durations);
        $avg = $count > 0 ? $sum / $count : 0.0;
        $min = $count > 0 ? $durations[0] : 0.0;
        $max = $count > 0 ? $durations[$count - 1] : 0.0;
        $p95Index = $count > 0 ? (int) floor(($count - 1) * 0.95) : 0;
        $p95 = $count > 0 ? $durations[$p95Index] : 0.0;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Key', $key],
                ['Measured requests', (string) $count],
                ['Warmup skipped', (string) $warmupRequests],
                ['Avg (ms)', number_format($avg, 2)],
                ['Min (ms)', number_format($min, 2)],
                ['P95 (ms)', number_format($p95, 2)],
                ['Max (ms)', number_format($max, 2)],
            ]
        );

        $this->comment('In-process baseline only. For HTTP concurrency use: k6 run scripts/k6/redirect-baseline.js -e BASE_URL=... -e URL_KEY='.$key);

        return self::SUCCESS;
    }
}
