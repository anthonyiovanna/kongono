<?php

declare(strict_types=1);

namespace Kongono\Http\Ops;

use Closure;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Core\Health\HealthCheck;
use Kongono\Core\Telemetry\Metrics;
use Kongono\Http\Dev\DevInspector;
use Kongono\Http\HttpException;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Jobs\Batch\JobBatch;
use Kongono\Jobs\Job;
use Kongono\Jobs\QueueDriverInterface;
use Kongono\Security\Gate;
use Kongono\Security\Principal;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Capability-gated, zero-npm, strict-CSP Operations Dashboard (`/_ops`).
 *
 * Brings Horizon + Pulse Lite straight into Kongono using Kongono's own primitives:
 *   - Fail-closed `Gate` authorization (`ops.view` for reads, `ops.manage` for queue retry/flush)
 *   - Live queue depth cards & 1-click dead-letter job retry / flush (strict queue allowlist)
 *   - Active `JobBatch` progress bars, `HealthCheck` status, `Metrics`, and recent request traces
 *   - Serves full HTML, Kongono Live 2.5 morph fragments, or JSON (`Accept: application/json`)
 */
final readonly class OpsDashboardMiddleware implements MiddlewareInterface
{
    private OpsDashboardRenderer $renderer;

    /**
     * @param list<string>                                                      $queues             Monitored queue names (also serves as strict allowlist for POST actions)
     * @param (Closure(): list<JobBatch>)|null                                  $batchesProvider    Optional provider returning tracked JobBatch snapshots
     * @param (Closure(ServerRequestInterface): ?string)|null                   $csrfTokenProvider  Optional CSRF token resolver for HTML forms
     * @param (Closure(Job): array{diagnosis: string, footer?: ?string})|null   $diagnoseJobHandler Optional Hantei DLQ diagnosis callback (protected by `ops.view`)
     */
    public function __construct(
        private string $appName = 'Kongono App',
        private string $prefix = '/_ops',
        private ?QueueDriverInterface $queueDriver = null,
        private array $queues = ['default'],
        private ?HealthCheck $healthCheck = null,
        private ?Metrics $metrics = null,
        private ?DevInspector $inspector = null,
        private ?Closure $batchesProvider = null,
        private Gate $gate = new Gate(),
        private string $viewCapability = 'ops.view',
        private string $manageCapability = 'ops.manage',
        private ?Closure $csrfTokenProvider = null,
        private ?Closure $diagnoseJobHandler = null,
    ) {
        $this->renderer = new OpsDashboardRenderer();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = '/' . trim($request->getUri()->getPath(), '/');
        $base = '/' . trim($this->prefix, '/');
        if ($path !== $base && !str_starts_with($path, $base . '/')) {
            return $handler->handle($request);
        }

        $method = strtoupper($request->getMethod());
        $principal = $request->getAttribute(Principal::class);
        $typedPrincipal = $principal instanceof Principal ? $principal : null;

        if ($path === $base && 'GET' === $method) {
            $this->gate->authorize($typedPrincipal, $this->viewCapability);

            return $this->respondWithSnapshot($request, $base);
        }

        if ($path === $base . '/queues/retry' && 'POST' === $method) {
            $this->gate->authorize($typedPrincipal, $this->manageCapability);

            return $this->handleQueueRetry($request, $base);
        }

        if ($path === $base . '/queues/flush' && 'POST' === $method) {
            $this->gate->authorize($typedPrincipal, $this->manageCapability);

            return $this->handleQueueFlush($request, $base);
        }

        if ($path === $base . '/queues/diagnose' && 'POST' === $method) {
            $this->gate->authorize($typedPrincipal, $this->viewCapability);

            return $this->handleQueueDiagnose($request, $base);
        }

        throw new HttpException(404, 'Ops endpoint not found.');
    }

    private function handleQueueDiagnose(ServerRequestInterface $request, string $base): ResponseInterface
    {
        if (null === $this->queueDriver) {
            throw new HttpException(400, 'No queue driver attached to OpsDashboardMiddleware.');
        }
        if (null === $this->diagnoseJobHandler) {
            return $this->hardenHeaders(Response::json(['error' => 'Hantei DLQ diagnosis is not configured on OpsDashboardMiddleware.'], 501));
        }

        $input = $this->extractInput($request);
        $queue = $this->resolveAllowedQueue($input['queue'] ?? null);
        $jobId = is_string($input['job_id'] ?? null) ? trim($input['job_id']) : '';

        $selected = null;
        foreach ($this->queueDriver->listFailed($queue, 50) as $job) {
            if ('' === $jobId || $job->id === $jobId) {
                $selected = $job;
                break;
            }
        }

        if (null === $selected) {
            throw new HttpException(404, sprintf('Failed job "%s" not found in queue "%s".', '' !== $jobId ? $jobId : '(latest)', $queue));
        }

        $result = ($this->diagnoseJobHandler)($selected);
        $diagnosis = SecretScrubber::scrub($result['diagnosis']);
        $footer = isset($result['footer']) ? SecretScrubber::scrub($result['footer']) : null;

        if ($this->wantsJson($request)) {
            return $this->hardenHeaders(Response::json([
                'action' => 'diagnose',
                'queue' => $queue,
                'job_id' => $selected->id,
                'job_type' => $selected->type,
                'diagnosis' => $diagnosis,
                'footer' => $footer,
            ]));
        }

        $notice = sprintf(
            'Hantei Diagnosis for #%s (%s): %s%s',
            $selected->id,
            $selected->type,
            $diagnosis,
            null !== $footer && '' !== $footer ? "\n" . $footer : '',
        );
        if ($this->isLiveRequest($request)) {
            return $this->respondWithSnapshot($request, $base, $notice);
        }

        return $this->respondWithSnapshot($request, $base, $notice);
    }

    private function handleQueueRetry(ServerRequestInterface $request, string $base): ResponseInterface
    {
        if (null === $this->queueDriver) {
            throw new HttpException(400, 'No queue driver attached to OpsDashboardMiddleware.');
        }

        $input = $this->extractInput($request);
        $queue = $this->resolveAllowedQueue($input['queue'] ?? null);
        $rawJobId = is_string($input['job_id'] ?? null) ? trim($input['job_id']) : '';
        $jobId = ('' === $rawJobId || 'all' === strtolower($rawJobId)) ? null : $rawJobId;

        $count = $this->queueDriver->retryFailed($queue, $jobId);
        if ($this->wantsJson($request)) {
            return $this->hardenHeaders(Response::json([
                'action' => 'retry',
                'queue' => $queue,
                'requeued' => $count,
            ]));
        }

        $notice = sprintf('Requeued %d failed job(s) in queue "%s".', $count, $queue);
        if ($this->isLiveRequest($request)) {
            return $this->respondWithSnapshot($request, $base, $notice);
        }

        return $this->hardenHeaders(Response::redirect($base, 303));
    }

    private function handleQueueFlush(ServerRequestInterface $request, string $base): ResponseInterface
    {
        if (null === $this->queueDriver) {
            throw new HttpException(400, 'No queue driver attached to OpsDashboardMiddleware.');
        }

        $input = $this->extractInput($request);
        $queue = $this->resolveAllowedQueue($input['queue'] ?? null);
        $removed = $this->queueDriver->flushFailed($queue);

        if ($this->wantsJson($request)) {
            return $this->hardenHeaders(Response::json([
                'action' => 'flush',
                'queue' => $queue,
                'flushed' => $removed,
            ]));
        }

        $notice = sprintf('Flushed %d failed job(s) from queue "%s".', $removed, $queue);
        if ($this->isLiveRequest($request)) {
            return $this->respondWithSnapshot($request, $base, $notice);
        }

        return $this->hardenHeaders(Response::redirect($base, 303));
    }

    private function respondWithSnapshot(ServerRequestInterface $request, string $base, ?string $notice = null): ResponseInterface
    {
        $snapshot = $this->collectSnapshot();
        if ($this->wantsJson($request)) {
            return $this->hardenHeaders(Response::json($snapshot));
        }

        $csrfToken = null !== $this->csrfTokenProvider ? ($this->csrfTokenProvider)($request) : null;
        $canDiagnose = null !== $this->diagnoseJobHandler;
        if ($this->isLiveRequest($request)) {
            $frag = Response::fragment($this->renderer->renderPanel($snapshot, $base, $csrfToken, $notice, $canDiagnose), swap: 'morph');

            return $this->hardenHeaders($frag);
        }

        $html = $this->renderer->renderPage($snapshot, $base, $csrfToken, $notice, $canDiagnose);

        return $this->hardenHeaders(Response::html($html));
    }

    /**
     * @return array{
     *     app: string,
     *     timestamp: string,
     *     health: array{status: string, healthy: bool, checks: array<string, array{ok: bool, duration_ms: float, error?: string}>}|null,
     *     queues: array<string, array{pending: int, reserved: int, completed: int, failed: int, dead: int}>,
     *     failed_jobs: list<array{id: string, queue: string, type: string, status: string, attempt: int, max_attempts: int, last_error: ?string}>,
     *     batches: list<array{id: string, name: string, total_jobs: int, pending_jobs: int, processed_jobs: int, failed_jobs: int, cancelled: bool, progress: float}>,
     *     metrics: array{
     *         counters: list<array{name: string, labels: array<string, string>, value: float}>,
     *         gauges: list<array{name: string, labels: array<string, string>, value: float}>,
     *         timings: list<array{name: string, labels: array<string, string>, count: int, sum: float, max: float, avg: float}>
     *     }|null,
     *     recent_traces: list<array{id: int, method: string, path: string, status: int, duration_ms: float}>
     * }
     */
    private function collectSnapshot(): array
    {
        $health = null;
        if (null !== $this->healthCheck) {
            $h = $this->healthCheck->run(exposeErrors: false);
            $health = [
                'status' => $h['status'],
                'healthy' => $h['healthy'],
                'checks' => $h['checks'],
            ];
        }

        $queues = [];
        $failedJobs = [];
        if (null !== $this->queueDriver) {
            foreach ($this->queues as $qName) {
                $queues[$qName] = $this->queueDriver->stats($qName);
                foreach ($this->queueDriver->listFailed($qName, 50) as $job) {
                    $failedJobs[] = [
                        'id' => $job->id,
                        'queue' => $job->queue,
                        'type' => $job->type,
                        'status' => $job->status->value,
                        'attempt' => $job->attempt,
                        'max_attempts' => $job->maxAttempts,
                        'last_error' => null !== $job->lastError ? SecretScrubber::scrub($job->lastError) : null,
                    ];
                }
            }
        }

        $batches = [];
        if (null !== $this->batchesProvider) {
            foreach (($this->batchesProvider)() as $batch) {
                $batches[] = [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'total_jobs' => $batch->totalJobs,
                    'pending_jobs' => $batch->pendingJobs,
                    'processed_jobs' => $batch->processedJobs,
                    'failed_jobs' => $batch->failedJobs,
                    'cancelled' => $batch->cancelled,
                    'progress' => $batch->progress(),
                ];
            }
        }

        $recentTraces = [];
        if (null !== $this->inspector) {
            foreach (array_slice($this->inspector->traces(), 0, 20) as $tr) {
                $recentTraces[] = [
                    'id' => $tr['id'],
                    'method' => $tr['method'],
                    'path' => $tr['path'],
                    'status' => $tr['status'],
                    'duration_ms' => $tr['duration_ms'],
                ];
            }
        }

        return [
            'app' => $this->appName,
            'timestamp' => gmdate(DATE_ATOM),
            'health' => $health,
            'queues' => $queues,
            'failed_jobs' => $failedJobs,
            'batches' => $batches,
            'metrics' => $this->metrics?->snapshot(),
            'recent_traces' => $recentTraces,
        ];
    }

    private function resolveAllowedQueue(mixed $candidate): string
    {
        $queue = is_string($candidate) && '' !== trim($candidate) ? trim($candidate) : ($this->queues[0] ?? 'default');
        if (!in_array($queue, $this->queues, true)) {
            throw new HttpException(400, sprintf('Queue "%s" is not in the configured OpsDashboard queue allowlist.', $queue));
        }

        return $queue;
    }

    /** @return array<string, mixed> */
    private function extractInput(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed) && [] !== $parsed) {
            /** @var array<string, mixed> $out */
            $out = [];
            foreach ($parsed as $k => $v) {
                $out[(string) $k] = $v;
            }

            return $out;
        }

        if ($request instanceof Request && str_contains(strtolower($request->getHeaderLine('Content-Type')), 'application/json')) {
            return $request->jsonBody();
        }

        return [];
    }

    private function wantsJson(ServerRequestInterface $request): bool
    {
        return str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json');
    }

    private function isLiveRequest(ServerRequestInterface $request): bool
    {
        return 'true' === strtolower($request->getHeaderLine('X-Kongono-Live'));
    }

    private function hardenHeaders(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Content-Security-Policy', "default-src 'self'; style-src 'unsafe-inline'; script-src 'self'; object-src 'none'; frame-ancestors 'none'");
    }
}
