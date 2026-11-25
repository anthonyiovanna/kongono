<?php

declare(strict_types=1);

namespace Kongono\Cli\Tests;

use Closure;
use DateTimeImmutable;
use Kongono\Ai\Agent\AgentPrincipal;
use Kongono\Ai\Agent\AgentRunner;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\ChatResponse;
use Kongono\Ai\Embedding\PgVectorStore;
use Kongono\Ai\Embedding\TextChunker;
use Kongono\Ai\Mcp\McpServer;
use Kongono\Ai\Message;
use Kongono\Ai\Provider\StreamingAiProviderInterface;
use Kongono\Ai\Security\PromptGuard;
use Kongono\Ai\TokenUsage;
use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\KongoCli;
use Kongono\Cli\Policy\Invariant;
use Kongono\Cli\Policy\PolicyVerifier;
use Kongono\Cli\Shadow\ShadowDiffProver;
use Kongono\Core\Async\Async;
use Kongono\Core\Capabilities\CapabilityException;
use Kongono\Core\Capabilities\ExecutionCapabilities;
use Kongono\Core\Container;
use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Database\BatchLoader;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\DatabaseException;
use Kongono\Database\Dialect;
use Kongono\Database\QueryBuilder;
use Kongono\Database\RowLevelSecurity;
use Kongono\Http\App;
use Kongono\Http\Cookie;
use Kongono\Http\HttpException;
use Kongono\Http\Live\LiveMiddleware;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Session\Session;
use Kongono\Http\UploadedFile;
use Kongono\Jobs\JobException;
use Kongono\Jobs\Scheduler\CronExpression;
use Kongono\Jobs\Scheduler\ScheduledTask;
use Kongono\Jobs\Workflow\MemoryWorkflowStore;
use Kongono\Jobs\Workflow\WorkflowContext;
use Kongono\Jobs\Workflow\WorkflowEngine;
use Kongono\Jobs\Workflow\WorkflowState;
use Kongono\Jobs\Workflow\WorkflowStatus;
use Kongono\Mail\Address;
use Kongono\Mail\Email;
use Kongono\Mail\MailException;
use Kongono\Mail\ResendTransport;
use Kongono\Security\Attributes\MaxQueries;
use Kongono\Security\Attributes\ReadOnlyDb;
use Kongono\Security\Middleware\CapabilityMiddleware;
use Kongono\Security\Middleware\CorsMiddleware;
use Kongono\Security\Middleware\SessionAuthMiddleware;
use Kongono\Storage\LocalStorage;
use Kongono\Storage\MemoryStorage;
use Kongono\Storage\S3Storage;
use Kongono\Storage\StorageException;
use Kongono\Storage\UploadedFileStore;
use Kongono\Validation\Attributes\ArrayOf;
use Kongono\Validation\Attributes\Date;
use Kongono\Validation\Attributes\Ip;
use Kongono\Validation\Attributes\Required;
use Kongono\Validation\Attributes\RequiredIf;
use Kongono\Validation\ValidationException;
use Kongono\Validation\Validator;
use Kongono\View\HtmlSanitizer;
use Kongono\View\TemplateCompiler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

enum AuditSampleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}

final readonly class EnumDefaultService
{
    public function __construct(
        public AuditSampleStatus $status = AuditSampleStatus::Draft,
    ) {}
}

final readonly class Round2ValidationDto
{
    /** @param list<string> $tags */
    public function __construct(
        #[Date]
        public string $day = '2026-02-28',
        #[Ip(allowPrivate: false)]
        public string $ip = '93.184.216.34',
        public string $kind = 'none',
        #[RequiredIf('kind', 'urgent')]
        public mixed $reason = null,
        #[ArrayOf('string', rules: [new Required()])]
        public array $tags = ['ok'],
    ) {}
}

final class RoleplayMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}

