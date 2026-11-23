<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\Security\PromptGuard;
use Kongono\Ai\Security\UntrustedInput;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\SecretScrubber;

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
        $safeFailure = SecretScrubber::scrub($failureText);
        if (null === $this->ai) {
            return "Hantei AI diagnosis requires an AI provider (OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST).\n"
                . "Scrubbed diagnostic captured:\n" . substr($safeFailure, 0, 1000);
        }

        $guard = new PromptGuard();
        $protectedSystem = $guard->buildProtectedSystemPrompt(self::DEBUG_SYSTEM_PROMPT);

        $prompt = sprintf(
            "Project context (untrusted repository metadata):\n%s\n\nFailure / Diagnostic output (secret-scrubbed untrusted log):\n%s",
            $guard->wrapUntrusted(new UntrustedInput($context->promptSummary(), source: 'project_summary')),
            $guard->wrapUntrusted(new UntrustedInput(substr($safeFailure, 0, 8000), source: 'failure_log')),
        );

        $reply = $this->ai->ask($prompt, $protectedSystem['prompt'], temperature: 0.2);
        $inspected = $guard->inspectOutput($reply, $protectedSystem['canary']);
        if (!$inspected['safe']) {
            throw new CoreException('Hantei output blocked by PromptGuard due to suspected prompt injection or canary exfiltration.');
        }

        return $inspected['output'];
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
