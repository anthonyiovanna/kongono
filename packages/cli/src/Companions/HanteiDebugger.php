<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Core\Diagnostics\SecretScrubber;

/**
 * Hantei (判定) — Kongono's built-in AI debugger, failure analyzer, and test generator.
 *
 * Runs real PHPUnit / PHPStan checks, scrubs secrets from logs/diagnostics,
 * explains root causes with exact fixes, and generates runnable PHPUnit 13 tests.
 */
final readonly class HanteiDebugger
{
    private const string DEBUG_SYSTEM_PROMPT = <<<'PROMPT'
You are Hantei (判定), the built-in AI debugger and testing specialist for Kongono v1 (PHP 8.5+, zero Symfony, PHPUnit 13, PHPStan level 9).

Given a failure log, stack trace, or Kongono diagnostic along with the project's live ApplicationGraph:
1. Identify the exact root cause concisely.
2. Point to the specific file/class/line involved.
3. Provide a minimal, strict PHP 8.5 fix.
4. Suggest one regression test to prevent it from recurring.
PROMPT;

    private const string TESTGEN_SYSTEM_PROMPT = <<<'PROMPT'
You are Hantei (判定), the built-in AI test generator for Kongono v1 (PHP 8.5+, PHPUnit 13).

Given a Kongono PHP source file and project context, output ONLY a complete, valid, runnable PHPUnit 13 test class (no markdown fences).
Requirements:
- Start with `<?php` and `declare(strict_types=1);`
- Use `PHPUnit\Framework\TestCase`
- Test happy paths, validation/edge errors, and persistent-runtime isolation where relevant.
- Use `Kongono\Http\Request::create(...)` / `Request::json(...)` for HTTP controller tests.
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
            return "Hantei AI diagnosis requires an AI provider (OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, or OLLAMA_HOST).\n"
                . "Scrubbed diagnostic captured:\n" . substr($safeFailure, 0, 1000);
        }

        $prompt = sprintf(
            "Project context:\n%s\n\nFailure / Diagnostic output (secret-scrubbed):\n%s",
            $context->promptSummary(),
            substr($safeFailure, 0, 8000),
        );

        return $this->ai->ask($prompt, self::DEBUG_SYSTEM_PROMPT, temperature: 0.2);
    }

    /**
     * Generate a runnable PHPUnit 13 test file for a given source file.
     *
     * @return array{target_path: string, code: string, written: bool}
     */
    public function generateTests(string $sourceRelativePath, ProjectContext $context, bool $apply = false): array
    {
        if (null === $this->ai) {
            throw new AiException('Hantei test generation requires an AI provider (set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, or OLLAMA_HOST).');
        }

        $sourceCode = $context->readFileSafe($sourceRelativePath);
        $targetPath = $this->deriveTestPath($sourceRelativePath);

        $prompt = sprintf(
            "Project context:\n%s\n\nTarget test file path: %s\n\nSource file (%s):\n%s",
            $context->promptSummary(),
            $targetPath,
            $sourceRelativePath,
            $sourceCode,
        );

        $generated = trim($this->ai->ask($prompt, self::TESTGEN_SYSTEM_PROMPT, temperature: 0.2));
        if (str_starts_with($generated, '```')) {
            $stripped = preg_replace('/\A```(?:php)?\s*|\s*```\z/', '', $generated);
            if (is_string($stripped)) {
                $generated = trim($stripped);
            }
        }
        $generated .= "\n";

        if ($apply) {
            $context->writeFileContained($targetPath, $generated);
        }

        return [
            'target_path' => $targetPath,
            'code' => $generated,
            'written' => $apply,
        ];
    }

    private function deriveTestPath(string $sourceRelativePath): string
    {
        $normalized = str_replace('\\', '/', $sourceRelativePath);
        $replaced = preg_replace('#\Asrc/#', 'tests/', $normalized);
        $base = is_string($replaced) ? $replaced : 'tests/' . basename($normalized);
        if (str_ends_with($base, '.php') && !str_ends_with($base, 'Test.php')) {
            return substr($base, 0, -4) . 'Test.php';
        }

        return $base;
    }
}
