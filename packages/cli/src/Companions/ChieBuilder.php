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
use Kongono\Ai\ToolDefinition;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Core\CoreException;

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

You can inspect the project's files and live ApplicationGraph via tools before generating code.
When you are ready with your final answer, respond ONLY with a valid JSON object matching this exact structure:
{
  "summary": "Brief description of what you built",
  "changes": [
    {
      "path": "relative/path/from/project/root.php",
      "action": "create|update",
      "explanation": "Why this file is created or updated",
      "content": "Full file contents (valid PHP 8.5 with declare(strict_types=1); or .kgn.html)"
    }
  ],
  "next_steps": ["Optional reminder such as registering a route in app/bootstrap.php"]
}

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
     *     model: string
     * }
     */
    public function build(string $instruction, ProjectContext $context, bool $apply = false): array
    {
        $principal = new AgentPrincipal(
            id: 'chie-builder',
            role: 'Kongono Project Builder',
            allowedTools: ['list_files', 'read_file', 'inspect_graph'],
            maxSteps: 6,
            maxToolCalls: 8,
        );

        $tools = [
            new ToolDefinition('list_files', 'List relative file paths in the Kongono project.'),
            new ToolDefinition('inspect_graph', 'Inspect the live ApplicationGraph JSON (routes, services, middleware, views).'),
            new ToolDefinition(
                'read_file',
                'Read a secret-scrubbed file from the project.',
                [
                    'type' => 'object',
                    'properties' => [
                        'path' => ['type' => 'string', 'description' => 'Relative file path from project root'],
                    ],
                    'required' => ['path'],
                ],
            ),
        ];

        $guard = new PromptGuard();
        $protectedSystem = $guard->buildProtectedSystemPrompt(self::SYSTEM_PROMPT);

        $messages = [
            Message::system($protectedSystem['prompt']),
            Message::user(sprintf(
                "Current project summary (untrusted repository contents):\n%s\n\nBuild task:\n%s",
                $guard->wrapUntrusted(new UntrustedInput($context->promptSummary(), source: 'project_summary')),
                $instruction,
            )),
        ];

        $runner = new AgentRunner($this->ai, $principal);
        $receipt = $runner->run($messages, $tools, static function (ToolCall $call) use ($context): string {
            return match ($call->name) {
                'list_files' => implode("\n", $context->listFiles()),
                'inspect_graph' => null !== $context->graph ? $context->graph->toJson() : '{"graph":null}',
                'read_file' => is_string($call->arguments['path'] ?? null)
                    ? $context->readFileSafe($call->arguments['path'])
                    : 'Error: missing string "path" argument.',
                default => 'Error: unknown tool.',
            };
        });

        $inspected = $guard->inspectOutput($receipt->finalText, $protectedSystem['canary']);
        if (!$inspected['safe']) {
            throw new CoreException('Chie output blocked by PromptGuard due to suspected prompt injection or canary exfiltration.');
        }

        $plan = $this->parsePlan($inspected['output']);
        $applied = [];

        if ($apply) {
            // Validate all paths & PHP syntax upfront so --apply never leaves partially applied broken files
            foreach ($plan['changes'] as $change) {
                self::assertAllowedChieWriteTarget($change['path']);
                if (str_ends_with(strtolower($change['path']), '.php')) {
                    self::assertValidPhpSyntax($change['path'], $change['content']);
                }
            }

            foreach ($plan['changes'] as $change) {
                $context->writeFileContained($change['path'], $change['content']);
                $applied[] = $change['path'];
            }
        }

        return [
            'summary' => $plan['summary'],
            'changes' => $plan['changes'],
            'next_steps' => $plan['next_steps'],
            'applied' => $applied,
            'provider' => $receipt->provider,
            'model' => $receipt->model,
        ];
    }

    /**
     * @return array{
     *     summary: string,
     *     changes: list<array{path: string, action: string, explanation: string, content: string}>,
     *     next_steps: list<string>
     * }
     */
    private function parsePlan(string $raw): array
    {
        $decoded = self::decodeJsonObjectResilient($raw);

        $changes = [];
        if (isset($decoded['changes']) && is_array($decoded['changes'])) {
            foreach ($decoded['changes'] as $item) {
                if (!is_array($item) || !is_string($item['path'] ?? null) || !is_string($item['content'] ?? null)) {
                    continue;
                }
                $changes[] = [
                    'path' => $item['path'],
                    'action' => is_string($item['action'] ?? null) ? $item['action'] : 'create',
                    'explanation' => is_string($item['explanation'] ?? null) ? $item['explanation'] : '',
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
