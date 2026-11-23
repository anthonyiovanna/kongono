<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

use Closure;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\Message;
use Kongono\Ai\Security\PromptGuard;
use Kongono\Ai\Security\UntrustedInput;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Http\Dev\TestExporter;
use Kongono\Jobs\Job;

/**
 * Hantei (判定) — Kongono's built-in AI debugger, failure analyzer, and test generator.
 *
 * Runs real PHPUnit / PHPStan checks, scrubs secrets from logs/diagnostics, isolates untrusted
 * failure logs & source files with PromptGuard, explains root causes with exact fixes,
 * and generates syntax-verified PHPUnit 13 tests.
 */
final readonly class HanteiDebugger
{
    private const string DEBUG_SYSTEM_PROMPT = <<<'PROMPT'
You are Hantei (判定), the built-in AI debugger and testing specialist for Kongono v1.18+ (PHP 8.5.13+, zero Symfony, zero magic facades, PHPUnit 13, PHPStan level 9 strict).

Given a failure log, stack trace, or Kongono diagnostic along with the project's live ApplicationGraph:
1. Identify the exact root cause concisely.
2. Point to the specific file/class/line involved.
3. Provide a minimal, strict PHP 8.5 fix (no Laravel/Symfony APIs).
4. Suggest one regression test using `TestClient` / `DatabaseTestHelper` / `FrozenClock` / `SafeHttpClient::fake()`.
PROMPT;

    private const string TESTGEN_SYSTEM_PROMPT = <<<'PROMPT'
You are Hantei (判定), the built-in AI test generator for Kongono v1.18+ (PHP 8.5.13+, PHPUnit 13).

Given a Kongono PHP source file and project context, output ONLY a complete, valid, runnable PHPUnit 13 test class (no markdown fences or extra commentary).
Requirements:
- Start with `<?php` and `declare(strict_types=1);`
- Extend `PHPUnit\Framework\TestCase`
- Test happy paths, validation/edge errors, auth/capability boundaries, and persistent-runtime isolation where relevant.
- Prefer Kongono's global-free testing helpers: `Kongono\Http\Testing\TestClient` (`actingAs`, `withStatefulCookies`, `withBearerToken`, `postMultipart`, `withLive`, `withBridge`), `TestUpload`, `Kongono\Database\Testing\DatabaseTestHelper::inRollbackTransaction()`, `Kongono\Security\Egress\SafeHttpClient::fake()`, and `Kongono\Core\Clock\FrozenClock`.
PROMPT;

    public function __construct(
        private ?AiClient $ai = null,
    ) {}

    /**
     * Run a shell command (e.g. phpunit or phpstan), scrub secrets, and optionally ask AI to diagnose failures.
     *
     * @param list<string> $command
     * @return array{exit_code: int, output: string, diagnosis: ?string}
     */
    public function runCheck(array $command, ProjectContext $context, bool $diagnoseWithAi = true): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $context->rootPath);
        if (!is_resource($process)) {
            return [
                'exit_code' => 1,
                'output' => 'Failed to start check process.',
                'diagnosis' => null,
            ];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $combined = SecretScrubber::scrub(trim((false === $stdout ? '' : $stdout) . "\n" . (false === $stderr ? '' : $stderr)));
        $diagnosis = null;

        if (0 !== $exitCode && $diagnoseWithAi && null !== $this->ai) {
            $diagnosis = $this->diagnose($combined, $context);
        }

        return [
            'exit_code' => $exitCode,
            'output' => $combined,
            'diagnosis' => $diagnosis,
        ];
    }

    /** Diagnose an error message, diagnostic ID, or failure log against the live project. */
    public function diagnose(string $failureText, ProjectContext $context): string
    {
        return $this->diagnoseWithTelemetry($failureText, $context)['diagnosis'];
    }

    /**
     * Diagnose a failure log and return both the explanation and structured token/cost/latency telemetry.
     *
     * @return array{diagnosis: string, telemetry: ?CompanionTelemetry}
     */
    public function diagnoseWithTelemetry(string $failureText, ProjectContext $context, ?float $maxCostUsd = null): array
    {
        $safeFailure = SecretScrubber::scrub($failureText);
        if (null === $this->ai) {
            return [
                'diagnosis' => "Hantei AI diagnosis requires an AI provider (OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST).\n"
                    . "Scrubbed diagnostic captured:\n" . substr($safeFailure, 0, 1000),
                'telemetry' => null,
            ];
        }

        $guard = new PromptGuard();
        $protectedSystem = $guard->buildProtectedSystemPrompt(self::DEBUG_SYSTEM_PROMPT);

        $prompt = sprintf(
            "Project context (untrusted repository metadata):\n%s\n\nFailure / Diagnostic output (secret-scrubbed untrusted log):\n%s",
            $guard->wrapUntrusted(new UntrustedInput($context->promptSummary(), source: 'project_summary')),
            $guard->wrapUntrusted(new UntrustedInput(substr($safeFailure, 0, 8000), source: 'failure_log')),
        );

        $start = hrtime(true);
        $response = $this->ai->chat(new ChatRequest([
            Message::system($protectedSystem['prompt']),
            Message::user($prompt),
        ], temperature: 0.2));
        $durationMs = (hrtime(true) - $start) / 1_000_000;

        $inspected = $guard->inspectOutput($response->text, $protectedSystem['canary']);
        if (!$inspected['safe']) {
            throw new CoreException('Hantei output blocked by PromptGuard due to suspected prompt injection or canary exfiltration.');
        }

        $telemetry = CompanionTelemetry::fromUsage($response->provider, $response->model, $response->usage, $durationMs, $maxCostUsd);
        $telemetry->assertWithinBudget('Hantei');

        return ['diagnosis' => $inspected['output'], 'telemetry' => $telemetry];
    }

    /**
     * Stream a failure diagnosis live via `$onDelta` while enforcing PromptGuard canary inspection and cost caps.
     *
     * @param Closure(string): void $onDelta
     * @return array{diagnosis: string, telemetry: ?CompanionTelemetry}
     */
    public function diagnoseStream(
        string $failureText,
        ProjectContext $context,
        Closure $onDelta,
        ?float $maxCostUsd = null,
    ): array {
        $safeFailure = SecretScrubber::scrub($failureText);
        if (null === $this->ai) {
            $offline = "Hantei AI diagnosis requires an AI provider (OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST).\n"
                . "Scrubbed diagnostic captured:\n" . substr($safeFailure, 0, 1000);
            $onDelta($offline);

            return ['diagnosis' => $offline, 'telemetry' => null];
        }

        $guard = new PromptGuard();
        $protectedSystem = $guard->buildProtectedSystemPrompt(self::DEBUG_SYSTEM_PROMPT);

        $prompt = sprintf(
            "Project context (untrusted repository metadata):\n%s\n\nFailure / Diagnostic output (secret-scrubbed untrusted log):\n%s",
            $guard->wrapUntrusted(new UntrustedInput($context->promptSummary(), source: 'project_summary')),
            $guard->wrapUntrusted(new UntrustedInput(substr($safeFailure, 0, 8000), source: 'failure_log')),
        );

        $start = hrtime(true);
        $response = $this->ai->chatStream(
            new ChatRequest([
                Message::system($protectedSystem['prompt']),
                Message::user($prompt),
            ], temperature: 0.2),
            $onDelta,
        );
        $durationMs = (hrtime(true) - $start) / 1_000_000;

        $inspected = $guard->inspectOutput($response->text, $protectedSystem['canary']);
        if (!$inspected['safe']) {
            throw new CoreException('Hantei output blocked by PromptGuard due to suspected prompt injection or canary exfiltration.');
        }

        $telemetry = CompanionTelemetry::fromUsage($response->provider, $response->model, $response->usage, $durationMs, $maxCostUsd);
        $telemetry->assertWithinBudget('Hantei');

        return ['diagnosis' => $inspected['output'], 'telemetry' => $telemetry];
    }

    /**
     * Diagnose a captured DevInspector request trace and export a ready-to-run PHPUnit 13 regression test.
     *
     * @param array{
     *     id: int,
     *     timestamp: string,
     *     method: string,
     *     path: string,
     *     query: string,
     *     headers: array<string, string>,
     *     body: string,
     *     status: int,
     *     duration_ms: float,
     *     queries: list<array{sql: string, ms: float}>,
     *     n_plus_one: list<array{pattern: string, count: int}>,
     *     error: ?string
     * } $trace
     * @return array{trace_id: int, diagnosis: string, regression_test: string, telemetry: ?CompanionTelemetry}
     */
    public function diagnoseTrace(array $trace, ProjectContext $context, ?float $maxCostUsd = null): array
    {
        $queryLines = [];
        foreach (array_slice($trace['queries'], 0, 20) as $q) {
            $queryLines[] = sprintf('  - (%.2fms) %s', $q['ms'], $q['sql']);
        }
        $nPlusOneLines = [];
        foreach ($trace['n_plus_one'] as $alert) {
            $nPlusOneLines[] = sprintf('  - %dx %s', $alert['count'], $alert['pattern']);
        }

        $summary = sprintf(
            "Captured HTTP Trace #%d (%s):\n%s %s%s -> HTTP %d (%.2fms)\nError: %s\nSQL Queries (%d):\n%s\nN+1 Alerts:\n%s",
            $trace['id'],
            $trace['timestamp'],
            $trace['method'],
            $trace['path'],
            '' !== $trace['query'] ? '?' . $trace['query'] : '',
            $trace['status'],
            $trace['duration_ms'],
            $trace['error'] ?? 'none',
            count($trace['queries']),
            [] !== $queryLines ? implode("\n", $queryLines) : '  (none)',
            [] !== $nPlusOneLines ? implode("\n", $nPlusOneLines) : '  (none)',
        );

        $res = $this->diagnoseWithTelemetry($summary, $context, $maxCostUsd);

        return [
            'trace_id' => $trace['id'],
            'diagnosis' => $res['diagnosis'],
            'regression_test' => TestExporter::exportPhpUnitTest($trace),
            'telemetry' => $res['telemetry'],
        ];
    }

    /**
     * Diagnose a failed or dead-lettered background Job against the live application graph.
     *
     * @return array{job_id: string, job_type: string, queue: string, diagnosis: string, telemetry: ?CompanionTelemetry}
     */
    public function diagnoseFailedJob(Job $job, ProjectContext $context, ?float $maxCostUsd = null): array
    {
        $safePayload = SecretScrubber::scrubArray($job->payload);
        $payloadJson = json_encode($safePayload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $summary = sprintf(
            "Failed / Dead-Lettered Background Job:\nID: %s\nType: %s\nQueue: %s\nStatus: %s\nAttempts: %d/%d\nLast Error: %s\nPayload (scrubbed):\n%s",
            $job->id,
            $job->type,
            $job->queue,
            $job->status->value,
            $job->attempt,
            $job->maxAttempts,
            SecretScrubber::scrub($job->lastError ?? 'none'),
            false !== $payloadJson ? $payloadJson : '{}',
        );

        $res = $this->diagnoseWithTelemetry($summary, $context, $maxCostUsd);

        return [
            'job_id' => $job->id,
            'job_type' => $job->type,
            'queue' => $job->queue,
            'diagnosis' => $res['diagnosis'],
            'telemetry' => $res['telemetry'],
        ];
    }

    /**
     * Generate a runnable PHPUnit 13 test file for a given source file.
     *
     * @return array{target_path: string, code: string, written: bool}
     */
    public function generateTests(string $sourceRelativePath, ProjectContext $context, bool $apply = false): array
    {
        if (null === $this->ai) {
            throw new AiException('Hantei test generation requires an AI provider (set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST).');
        }

        $sourceCode = $context->readFileSafe($sourceRelativePath);
        $targetPath = $this->deriveTestPath($sourceRelativePath);

        $guard = new PromptGuard();
        $protectedSystem = $guard->buildProtectedSystemPrompt(self::TESTGEN_SYSTEM_PROMPT);

        $prompt = sprintf(
            "Project context (untrusted repository metadata):\n%s\n\nTarget test file path: %s\n\nSource file (%s, untrusted source code):\n%s",
            $guard->wrapUntrusted(new UntrustedInput($context->promptSummary(), source: 'project_summary')),
            $targetPath,
            $sourceRelativePath,
            $guard->wrapUntrusted(new UntrustedInput($sourceCode, source: 'source_file')),
        );

        $raw = $this->ai->ask($prompt, $protectedSystem['prompt'], temperature: 0.2);
        $inspected = $guard->inspectOutput($raw, $protectedSystem['canary']);
        if (!$inspected['safe']) {
            throw new CoreException('Hantei test generation output blocked by PromptGuard due to suspected prompt injection or canary exfiltration.');
        }

        $generated = self::extractPhpCode($inspected['output']) . "\n";

        if ($apply) {
            ChieBuilder::assertValidPhpSyntax($targetPath, $generated);
            $context->writeFileContained($targetPath, $generated);
        }

        return [
            'target_path' => $targetPath,
            'code' => $generated,
            'written' => $apply,
        ];
    }

    private static function extractPhpCode(string $raw): string
    {
        $clean = trim($raw);
        if (1 === preg_match('/```(?:php)?\s*(<\?php.*)\s*```/s', $clean, $m)) {
            return trim($m[1]);
        }
        if (str_starts_with($clean, '```')) {
            $stripped = preg_replace('/\A```(?:php)?\s*|\s*```\z/', '', $clean);
            if (is_string($stripped)) {
                $clean = trim($stripped);
            }
        }
        $phpPos = strpos($clean, '<?php');
        if (false !== $phpPos && $phpPos > 0) {
            return trim(substr($clean, $phpPos));
        }

        return $clean;
    }

    private function deriveTestPath(string $sourceRelativePath): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($sourceRelativePath)), '/');
        if (!str_ends_with(strtolower($normalized), '.php') || str_contains($normalized, '..')) {
            throw new AiException(sprintf('Hantei test generation requires a valid .php source file path (got "%s").', $sourceRelativePath));
        }

        $relative = str_starts_with($normalized, 'src/') ? substr($normalized, 4) : basename($normalized);
        $base = 'tests/' . ltrim($relative, '/');
        if (!str_ends_with($base, 'Test.php')) {
            return substr($base, 0, -4) . 'Test.php';
        }

        return $base;
    }
}
