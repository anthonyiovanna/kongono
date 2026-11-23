<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

use JsonException;
use Kongono\Ai\Agent\AgentPrincipal;
use Kongono\Ai\Agent\AgentRunner;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\Message;
use Kongono\Ai\Security\PromptGuard;
use Kongono\Ai\Security\UntrustedInput;
use Kongono\Ai\ToolCall;
use Kongono\Cli\Audit\SecurityAuditor;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Database\MigrationLinter;
use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\SecretScrubber;
use Throwable;

/**
 * Chie (知恵) — Kongono's built-in project-aware AI builder & scaffolder.
 *
 * Uses a governed AgentPrincipal to inspect the live ApplicationGraph and project files,
 * then proposes clean PHP 8.5 Kongono code changes (previewed by default; syntax-verified and written only with --apply).
 */
final readonly class ChieBuilder
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
You are Chie (知恵), the built-in AI code builder for Kongono v1.18+ (pure PHP 8.5.13+, zero Symfony, zero mutable static state, zero global magic facades).

You can inspect the project's files, code search matches, live ApplicationGraph, and live DB schema via tools before generating code.
When you are ready with your final answer, respond ONLY with a valid JSON object matching this exact structure:
{
  "summary": "Brief description of what you built",
  "changes": [
    {
      "path": "relative/path/from/project/root.php",
      "action": "create|update|patch",
      "explanation": "Why this file is created, updated, or patched",
      "content": "Full file contents (required for create/update; omit for patch)",
      "edits": [
        {"search": "exact unique existing code block", "replace": "replacement code block"}
      ]
    }
  ],
  "next_steps": ["Optional reminder such as registering a route in app/bootstrap.php"]
}
Prefer `"action": "patch"` with minimal exact `"edits"` when editing existing files so unrelated code is never dropped. Use `"action": "create"` with full `"content"` for new files.

