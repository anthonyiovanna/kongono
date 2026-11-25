<?php

declare(strict_types=1);

use Kongono\Http\App;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Post;
use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Required;
use Kongono\View\ViewEngine;

require dirname(__DIR__) . '/vendor/autoload.php';

final readonly class BenchInput
{
    public function __construct(
        #[Required, Min(1), Max(120)]
        public string $title,
    ) {}
}

final readonly class BenchController
{
    public function __construct(
        private ViewEngine $views,
    ) {}

    #[Get('/ping', name: 'bench.ping')]
    public function ping(): Response
    {
        return Response::json(['ok' => true]);
    }

    #[Post('/items', name: 'bench.items')]
    public function create(BenchInput $input): Response
    {
        return Response::json(['title' => $input->title], 201);
    }

    #[Get('/view', name: 'bench.view')]
    public function page(): Response
    {
        return Response::html($this->views->render('home', [
            'title' => 'Kongono Benchmark',
            'notes' => [],
            'flash' => null,
            'routeCount' => 3,
            'phpVersion' => PHP_VERSION,
        ]));
    }
}

$iterations = 5000;
$starterRoot = dirname(__DIR__) . '/examples/starter-app';
$benchCache = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-bench-views-' . bin2hex(random_bytes(4));

// 1. Measure cold bootstrap
$bootStart = hrtime(true);
$app = App::create($starterRoot, name: 'Kongono Benchmark')
    ->debug(false)
    ->views($starterRoot . '/views', $benchCache, autoReload: false)
    ->controller(BenchController::class);
$app->boot();
$bootMs = (hrtime(true) - $bootStart) / 1_000_000;

$runBench = static function (string $label, callable $requestFactory) use ($app, $iterations): array {
    // Warmup 100 requests to populate opcache/reflection & stabilize memory
    for ($i = 0; $i < 100; ++$i) {
        $app->handle($requestFactory());
    }
    gc_collect_cycles();
    $memBefore = memory_get_usage(false);

    $latencies = [];
    $startAll = hrtime(true);
    for ($i = 0; $i < $iterations; ++$i) {
        $t0 = hrtime(true);
        $res = $app->handle($requestFactory());
        $latencies[] = (hrtime(true) - $t0) / 1_000_000;
        if ($res->getStatusCode() >= 400) {
            throw new RuntimeException(sprintf('Benchmark "%s" failed with HTTP %d.', $label, $res->getStatusCode()));
        }
    }
    $totalSec = (hrtime(true) - $startAll) / 1_000_000_000;

    sort($latencies, SORT_NUMERIC);
    $p50 = $latencies[(int) floor($iterations * 0.50)];
    $p95 = $latencies[(int) floor($iterations * 0.95)];
    $p99 = $latencies[(int) floor($iterations * 0.99)];

    unset($latencies);
    gc_collect_cycles();
    $memAfter = memory_get_usage(false);

    return [
        'label' => $label,
        'rps' => (int) round($iterations / $totalSec),
        'p50_ms' => round($p50, 3),
        'p95_ms' => round($p95, 3),
        'p99_ms' => round($p99, 3),
        'mem_delta_kb' => round(($memAfter - $memBefore) / 1024, 2),
    ];
};

$results = [
    $runBench('GET /ping (JSON + attribute route)', static fn(): Request => Request::create('GET', '/ping')),
    $runBench('POST /items (JSON + DTO validation)', static fn(): Request => Request::json('POST', '/items', ['title' => 'Hello Kongono'])),
    $runBench('GET /view (Compiled .kgn.html + layout)', static fn(): Request => Request::create('GET', '/view')),
];

$app->shutdown();

// Clean temporary benchmark view cache
$cachedFiles = glob($benchCache . DIRECTORY_SEPARATOR . '*');
foreach (false !== $cachedFiles ? $cachedFiles : [] as $f) {
    @unlink($f);
}
@rmdir($benchCache);

$peakMb = round(memory_get_peak_usage(true) / 1_048_576, 2);

echo "Kongono Framework v1.1.1 — Persistent-Worker In-Process Benchmark\n";
echo sprintf("Runtime: PHP %s (%s) | Iterations: %d per scenario\n", PHP_VERSION, PHP_OS_FAMILY, $iterations);
echo sprintf("Cold App Boot: %.2f ms | Peak Memory: %.2f MB | Active Ops After Run: %d\n\n", $bootMs, $peakMb, $app->kernel()->activeOperationCount());
echo sprintf("%-42s %10s %9s %9s %9s %12s\n", 'SCENARIO', 'REQ/SEC', 'P50 (ms)', 'P95 (ms)', 'P99 (ms)', 'MEM DELTA');
echo str_repeat('-', 96) . "\n";

foreach ($results as $r) {
    echo sprintf(
        "%-42s %10d %9.3f %9.3f %9.3f %9.2f KB\n",
        $r['label'],
        $r['rps'],
        $r['p50_ms'],
        $r['p95_ms'],
        $r['p99_ms'],
        $r['mem_delta_kb'],
    );
}
