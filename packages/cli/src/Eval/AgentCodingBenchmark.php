<?php

declare(strict_types=1);

namespace Kongono\Cli\Eval;

use Kongono\Ai\AiClient;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\Eval\EvalReport;
use Kongono\Ai\Eval\EvalResult;
use Kongono\Ai\Message;
use Kongono\Cli\Knowledge\KongonoKnowledgeIndex;
use Kongono\View\TemplateCompiler;
use ParseError;
use PhpToken;
use Throwable;

/**
 * Automated Kongono v1.20 AI Coding Agent Benchmark & Verified Dataset Exporter (`kongo eval --agent-bench`).
 *
 * Tests both RAG retrieval precision (`KongonoKnowledgeIndex`) and code-generation accuracy across
 * 6 realistic Kongono PHP 8.5 tasks, grading every output with PHP 8.5 `TOKEN_PARSE`, `TemplateCompiler`,
 * `PolicyVerifier`, and strict zero-Laravel/Symfony/superglobal rules. Optionally exports verified passing
 * trajectories as ChatML JSONL for future local-model (Ollama LoRA) fine-tuning.
 */
final readonly class AgentCodingBenchmark
{
    /** @var list<string> */
    private const array FORBIDDEN_PHP_MARKERS = [
        'Illuminate\\',
        'Symfony\\Component\\',
        'Route::get(',
        'Route::post(',
        'Auth::user(',
        'DB::table(',
        'Http::fake(',
        'Carbon::setTestNow(',
        'Eloquent',
        '$_GET',
        '$_POST',
        '$_COOKIE',
        '$_SESSION',
    ];

    private const string AGENT_SYSTEM_PROMPT = <<<'PROMPT'
You are an expert Kongono v1.20+ (PHP 8.5.13+) engineer.
Follow these strict rules:
- Output ONLY the complete requested file contents (no markdown prose or explanation).
- For PHP files: start with `<?php` and `declare(strict_types=1);`, use `final readonly class`, native attributes, explicit DI, and zero Laravel/Symfony facades or PHP superglobals.
- For `.kgn.html` views: use `{{ $expr }}`, `@safeHtml(...)`, and strict-CSP Kongono Live 2.5 attributes (`kgn-post`, `kgn-target`, `kgn-swap="morph"`) with zero inline `onclick=` or Blade `{!! !!}` syntax.
PROMPT;

    private KongonoKnowledgeIndex $knowledge;

    public function __construct(?KongonoKnowledgeIndex $knowledge = null)
    {
        $this->knowledge = $knowledge ?? new KongonoKnowledgeIndex();
    }

    /**
     * Run all 6 Kongono agent coding benchmark tasks (offline reference mode when `$ai === null`,
     * or live LLM evaluation when `$ai` is provided).
     */
    public function run(?AiClient $ai = null, ?string $exportJsonlPath = null): EvalReport
    {
        $tasks = $this->tasks();
        $results = [];
        $totalTokens = 0;
        $totalDurationMs = 0.0;
        $passedCount = 0;
        /** @var list<string> $jsonlLines */
        $jsonlLines = [];

        foreach ($tasks as $task) {
            $t0 = hrtime(true);
            $failures = [];
            $tokens = 0;

            // 1. Verify RAG retrieval surfaces the exact expected v1.20 reference markers
            $ragContext = $this->knowledge->formatContextForPrompt($task['prompt'], limit: 3, maxChars: 3800);
            $ragLower = strtolower($ragContext);
            foreach ($task['expected_rag_markers'] as $marker) {
                if (!str_contains($ragLower, strtolower($marker))) {
                    $failures[] = sprintf('RAG retrieval missed expected reference symbol "%s".', $marker);
                }
            }

            // 2. Obtain candidate output (canonical reference offline, or live LLM grounded with RAG)
            $code = $task['reference_code'];
            if (null !== $ai) {
                try {
                    $userPrompt = "Verified Kongono v1.20 Reference:\n" . $ragContext . "\n\nTask:\n" . $task['prompt'];
                    $response = $ai->chat(new ChatRequest([
                        Message::system(self::AGENT_SYSTEM_PROMPT),
                        Message::user($userPrompt),
                    ], temperature: 0.1));
                    $tokens = $response->usage->totalTokens();
                    $totalTokens += $tokens;
                    $code = $this->stripCodeFences($response->text);
                } catch (Throwable $e) {
                    $failures[] = 'AI provider call failed: ' . $e->getMessage();
                    $code = '';
                }
            }

            // 3. Grade generated code deterministically using real Kongono compilers & rules
            if ('' === trim($code)) {
                $failures[] = 'Empty generated output.';
            } else {
                $failures = [...$failures, ...$this->gradeTask($task, $code)];
            }

            $durationMs = (hrtime(true) - $t0) / 1_000_000;
            $totalDurationMs += $durationMs;
            $passed = [] === $failures;
            if ($passed) {
                ++$passedCount;
                if (null !== $exportJsonlPath) {
                    $jsonlLines[] = json_encode([
                        'task' => $task['id'],
                        'messages' => [
                            ['role' => 'system', 'content' => self::AGENT_SYSTEM_PROMPT],
                            ['role' => 'user', 'content' => $task['prompt']],
                            ['role' => 'assistant', 'content' => $code],
                        ],
                    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                }
            }

            $flatExcerpt = trim((string) preg_replace('/\s+/', ' ', trim($code)));
            $results[] = new EvalResult(
                name: $task['id'],
                passed: $passed,
                failures: $failures,
                durationMs: $durationMs,
                totalTokens: $tokens,
                outputExcerpt: substr($flatExcerpt, 0, 96),
            );
        }

        if (null !== $exportJsonlPath && [] !== $jsonlLines) {
            $dir = dirname($exportJsonlPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0o775, true);
            }
            file_put_contents($exportJsonlPath, implode("\n", $jsonlLines) . "\n", LOCK_EX);
        }

        $total = count($results);
        $failedCount = $total - $passedCount;
        $passRate = $total > 0 ? (($passedCount / $total) * 100.0) : 100.0;

        return new EvalReport(
            passed: 0 === $failedCount,
            total: $total,
            passedCount: $passedCount,
            failedCount: $failedCount,
            passRate: $passRate,
            totalTokens: $totalTokens,
            totalDurationMs: $totalDurationMs,
            results: $results,
        );
    }

    /**
     * @param array{
     *     id: string,
     *     kind: 'php'|'policy'|'view',
     *     prompt: string,
     *     expected_rag_markers: list<string>,
     *     required_markers: list<string>,
     *     reference_code: string
     * } $task
     * @return list<string>
     */
    private function gradeTask(array $task, string $code): array
    {
        $failures = [];

        if ('view' === $task['kind']) {
            if (str_contains($code, '{!!') || 1 === preg_match('/\bon[a-z]+\s*=/i', $code)) {
                $failures[] = 'View contains unsafe Blade raw output or inline JS event handler (violates strict CSP / XSS policy).';
            }
            try {
                (new TemplateCompiler())->compile($code);
            } catch (Throwable $e) {
                $failures[] = 'TemplateCompiler rejected .kgn.html view: ' . $e->getMessage();
            }
        } else {
            if (!str_contains($code, 'declare(strict_types=1);')) {
                $failures[] = 'Missing required `declare(strict_types=1);`.';
            }
            foreach (self::FORBIDDEN_PHP_MARKERS as $forbidden) {
                if (str_contains($code, $forbidden)) {
                    $failures[] = sprintf('Forbidden legacy/unsafe construct detected: %s', $forbidden);
                }
            }
            try {
                PhpToken::tokenize($code, TOKEN_PARSE);
            } catch (ParseError $e) {
                $failures[] = 'PHP 8.5 parse error: ' . $e->getMessage();
            }
            if ('policy' === $task['kind'] && [] === $failures) {
                $failures = [...$failures, ...$this->verifyPolicyFile($code)];
            }
        }

        foreach ($task['required_markers'] as $marker) {
            if (!str_contains($code, $marker)) {
                $failures[] = sprintf('Missing required Kongono construct: %s', $marker);
            }
        }

        return $failures;
    }

    /**
     * Statically validates a generated `app/invariants.php` policy file via PHP 8.5 token analysis
     * WITHOUT ever executing or `require`-ing untrusted LLM output in-process.
     *
     * @return list<string>
     */
    private function verifyPolicyFile(string $code): array
    {
        try {
            $tokens = array_values(PhpToken::tokenize($code, TOKEN_PARSE));
        } catch (ParseError $e) {
            return ['Generated app/invariants.php failed PHP 8.5 parse check: ' . $e->getMessage()];
        }

        $allowedStaticMethods = ['routesMatching', 'mutatingRoutes', 'namespace'];
        $allowedChainMethods = [
            'withId',
            'except',
            'methods',
            'mustRequireAuth',
            'mustRequireAuthOrCsrf',
            'mustHaveAttribute',
            'mustBindInputDto',
            'mustHaveRouteMiddleware',
            'mustVerifyWebhookSignature',
            'mustNotDependOn',
            'describe',
        ];

        $hasReturn = false;
        $invariantRules = 0;
        $inUseStatement = false;
        $inDeclare = false;
        $count = count($tokens);

        for ($i = 0; $i < $count; ++$i) {
            $tok = $tokens[$i];
            if ($tok->isIgnorable()) {
                continue;
            }

            if (T_OPEN_TAG === $tok->id) {
                continue;
            }
            if (T_DECLARE === $tok->id) {
                $inDeclare = true;
                continue;
            }
            if ($inDeclare) {
                if (';' === $tok->text) {
                    $inDeclare = false;
                }
                continue;
            }
            if (T_USE === $tok->id) {
                $inUseStatement = true;
                continue;
            }
            if ($inUseStatement) {
                if (';' === $tok->text) {
                    $inUseStatement = false;
                    continue;
                }
                if (T_NAME_QUALIFIED === $tok->id || T_NAME_FULLY_QUALIFIED === $tok->id || T_STRING === $tok->id) {
                    $sym = ltrim($tok->text, '\\');
                    if (str_contains($sym, '\\') && !str_starts_with($sym, 'Kongono\\') && !str_starts_with($sym, 'App\\')) {
                        return [sprintf('Generated app/invariants.php imports disallowed namespace "%s".', $sym)];
                    }
                    continue;
                }
                if (T_AS === $tok->id || ',' === $tok->text || '{' === $tok->text || '}' === $tok->text) {
                    continue;
                }

                return [sprintf('Generated app/invariants.php contains unexpected token "%s" in use declaration.', $tok->text)];
            }

            if (T_RETURN === $tok->id) {
                $hasReturn = true;
                continue;
            }

            // Reject variables, shell backticks, closures, anonymous classes, include/require/eval, and arbitrary calls
            if (in_array($tok->id, [
                T_VARIABLE,
                T_EVAL,
                T_INCLUDE,
                T_INCLUDE_ONCE,
                T_REQUIRE,
                T_REQUIRE_ONCE,
                T_NEW,
                T_FUNCTION,
                T_FN,
                T_CLASS,
                T_TRAIT,
                T_INTERFACE,
                T_ENUM,
                T_GLOBAL,
                T_PRINT,
                T_ECHO,
                T_EXIT,
                T_ENCAPSED_AND_WHITESPACE,
            ], true) || '`' === $tok->text) {
                return [sprintf('Generated app/invariants.php contains forbidden executable construct "%s".', $tok->text)];
            }

            // Allow pure literal / structural punctuation tokens
            if (
                in_array($tok->id, [T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DNUMBER, T_ARRAY, T_DOUBLE_ARROW, T_DOUBLE_COLON, T_OBJECT_OPERATOR], true)
                || in_array($tok->text, ['[', ']', '(', ')', ',', ';'], true)
            ) {
                continue;
            }

            if (T_STRING === $tok->id || T_NAME_QUALIFIED === $tok->id || T_NAME_FULLY_QUALIFIED === $tok->id) {
                $prev = $this->previousNonIgnorableToken($tokens, $i);
                $next = $this->nextNonIgnorableToken($tokens, $i);

                // `true`, `false`, `null`
                if (in_array(strtolower($tok->text), ['true', 'false', 'null'], true)) {
                    continue;
                }

                // Class reference followed by `::` (e.g. `Invariant::routesMatching`, `Authenticated::class`)
                if (null !== $next && T_DOUBLE_COLON === $next->id) {
                    continue;
                }

                // `::class` constant or `Invariant::<allowedStaticMethod>(...)`
                if (null !== $prev && T_DOUBLE_COLON === $prev->id) {
                    if ('class' === strtolower($tok->text)) {
                        continue;
                    }
                    if (in_array($tok->text, $allowedStaticMethods, true) && null !== $next && '(' === $next->text) {
                        ++$invariantRules;
                        continue;
                    }

                    return [sprintf('Generated app/invariants.php invokes disallowed static member "::%s".', $tok->text)];
                }

                // Fluent `->method(...)` chain on `Invariant`
                if (null !== $prev && T_OBJECT_OPERATOR === $prev->id) {
                    if (in_array($tok->text, $allowedChainMethods, true) && null !== $next && '(' === $next->text) {
                        continue;
                    }

                    return [sprintf('Generated app/invariants.php invokes disallowed method "->%s()".', $tok->text)];
                }

                return [sprintf('Generated app/invariants.php contains disallowed symbol or function call "%s".', $tok->text)];
            }

            return [sprintf('Generated app/invariants.php contains disallowed token "%s".', $tok->text)];
        }

        if (!$hasReturn || $invariantRules < 1) {
            return ['Generated app/invariants.php must return at least one `Invariant::...` policy rule.'];
        }

        return [];
    }

    /** @param list<PhpToken> $tokens */
    private function previousNonIgnorableToken(array $tokens, int $index): ?PhpToken
    {
        for ($j = $index - 1; $j >= 0; --$j) {
            if (!$tokens[$j]->isIgnorable()) {
                return $tokens[$j];
            }
        }

        return null;
    }

    /** @param list<PhpToken> $tokens */
    private function nextNonIgnorableToken(array $tokens, int $index): ?PhpToken
    {
        $count = count($tokens);
        for ($j = $index + 1; $j < $count; ++$j) {
            if (!$tokens[$j]->isIgnorable()) {
                return $tokens[$j];
            }
        }

        return null;
    }

    private function stripCodeFences(string $text): string
    {
        $trimmed = trim($text);
        if (1 === preg_match('/\A```[a-zA-Z0-9._-]*\R(.*?)\R```\z/s', $trimmed, $m)) {
            return trim($m[1]);
        }

        return $trimmed;
    }

    /**
     * @return list<array{
     *     id: string,
     *     kind: 'php'|'policy'|'view',
     *     prompt: string,
     *     expected_rag_markers: list<string>,
     *     required_markers: list<string>,
     *     reference_code: string
     * }>
     */
    private function tasks(): array
    {
        return [
            [
                'id' => 'readonly-capability-controller',
                'kind' => 'php',
                'prompt' => 'Write a Kongono v1.20 final readonly controller `NoteController` with `#[Prefix(\'/api/notes\')]` and a `#[Get(\'/\')]` `index()` method annotated with `#[ReadOnlyDb]`, `#[NoOutboundHttp]`, and `#[MaxQueries(3)]` returning `Response::json(...)`.',
                'expected_rag_markers' => ['#[ReadOnlyDb]', '#[MaxQueries', 'Response::json'],
                'required_markers' => ['final readonly class', '#[Prefix(', '#[Get(', '#[ReadOnlyDb]', '#[NoOutboundHttp]', '#[MaxQueries(', 'Response::json('],
                'reference_code' => <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http;

use Kongono\Http\Response;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Prefix;
use Kongono\Security\Attributes\MaxQueries;
use Kongono\Security\Attributes\NoOutboundHttp;
use Kongono\Security\Attributes\ReadOnlyDb;

#[Prefix('/api/notes')]
final readonly class NoteController
{
    #[Get('/', name: 'notes.index')]
    #[ReadOnlyDb]
    #[NoOutboundHttp]
    #[MaxQueries(3)]
    public function index(): Response
    {
        return Response::json(['items' => []]);
    }
}
PHP,
            ],
            [
                'id' => 'typed-dto-validation',
                'kind' => 'php',
                'prompt' => 'Write a Kongono `final readonly class` DTO `CreateArticleInput` implementing `ValidatableDtoInterface` with `#[Required]`, `#[Email]`, `#[Min(3)]`, and `#[ArrayOf(\'string\', min: 1, max: 5)]`.',
                'expected_rag_markers' => ['ValidatableDtoInterface', '#[ArrayOf', 'validate()'],
                'required_markers' => ['final readonly class', 'implements ValidatableDtoInterface', 'function validate(): array', '#[ArrayOf('],
                'reference_code' => <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Dto;

use Kongono\Validation\Attributes\ArrayOf;
use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Required;
use Kongono\Validation\ValidatableDtoInterface;

final readonly class CreateArticleInput implements ValidatableDtoInterface
{
    /** @param list<string> $tags */
    public function __construct(
        #[Required, Min(3)] public string $title,
        #[Required, Email] public string $authorEmail,
        #[ArrayOf('string', min: 1, max: 5)] public array $tags = ['php85'],
    ) {}

    /** @return array<string, string|list<string>> */
    public function validate(): array
    {
        if (str_contains(strtolower($this->title), 'spam')) {
            return ['title' => 'Title cannot contain spam.'];
        }

        return [];
    }
}
PHP,
            ],
            [
                'id' => 'multi-tenant-batch-query',
                'kind' => 'php',
                'prompt' => 'Write a Kongono repository class using `PdoConnection`, `QueryBuilder` with `forTenant($tenant)` and `withoutTrashed()`, plus `BatchLoader::hasMany()` to prevent N+1 queries.',
                'expected_rag_markers' => ['forTenant', 'withoutTrashed', 'BatchLoader::hasMany'],
                'required_markers' => ['final readonly class', 'PdoConnection', '->forTenant(', '->withoutTrashed()', 'BatchLoader::hasMany('],
                'reference_code' => <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Data;

use Kongono\Database\BatchLoader;
use Kongono\Database\PdoConnection;

final readonly class PostRepository
{
    public function __construct(
        private PdoConnection $db,
    ) {}

    /** @return array{posts: list<array<string, mixed>>, comments: array<int|string, list<array<string, mixed>>>} */
    public function listWithComments(string $tenant): array
    {
        $posts = $this->db->table('posts')
            ->forTenant($tenant)
            ->withoutTrashed()
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->get();

        $comments = [] !== $posts ? BatchLoader::hasMany($this->db, $posts, 'comments', 'post_id') : [];

        return ['posts' => $posts, 'comments' => $comments];
    }
}
PHP,
            ],
            [
                'id' => 'executable-policy-invariants',
                'kind' => 'policy',
                'prompt' => 'Write a Kongono `app/invariants.php` policy file using `Kongono\Cli\Policy\Invariant::routesMatching(...)` requiring `Authenticated::class` on `/api/admin/*` and `mustVerifyWebhookSignature()` on `/webhooks/*`.',
                'expected_rag_markers' => ['Invariant::routesMatching', 'mustHaveAttribute', 'mustVerifyWebhookSignature'],
                'required_markers' => ['Invariant::routesMatching(', 'mustHaveAttribute(', 'mustVerifyWebhookSignature('],
                'reference_code' => <<<'PHP'
<?php

declare(strict_types=1);

use Kongono\Cli\Policy\Invariant;
use Kongono\Security\Attributes\Authenticated;

return [
    Invariant::routesMatching('/api/admin/*', 'KGN-POLICY-ADMIN')
        ->mustHaveAttribute(Authenticated::class)
        ->describe('Admin API endpoints require authentication.'),
    Invariant::routesMatching('/webhooks/*', 'KGN-POLICY-WEBHOOK')
        ->mustVerifyWebhookSignature()
        ->describe('Inbound webhooks must verify cryptographic signatures.'),
];
PHP,
            ],
            [
                'id' => 'live-strict-csp-view',
                'kind' => 'view',
                'prompt' => 'Write a Kongono `.kgn.html` view using `@liveScript`, auto-escaped `{{ $title }}`, sanitized `@safeHtml($body)`, and Live 2.5 `kgn-post` + `kgn-target="#notes"` + `kgn-swap="morph"`.',
                'expected_rag_markers' => ['@liveScript', '@safeHtml', 'kgn-swap="morph"'],
                'required_markers' => ['@liveScript', '{{ $title }}', '@safeHtml(', 'kgn-post=', 'kgn-target=', 'kgn-swap="morph"'],
                'reference_code' => <<<'HTML'
<section class="notes-shell">
  <h1>{{ $title }}</h1>
  <article>@safeHtml($body)</article>
  <form kgn-post="/notes" kgn-target="#notes" kgn-swap="morph">
    <input type="text" name="title" value="@old('title')" required>
    <button type="submit">Save Note</button>
  </form>
  <div id="notes"></div>
  @liveScript
</section>
HTML,
            ],
            [
                'id' => 'global-free-phpunit-test',
                'kind' => 'php',
                'prompt' => 'Write a global-free PHPUnit 13 test using Kongono `TestClient`, `FrozenClock::at(...)`, and `SafeHttpClient::fake(...)` without static Carbon or Laravel Http facades.',
                'expected_rag_markers' => ['TestClient', 'FrozenClock', 'SafeHttpClient::fake'],
                'required_markers' => ['TestClient', 'FrozenClock::at(', 'SafeHttpClient::fake('],
                'reference_code' => <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Tests;

use Kongono\Core\Clock\ClockInterface;
use Kongono\Core\Clock\FrozenClock;
use Kongono\Http\App;
use Kongono\Http\Testing\TestClient;
use Kongono\Security\Egress\SafeHttpClient;
use PHPUnit\Framework\TestCase;

final class HealthFlowTest extends TestCase
{
    public function testHealthRouteUsesFrozenClockAndFakeHttp(): void
    {
        $app = App::create(dirname(__DIR__))
            ->instance(ClockInterface::class, FrozenClock::at('2026-11-24T12:00:00Z'));

        $http = SafeHttpClient::fake([
            'https://status.example.test/*' => ['status' => 200, 'body' => '{"ok":true}'],
        ]);

        self::assertSame(200, $http->get('https://status.example.test/ping')->status);
        $client = new TestClient($app);
        self::assertInstanceOf(TestClient::class, $client);
    }
}
PHP,
            ],
        ];
    }
}