Exact Kongono v1.18+ rules & namespaces:
- Always start every PHP file with `<?php` and `declare(strict_types=1);`. Use `final readonly class`, backed enums, and native attributes.
- Never invent Laravel or Symfony APIs (`Route::get`, `Auth::user()`, `DB::table`, `Http::fake`, `Carbon::setTestNow`, ActiveRecord magic, or `$_SESSION`/`$_POST`/`$_GET`).
- Routing: `Kongono\Http\Routing\{Prefix, Get, Post, Put, Patch, Delete, Middleware, CacheControl, Idempotent, Feature}`, `Kongono\Http\{Request, Response}` (`Response::json/html/fragment/page/adaptive/download/fromStorage/sse`).
- Auth & Security: `Kongono\Security\{Principal, Gate}` (`Gate::define()`, `policy()`, `before()`), `Kongono\Security\Attributes\{Authenticated, Can}`, `Kongono\Security\Auth\{SessionAuth, PersonalAccessToken, LoginThrottler, PasswordResetBroker, EmailVerifier, RememberMeManager}`, `Kongono\Http\Session\{Session, CacheSessionStore}`, `Kongono\Security\Egress\SafeHttpClient`.
- Validation: `Kongono\Validation\Attributes\{Required, Min, Max, Between, Email, Url, Uuid, Date, Ip, In, NotIn, Regex, Confirmed, RequiredIf, ArrayOf}`, `RuleInterface`, `ValidatableDtoInterface`.
- Database: `Kongono\Database\{ConnectionInterface, PdoConnection, ConnectionManager, ReadWriteConnection, QueryBuilder, BatchLoader}`, `Kongono\Database\Schema\{Schema, Blueprint}`, `Kongono\Database\Testing\{DatabaseTestHelper, Factory}`, `Kongono\Database\Search\PostgresFullTextSearch`.
- Views & Live 2.5 / Bridge: `Kongono\View\ViewEngine` (`{{ $escaped }}`, `@safeHtml($html)`, `@old('field')`, `@error('field')`, `@liveScript`, `@island`, `kgn-post`/`kgn-target`/`kgn-swap="morph"`).
- Testing: `Kongono\Http\Testing\{TestClient, TestUpload}` (`actingAs`, `withStatefulCookies`, `withBearerToken`, `postMultipart`), `Kongono\Core\Clock\FrozenClock`, `SafeHttpClient::fake()`.
- Always return valid JSON with no markdown code fences around it.
PROMPT;

    public function __construct(
        private AiClient $ai,
    ) {}

    /**
     * Plan (and optionally apply) a code generation task in the target Kongono project.
     *
     * @return array{
     *     summary: string,
     *     changes: list<array{path: string, action: string, explanation: string, content: string}>,
     *     next_steps: list<string>,
     *     applied: list<string>,
     *     provider: string,
     *     model: string,
     *     telemetry: CompanionTelemetry
     * }
     */
    public function build(
        string $instruction,
        ProjectContext $context,
        bool $apply = false,
        ?float $maxCostUsd = null,
        ?string $previousContext = null,
    ): array {
        $cap = $maxCostUsd ?? CompanionTelemetry::maxCostFromEnv();
        $principal = new AgentPrincipal(
            id: 'chie-builder',
            role: 'Kongono Project Builder',
            allowedTools: CompanionTools::NAMES,
            maxSteps: 8,
            maxToolCalls: 14,
            maxCostUsd: 'ollama' === strtolower($this->ai->providerName()) ? 0.0 : max(0.0, $cap),
        );

        $tools = CompanionTools::definitions();
        $guard = new PromptGuard();
        $protectedSystem = $guard->buildProtectedSystemPrompt(self::SYSTEM_PROMPT);

        $userParts = [
            "Current project summary (untrusted repository contents):\n"
                . $guard->wrapUntrusted(new UntrustedInput($context->promptSummary(), source: 'project_summary')),
        ];
        if (null !== $previousContext && '' !== trim($previousContext)) {
            $userParts[] = "Previous Chie session context (untrusted prior plan):\n"
                . $guard->wrapUntrusted(new UntrustedInput(substr(SecretScrubber::scrub($previousContext), 0, 6000), source: 'prior_session'));
        }
        $userParts[] = "Build task:\n" . $instruction;

        $messages = [
            Message::system($protectedSystem['prompt']),
            Message::user(implode("\n\n", $userParts)),
        ];

        $runner = new AgentRunner($this->ai, $principal);
        $start = hrtime(true);
        $receipt = $runner->run(
            $messages,
            $tools,
            static fn(ToolCall $call): string => CompanionTools::execute($call, $context),
        );
        $durationMs = (hrtime(true) - $start) / 1_000_000;

        $telemetry = CompanionTelemetry::fromUsage($receipt->provider, $receipt->model, $receipt->usage, $durationMs, $cap);
        $telemetry->assertWithinBudget('Chie');

        $inspected = $guard->inspectOutput($receipt->finalText, $protectedSystem['canary']);
        if (!$inspected['safe']) {
            throw new CoreException('Chie output blocked by PromptGuard due to suspected prompt injection or canary exfiltration.');
        }

        $plan = $this->parsePlan($inspected['output'], $context);
        $applied = $apply ? $this->applyChanges($plan['changes'], $context) : [];

        return [
            'summary' => $plan['summary'],
            'changes' => $plan['changes'],
            'next_steps' => $plan['next_steps'],
            'applied' => $applied,
            'provider' => $receipt->provider,
            'model' => $receipt->model,
            'telemetry' => $telemetry,
        ];
    }

    /**
     * Validate and atomically apply all (or a selected subset of) planned changes with rollback on write failure.
     *
     * @param list<array{path: string, action: string, explanation: string, content: string}> $changes
     * @param list<string>|null                                                               $selectedPaths
     * @return list<string>
     */
    public function applyChanges(array $changes, ProjectContext $context, ?array $selectedPaths = null): array
    {
        $filtered = [];
        foreach ($changes as $change) {
            if (null !== $selectedPaths && !in_array($change['path'], $selectedPaths, true)) {
                continue;
            }
            self::assertAllowedChieWriteTarget($change['path']);
            if (str_ends_with(strtolower($change['path']), '.php')) {
                self::assertValidPhpSyntax($change['path'], $change['content']);
            }
            $filtered[] = $change;
        }

        $snapshots = self::captureSnapshots($filtered, $context);
        $applied = [];

        try {
            foreach ($filtered as $change) {
                $context->writeFileContained($change['path'], $change['content']);
                $applied[] = $change['path'];
            }
        } catch (Throwable $e) {
            self::restoreSnapshots($snapshots, $context);
            throw $e;
        }

        return $applied;
    }

    /**
     * Self-healing Chie -> Hantei multi-gate verification loop (`kongo chie "..." --apply --verify`):
     * generates code, validates PHP syntax, applies changes, runs the primary verification command (e.g. PHPUnit),
     * any extra quality gates (e.g. PHPStan / PHP-CS-Fixer), `MigrationLinter` when migrations change, and
     * `SecurityAuditor` critical checks — feeding scrubbed failures back to Chie for bounded repair.
     *
     * @param list<string>                                            $verifyCommand
     * @param list<array{name: string, command: list<string>}>        $extraVerifyCommands
     * @return array{
     *     summary: string,
     *     changes: list<array{path: string, action: string, explanation: string, content: string}>,
     *     next_steps: list<string>,
     *     applied: list<string>,
     *     verified: bool,
     *     rolled_back: bool,
     *     attempts: int,
     *     verify_output: string,
     *     gates: list<array{name: string, passed: bool}>,
     *     provider: string,
     *     model: string,
     *     telemetry: CompanionTelemetry
     * }
     */
    public function buildAndVerify(
        string $instruction,
        ProjectContext $context,
        HanteiDebugger $hantei,
        array $verifyCommand,
        int $maxRepairs = 2,
        ?float $maxCostUsd = null,
        bool $rollbackOnFailure = false,
        array $extraVerifyCommands = [],
        bool $runSecurityAudit = true,
        ?string $previousContext = null,
    ): array {
        $cap = $maxCostUsd ?? CompanionTelemetry::maxCostFromEnv();
        $maxAttempts = 1 + max(0, $maxRepairs);
        $currentInstruction = $instruction;
        /** @var array<string, ?string> $snapshots */
        $snapshots = [];
        $allApplied = [];
        $totalTelemetry = null;
        $verified = false;
        $rolledBack = false;
        $lastVerifyOutput = '';
        /** @var list<array{name: string, passed: bool}> $lastGates */
        $lastGates = [];
        $attempts = 0;
        $lastPlan = null;

        for ($attempt = 1; $attempt <= $maxAttempts; ++$attempt) {
            $remainingCap = $cap > 0.0 && null !== $totalTelemetry
                ? max(0.0, $cap - $totalTelemetry->estimatedCostUsd)
                : $cap;
            if ($attempt > 1 && $cap > 0.0 && $remainingCap <= 0.0) {
                break;
            }

            ++$attempts;
            $plan = $this->build(
                $currentInstruction,
                $context,
                apply: false,
                maxCostUsd: $remainingCap,
                previousContext: 1 === $attempt ? $previousContext : null,
            );
            $lastPlan = $plan;
            $totalTelemetry = null === $totalTelemetry
                ? $plan['telemetry']
                : $totalTelemetry->merge($plan['telemetry']);
            $totalTelemetry->assertWithinBudget('Chie');

            foreach (self::captureSnapshots($plan['changes'], $context) as $path => $before) {
                if (!array_key_exists($path, $snapshots)) {
                    $snapshots[$path] = $before;
                }
            }

            /** @var list<array{name: string, passed: bool}> $gates */
            $gates = [];
            try {
                $appliedNow = $this->applyChanges($plan['changes'], $context);
                $allApplied = array_values(array_unique([...$allApplied, ...$appliedNow]));
                $gates[] = ['name' => 'syntax', 'passed' => true];
            } catch (CoreException $e) {
                $gates[] = ['name' => 'syntax', 'passed' => false];
                $lastGates = $gates;
                $lastVerifyOutput = SecretScrubber::scrub($e->getMessage());
                if ($attempt < $maxAttempts) {
                    $currentInstruction = self::buildRepairInstruction($instruction, $lastVerifyOutput, $attempt);
                    continue;
                }
                break;
            }

            $verifyOutputs = [];
            $verifyPassed = true;

            // Extra quality gates first (e.g. PHPStan Level 9 / PHP-CS-Fixer)
            foreach ($extraVerifyCommands as $extra) {
                $extraRes = $hantei->runCheck($extra['command'], $context, diagnoseWithAi: false);
                $ok = 0 === $extraRes['exit_code'];
                $gates[] = ['name' => $extra['name'], 'passed' => $ok];
                if (!$ok) {
                    $verifyPassed = false;
                    $verifyOutputs[] = sprintf("--- Gate [%s] FAILED ---\n%s", $extra['name'], $extraRes['output']);
                }
            }

            // Primary test gate (PHPUnit / custom check)
            $check = $hantei->runCheck($verifyCommand, $context, diagnoseWithAi: false);
            $primaryOk = 0 === $check['exit_code'];
            $gates[] = ['name' => 'tests', 'passed' => $primaryOk];
            if (!$primaryOk) {
                $verifyPassed = false;
                $verifyOutputs[] = $check['output'];
            } elseif ('' !== trim($check['output'])) {
                $verifyOutputs[] = $check['output'];
            }

            // Zero-downtime MigrationLinter gate whenever migrations/ changed
            $touchedMigration = false;
            foreach ($appliedNow as $path) {
                if (str_starts_with(ltrim(str_replace('\\', '/', $path), '/'), 'migrations/')) {
                    $touchedMigration = true;
                    break;
                }
            }
            if ($touchedMigration) {
                $lintReport = (new MigrationLinter())->lintDirectory($context->rootPath . DIRECTORY_SEPARATOR . 'migrations');
                $gates[] = ['name' => 'migration-lint', 'passed' => $lintReport['passed']];
                if (!$lintReport['passed']) {
                    $verifyPassed = false;
                    $lintLines = array_map(
                        static fn(array $f): string => sprintf('[%s] %s: %s', strtoupper($f['severity']), $f['file'], $f['message']),
                        $lintReport['findings'],
                    );
                    $verifyOutputs[] = "Migration lint errors:\n" . implode("\n", $lintLines);
                }
            }

            // SecurityAuditor critical-finding gate (blocks raw HTML XSS, secrets, Symfony leakage, etc.)
            if ($runSecurityAudit) {
                $auditReport = (new SecurityAuditor())->audit($context->rootPath, $context->app, attack: false);
                $gates[] = ['name' => 'security-audit', 'passed' => $auditReport['passed']];
                if (!$auditReport['passed']) {
                    $verifyPassed = false;
                    $critLines = [];
                    foreach ($auditReport['findings'] as $f) {
                        if ('critical' === $f['severity']) {
                            $critLines[] = sprintf('[CRITICAL] %s (%s): %s', $f['code'], $f['location'], $f['message']);
                        }
                    }
                    $verifyOutputs[] = "Security audit critical findings:\n" . implode("\n", $critLines);
                }
            }

            $lastGates = $gates;
            $lastVerifyOutput = SecretScrubber::scrub(trim(implode("\n\n", array_filter($verifyOutputs, static fn(string $s): bool => '' !== trim($s)))));
            if ($verifyPassed) {
                $verified = true;
                break;
            }

            if ($attempt < $maxAttempts) {
                $currentInstruction = self::buildRepairInstruction($instruction, $lastVerifyOutput, $attempt);
            }
        }

        if (!$verified && $rollbackOnFailure && [] !== $snapshots) {
            self::restoreSnapshots($snapshots, $context);
            $rolledBack = true;
        }

        if (null === $lastPlan) {
            throw new CoreException('Chie verification loop produced no build plan.');
        }

        return [
            'summary' => $lastPlan['summary'],
            'changes' => $lastPlan['changes'],
            'next_steps' => $lastPlan['next_steps'],
            'applied' => $allApplied,
            'verified' => $verified,
            'rolled_back' => $rolledBack,
            'attempts' => $attempts,
            'verify_output' => $lastVerifyOutput,
            'gates' => $lastGates,
            'provider' => $lastPlan['provider'],
            'model' => $lastPlan['model'],
            'telemetry' => $totalTelemetry,
        ];
    }

    private static function buildRepairInstruction(string $originalInstruction, string $scrubbedFailure, int $attempt): string
    {
        return sprintf(
            "Original build task:\n%s\n\nVerification attempt #%d failed with the following secret-scrubbed output:\n%s\n\nFix the root cause cleanly while preserving all Kongono PHP 8.5 strict conventions.",
            $originalInstruction,
            $attempt,
            substr($scrubbedFailure, 0, 6000),
        );
    }

    /**
     * @param list<array{path: string, action: string, explanation: string, content: string}> $changes
     * @return array<string, ?string>
     */
    private static function captureSnapshots(array $changes, ProjectContext $context): array
    {
        $snapshots = [];
        foreach ($changes as $change) {
            $path = $change['path'];
            $full = $context->rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim(str_replace('\\', '/', $path), '/'));
            if (is_file($full) && !is_link($full)) {
                $raw = file_get_contents($full);
                $snapshots[$path] = false !== $raw ? $raw : null;
            } else {
                $snapshots[$path] = null;
            }
        }

        return $snapshots;
    }

    /** @param array<string, ?string> $snapshots */
    private static function restoreSnapshots(array $snapshots, ProjectContext $context): void
    {
        foreach ($snapshots as $path => $previous) {
            try {
                self::assertAllowedChieWriteTarget($path);
            } catch (CoreException) {
                continue;
            }
            $full = $context->rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim(str_replace('\\', '/', $path), '/'));
            if (null === $previous) {
                if (is_file($full) && !is_link($full)) {
                    @unlink($full);
                }
            } else {
                $context->writeFileContained($path, $previous);
            }
        }
    }

    /**
     * Apply ordered exact search-and-replace edits to an existing file's content.
     * Each search block must match uniquely (count === 1) so ambiguous or stale patches fail closed.
     *
     * @param list<array{search: string, replace: string}> $edits
     */
    public static function applyPatchEdits(string $original, array $edits, string $path): string
    {
        if ([] === $edits) {
            throw new CoreException(sprintf('Chie patch for "%s" supplied zero edits.', $path));
        }

        $working = $original;
        foreach ($edits as $idx => $edit) {
            $search = $edit['search'];
            $replace = $edit['replace'];
            if ('' === $search) {
                throw new CoreException(sprintf('Chie patch edit #%d for "%s" has an empty search block.', $idx + 1, $path));
            }

            $occurrences = substr_count($working, $search);
            if (0 === $occurrences) {
                // Retry with normalized LF line endings for cross-platform CRLF files
                $normWorking = str_replace("\r\n", "\n", $working);
                $normSearch = str_replace("\r\n", "\n", $search);
                $occurrences = substr_count($normWorking, $normSearch);
                if (1 === $occurrences) {
                    $working = $normWorking;
                    $search = $normSearch;
                    $replace = str_replace("\r\n", "\n", $replace);
                }
            }

            if (0 === $occurrences) {
                throw new CoreException(sprintf('Chie patch edit #%d for "%s" failed: search block not found in existing file.', $idx + 1, $path));
            }
            if ($occurrences > 1) {
                throw new CoreException(sprintf('Chie patch edit #%d for "%s" failed: search block matched %d times (must match uniquely).', $idx + 1, $path, $occurrences));
            }

            $pos = strpos($working, $search);
            if (false === $pos) {
                throw new CoreException(sprintf('Chie patch edit #%d for "%s" failed: search block not found.', $idx + 1, $path));
            }
            $working = substr_replace($working, $replace, $pos, strlen($search));
        }

        return $working;
    }

    /**
     * @return array{
     *     summary: string,
     *     changes: list<array{path: string, action: string, explanation: string, content: string}>,
     *     next_steps: list<string>
     * }
     */
    private function parsePlan(string $raw, ProjectContext $context): array
    {
        $decoded = self::decodeJsonObjectResilient($raw);

        $changes = [];
        if (isset($decoded['changes']) && is_array($decoded['changes'])) {
            foreach ($decoded['changes'] as $item) {
                if (!is_array($item) || !is_string($item['path'] ?? null)) {
                    continue;
                }
                $path = $item['path'];
                $action = is_string($item['action'] ?? null) ? strtolower(trim($item['action'])) : 'create';
                $explanation = is_string($item['explanation'] ?? null) ? $item['explanation'] : '';

                if ('patch' === $action && is_array($item['edits'] ?? null)) {
                    /** @var list<array{search: string, replace: string}> $edits */
                    $edits = [];
                    foreach ($item['edits'] as $rawEdit) {
                        if (is_array($rawEdit) && is_string($rawEdit['search'] ?? null) && is_string($rawEdit['replace'] ?? null)) {
                            $edits[] = [
                                'search' => $rawEdit['search'],
                                'replace' => $rawEdit['replace'],
                            ];
                        }
                    }
                    $existing = $this->readRawTargetForPatch($path, $context);
                    $patched = self::applyPatchEdits($existing, $edits, $path);
                    $changes[] = [
                        'path' => $path,
                        'action' => 'patch',
                        'explanation' => $explanation,
                        'content' => $patched,
                    ];
                    continue;
                }

                if (!is_string($item['content'] ?? null)) {
                    continue;
                }
                $changes[] = [
                    'path' => $path,
                    'action' => '' !== $action ? $action : 'create',
                    'explanation' => $explanation,
                    'content' => $item['content'],
                ];
            }
        }

        $nextSteps = [];
        if (isset($decoded['next_steps']) && is_array($decoded['next_steps'])) {
            foreach ($decoded['next_steps'] as $step) {
                if (is_string($step)) {
                    $nextSteps[] = $step;
                }
            }
        }

        return [
            'summary' => is_string($decoded['summary'] ?? null) ? $decoded['summary'] : 'Generated build plan.',
            'changes' => $changes,
            'next_steps' => $nextSteps,
        ];
    }

    private function readRawTargetForPatch(string $relativePath, ProjectContext $context): string
    {
        self::assertAllowedChieWriteTarget($relativePath);
        // Validate containment & non-symlink safety via ProjectContext::readFileSafe() first
        $context->readFileSafe($relativePath);

        $normalized = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        $full = $context->rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
        $raw = file_get_contents($full);
        if (false === $raw) {
            throw new CoreException(sprintf('Unable to read "%s" for patching.', $relativePath));
        }

        return $raw;
    }

    /**
     * Decode a JSON object even when a real LLM adds conversational prose or markdown fences around it.
     *
     * @return array<mixed>
     */
    private static function decodeJsonObjectResilient(string $raw): array
    {
        $candidates = [trim($raw)];

        // 1. Extract fenced ```json ... ``` or ``` ... ``` blocks anywhere in the response
        if (1 === preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $raw, $m)) {
            $candidates[] = trim($m[1]);
        }

        // 2. Extract outermost balanced JSON object { ... } respecting JSON string literals
        $balanced = self::extractFirstJsonObject($raw);
        if (null !== $balanced) {
            $candidates[] = $balanced;
        }

        $lastError = null;
        foreach (array_unique($candidates) as $candidate) {
            if ('' === $candidate) {
                continue;
            }
            try {
                $decoded = json_decode($candidate, true, 64, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (JsonException $e) {
                $lastError = $e;
            }
        }

        throw new AiException('Chie received non-JSON build plan from model.', previous: $lastError);
    }

    private static function extractFirstJsonObject(string $text): ?string
    {
        $start = strpos($text, '{');
        if (false === $start) {
            return null;
        }

        $len = strlen($text);
        $depth = 0;
        $inString = false;
        $escaped = false;

        for ($i = $start; $i < $len; ++$i) {
            $ch = $text[$i];
            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ('\\' === $ch) {
                    $escaped = true;
                } elseif ('"' === $ch) {
                    $inString = false;
                }
                continue;
            }

            if ('"' === $ch) {
                $inString = true;
            } elseif ('{' === $ch) {
                ++$depth;
            } elseif ('}' === $ch) {
                --$depth;
                if (0 === $depth) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    /**
     * Restrict AI-generated `--apply` writes strictly to application source/view/test/migration directories
     * and safe file extensions so prompt injection can never overwrite bootstrap, CI, Composer, or deploy scripts.
     */
    private static function assertAllowedChieWriteTarget(string $relativePath): void
    {
        $normalized = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        if (
            str_contains($normalized, '..')
            || str_contains($normalized, "\0")
            || 1 !== preg_match('#\A(?:src|views|tests|migrations|seeders|public/assets)/[a-zA-Z0-9._/\-]+\.(?:php|kgn\.html|css|js|json)\z#', $normalized)
        ) {
            throw new CoreException(sprintf(
                'Chie refused to write "%s": AI --apply is restricted to src/, views/, tests/, migrations/, seeders/, or public/assets/.',
                $relativePath,
            ));
        }
    }

    /** Validate generated PHP code via `php -l` over stdin before writing to disk. */
    public static function assertValidPhpSyntax(string $relativePath, string $phpCode): void
    {
        $clean = ltrim($phpCode, "\xEF\xBB\xBF");
        $proc = proc_open([PHP_BINARY, '-l'], [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($proc)) {
            throw new CoreException(sprintf('Unable to run PHP syntax check for "%s".', $relativePath));
        }

        fwrite($pipes[0], $clean);
        fclose($pipes[0]);
        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        if (0 !== $exit) {
            throw new CoreException(sprintf(
                'Refused to write "%s" because generated PHP failed syntax validation: %s',
                $relativePath,
                trim($stderr . ' ' . $stdout),
            ));
        }
    }
}