final class Round2SecurityAuditRegressionTest extends TestCase
{
    public function testRound1RegressionsEnumAutowireRlsReadOnlyAndStarterUserStore(): void
    {
        // Regression 1: Container::autowire() must NOT clone uncloneable PHP 8.5 Enum default parameters
        $container = new Container();
        $svc = $container->make(EnumDefaultService::class);
        self::assertSame(AuditSampleStatus::Draft, $svc->status);

        // Regression 2: RowLevelSecurity::withTenant() must succeed under #[ReadOnlyDb], while arbitrary set_config is blocked
        $queries = [];
        $fakeDb = $this->fakeConnection($queries, Dialect::Postgres);
        $caps = new ExecutionCapabilities(readOnlyDb: true);
        ExecutionCapabilities::withActive($caps, static function () use ($fakeDb, $caps): void {
            $res = RowLevelSecurity::withTenant($fakeDb, 'tenant-42', static function (ConnectionInterface $db) use ($caps): string {
                $caps->assertDbQueryAllowed('SELECT * FROM notes WHERE id = ?');

                return 'ok';
            });
            self::assertSame('ok', $res);
        });
        self::assertStringContainsString('set_config(?, ?, true)', $queries[0]);

        // Regression 3: Starter-app UserStore caches dummy hash once so unknown users take a single Argon2id verify (1:1 timing)
        $userStoreSrc = file_get_contents(dirname(__DIR__, 3) . '/examples/starter-app/src/Domain/UserStore.php');
        self::assertIsString($userStoreSrc);
        self::assertStringContainsString('$this->dummyHash ??=', $userStoreSrc);
    }

    public function testCriticalSqlCapabilitiesWorkerIsolationAndFiberInheritance(): void
    {
        // C-3: ExecutionCapabilities blocks dialect differentials & dangerous PG functions under #[ReadOnlyDb]
        $readOnly = new ExecutionCapabilities(readOnlyDb: true);
        $hostileSqls = [
            "SELECT set_config('search_path', 'evil', false)",
            "SELECT pg_read_file('/etc/passwd')",
            "SELECT lo_import('/etc/passwd')",
            'WITH d AS (DELETE FROM users RETURNING *) SELECT * FROM d',
            '/*!50000 INSERT INTO users VALUES (1) */',
            'EXPLAIN ANALYZE DELETE FROM users',
            "SELECT * INTO OUTFILE '/tmp/pwn' FROM users",
            'SELECT 1; DROP TABLE users',
        ];
        foreach ($hostileSqls as $sql) {
            try {
                $readOnly->assertDbQueryAllowed($sql);
                self::fail('ReadOnlyDb failed to block hostile SQL: ' . $sql);
            } catch (CapabilityException) {
                $this->addToAssertionCount(1);
            }
        }

        // C-4: CapabilityMiddleware creates a fresh ExecutionCapabilities per request (no cross-request bleed)
        $app = App::create(dirname(__DIR__, 3) . '/examples/starter-app', 'Cap Isolation');
        $app->middleware(new CapabilityMiddleware($app->container(), new ExecutionCapabilities(readOnlyDb: true, maxQueries: 1)));
        $app->router()->get('/capped', #[ReadOnlyDb] #[MaxQueries(1)] static function (): Response {
            $caps = ExecutionCapabilities::current();
            self::assertNotNull($caps);
            $caps->assertDbQueryAllowed('SELECT 1');

            return Response::text('ok');
        });
        self::assertSame(200, $app->handle(Request::create('GET', '/capped'))->getStatusCode());
        self::assertSame(200, $app->handle(Request::create('GET', '/capped'))->getStatusCode());

