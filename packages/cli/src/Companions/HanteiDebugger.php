<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

use Closure;
use Kongono\Ai\Agent\AgentPrincipal;
use Kongono\Ai\Agent\AgentRunner;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\Message;
use Kongono\Ai\Security\PromptGuard;
use Kongono\Ai\Security\UntrustedInput;
use Kongono\Ai\ToolCall;
use Kongono\Cli\Audit\SecurityAuditor;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Database\MigrationLinter;
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
You are Hantei (判定), the built-in AI debugger and testing specialist for Kongono v1.20+ (PHP 8.5.13+, zero Symfony, zero magic facades, PHPUnit 13, PHPStan level 9 strict, self-verifying proofs).

Given a failure log, stack trace, `KGN-*` incident reference, or Kongono 5W1H diagnostic along with the project's live ApplicationGraph:
1. Identify the exact root cause concisely (What, Where, Why).
2. Point to the specific file/class/line involved and note any violated route capability (`#[ReadOnlyDb]`, `#[NoOutboundHttp]`, `#[MaxQueries]`, `#[MaxAiCost]`) or policy invariant (`app/invariants.php`).
3. Provide a minimal, strict PHP 8.5 fix (no Laravel/Symfony APIs) and mention `kongo diagnose --fix` / `kongo prove` / `kongo incident:replay` when applicable.
4. Suggest one regression test using `TestClient` / `DatabaseTestHelper` / `FrozenClock` / `SafeHttpClient::fake()`.
PROMPT;

    private const string TESTGEN_SYSTEM_PROMPT = <<<'PROMPT'
You are Hantei (判定), the built-in AI test generator for Kongono v1.20+ (PHP 8.5.13+, PHPUnit 13).