        // C-5: Async::all() Fibers inherit parent ExecutionCapabilities (cannot bypass #[ReadOnlyDb] / #[NoOutboundHttp])
        $parentCaps = new ExecutionCapabilities(readOnlyDb: true, noOutboundHttp: true);
        $blockedInFiber = false;
        ExecutionCapabilities::withActive($parentCaps, static function () use (&$blockedInFiber): void {
            Async::all([
                'write_attempt' => static function () use (&$blockedInFiber): string {
                    try {
                        ExecutionCapabilities::current()?->assertDbQueryAllowed('DELETE FROM users WHERE id = 1');
                    } catch (CapabilityException) {
                        $blockedInFiber = true;
                    }

                    return 'done';
                },
            ]);
        });
        self::assertTrue($blockedInFiber, 'Child Fiber inside Async::all() must inherit parent ExecutionCapabilities.');
    }

    public function testCriticalLiveDomXssVsCodeTrustAndUploadPolyglots(): void
    {
        // C-1: LiveMiddleware runtime strips scripts instead of raw innerHTML swap
        $liveJs = LiveMiddleware::scriptSource();
        self::assertStringNotContainsString('target.innerHTML = html;', $liveJs);

        // C-2: VS Code extension refuses untrusted workspaces and restricts executable config scope
        $pkgPath = dirname(__DIR__, 3) . '/editors/vscode-kongono/package.json';
        $pkg = json_decode((string) file_get_contents($pkgPath), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($pkg);
        self::assertFalse($pkg['capabilities']['untrustedWorkspaces']['supported']);
        self::assertSame('application', $pkg['contributes']['configuration']['properties']['kongono.phpPath']['scope']);
        $extJs = (string) file_get_contents(dirname(__DIR__, 3) . '/editors/vscode-kongono/extension.js');
        self::assertStringContainsString('isTrusted', $extJs);

        // C-6: UploadedFileStore blocks PDF polyglots, UTF-16 script tags, and trailing-dot explicit keys
        $store = new UploadedFileStore(new MemoryStorage(), ['application/pdf', 'text/plain']);
        $pdfPolyglot = UploadedFile::fromString("%PDF-1.7\n1 0 obj\n<<>>\nendobj\n<script>alert(1)</script>", 'evil.pdf', 'application/pdf');
        try {
            $store->store($pdfPolyglot);
            self::fail('PDF polyglot with embedded <script> must be rejected.');
        } catch (StorageException) {
            $this->addToAssertionCount(1);
        }

        $utf16Polyglot = UploadedFile::fromString("\xFF\xFE<\x00s\x00c\x00r\x00i\x00p\x00t\x00>\x00", 'evil.txt', 'text/plain');
        try {
            $store->store($utf16Polyglot);
            self::fail('UTF-16 encoded <script> text upload must be rejected.');
        } catch (StorageException) {
            $this->addToAssertionCount(1);
        }

        $validPdf = UploadedFile::fromString("%PDF-1.7\n%clean", 'doc.pdf', 'application/pdf');
        try {
            $store->store($validPdf, explicitKey: 'docs/report.pdf.');
            self::fail('Trailing dot in explicitKey must be rejected.');
        } catch (StorageException) {
            $this->addToAssertionCount(1);
        }
    }

    public function testValidationSecurityHttpAndViewHardening(): void
    {
        $validator = new Validator();

        // Real calendar date validation (2026-02-29 is NOT a leap year; 2026-02-30 never exists)
        try {
            $validator->validate(Round2ValidationDto::class, ['day' => '2026-02-29']);
            self::fail('Non-leap-year 2026-02-29 must fail #[Date].');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('day', $e->errors);
        }

        // IPv4-mapped loopback IPv6 rejected when allowPrivate: false
        try {
            $validator->validate(Round2ValidationDto::class, ['ip' => '::ffff:127.0.0.1']);
            self::fail('IPv4-mapped loopback ::ffff:127.0.0.1 must fail #[Ip(allowPrivate: false)].');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('ip', $e->errors);
        }

        // Empty array [] rejected by #[RequiredIf] & empty string rejected by #[ArrayOf(rules: [new Required()])]
        try {
            $validator->validate(Round2ValidationDto::class, ['kind' => 'urgent', 'reason' => [], 'tags' => ['']]);
            self::fail('Empty array in #[RequiredIf] and blank element in #[ArrayOf] must fail.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('reason', $e->errors);
            self::assertArrayHasKey('tags.0', $e->errors);
        }

        // HtmlSanitizer: fragment flag & legacy rawtext tags
        self::assertFalse(HtmlSanitizer::isSafeUrl('#section', allowFragment: false));
        $cleaned = HtmlSanitizer::sanitize('<p>ok</p><noembed><img src=x onerror=alert(1)></noembed><plaintext>boom');
        self::assertStringNotContainsString('onerror', $cleaned);
        self::assertStringNotContainsString('<noembed', $cleaned);
        self::assertStringNotContainsString('<plaintext', $cleaned);

        // TemplateCompiler: nested same-name components preserve inner/outer pairing
        $compiler = new TemplateCompiler();
        $compiled = $compiler->compile('<x-card title="outer"><x-card title="inner">body</x-card></x-card>');
        self::assertStringContainsString("'outer'", $compiled);
        self::assertStringContainsString("'inner'", $compiled);
        self::assertSame(2, substr_count($compiled, "renderComponent('components/card'"));

        // Cookie rejects control chars in path/domain and enforces __Host- prefix invariants
        try {
            new Cookie('sid', 'ok', path: "/\r\nSet-Cookie: evil=1");
            self::fail('Cookie path with CRLF must throw InvalidArgumentException.');
        } catch (\InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }
        try {
            new Cookie('__Host-sid', 'ok', secure: false);
            self::fail('__Host- cookie without secure: true must throw InvalidArgumentException.');
        } catch (\InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $session = new Session('sid-r2');
        $session->flashValidation([], ['email' => 'a@b.test', 'currentPassword' => 's3cr3t', 'apiToken' => 'tok_123', 'userPin' => '9999']);
        $nextSession = new Session('sid-r2', $session->exportPayload());
        self::assertSame('a@b.test', $nextSession->old('email'));
        self::assertSame('', $nextSession->old('currentPassword'));
        self::assertSame('', $nextSession->old('apiToken'));
        self::assertSame('', $nextSession->old('userPin'));

        // SessionAuthMiddleware rejects control-char open redirect "/\t/evil.com"
        try {
            new SessionAuthMiddleware(loginRedirectUrl: "/\t/evil.com");
            self::fail('SessionAuthMiddleware must reject control characters in loginRedirectUrl.');
        } catch (\Kongono\Security\SecurityException) {
            $this->addToAssertionCount(1);
        }

        // CorsMiddleware forwards non-CORS OPTIONS and attaches CORS headers onto HttpException
        $cors = new CorsMiddleware(['https://app.example.test']);
        $optHandler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return Response::text('allowed', 200)->withHeader('Allow', 'GET, OPTIONS');
            }
        };
        $optRes = $cors->process(
            Request::create('OPTIONS', '/api/items'),
            $optHandler,
        );
        self::assertSame(200, $optRes->getStatusCode());
        self::assertSame('GET, OPTIONS', $optRes->getHeaderLine('Allow'));

        $errHandler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new HttpException(404, 'Not found');
            }
        };
        try {
            $cors->process(
                Request::create('GET', '/api/missing', headers: ['Origin' => 'https://app.example.test']),
                $errHandler,
            );
            self::fail('HttpException should propagate with CORS headers.');
        } catch (HttpException $e) {
            self::assertSame('https://app.example.test', $e->headers['Access-Control-Allow-Origin'] ?? null);
            self::assertStringContainsString('Origin', $e->headers['Vary'] ?? '');
        }
    }

    public function testDatabaseStorageJobsAndMailHardening(): void
    {
        $queries = [];
        $db = $this->fakeConnection($queries, Dialect::Postgres);

        // QueryBuilder: withoutTrashed() and forTenant() cannot be bypassed by top-level orWhere()
        $compiledSelect = $db->table('notes')
            ->forTenant(7)
            ->withoutTrashed()
            ->where('status', '=', 'draft')
            ->orWhere('public', '=', true)
            ->toSelectSql();
        self::assertStringContainsString('"workspace_id" = ? AND "deleted_at" IS NULL AND ("status" = ? OR "public" = ?)', $compiledSelect->query);
        self::assertSame([7, 'draft', true], $compiledSelect->bindings);

        // QueryBuilder: conflicting forTenant/requireTenant rejected; empty insertMany rejected; first() doesn't mutate
        try {
            $db->table('notes')->forTenant(1, 'workspace_id')->requireTenant('org_id')->toSelectSql();
            self::fail('Conflicting tenant columns must throw DatabaseException.');
        } catch (DatabaseException) {
            $this->addToAssertionCount(1);
        }

        try {
            $db->table('notes')->toInsertManySql([]);
            self::fail('Empty insertMany must throw DatabaseException.');
        } catch (DatabaseException) {
            $this->addToAssertionCount(1);
        }

        $qb = $db->table('notes')->where('active', '=', true);
        $qb->first();
        $afterFirst = $qb->toSelectSql();
        self::assertStringNotContainsString('LIMIT 1', $afterFirst->query);

        // BatchLoader negative-caches missing keys
        $loader = new BatchLoader();
        $fetchCalls = 0;
        $fetcher = static function (array $keys) use (&$fetchCalls): array {
            ++$fetchCalls;

            return [];
        };
        self::assertSame([99 => null], $loader->loadMany([99], $fetcher));
        self::assertSame([99 => null], $loader->loadMany([99], $fetcher));
        self::assertSame(1, $fetchCalls);

        // S3Storage rejects invalid bucket & enforces #[NoOutboundHttp]
        try {
            new S3Storage('../evil-bucket', 'ak', 'sk');
            self::fail('Invalid S3 bucket must throw StorageException.');
        } catch (StorageException) {
            $this->addToAssertionCount(1);
        }

        $s3 = new S3Storage('valid-bucket-123', 'ak', 'sk', httpSender: static fn(): array => ['status' => 200, 'headers' => [], 'body' => '']);
        ExecutionCapabilities::withActive(new ExecutionCapabilities(noOutboundHttp: true), static function () use ($s3): void {
            try {
                $s3->get('secret.txt');
                self::fail('S3Storage must respect #[NoOutboundHttp].');
            } catch (CapabilityException) {
                // expected
            }
        });
        $this->addToAssertionCount(1);

        // LocalStorage rejects CR/LF in metadata
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_r2_ls_' . bin2hex(random_bytes(5));
        $ls = new LocalStorage($tmpDir);
        try {
            try {
                $ls->put('a.txt', 'hi', "text/plain\r\nX-Evil: 1");
                self::fail('LocalStorage must reject CR/LF contentType.');
            } catch (StorageException) {
                $this->addToAssertionCount(1);
            }
        } finally {
            @rmdir($tmpDir);
        }

        // CronExpression: POSIX DOM/DOW OR semantics, 7/step, and eager validation
        $cron = new CronExpression('0 0 15 * 1'); // 15th of month OR Monday
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-11-16 00:00:00'))); // Monday, 16th
        self::assertTrue($cron->isDue(new DateTimeImmutable('2026-11-15 00:00:00'))); // Sunday, 15th
        self::assertFalse($cron->isDue(new DateTimeImmutable('2026-11-17 00:00:00'))); // Tuesday, 17th
        self::assertTrue((new CronExpression('0 0 * * 7/2'))->isDue(new DateTimeImmutable('2026-11-22 00:00:00'))); // Sunday

        try {
            new CronExpression('99 * * * *');
            self::fail('Invalid cron minute 99 must throw eagerly.');
        } catch (JobException) {
            $this->addToAssertionCount(1);
        }

        try {
            (new ScheduledTask('t', static function (): void {}))->dailyAt('25:99');
            self::fail('Invalid dailyAt time 25:99 must throw.');
        } catch (JobException) {
            $this->addToAssertionCount(1);
        }

        // WorkflowEngine: rejects concurrent Running re-entry & short-circuits early Sleeping polls
        $wfStore = new MemoryWorkflowStore();
        $wfStore->save(new WorkflowState('run-busy', 'billing', WorkflowStatus::Running, attempts: 1));
        $wfEngine = new WorkflowEngine($wfStore);
        try {
            $wfEngine->run('run-busy', 'billing', static fn(WorkflowContext $c): string => 'nope', now: 1001);
            self::fail('Re-entering Running workflow must throw JobException.');
        } catch (JobException) {
            $this->addToAssertionCount(1);
        }

        $wfStore->save(new WorkflowState('run-sleep', 'billing', WorkflowStatus::Sleeping, wakeAt: 2000, attempts: 1));
        $early = $wfEngine->run('run-sleep', 'billing', static fn(WorkflowContext $c): string => 'should-not-run', now: 1500);
        self::assertSame(WorkflowStatus::Sleeping, $early->status);
        self::assertSame(1, $early->attempts);

        // Mail: Address rejects angle brackets; Email emits multipart/alternative + RFC 2047 subject; Resend respects #[NoOutboundHttp]
        try {
            new Address('bad<addr>@example.com');
            self::fail('Address with < or > must throw MailException.');
        } catch (MailException) {
            $this->addToAssertionCount(1);
        }

        $mime = Email::create()
            ->from('no-reply@example.com')
            ->to('user@example.com')
            ->subject('日本語テスト')
            ->text('Plain body')
            ->html('<p>HTML body</p>')
            ->toMimeString();
        self::assertStringContainsString('multipart/alternative', $mime);
        self::assertStringContainsString('=?UTF-8?B?', $mime);

        $resend = new ResendTransport('re_test_1234567890abcdef', httpSender: static fn(): array => ['status' => 200, 'body' => '{}']);
        ExecutionCapabilities::withActive(new ExecutionCapabilities(noOutboundHttp: true), static function () use ($resend): void {
            try {
                $resend->send(Email::create()->from('a@example.com')->to('b@example.com')->subject('Hi')->text('Body'));
                self::fail('ResendTransport must respect #[NoOutboundHttp].');
            } catch (CapabilityException) {
                // expected
            }
        });
        $this->addToAssertionCount(1);
    }

    public function testAiMcpProjectContextShadowPolicyAndCliHardening(): void
    {
        // H-25: AgentRunner establishes canary without system message AND blocks canary exfiltration via tool-call args BEFORE executing tool
        $toolExecuted = false;
        $provider = new class implements StreamingAiProviderInterface {
            public function name(): string
            {
                return 'openai';
            }

            public function defaultModel(): string
            {
                return 'gpt-4.1';
            }

            public function chat(ChatRequest $request): ChatResponse
            {
                $sys = $request->messages[0]->content ?? '';
                preg_match('/kgn_canary_[a-f0-9]{12,64}/', $sys, $m);
                $leaked = $m[0] ?? 'missing';

                return new ChatResponse(
                    text: '',
                    model: 'gpt-4.1',
                    provider: 'openai',
                    toolCalls: [new ToolCall('c1', 'echo', ['payload' => 'exfil:' . $leaked])],
                    usage: new TokenUsage(10, 10),
                );
            }

            public function chatStream(ChatRequest $request, Closure $onDelta): ChatResponse
            {
                $text = str_repeat('Streaming output token ', 400);
                $onDelta($text);

                // Deliberately return 0/0 usage to verify AiClient #[MaxAiCost] fallback estimation
                return new ChatResponse($text, 'gpt-4.1', 'openai', usage: new TokenUsage(0, 0));
            }
        };

        $runner = new AgentRunner(
            new AiClient($provider),
            new AgentPrincipal('test-agent', 'tester', allowedTools: ['echo']),
        );
        try {
            $runner->run(
                [Message::user('Trigger tool call without system message')],
                [new ToolDefinition('echo', 'Echo tool')],
                static function (ToolCall $call) use (&$toolExecuted): string {
                    $toolExecuted = true;

                    return 'executed';
                },
            );
            self::fail('AgentRunner must block canary exfiltration in tool-call arguments.');
        } catch (AiException $e) {
            self::assertStringContainsString('PromptGuard', $e->getMessage());
        }
        self::assertFalse($toolExecuted, 'Tool executor must NEVER run when tool args leak the canary.');

        // H-26: Streaming chat enforces #[MaxAiCost] even when provider omits token usage
        $streamClient = new AiClient($provider);
        ExecutionCapabilities::withActive(new ExecutionCapabilities(maxAiCostUsd: 0.0001), static function () use ($streamClient): void {
            try {
                $streamClient->chatStream(new ChatRequest([Message::user('Hi')]), static function (string $_): void {});
                self::fail('Streaming response exceeding #[MaxAiCost] must throw CapabilityException even with 0/0 provider usage.');
            } catch (CapabilityException) {
                // expected
            }
        });
        $this->addToAssertionCount(1);

        // H-29: PromptGuard streaming filter never splits multibyte UTF-8 & resists 40-word Base64 flooding
        $guard = new PromptGuard();
        $canary = 'kgn_canary_a1b2c3d4e5f6a1b2c3d4e5f6';
        $chunks = [];
        $filter = $guard->createStreamingFilter($canary, static function (string $c) use (&$chunks): void {
            $chunks[] = $c;
        });
        $utf8Payload = str_repeat('日本語🎉', 200);
        foreach (str_split($utf8Payload, 37) as $slice) {
            $filter['push']($slice);
        }
        $filter['flush']();
        foreach ($chunks as $emittedChunk) {
            self::assertTrue(mb_check_encoding($emittedChunk, 'UTF-8'), 'Emitted streaming chunk must remain valid UTF-8.');
        }
        self::assertSame($utf8Payload, implode('', $chunks));

        $floodWords = implode(' ', array_fill(0, 45, 'internationalization'));
        $floodedLeak = $floodWords . ' ' . base64_encode($canary);
        self::assertFalse($guard->inspectOutput($floodedLeak, $canary)['safe']);

        // McpServer: rejects null for required non-nullable string & does not burn tool budget on invalid calls
        $mcpPrincipal = new AgentPrincipal('mcp-test', 'reader', allowedTools: ['ping'], maxToolCalls: 1);
        $mcp = (new McpServer('test', '1.0', $mcpPrincipal))->registerTool(
            new ToolDefinition('ping', 'Ping', [
                'type' => 'object',
                'required' => ['msg'],
                'properties' => ['msg' => ['type' => 'string']],
            ]),
            static fn(array $args): string => 'pong:' . (is_string($args['msg'] ?? null) ? $args['msg'] : ''),
        );
        $badResp = json_decode((string) $mcp->handleJson('{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"ping","arguments":{"msg":null}}}'), true, 16, JSON_THROW_ON_ERROR);
        self::assertIsArray($badResp);
        self::assertArrayHasKey('error', $badResp);
        // Budget was NOT burned by the invalid call, so the valid call succeeds
        $okResp = json_decode((string) $mcp->handleJson('{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"ping","arguments":{"msg":"hi"}}}'), true, 16, JSON_THROW_ON_ERROR);
        self::assertIsArray($okResp);
        self::assertFalse($okResp['result']['isError']);

        // TextChunker & PgVectorStore guards
        try {
            new TextChunker(100, 100);
            self::fail('TextChunker with overlap >= chunkSize must throw.');
        } catch (AiException) {
            $this->addToAssertionCount(1);
        }

        $vecQueries = [];
        $vecStore = new PgVectorStore($this->fakeConnection($vecQueries, Dialect::Postgres));
        try {
            $vecStore->search([NAN, 1.0]);
            self::fail('PgVectorStore must reject NAN vectors.');
        } catch (AiException) {
            $this->addToAssertionCount(1);
        }

        // H-27: ProjectContext rejects trailing dots, ADS colons, Windows reserved names, and .kongono/ writes
        $ctxDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_r2_ctx_' . bin2hex(random_bytes(5));
        mkdir($ctxDir, 0o775, true);
        try {
            $ctx = new ProjectContext($ctxDir);
            foreach (['src/Foo.php.', 'src/Foo.php:ads', 'src/CON.php', '.kongono/companions.md', 'AGENTS.md'] as $badPath) {
                try {
                    $ctx->writeFileContained($badPath, 'x');
                    self::fail('ProjectContext must block write to: ' . $badPath);
                } catch (CoreException) {
                    $this->addToAssertionCount(1);
                }
            }

            // PolicyVerifier catches grouped `use App\Admin\{Secret};` and rejects unrelated `RoleplayMiddleware`
            mkdir($ctxDir . '/src/Billing', 0o775, true);
            file_put_contents(
                $ctxDir . '/src/Billing/Leaky.php',
                "<?php\ndeclare(strict_types=1);\nnamespace App\\Billing;\nuse App\\Admin\\{Secret, Other};\nfinal class Leaky {}\n",
            );
            $policyApp = App::create($ctxDir, 'Policy R2');
            $policyApp->router()->get('/admin/panel', static fn(): Response => Response::text('exposed'))->middleware(new RoleplayMiddleware());
            $policyReport = (new PolicyVerifier())->verify($ctxDir, $policyApp, [
                Invariant::namespace('App\\Billing', 'KGN-POLICY-NO-ADMIN')->mustNotDependOn('App\\Admin'),
            ]);
            $policyCodes = array_column($policyReport['violations'], 'code');
            self::assertContains('KGN-POLICY-NO-ADMIN', $policyCodes);
            self::assertContains('KGN-POLICY-ADMIN-AUTH', $policyCodes);

            // ShadowDiffProver refuses internal /mcp & /_kongono baseline replay
            $shadowApp = App::create($ctxDir, 'Shadow R2');
            $shadowApp->router()->get('/ok', static fn(): Response => Response::text('ok'));
            $shadowReport = (new ShadowDiffProver())->diff([
                'entries' => [
                    '/mcp' => ['status' => 200, 'content_type' => 'application/json', 'query_count' => 0],
                    '/_kongono/api/chie' => ['status' => 200, 'content_type' => 'application/json', 'query_count' => 0],
                    '/ok' => ['status' => 200, 'content_type' => 'text/plain', 'query_count' => 0],
                ],
            ], $shadowApp);
            self::assertFalse($shadowReport['equivalent']);
            self::assertContains('KGN-SHADOW-UNSAFE-PATH', array_column($shadowReport['findings'], 'code'));

            // KongoCli: --version works, upgrade --plan --apply conflict fails closed
            $cli = new KongoCli($ctxDir);
            [$verCode, $verOut] = $cli->run(['--version']);
            self::assertSame(0, $verCode);
            self::assertStringContainsString('Kongono Framework', $verOut);

            [$upgCode, $upgOut] = $cli->run(['upgrade', '--plan', '--apply', '--app=' . $ctxDir]);
            self::assertSame(1, $upgCode);
            self::assertStringContainsString('Cannot combine --plan and --apply', $upgOut);

            // SecretScrubber redacts DSN password
            self::assertStringNotContainsString('s3cr3tpass', SecretScrubber::scrub('postgres://admin:s3cr3tpass@db.internal:5432/app'));
        } finally {
            @unlink($ctxDir . '/src/Billing/Leaky.php');
            @rmdir($ctxDir . '/src/Billing');
            @rmdir($ctxDir . '/src');
            @rmdir($ctxDir);
        }
    }

    /**
     * @param list<string> $queries
     */
    private function fakeConnection(array &$queries, Dialect $dialect): ConnectionInterface
    {
        return new class ($queries, $dialect) implements ConnectionInterface {
            /** @param list<string> $queries */
            public function __construct(
                private array &$queries,
                private readonly Dialect $dialect,
            ) {}

            public function dialect(): Dialect
            {
                return $this->dialect;
            }

            public function query(string $sql, array $bindings = []): array
            {
                ExecutionCapabilities::current()?->assertDbQueryAllowed($sql);
                $this->queries[] = $sql;

                return [];
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                ExecutionCapabilities::current()?->assertDbQueryAllowed($sql);
                $this->queries[] = $sql;

                return null;
            }

            public function execute(string $sql, array $bindings = []): int
            {
                ExecutionCapabilities::current()?->assertDbQueryAllowed($sql);
                $this->queries[] = $sql;

                return 0;
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, $this->dialect, $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                return $callback($this);
            }
        };
    }
}