Given a Kongono PHP source file and project context, output ONLY a complete, valid, runnable PHPUnit 13 test class (no markdown fences or extra commentary).
Requirements:
- Start with `<?php` and `declare(strict_types=1);`
- Extend `PHPUnit\Framework\TestCase`
- Test happy paths, validation/edge errors, auth/capability boundaries (`#[ReadOnlyDb]`, `#[NoOutboundHttp]`, `#[MaxQueries]`, `#[Authenticated]`, `#[Can]`), production nondisclosure (`APP_DEBUG=false`), and persistent-runtime isolation where relevant.
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
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $open = [1 => $pipes[1], 2 => $pipes[2]];
        while ([] !== $open) {
            $read = array_values($open);
            $write = null;
            $except = null;
            $ready = @stream_select($read, $write, $except, 1);
            if (false === $ready) {
                break;
            }
            foreach ($read as $stream) {
                $chunk = fread($stream, 65536);
                if (false === $chunk || ('' === $chunk && feof($stream))) {
                    foreach ($open as $k => $s) {
                        if ($s === $stream) {
                            unset($open[$k]);
                        }
                    }
                    continue;
                }
                if ($stream === $pipes[1]) {
                    $stdout .= $chunk;
                } else {
                    $stderr .= $chunk;
                }
            }
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $combined = SecretScrubber::scrub(trim($stdout . "\n" . $stderr));
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
     * Diagnose a failure log using read-only inspection tools (`search_code`, `read_file`, `inspect_graph`, `inspect_schema`)
     * and return both the explanation and structured token/cost/latency telemetry.
     *
     * @return array{diagnosis: string, telemetry: ?CompanionTelemetry, tools_used: list<string>}
     */
    public function diagnoseWithTelemetry(string $failureText, ProjectContext $context, ?float $maxCostUsd = null): array
    {
        $safeFailure = SecretScrubber::scrub($failureText);
        if (null === $this->ai) {
            return [
                'diagnosis' => "Hantei AI diagnosis requires an AI provider (OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST).\n"
                    . "Scrubbed diagnostic captured:\n" . substr($safeFailure, 0, 1000),
                'telemetry' => null,
                'tools_used' => [],
            ];
        }

        $guard = new PromptGuard();
        $protectedSystem = $guard->buildProtectedSystemPrompt(
            self::DEBUG_SYSTEM_PROMPT
            . "\n\nYou also have read-only project tools (`list_files`, `read_file` with line ranges, `search_code`, `inspect_graph`, `inspect_schema`). Use them whenever inspecting a stack-trace file, route, or database table helps pinpoint the exact root cause.",
        );

        $prompt = sprintf(
            "Project context (untrusted repository metadata):\n%s\n\nFailure / Diagnostic output (secret-scrubbed untrusted log):\n%s",
            $guard->wrapUntrusted(new UntrustedInput($context->promptSummary(), source: 'project_summary')),
            $guard->wrapUntrusted(new UntrustedInput(substr($safeFailure, 0, 8000), source: 'failure_log')),
        );

        $cap = $maxCostUsd ?? CompanionTelemetry::maxCostFromEnv();
        $principal = new AgentPrincipal(
            id: 'hantei-debugger',
            role: 'Kongono AI Debugger',
            allowedTools: CompanionTools::NAMES,
            maxSteps: 5,
            maxToolCalls: 8,
            maxTokens: 1_000_000,
            maxCostUsd: max(0.0, $cap),
        );
        $runner = new AgentRunner($this->ai, $principal);

        $start = hrtime(true);
        try {
            $receipt = $runner->run(
                [
                    Message::system($protectedSystem['prompt']),
                    Message::user($prompt),
                ],
                CompanionTools::definitions(),
                static fn(ToolCall $call): string => CompanionTools::execute($call, $context),
            );
        } catch (\Kongono\Ai\AiException $e) {
            if (str_contains($e->getMessage(), 'cost budget')) {
                throw new CoreException(sprintf('Hantei exceeded configured cost budget: %s', $e->getMessage()), previous: $e);
            }
            throw $e;
        }
        $durationMs = (hrtime(true) - $start) / 1_000_000;

        $inspected = $guard->inspectOutput($receipt->finalText, $protectedSystem['canary']);
        if (!$inspected['safe']) {
            throw new CoreException('Hantei output blocked by PromptGuard due to suspected prompt injection or canary exfiltration.');
        }

        $telemetry = CompanionTelemetry::fromUsage($receipt->provider, $receipt->model, $receipt->usage, $durationMs, $maxCostUsd);
        $telemetry->assertWithinBudget('Hantei');

        $toolsUsed = array_values(array_unique(array_map(
            static fn(array $entry): string => $entry['tool'],
            $receipt->toolAudit,
        )));

        return [
            'diagnosis' => $inspected['output'],
            'telemetry' => $telemetry,
            'tools_used' => $toolsUsed,
        ];
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

        $streamFilter = $guard->createStreamingFilter($protectedSystem['canary'], $onDelta);

        $start = hrtime(true);
        $response = $this->ai->chatStream(
            new ChatRequest([
                Message::system($protectedSystem['prompt']),
                Message::user($prompt),
            ], temperature: 0.2),
            $streamFilter['push'],
        );
        $durationMs = (hrtime(true) - $start) / 1_000_000;

        $inspected = $guard->inspectOutput($response->text, $protectedSystem['canary']);
        if (!$inspected['safe']) {
            throw new CoreException('Hantei output blocked by PromptGuard due to suspected prompt injection or canary exfiltration.');
        }
        $streamFilter['flush']();

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
     *     memory_delta_kb?: float,
     *     response_headers?: array<string, string>,
     *     response_body?: string,
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
     * Closed-loop Debug -> Patch -> Verify workflow (`kongo hantei fix`):
     * diagnoses the failure with Hantei's project tools, hands the scrubbed root cause to Chie to propose
     * minimal targeted fixes, and optionally applies + verifies them with bounded auto-repair.
     *
     * @param list<string>                                     $verifyCommand
     * @param list<array{name: string, command: list<string>}> $extraVerifyCommands
     * @return array{
     *     diagnosis: string,
     *     summary: string,
     *     changes: list<array{path: string, action: string, explanation: string, content: string}>,
     *     next_steps: list<string>,
     *     applied: list<string>,
     *     verified: bool,
     *     rolled_back: bool,
     *     attempts: int,
     *     verify_output: string,
     *     tools_used: list<string>,
     *     telemetry: CompanionTelemetry
     * }
     */
    public function diagnoseAndFix(
        string $failureText,
        ProjectContext $context,
        ChieBuilder $chie,
        array $verifyCommand = [],
        bool $apply = false,
        bool $verify = false,
        int $maxRepairs = 2,
        ?float $maxCostUsd = null,
        bool $rollbackOnFailure = false,
        array $extraVerifyCommands = [],
        bool $runSecurityAudit = true,
    ): array {
        $cap = $maxCostUsd ?? CompanionTelemetry::maxCostFromEnv();
        $safeFailure = SecretScrubber::scrub($failureText);
        $diag = $this->diagnoseWithTelemetry($safeFailure, $context, $cap);
        $diagTelemetry = $diag['telemetry'];

        $remainingCap = $cap > 0.0 && null !== $diagTelemetry
            ? max(0.0, $cap - $diagTelemetry->estimatedCostUsd)
            : $cap;

        $guard = new PromptGuard();
        $fixInstruction = sprintf(
            "Fix the following verified failure using minimal, surgical PHP 8.5 Kongono changes (do not break unrelated code).\n\nFailure output (untrusted):\n%s\n\nHantei root-cause diagnosis (untrusted):\n%s",
            $guard->wrapUntrusted(new UntrustedInput(substr($safeFailure, 0, 4000), source: 'failure_log')),
            $guard->wrapUntrusted(new UntrustedInput(substr(SecretScrubber::scrub($diag['diagnosis']), 0, 3000), source: 'hantei_diagnosis')),
        );

        if ($verify && $apply && [] !== $verifyCommand) {
            $plan = $chie->buildAndVerify(
                instruction: $fixInstruction,
                context: $context,
                hantei: $this,
                verifyCommand: $verifyCommand,
                maxRepairs: $maxRepairs,
                maxCostUsd: $remainingCap,
                rollbackOnFailure: $rollbackOnFailure,
                extraVerifyCommands: $extraVerifyCommands,
                runSecurityAudit: $runSecurityAudit,
            );
            $mergedTelemetry = null !== $diagTelemetry ? $diagTelemetry->merge($plan['telemetry']) : $plan['telemetry'];
            $mergedTelemetry->assertWithinBudget('Hantei Fix');

            return [
                'diagnosis' => $diag['diagnosis'],
                'summary' => $plan['summary'],
                'changes' => $plan['changes'],
                'next_steps' => $plan['next_steps'],
                'applied' => $plan['applied'],
                'verified' => $plan['verified'],
                'rolled_back' => $plan['rolled_back'],
                'attempts' => $plan['attempts'],
                'verify_output' => $plan['verify_output'],
                'tools_used' => $diag['tools_used'],
                'telemetry' => $mergedTelemetry,
            ];
        }

        $plan = $chie->build($fixInstruction, $context, apply: $apply, maxCostUsd: $remainingCap);
        $mergedTelemetry = null !== $diagTelemetry ? $diagTelemetry->merge($plan['telemetry']) : $plan['telemetry'];
        $mergedTelemetry->assertWithinBudget('Hantei Fix');

        return [
            'diagnosis' => $diag['diagnosis'],
            'summary' => $plan['summary'],
            'changes' => $plan['changes'],
            'next_steps' => $plan['next_steps'],
            'applied' => $plan['applied'],
            'verified' => false,
            'rolled_back' => false,
            'attempts' => 1,
            'verify_output' => '',
            'tools_used' => $diag['tools_used'],
            'telemetry' => $mergedTelemetry,
        ];
    }

    /**
     * Review a git diff against deterministic Kongono invariants (persistent-runtime safety, superglobals,
     * SecurityAuditor, MigrationLinter, leaked secrets) plus optional AI architectural review (`kongo hantei review`).
     *
     * @return array{
     *     passed: bool,
     *     counts: array{critical: int, warning: int, info: int},
     *     findings: list<array{severity: 'critical'|'warning'|'info', rule: string, file: string, line: int, message: string}>,
     *     ai_review: ?string,
     *     telemetry: ?CompanionTelemetry
     * }
     */
    public function reviewDiff(string $diffText, ProjectContext $context, ?float $maxCostUsd = null): array
    {
        /** @var list<array{severity: 'critical'|'warning'|'info', rule: string, file: string, line: int, message: string}> $findings */
        $findings = [];

        // 1. Deterministic diff line analysis (added lines only)
        $currentFile = 'diff';
        $currentLine = 1;
        $diffLines = preg_split("/\r\n|\n|\r/", $diffText);
        if (false !== $diffLines) {
            foreach ($diffLines as $rawLine) {
                if (str_starts_with($rawLine, '+++ b/')) {
                    $currentFile = trim(substr($rawLine, 6));
                    continue;
                }
                if (1 === preg_match('/\A@@ -\d+(?:,\d+)? \+(\d+)/', $rawLine, $hunkMatch)) {
                    $currentLine = (int) $hunkMatch[1];
                    continue;
                }
                if (str_starts_with($rawLine, '+') && !str_starts_with($rawLine, '+++')) {
                    $added = substr($rawLine, 1);
                    $this->scanAddedDiffLine($currentFile, $currentLine, $added, $findings);
                    ++$currentLine;
                } elseif (!str_starts_with($rawLine, '-')) {
                    ++$currentLine;
                }
            }
        }

        // 2. Deterministic SecurityAuditor & MigrationLinter checks
        $audit = (new SecurityAuditor())->audit($context->rootPath, $context->app, attack: false);
        foreach ($audit['findings'] as $f) {
            $findings[] = [
                'severity' => $f['severity'],
                'rule' => $f['code'],
                'file' => $f['location'],
                'line' => 1,
                'message' => $f['message'],
            ];
        }

        $migReport = (new MigrationLinter())->lintDirectory($context->rootPath . DIRECTORY_SEPARATOR . 'migrations');
        foreach ($migReport['findings'] as $mf) {
            $findings[] = [
                'severity' => $mf['severity'],
                'rule' => $mf['code'],
                'file' => 'migrations/' . $mf['file'],
                'line' => 1,
                'message' => $mf['message'],
            ];
        }

        // 3. Optional AI architectural review (FrankenPHP worker leaks, N+1, auth/validation boundaries)
        $aiReview = null;
        $telemetry = null;
        if (null !== $this->ai && '' !== trim($diffText)) {
            $guard = new PromptGuard();
            $protectedSystem = $guard->buildProtectedSystemPrompt(
                "You are Hantei (判定), Kongono's strict PHP 8.5 code reviewer. Review the git diff for:\n"
                . "1. Persistent-runtime mutable static/global state leaks (FrankenPHP/RoadRunner hazards)\n"
                . "2. Missing #[Authenticated]/#[Can]/#[Idempotent] or DTO validation boundaries\n"
                . "3. N+1 SQL queries (recommend BatchLoader::hasMany/belongsTo)\n"
                . "4. Secret disclosure or unsafe raw HTML output.\n"
                . "Be concise, list file:line findings grouped by severity, or reply 'LGTM — no Kongono invariant issues detected.' if clean.",
            );
            $safeDiff = substr(SecretScrubber::scrub($diffText), 0, 12000);
            $prompt = sprintf(
                "Project context (untrusted):\n%s\n\nGit diff to review (untrusted):\n%s",
                $guard->wrapUntrusted(new UntrustedInput($context->promptSummary(), source: 'project_summary')),
                $guard->wrapUntrusted(new UntrustedInput($safeDiff, source: 'git_diff')),
            );

            $start = hrtime(true);
            $response = $this->ai->chat(new ChatRequest([
                Message::system($protectedSystem['prompt']),
                Message::user($prompt),
            ], temperature: 0.1));
            $durationMs = (hrtime(true) - $start) / 1_000_000;

            $inspected = $guard->inspectOutput($response->text, $protectedSystem['canary']);
            if (!$inspected['safe']) {
                throw new CoreException('Hantei review output blocked by PromptGuard.');
            }
            $aiReview = $inspected['output'];
            $telemetry = CompanionTelemetry::fromUsage($response->provider, $response->model, $response->usage, $durationMs, $maxCostUsd);
            $telemetry->assertWithinBudget('Hantei Review');
        }

        $counts = ['critical' => 0, 'warning' => 0, 'info' => 0];
        foreach ($findings as $item) {
            ++$counts[$item['severity']];
        }

        return [
            'passed' => 0 === $counts['critical'],
            'counts' => $counts,
            'findings' => $findings,
            'ai_review' => $aiReview,
            'telemetry' => $telemetry,
        ];
    }

    /**
     * Format a `reviewDiff()` report as GitHub-flavored Markdown (ideal for CLI and PR comments).
     *
     * @param array{
     *     passed: bool,
     *     counts: array{critical: int, warning: int, info: int},
     *     findings: list<array{severity: 'critical'|'warning'|'info', rule: string, file: string, line: int, message: string}>,
     *     ai_review: ?string,
     *     telemetry: ?CompanionTelemetry
     * } $report
     */
    public static function formatReviewMarkdown(array $report): string
    {
        $lines = [
            sprintf(
                '## Hantei Code Review — %s (%d critical, %d warning, %d info)',
                $report['passed'] ? 'PASS' : 'FAILED',
                $report['counts']['critical'],
                $report['counts']['warning'],
                $report['counts']['info'],
            ),
            '',
        ];

        if ([] === $report['findings']) {
            $lines[] = '- Deterministic Kongono checks: **0 findings**.';
        } else {
            foreach ($report['findings'] as $f) {
                $lines[] = sprintf(
                    '- **[%s]** `%s` (`%s:%d`): %s',
                    strtoupper($f['severity']),
                    $f['rule'],
                    $f['file'],
                    $f['line'],
                    $f['message'],
                );
            }
        }

        if (null !== $report['ai_review'] && '' !== trim($report['ai_review'])) {
            $lines[] = '';
            $lines[] = '### AI Architectural Review';
            $lines[] = $report['ai_review'];
        }

        if (null !== $report['telemetry']) {
            $lines[] = '';
            $lines[] = $report['telemetry']->formatFooter();
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Format a `reviewDiff()` report as SARIF 2.1.0 JSON for GitHub Code Scanning / CI integration.
     *
     * @param array{
     *     passed: bool,
     *     counts: array{critical: int, warning: int, info: int},
     *     findings: list<array{severity: 'critical'|'warning'|'info', rule: string, file: string, line: int, message: string}>,
     *     ai_review: ?string,
     *     telemetry: ?CompanionTelemetry
     * } $report
     */
    public static function formatReviewSarif(array $report): string
    {
        $results = [];
        foreach ($report['findings'] as $f) {
            $level = match ($f['severity']) {
                'critical' => 'error',
                'warning' => 'warning',
                'info' => 'note',
            };
            $results[] = [
                'ruleId' => $f['rule'],
                'level' => $level,
                'message' => ['text' => $f['message']],
                'locations' => [[
                    'physicalLocation' => [
                        'artifactLocation' => ['uri' => $f['file']],
                        'region' => ['startLine' => max(1, $f['line'])],
                    ],
                ]],
            ];
        }

        $sarif = [
            '$schema' => 'https://json.schemastore.org/sarif-2.1.0.json',
            'version' => '2.1.0',
            'runs' => [[
                'tool' => [
                    'driver' => [
                        'name' => 'Kongono Hantei',
                        'version' => '1.22.0',
                        'informationUri' => 'https://github.com/kongono/framework',
                    ],
                ],
                'results' => $results,
            ]],
        ];

        return json_encode($sarif, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    /**
     * @param list<array{severity: 'critical'|'warning'|'info', rule: string, file: string, line: int, message: string}> $findings
     */
    private function scanAddedDiffLine(string $file, int $line, string $content, array &$findings): void
    {
        $isPhp = str_ends_with(strtolower($file), '.php');
        $isTest = str_starts_with(strtolower($file), 'tests/') || str_contains(strtolower($file), '/tests/');

        // Hardcoded secret pattern in any added line
        if (SecretScrubber::scrub($content) !== $content && !$isTest) {
            $findings[] = [
                'severity' => 'critical',
                'rule' => 'KGN-REV-SECRET-LEAK',
                'file' => $file,
                'line' => $line,
                'message' => 'Added line appears to contain a hardcoded API token or secret credential.',
            ];
        }

        if (!$isPhp) {
            return;
        }

        // Mutable static property (FrankenPHP / persistent-runtime state leak hazard)
        if (!$isTest && 1 === preg_match('/\b(?:public|protected|private)\s+static\s+(?!readonly\b)(?:[?\\\\a-zA-Z0-9_|]+\s+)?\$[a-zA-Z_]/', $content)) {
            $findings[] = [
                'severity' => 'critical',
                'rule' => 'KGN-REV-MUTABLE-STATIC',
                'file' => $file,
                'line' => $line,
                'message' => 'Mutable static property breaks persistent-runtime isolation (FrankenPHP/RoadRunner). Store state in scoped services instead.',
            ];
        }

        // Raw PHP superglobal usage outside bootstrap/entry
        if (!$isTest && 1 === preg_match('/\$_(?:GET|POST|REQUEST|SESSION|COOKIE)\b/', $content)) {
            $findings[] = [
                'severity' => 'warning',
                'rule' => 'KGN-REV-SUPERGLOBAL',
                'file' => $file,
                'line' => $line,
                'message' => 'Avoid raw PHP superglobals; inject Kongono\\Http\\Request, Session, or validated readonly DTOs instead.',
            ];
        }

        // Legacy Laravel/Symfony API creep
        if (1 === preg_match('/\b(?:Illuminate\\\\|Symfony\\\\Component\\\\|Route::get\(|Auth::user\(|DB::table\()/', $content)) {
            $findings[] = [
                'severity' => 'critical',
                'rule' => 'KGN-REV-LEGACY-API',
                'file' => $file,
                'line' => $line,
                'message' => 'Forbidden Laravel/Symfony API detected; use native Kongono PHP 8.5 APIs.',
            ];
        }
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
