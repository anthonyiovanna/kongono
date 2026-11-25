<?php

declare(strict_types=1);

namespace Kongono\Cli\Tests;

use Fiber;
use Kongono\Ai\Agent\AgentPrincipal;
use Kongono\Ai\Agent\AgentRunner;
use Kongono\Ai\Agent\RequiresApproval;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\Embedding\MemoryVectorStore;
use Kongono\Ai\Embedding\VectorDocument;
use Kongono\Ai\Mcp\McpServer;
use Kongono\Ai\Message;
use Kongono\Ai\Provider\OpenAiProvider;
use Kongono\Ai\Security\PromptGuard;
use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Cli\AppScaffolder;
use Kongono\Cli\Auth\AuthScaffolder;
use Kongono\Cli\CodeGenerator;
use Kongono\Cli\Companions\ChieBuilder;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Deploy\DeployBlueprintGenerator;
use Kongono\Cli\Eval\AgentCodingBenchmark;
use Kongono\Cli\KongoCli;
use Kongono\Cli\Lsp\KongonoLanguageServer;
use Kongono\Cli\Mcp\KongonoMcpFactory;
use Kongono\Cli\Policy\PolicyVerifier;
use Kongono\Cli\Security\IsolationProbe;
use Kongono\Cli\Security\TenantIsolationProver;
use Kongono\Cli\Shadow\ShadowDiffProver;
use Kongono\Core\Cache\CacheInterface;
use Kongono\Core\Cache\MemoryCache;
use Kongono\Core\Capabilities\CapabilityException;
use Kongono\Core\Capabilities\ExecutionCapabilities;
use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\ErrorExplainer;
use Kongono\Core\Diagnostics\FileIncidentStore;
use Kongono\Core\Diagnostics\IncidentRecord;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Database\BatchLoader;
use Kongono\Database\DatabaseException;
use Kongono\Database\Dialect;
use Kongono\Database\QueryBuilder;
use Kongono\Http\App;
use Kongono\Http\Bridge\BridgeMiddleware;
use Kongono\Http\Cookie;
use Kongono\Http\Dev\DevDashboardMiddleware;
use Kongono\Http\HttpException;
use Kongono\Http\Middleware\CompressionMiddleware;
use Kongono\Http\Middleware\HttpCacheMiddleware;
use Kongono\Http\Middleware\IdempotencyMiddleware;
use Kongono\Http\Middleware\StaticFilesMiddleware;
use Kongono\Http\Ops\OpsDashboardMiddleware;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Session\CacheSessionStore;
use Kongono\Http\Session\Session;
use Kongono\Http\Session\SessionMiddleware;
use Kongono\Http\UploadedFile;
use Kongono\Jobs\PostgresQueueDriver;
use Kongono\Security\Auth\CachePersonalAccessTokenStore;
use Kongono\Security\Auth\LoginThrottler;
use Kongono\Security\Auth\PasskeyVerifier;
use Kongono\Security\Auth\PersonalAccessToken;
use Kongono\Security\Auth\RememberMeManager;
use Kongono\Security\Egress\SafeHttpClient;
use Kongono\Security\Gate;
use Kongono\Security\Middleware\CorsMiddleware;
use Kongono\Security\Middleware\CsrfMiddleware;
use Kongono\Security\Middleware\SessionAuthMiddleware;
use Kongono\Security\Principal;
use Kongono\Security\PrincipalKind;
use Kongono\Security\SecurityException;
use Kongono\Security\TokenSigner;
use Kongono\Security\Totp;
use Kongono\Storage\MemoryStorage;
use Kongono\Storage\StorageException;
use Kongono\Storage\UploadedFileStore;
use Kongono\Validation\Attributes\Confirmed;
use Kongono\Validation\Attributes\Regex;
use Kongono\Validation\Validator;
use Kongono\View\HtmlSanitizer;
use Kongono\View\ViewEngine;
use Kongono\View\ViewException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use stdClass;

/**
 * Comprehensive security, persistent-runtime, and correctness regression suite
 * covering Batches 1-4 of the Kongono deep audit across all packages.
 */
final class Round4SecurityAuditRegressionTest extends TestCase
{
    public function testExecutionCapabilitiesBlocksSystemFunctionsUnicodeEscapesPragmasAndTracksParentBudget(): void
    {
        $blockedQueries = [
            "SELECT pg_read_file('/etc/passwd')",
            "SELECT lo_import('/etc/passwd')",
            "SELECT dblink('dbname=other', 'DELETE FROM users')",
            "SELECT U&'test' UESCAPE '\\'",
            'PRAGMA journal_mode = WAL',
        ];

        ExecutionCapabilities::withActive(
            new ExecutionCapabilities(readOnlyDb: true),
            static function () use ($blockedQueries): void {
                $active = ExecutionCapabilities::current();
                self::assertNotNull($active);
                // Safe read-only PRAGMA remains allowed
                $active->assertDbQueryAllowed("PRAGMA table_info('users')");

                foreach ($blockedQueries as $sql) {
                    try {
                        $active->assertDbQueryAllowed($sql);
                        self::fail(sprintf('Expected read-only DB capability to block: %s', $sql));
                    } catch (CapabilityException $e) {
                        self::assertStringContainsString('ReadOnlyDb', $e->getMessage());
                    }
                }
            },
        );

        // Nested scope must still charge parent MaxQueries budget
        $this->expectException(CapabilityException::class);
        ExecutionCapabilities::withActive(
            new ExecutionCapabilities(maxQueries: 2),
            static function (): void {
                ExecutionCapabilities::current()?->assertDbQueryAllowed('SELECT 1');
                ExecutionCapabilities::withActive(
                    new ExecutionCapabilities(maxQueries: 10),
                    static function (): void {
                        ExecutionCapabilities::current()?->assertDbQueryAllowed('SELECT 2');
                        ExecutionCapabilities::current()?->assertDbQueryAllowed('SELECT 3');
                    },
                );
            },
        );
    }

    public function testChildFiberInheritsCapabilitiesAndEgressGuardWhileSuspendedSiblingRemainsIsolated(): void
    {
        $childBlockedDb = false;
        $childBlockedHttp = false;
        $siblingAllowedDb = false;
        $siblingAllowedHttp = false;

        $restrictedFiber = new Fiber(static function () use (&$childBlockedDb, &$childBlockedHttp): void {
            ExecutionCapabilities::withActive(
                new ExecutionCapabilities(readOnlyDb: true, noOutboundHttp: true),
                static function () use (&$childBlockedDb, &$childBlockedHttp): void {
                    // Suspend while restricted to verify a sibling fiber is unaffected
                    Fiber::suspend('restricted_active');

                    // Spawn a child Fiber inside the restricted scope: it MUST inherit both guards
                    $child = new Fiber(static function () use (&$childBlockedDb, &$childBlockedHttp): void {
                        try {
                            ExecutionCapabilities::current()?->assertDbQueryAllowed('DELETE FROM users');
                        } catch (CapabilityException) {
                            $childBlockedDb = true;
                        }
                        try {
                            SafeHttpClient::fake(['https://api.example.test/*' => ['status' => 200, 'body' => 'ok']])
                                ->request('GET', 'https://api.example.test/ping');
                        } catch (CapabilityException) {
                            $childBlockedHttp = true;
                        }
                    });
                    $child->start();
                },
            );
        });

        $siblingFiber = new Fiber(static function () use (&$siblingAllowedDb, &$siblingAllowedHttp): void {
            $siblingAllowedDb = null === ExecutionCapabilities::current();
            $resp = SafeHttpClient::fake(['https://api.example.test/*' => ['status' => 200, 'body' => 'ok']])
                ->request('GET', 'https://api.example.test/ping');
            $siblingAllowedHttp = 200 === $resp['status'];
        });

        $yielded = $restrictedFiber->start();
        self::assertSame('restricted_active', $yielded);

        // Run sibling while restrictedFiber is suspended — must stay isolated
        $siblingFiber->start();
        self::assertTrue($siblingAllowedDb);
        self::assertTrue($siblingAllowedHttp);

        // Resume restrictedFiber so its child Fiber runs inside the active ancestor frame
        $restrictedFiber->resume();
        self::assertTrue($childBlockedDb);
        self::assertTrue($childBlockedHttp);
    }

    public function testQueryBuilderTenantConflictBatchLoaderQualifiedKeyUploadedFileStoreAndSecretScrubber(): void
    {
        $conn = new Round4InMemoryConnection();

        // Conflicting forTenant() must fail closed; idempotent forTenant() succeeds
        $qb = new QueryBuilder('notes', Dialect::Sqlite, $conn);
        $qb->forTenant(1)->forTenant(1);
        try {
            $qb->forTenant(2);
            self::fail('Expected conflicting forTenant() to throw DatabaseException.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('tenant', strtolower($e->getMessage()));
        }

        // BatchLoader::hasMany with qualified `table.col` key
        $parents = [['id' => 1, 'name' => 'Acme']];
        $loaded = BatchLoader::hasMany(
            $conn,
            $parents,
            'notes',
            'notes.org_id',
            'id',
        );
        self::assertCount(1, $loaded[1]);
        self::assertSame('Roadmap', $loaded[1][0]['title']);

        // UploadedFileStore rejects PHP short-echo, namespaced SVG, and control-char explicitKey
        $store = new UploadedFileStore(new MemoryStorage(), ['text/plain', 'image/png']);
        foreach ([
            ['payload' => '<?= $x ?>', 'name' => 'note.txt', 'key' => null],
            ['payload' => '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"></svg:svg>', 'name' => 'pic.txt', 'key' => null],
            ['payload' => 'hello world', 'name' => 'note.txt', 'key' => "\x07note.txt"],
        ] as $case) {
            $upload = UploadedFile::fromString($case['payload'], $case['name'], 'text/plain');
            try {
                $store->store($upload, explicitKey: $case['key']);
                self::fail(sprintf('Expected UploadedFileStore to reject %s', $case['key'] ?? $case['name']));
            } catch (StorageException) {
                $this->addToAssertionCount(1);
            }
        }

        // SecretScrubber redacts dynamically constructed tokens and ErrorExplainer handles >255-byte strings safely
        $fakeStripe = 'sk_' . 'live_' . str_repeat('a1B2', 8);
        $fakeSlack = 'xox' . 'b-' . str_repeat('98765', 6);
        $scrubbed = SecretScrubber::scrub("token={$fakeStripe} slack={$fakeSlack}");
        self::assertStringNotContainsString($fakeStripe, $scrubbed);
        self::assertStringNotContainsString($fakeSlack, $scrubbed);

        $longMsg = str_repeat('InvalidPropertyNameSegment', 20);
        $explainer = new ErrorExplainer();
        $explanation = $explainer->explainThrowable(new RuntimeException($longMsg), availableRoutes: ['GET /' . str_repeat('x', 300)]);
        self::assertNotSame('', $explanation->what);
    }

    public function testViewJsonEscapingHtmlSanitizerOpsBridgeAndDevDashboardHardening(): void
    {
        $tmpViews = $this->makeTempDir('view');
        try {
            file_put_contents($tmpViews . '/json.kgn.html', '<script>const x = @json($data, JSON_PRETTY_PRINT);</script>');
            $engine = new ViewEngine($tmpViews);
            $rendered = $engine->render('json', ['data' => ['x' => "</script><img src=x onerror=alert(1)>'\u{2028}\u{2029}&"]]);
            self::assertStringNotContainsString('</script><img', $rendered);
            self::assertStringNotContainsString("\u{2028}", $rendered);
            self::assertStringNotContainsString("\u{2029}", $rendered);

            try {
                $engine->render("bad?>\nname");
                self::fail('Expected ViewEngine to reject unsafe template name.');
            } catch (ViewException) {
                $this->addToAssertionCount(1);
            }
        } finally {
            $this->removeDir($tmpViews);
        }

        // HtmlSanitizer rejects pre-trim control chars and empty-host URLs
        self::assertStringNotContainsString('javascript:', HtmlSanitizer::sanitize('<a href="' . "\x01" . 'javascript:alert(1)">x</a>'));
        self::assertStringNotContainsString('https://"', HtmlSanitizer::sanitize('<a href="https://">empty</a>'));

        // OpsDashboardMiddleware enforces same-origin and CSRF verifier even without ambient cookies
        $ops = new OpsDashboardMiddleware(
            queueDriver: new \Kongono\Jobs\MemoryQueueDriver(),
            gate: new Gate(),
            csrfTokenVerifier: static fn(ServerRequestInterface $r): bool => 'good-tok' === $r->getHeaderLine('X-CSRF-Token'),
        );
        $opsAdmin = new Principal('admin-1', PrincipalKind::Human, ['ops.view', 'ops.manage']);
        $noopHandler = $this->handler(static fn(): ResponseInterface => Response::text('fallthrough'));

        try {
            $ops->process(
                Request::create('POST', 'https://ops.example.test/_ops/queues/retry', headers: [
                    'Host' => 'ops.example.test',
                    'Origin' => 'https://evil.example.test',
                    'X-CSRF-Token' => 'good-tok',
                ])->withAttribute(Principal::class, $opsAdmin),
                $noopHandler,
            );
            self::fail('Expected cross-origin POST to /_ops/queues/retry to throw HttpException 403.');
        } catch (HttpException $e) {
            self::assertSame(403, $e->statusCode);
        }

        try {
            $ops->process(
                Request::create('POST', 'https://ops.example.test/_ops/queues/retry', headers: [
                    'Host' => 'ops.example.test',
                    'Origin' => 'https://ops.example.test',
                ])->withAttribute(Principal::class, $opsAdmin),
                $noopHandler,
            );
            self::fail('Expected missing CSRF token on /_ops/queues/retry to throw HttpException 419.');
        } catch (HttpException $e) {
            self::assertSame(419, $e->statusCode);
        }

        // BridgeMiddleware adds Vary headers and forces Cache-Control: private, no-store when csrfToken is present
        $bridge = new BridgeMiddleware(version: 'v1', sharedProps: ['csrfToken' => 'csrf-secret-123']);
        $bridgeResp = $bridge->process(
            Request::create('GET', '/dashboard', headers: ['X-Kongono-Bridge' => 'true']),
            $this->handler(static fn(): ResponseInterface => Response::page('Dashboard', ['items' => []])),
        );
        self::assertStringContainsString('X-Kongono-Bridge', $bridgeResp->getHeaderLine('Vary'));
        self::assertStringContainsString('no-store', strtolower($bridgeResp->getHeaderLine('Cache-Control')));

        // DevDashboardMiddleware refuses non-loopback Forwarded/XFF and spoofed Host, and sets strict headers on API
        $app = App::create(dirname(__DIR__, 3))->debug(true);
        $devMw = new DevDashboardMiddleware($app, new \Kongono\Http\Dev\DevInspector());
        $spoofedHostResp = $devMw->process(
            Request::create('GET', '/_kongono/api/traces', headers: ['Host' => 'evil.example'], serverParams: ['REMOTE_ADDR' => '127.0.0.1']),
            $noopHandler,
        );
        self::assertSame(403, $spoofedHostResp->getStatusCode());

        $forwardedResp = $devMw->process(
            Request::create('GET', '/_kongono/api/traces', headers: ['Host' => '127.0.0.1:8000', 'X-Forwarded-For' => '203.0.113.5'], serverParams: ['REMOTE_ADDR' => '127.0.0.1']),
            $noopHandler,
        );
        self::assertSame(403, $forwardedResp->getStatusCode());

        $okDevResp = $devMw->process(
            Request::create('GET', '/_kongono/api/traces', headers: ['Host' => '127.0.0.1:8000'], serverParams: ['REMOTE_ADDR' => '127.0.0.1']),
            $noopHandler,
        );
        self::assertSame(200, $okDevResp->getStatusCode());
        self::assertStringContainsString('no-store', strtolower($okDevResp->getHeaderLine('Cache-Control')));
        self::assertSame('nosniff', $okDevResp->getHeaderLine('X-Content-Type-Options'));
        self::assertSame('same-origin', $okDevResp->getHeaderLine('Cross-Origin-Resource-Policy'));
    }

    public function testSessionHttpExceptionCleanupCsrfCorsIdempotencyCacheCompressionStaticCookieAndReferer(): void
    {
        // 1. SessionMiddleware destroys invalidated sessions and attaches Set-Cookie even when downstream throws HttpException
        $sessionStore = new CacheSessionStore(new MemoryCache());
        $oldId = bin2hex(random_bytes(24));
        $sessionStore->write($oldId, ['user_id' => 'u1', '_created_at' => time()], 3600);
        $sessionMw = new SessionMiddleware($sessionStore, cookieName: 'kgn_sid', secure: true);

        try {
            $sessionMw->process(
                Request::create('POST', 'https://app.test/logout')->withCookieParams(['kgn_sid' => $oldId]),
                $this->handler(static function (ServerRequestInterface $req): ResponseInterface {
                    $sess = $req->getAttribute(Session::class);
                    self::assertInstanceOf(Session::class, $sess);
                    $sess->invalidate();
                    throw (new HttpException(401, 'Session ended'))->withAddedHeader('Set-Cookie', 'remember=; Max-Age=0');
                }),
            );
            self::fail('Expected HttpException 401.');
        } catch (HttpException $e) {
            self::assertSame(401, $e->statusCode);
            self::assertNull($sessionStore->read($oldId));
            $setCookies = $e->headers['Set-Cookie'] ?? null;
            self::assertIsArray($setCookies);
            self::assertCount(2, $setCookies);
            self::assertStringContainsString('no-cache="Set-Cookie"', $e->headerLine('Cache-Control'));
        }

        // Verify SessionAuthMiddleware uses withAddedHeader so existing Set-Cookie headers are preserved
        $remember = new RememberMeManager(new MemoryCache(), cookieName: 'kgn_rem', secure: true);
        $authMw = new SessionAuthMiddleware(rememberMe: $remember);
        try {
            $authMw->process(
                Request::create('GET', 'https://app.test/me')
                    ->withCookieParams(['kgn_rem' => 'invalid-cookie'])
                    ->withAttribute(Session::class, new Session(bin2hex(random_bytes(24)))),
                $this->handler(static function (): ResponseInterface {
                    throw (new HttpException(401, 'Auth required'))->withAddedHeader('Set-Cookie', 'other=1');
                }),
            );
            self::fail('Expected HttpException 401.');
        } catch (HttpException $e) {
            self::assertIsArray($e->headers['Set-Cookie'] ?? null);
            self::assertCount(2, $e->headers['Set-Cookie']);
        }

        // 2. CsrfMiddleware rejects Bearer bypass when ANY ambient cookie is sent, and normalizes trailing slashes
        $csrf = new CsrfMiddleware(new TokenSigner(str_repeat('k', 32)), exemptPrefixes: ['/api']);
        try {
            $csrf->process(
                Request::create('POST', '/notes', headers: ['Authorization' => 'Bearer tok-123', 'Cookie' => 'ambient=1']),
                $this->handler(static fn(): ResponseInterface => Response::text('ok')),
            );
            self::fail('Expected CsrfMiddleware to reject Bearer request carrying ambient Cookie header.');
        } catch (HttpException $e) {
            self::assertSame(403, $e->statusCode);
        }

        // Exact exempt prefix `/api` matches `/api/` safely, while `/api-v2` does NOT match `/api`
        $okExempt = $csrf->process(
            Request::create('POST', '/api/'),
            $this->handler(static fn(): ResponseInterface => Response::text('ok')),
        );
        self::assertSame(200, $okExempt->getStatusCode());
        try {
            $csrf->process(
                Request::create('POST', '/api-v2/items'),
                $this->handler(static fn(): ResponseInterface => Response::text('ok')),
            );
            self::fail('Expected /api-v2/items not to match /api exempt prefix.');
        } catch (HttpException $e) {
            self::assertSame(403, $e->statusCode);
        }

        // 3. CorsMiddleware updates Vary case-insensitively without duplicates
        $cors = new CorsMiddleware(allowedOrigins: ['https://app.example.test']);
        $corsResp = $cors->process(
            Request::create('GET', '/data', headers: ['Origin' => 'https://app.example.test']),
            $this->handler(static fn(): ResponseInterface => Response::text('hi')->withHeader('vary', 'accept-encoding, origin')),
        );
        self::assertSame(1, substr_count(strtolower($corsResp->getHeaderLine('Vary')), 'origin'));

        // 4. IdempotencyMiddleware enforces maxRequestBytes (413) & compareAndDelete lock ownership
        $cache = new MemoryCache();
        self::assertTrue($cache->add('lock:1', 'owner-a', 60));
        self::assertFalse($cache->compareAndDelete('lock:1', 'owner-b'));
        self::assertTrue($cache->compareAndDelete('lock:1', 'owner-a'));

        $idemp = new IdempotencyMiddleware($cache, maxRequestBytes: 16);
        try {
            $idemp->process(
                Request::create('POST', '/pay', headers: ['Idempotency-Key' => 'k-413'], body: str_repeat('A', 32)),
                $this->handler(static fn(): ResponseInterface => Response::text('charged')),
            );
            self::fail('Expected 413 on oversized IdempotencyMiddleware request.');
        } catch (HttpException $e) {
            self::assertSame(413, $e->statusCode);
        }

        // 5. HttpCacheMiddleware preserves stricter max-age & must-revalidate, and skips oversized ETag
        $httpCache = new HttpCacheMiddleware(maxAge: 300, public: true, etag: true, maxEtagBytes: 16);
        $cachedResp = $httpCache->process(
            Request::create('GET', '/feed'),
            $this->handler(static fn(): ResponseInterface => Response::text(str_repeat('X', 32))->withHeader('Cache-Control', 'public, max-age=15, must-revalidate')),
        );
        $cc = $cachedResp->getHeaderLine('Cache-Control');
        self::assertStringContainsString('max-age=15', $cc);
        self::assertStringContainsString('must-revalidate', $cc);
        self::assertFalse($cachedResp->hasHeader('ETag'));

        // 6. CompressionMiddleware skips active-session BREACH compression and emits Vary: Accept-Encoding when cacheable
        $compress = new CompressionMiddleware(minBytes: 16);
        $activeSession = new Session(bin2hex(random_bytes(24)), ['csrf' => 'secret'], persisted: true);
        $breachSafe = $compress->process(
            Request::create('GET', '/account', headers: ['Accept-Encoding' => 'gzip'])->withAttribute(Session::class, $activeSession),
            $this->handler(static fn(): ResponseInterface => Response::html(str_repeat('<p>secret</p>', 30))),
        );
        self::assertSame('', $breachSafe->getHeaderLine('Content-Encoding'));

        $uncompressedCacheable = $compress->process(
            Request::create('GET', '/public'),
            $this->handler(static fn(): ResponseInterface => Response::text(str_repeat('public-data-', 30))->withHeader('Cache-Control', 'public, max-age=60')),
        );
        self::assertSame('', $uncompressedCacheable->getHeaderLine('Content-Encoding'));
        self::assertStringContainsString('Accept-Encoding', $uncompressedCacheable->getHeaderLine('Vary'));

        // 7. StaticFilesMiddleware normalizes prefix `/assets` -> `/assets/` so `/assets-v2` does NOT match
        $pubDir = $this->makeTempDir('static');
        try {
            file_put_contents($pubDir . '/app.js', 'console.log(1);');
            $staticMw = new StaticFilesMiddleware($pubDir, urlPrefix: '/assets');
            $missResp = $staticMw->process(
                Request::create('GET', '/assets-v2/app.js'),
                $this->handler(static fn(): ResponseInterface => Response::text('passed-through')),
            );
            self::assertSame('passed-through', (string) $missResp->getBody());
        } finally {
            $this->removeDir($pubDir);
        }

        // 8. Cookie enforces __Host- and __Secure- case-insensitively, and Response::redirect trims URL
        try {
            new Cookie('__host-sid', 'abc', secure: false, path: '/');
            self::fail('Expected case-insensitive __host- cookie without Secure to fail.');
        } catch (\InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }
        try {
            new Cookie('__secure-sid', 'abc', secure: false, path: '/');
            self::fail('Expected case-insensitive __secure- cookie without Secure to fail.');
        } catch (\InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }
        $redir = Response::redirect('  /home?ok=1  ');
        self::assertSame('/home?ok=1', $redir->getHeaderLine('Location'));

        // 9. App::resolveSafeSameOriginReferer normalizes standard ports and blocks scheme downgrade
        $refApp = App::create(dirname(__DIR__, 3));
        $refMethod = new \ReflectionMethod(App::class, 'resolveSafeSameOriginReferer');
        $sameOriginDefaultPort = $refMethod->invoke(
            $refApp,
            Request::create('POST', 'https://app.example.test/form', headers: ['Referer' => 'https://app.example.test:443/back?step=2']),
        );
        self::assertSame('/back?step=2', $sameOriginDefaultPort);

        $downgradedScheme = $refMethod->invoke(
            $refApp,
            Request::create('POST', 'https://app.example.test/form', headers: ['Referer' => 'http://app.example.test/back']),
        );
        self::assertNull($downgradedScheme);
    }

    public function testTotpRememberMePatLoginThrottlerPasskeyAndValidatorHardening(): void
    {
        // 1. TOTP monotonic watermark in replayCache rejects backward step replay
        $totp = new Totp();
        $secret = Totp::generateSecret();
        $replayCache = new MemoryCache();
        $tNow = 1_700_000_060;
        $codeCurrent = $totp->code($secret, $tNow);
        $codePrevious = $totp->code($secret, $tNow - 30);

        self::assertTrue($totp->verify($secret, $codeCurrent, window: 1, timestamp: $tNow, replayCache: $replayCache));
        self::assertFalse($totp->verify($secret, $codePrevious, window: 1, timestamp: $tNow, replayCache: $replayCache));

        // 2. RememberMeManager revocation generation invalidates existing series cookies
        $remCache = new MemoryCache();
        $rem = new RememberMeManager($remCache, secure: true);
        $user = new Principal('u-42', PrincipalKind::Human, ['notes.read']);
        $cookie = $rem->issue($user, ttlSeconds: 3600, now: $tNow);
        $rem->revokeAllForPrincipal('u-42');
        self::assertNull($rem->consumeAndRotate($cookie->value, now: $tNow));

        // 3. CachePersonalAccessTokenStore same-second revokeAll + issue keeps new token valid while revoking old
        $patStore = new CachePersonalAccessTokenStore(new MemoryCache());
        $oldTok = $patStore->issue($user, 'old', ttlSeconds: 3600, now: $tNow);
        $patStore->revokeAllForPrincipal('u-42');
        $newTok = $patStore->issue($user, 'new', ttlSeconds: 3600, now: $tNow);
        self::assertNull($patStore->authenticate($oldTok->plainTextToken, now: $tNow));
        self::assertNotNull($patStore->authenticate($newTok->plainTextToken, now: $tNow));

        // 4. PersonalAccessToken fails closed (returns null) on unknown principal_kind
        self::assertNull(PersonalAccessToken::fromArray([
            'id' => 'tok1',
            'principal_id' => 'u-1',
            'principal_kind' => 'superadmin_unknown',
            'name' => 'bad',
            'capabilities' => [],
            'token_hash' => hash('sha256', 'x'),
            'created_at' => $tNow,
            'expires_at' => null,
            'last_used_at' => null,
            'attributes' => [],
        ]));

        // 5. LoginThrottler canonicalizes IPv6 variants and IPv4-mapped IPv6
        $throttler = new LoginThrottler(new MemoryCache(), maxAttempts: 2, lockoutSeconds: 300);
        $throttler->recordFailure('alice@example.test', '2001:0db8:0000:0000:0000:0000:0000:0001', $tNow);
        $throttler->recordFailure('alice@example.test', '2001:db8::1', $tNow);
        self::assertTrue($throttler->isLockedOut('alice@example.test', '2001:db8::1', $tNow));

        $throttler->recordFailure('bob@example.test', '::ffff:192.0.2.10', $tNow);
        $throttler->recordFailure('bob@example.test', '192.0.2.10', $tNow);
        self::assertTrue($throttler->isLockedOut('bob@example.test', '192.0.2.10', $tNow));

        // 6. PasskeyVerifier rejects exact-expiry ($now === $expiresAt) and invalid base64url characters
        $passkey = new PasskeyVerifier(new MemoryCache());
        $challenge = $passkey->createChallenge('Example.Test', 'HTTPS://Example.Test/', ttlSeconds: 60, now: $tNow);
        self::assertSame('https://example.test', $challenge['origin']);
        try {
            $passkey->verifyAssertion($challenge['challenge'], 'e30', 'AAAA', 'AAAA', 'pk', now: $challenge['expiresAt']);
            self::fail('Expected exact-expiry passkey challenge to throw SecurityException.');
        } catch (SecurityException $e) {
            self::assertStringContainsString('expired', strtolower($e->getMessage()));
        }

        $challenge2 = $passkey->createChallenge('example.test', 'https://example.test', ttlSeconds: 60, now: $tNow);
        try {
            $passkey->verifyAssertion($challenge2['challenge'], 'not+valid/base64url===', 'AAAA', 'AAAA', 'pk', now: $tNow + 1);
            self::fail('Expected non-base64url clientDataJSON to throw SecurityException.');
        } catch (SecurityException) {
            $this->addToAssertionCount(1);
        }

        // 7. Validator clones non-enum object defaults, rejects invalid #[Regex], coerces #[Confirmed]
        $validator = new Validator();
        $dto1 = $validator->validate(Round4ObjectDefaultDto::class, ['pin' => 4242, 'pin_confirmation' => '4242']);
        $dto2 = $validator->validate(Round4ObjectDefaultDto::class, ['pin' => 4242, 'pin_confirmation' => '4242']);
        self::assertNotSame($dto1->meta, $dto2->meta);
        self::assertSame(4242, $dto1->pin);

        try {
            new Regex('/[unterminated/');
            self::fail('Expected invalid #[Regex] pattern to fail in constructor.');
        } catch (\InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }
    }

    public function testAiMcpShadowProjectContextBenchmarkScaffolderDeployAndLspHardening(): void
    {
        // 1. ProjectContext blocks expanded secret files & tails logs safely
        $tmpProj = $this->makeTempDir('proj');
        try {
            file_put_contents($tmpProj . '/secrets.json', '{"api_key":"secret"}');
            file_put_contents($tmpProj . '/prod.env', 'APP_KEY=secret');
            mkdir($tmpProj . '/.aws', 0o777, true);
            file_put_contents($tmpProj . '/.aws/config', '[default]');
            mkdir($tmpProj . '/storage/incidents', 0o777, true);
            file_put_contents($tmpProj . '/storage/incidents/KGN-20261125-ABCDEF01.json', '{}');
            file_put_contents($tmpProj . '/app.log', str_repeat("OLD LINE\n", 100) . "LATEST_ERROR_MARKER\n");

            $ctx = ProjectContext::discover($tmpProj);
            foreach (['secrets.json', 'prod.env', '.aws/config', 'storage/incidents/KGN-20261125-ABCDEF01.json'] as $forbidden) {
                try {
                    $ctx->readFileSafe($forbidden);
                    self::fail(sprintf('Expected ProjectContext::readFileSafe to block %s', $forbidden));
                } catch (CoreException) {
                    $this->addToAssertionCount(1);
                }
            }
            $tail = $ctx->readFileTailSafe('app.log', 128);
            self::assertStringContainsString('LATEST_ERROR_MARKER', $tail['content']);
        } finally {
            $this->removeDir($tmpProj);
        }

        // 2. PromptGuard minimum canary length & alphanumeric streaming holdback
        $guard = new PromptGuard();
        try {
            $guard->buildProtectedSystemPrompt('Be helpful.', canaryOverride: 'short123');
            self::fail('Expected short custom canary override to throw CoreException.');
        } catch (CoreException) {
            $this->addToAssertionCount(1);
        }

        $protected = $guard->buildProtectedSystemPrompt('Be helpful.');
        $emitted = '';
        $streamFilter = $guard->createStreamingFilter($protected['canary'], static function (string $chunk) use (&$emitted): void {
            $emitted .= $chunk;
        });
        // Obfuscate canary with punctuation spaces so byte length grows while alnum matches canary
        $spacedCanary = implode(' . ', str_split($protected['canary']));
        $streamBlocked = false;
        try {
            foreach (str_split('Prefix text ' . $spacedCanary . ' suffix', 7) as $part) {
                $streamFilter['push']($part);
            }
            $streamFilter['flush']();
        } catch (CoreException) {
            $streamBlocked = true;
        }
        self::assertTrue($streamBlocked, 'Expected streaming filter to block obfuscated canary before flushing.');
        self::assertStringNotContainsString($protected['canary'], $emitted);

        // 3. AgentRunner pre-scans approvals before executing ANY tool & blocks split canary across parallel calls
        $canary = 'kgn_canary_0123456789abcdef01234567';
        $safeToolExecuted = false;
        $approvalTransport = new Round4ScriptedTransport([
            json_encode([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [
                            ['id' => 'tc1', 'type' => 'function', 'function' => ['name' => 'read_doc', 'arguments' => '{}']],
                            ['id' => 'tc2', 'type' => 'function', 'function' => ['name' => 'delete_db', 'arguments' => '{}']],
                        ],
                    ],
                ]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10, 'total_tokens' => 20],
            ], JSON_THROW_ON_ERROR),
        ]);
        $runner = new AgentRunner(
            new AiClient(new OpenAiProvider('test-key', transport: $approvalTransport)),
            new AgentPrincipal('agent-1', 'worker', allowedTools: ['read_doc', 'delete_db'], approvalRequiredTools: ['delete_db']),
        );
        $receipt = $runner->run(
            [Message::system('Prompt ' . $canary), Message::user('Do both')],
            [
                new ToolDefinition('read_doc', 'Read', ['type' => 'object', 'properties' => []]),
                new ToolDefinition('delete_db', 'Delete', ['type' => 'object', 'properties' => []]),
            ],
            static function (ToolCall $_call) use (&$safeToolExecuted): string {
                $safeToolExecuted = true;

                return 'ok';
            },
        );
        self::assertSame('awaiting_approval', $receipt->status);
        self::assertSame('delete_db', $receipt->pendingApproval?->name);
        self::assertFalse($safeToolExecuted, 'Earlier safe tool in same turn must not execute when a later tool requires approval.');

        // Split canary across two parallel tool calls must be caught before execution
        $half = intdiv(strlen($canary), 2);
        $splitTransport = new Round4ScriptedTransport([
            json_encode([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [
                            ['id' => 'tc1', 'type' => 'function', 'function' => ['name' => 'echo_tool', 'arguments' => json_encode(['part' => substr($canary, 0, $half)], JSON_THROW_ON_ERROR)]],
                            ['id' => 'tc2', 'type' => 'function', 'function' => ['name' => 'echo_tool', 'arguments' => json_encode(['part' => substr($canary, $half)], JSON_THROW_ON_ERROR)]],
                        ],
                    ],
                ]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10, 'total_tokens' => 20],
            ], JSON_THROW_ON_ERROR),
        ]);
        $splitRunner = new AgentRunner(
            new AiClient(new OpenAiProvider('test-key', transport: $splitTransport)),
            new AgentPrincipal('agent-2', 'worker', allowedTools: ['echo_tool']),
        );
        $splitExecuted = false;
        try {
            $splitRunner->run(
                [Message::system('Internal ' . $canary), Message::user('Leak split')],
                [new ToolDefinition('echo_tool', 'Echo', ['type' => 'object', 'properties' => ['part' => ['type' => 'string']]])],
                static function (ToolCall $_call) use (&$splitExecuted): string {
                    $splitExecuted = true;

                    return 'leaked';
                },
            );
            self::fail('Expected split canary across parallel tool calls to be blocked.');
        } catch (AiException) {
            self::assertFalse($splitExecuted);
        }

        // 4. McpServer validates JSON-RPC id types, rejects list arguments, enforces enum & array vs object
        $mcp = new McpServer();
        $mcp->registerTool(
            new ToolDefinition('typed_tool', 'Typed', [
                'type' => 'object',
                'required' => ['mode'],
                'properties' => [
                    'mode' => ['type' => 'string', 'enum' => ['fast', 'safe']],
                    'tags' => ['type' => 'array'],
                    'meta' => ['type' => 'object'],
                ],
            ]),
            static fn(array $_a): string => 'ok',
        );
        $badId = json_decode((string) $mcp->handleJson('{"jsonrpc":"2.0","id":{"nested":1},"method":"tools/list"}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($badId);
        self::assertSame(-32600, $badId['error']['code'] ?? null);

        $listArgs = json_decode((string) $mcp->handleJson('{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"typed_tool","arguments":["fast"]}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($listArgs);
        self::assertSame(-32602, $listArgs['error']['code'] ?? null);

        $badEnum = json_decode((string) $mcp->handleJson('{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"typed_tool","arguments":{"mode":"unsafe"}}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($badEnum);
        self::assertSame(-32602, $badEnum['error']['code'] ?? null);

        $mapAsArray = json_decode((string) $mcp->handleJson('{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"typed_tool","arguments":{"mode":"fast","tags":{"a":1}}}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($mapAsArray);
        self::assertSame(-32602, $mapAsArray['error']['code'] ?? null);

        $listAsObject = json_decode((string) $mcp->handleJson('{"jsonrpc":"2.0","id":4,"method":"tools/call","params":{"name":"typed_tool","arguments":{"mode":"fast","meta":["a"]}}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($listAsObject);
        self::assertSame(-32602, $listAsObject['error']['code'] ?? null);

        // 5. MemoryVectorStore clamps negative/zero limit
        $vec = new MemoryVectorStore();
        $vec->upsert(new VectorDocument('d1', 'hello', [1.0, 0.0]));
        self::assertCount(1, $vec->search([1.0, 0.0], limit: -5));

        // 6. ShadowDiffProver rejects double-encoded traversal, dot segments, and fragment-masked internal endpoints
        $probeApp = App::create(dirname(__DIR__, 3));
        $probeApp->router()->get('/ok', static fn(): Response => Response::text('ok'));
        $recorded = (new ShadowDiffProver())->record($probeApp, [
            '/ok',
            '/%252e%252e/admin',
            '/./ok',
            '/_ops#frag',
            '/_dev#frag',
        ]);
        self::assertSame(['/ok'], array_keys($recorded['entries']));

        // 7. AgentCodingBenchmark rejects `..` traversal in exportJsonlPath
        try {
            (new AgentCodingBenchmark())->run(null, '../escaped.jsonl');
            self::fail('Expected AgentCodingBenchmark to reject traversal exportJsonlPath.');
        } catch (\InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        // 8. CodeGenerator::makePolicy defaults to ownership/admin check (no IDOR `return true`)
        $policyTmp = $this->makeTempDir('policy');
        try {
            $policyPath = (new CodeGenerator())->makePolicy(ProjectContext::discover($policyTmp), 'InvoicePolicy');
            $policyCode = (string) file_get_contents($policyTmp . DIRECTORY_SEPARATOR . $policyPath);
            self::assertStringContainsString("hasCapability('admin')", $policyCode);
            self::assertStringContainsString("['user_id']", $policyCode);
            self::assertStringNotContainsString("public function view(Principal \$principal): bool\n    {\n        return true;", $policyCode);
        } finally {
            $this->removeDir($policyTmp);
        }

        // 9. AuthScaffolder includes dummy Argon2id constant-time password verification
        $authTmp = $this->makeTempDir('auth');
        try {
            (new AuthScaffolder())->scaffold(ProjectContext::discover($authTmp), mode: 'both');
            $webCtrl = (string) file_get_contents($authTmp . '/src/Http/WebAuthController.php');
            $apiCtrl = (string) file_get_contents($authTmp . '/src/Http/ApiAuthController.php');
            self::assertStringContainsString('kongono-auth-dummy-timing-guard', $webCtrl);
            self::assertStringContainsString('kongono-auth-dummy-timing-guard', $apiCtrl);
        } finally {
            $this->removeDir($authTmp);
        }

        // 10. DeployBlueprintGenerator rejects embedded credentials in gitRepo & mounts writable /app/storage
        $deploy = new DeployBlueprintGenerator();
        $files = $deploy->generate('demo', 'demo.example.test');
        self::assertStringContainsString('/app/storage', $files['docker-compose.prod.yml']);
        try {
            $deploy->generate('demo', 'demo.example.test', gitRepo: 'https://user:pass@github.com/org/repo.git');
            self::fail('Expected embedded git credentials to be rejected.');
        } catch (\InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        // 11. KongonoLanguageServer bounds oversized header lines without crashing
        $lsp = new KongonoLanguageServer(dirname(__DIR__, 3), $probeApp);
        $in = fopen('php://memory', 'r+b');
        $out = fopen('php://memory', 'r+b');
        self::assertIsResource($in);
        self::assertIsResource($out);
        fwrite($in, 'X-Oversized: ' . str_repeat('A', 12000) . "\r\n\r\n");
        rewind($in);
        self::assertSame(0, $lsp->serveStreams($in, $out));
        fclose($in);
        fclose($out);

        // 12. KongonoMcpFactory enforces hardened JSON path denylist (blocks secrets.json and .env)
        $mcpApp = KongonoMcpFactory::forApp(dirname(__DIR__, 3) . '/examples/starter-app');
        foreach (['secrets.json', '.env'] as $blockedTarget) {
            $badShadow = json_decode((string) $mcpApp->handleJson(json_encode([
                'jsonrpc' => '2.0',
                'id' => 9,
                'method' => 'tools/call',
                'params' => ['name' => 'kongono_shadow_diff', 'arguments' => ['against' => $blockedTarget]],
            ], JSON_THROW_ON_ERROR)), true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($badShadow);
            $text = is_string($badShadow['result']['content'][0]['text'] ?? null) ? $badShadow['result']['content'][0]['text'] : '';
            self::assertStringContainsString('not allowed', $text);
        }
    }

    public function testAikidoSecurityAndConcurrencyRegressions(): void
    {
        $tNow = 1_700_000_060;

        // 1. Concurrent RememberMeManager::consumeAndRotate() cannot bypass revokeAllForPrincipal() or resurrect a revoked series
        $baseCache = new MemoryCache();
        $raceTriggered = false;
        $remHolder = new class {
            public ?RememberMeManager $manager = null;
        };
        $interceptingCache = new Round4InterceptingCache(
            $baseCache,
            onSet: static function (string $key) use (&$raceTriggered, $remHolder): void {
                if (!$raceTriggered && str_contains($key, 'used:') && null !== $remHolder->manager) {
                    $raceTriggered = true;
                    $remHolder->manager->revokeAllForPrincipal('u-race');
                }
            },
        );
        $rem = new RememberMeManager($interceptingCache, secure: true);
        $remHolder->manager = $rem;
        $raceUser = new Principal('u-race', PrincipalKind::Human, ['notes.read']);
        $issuedCookie = $rem->issue($raceUser, ttlSeconds: 3600, now: $tNow);
        $rotated = $rem->consumeAndRotate($issuedCookie->value, now: $tNow);
        self::assertTrue($raceTriggered);
        self::assertNull($rotated, 'Concurrent revokeAllForPrincipal during rotation must fail closed.');

        // 2. MySQL PostgresQueueDriver::retryFailed() enforces unique_key deduplication on both single and bulk retry
        $mysqlConn = new Round4InMemoryConnection(Dialect::Mysql);
        $queueDriver = new PostgresQueueDriver($mysqlConn);
        $queueDriver->retryFailed('default', 'job-123', $tNow);
        $queueDriver->retryFailed('default', null, $tNow);
        self::assertCount(2, $mysqlConn->executedSql);
        self::assertStringContainsString('unique_key NOT IN', $mysqlConn->executedSql[0]);
        self::assertStringContainsString('unique_key NOT IN', $mysqlConn->executedSql[1]);
        self::assertStringContainsString('ROW_NUMBER() OVER (PARTITION BY unique_key', $mysqlConn->executedSql[1]);

        // 3. IncidentRecord & FileIncidentStore enforce hard node, header, and file-size bounds
        $hugePayload = [];
        for ($i = 0; $i < 400; ++$i) {
            $hugePayload['k_' . $i] = str_repeat('Z', 2000);
        }
        $sanitizedPayload = IncidentRecord::sanitizePayload($hugePayload);
        self::assertTrue($sanitizedPayload['_truncated'] ?? false);
        self::assertLessThanOrEqual(65, count($sanitizedPayload));

        $hugeHeaders = [];
        for ($i = 0; $i < 100; ++$i) {
            $hugeHeaders['X-Flood-' . $i] = str_repeat('H', 1500);
        }
        $sanitizedHeaders = IncidentRecord::sanitizeHeaders($hugeHeaders);
        self::assertSame('true', $sanitizedHeaders['_truncated'] ?? null);
        self::assertLessThanOrEqual(65, count($sanitizedHeaders));

        $incDir = $this->makeTempDir('inc_bound');
        try {
            $incStore = new FileIncidentStore($incDir);
            $hugeBody = [];
            for ($i = 0; $i < 400; ++$i) {
                $hugeBody['f' . $i] = str_repeat('B', 2000);
            }
            $incStore->record(new IncidentRecord(
                reference: 'KGN-20261125-ABCDEF01',
                occurredAt: '2026-11-25T19:00:00Z',
                appName: 'BoundTest',
                statusCode: 500,
                method: 'POST',
                path: '/boom',
                query: [],
                headers: [],
                body: $hugeBody,
                contentType: 'application/json',
                explanation: ['code' => 'E', 'what' => 'boom'],
            ));
            $savedPath = $incDir . DIRECTORY_SEPARATOR . 'KGN-20261125-ABCDEF01.json';
            self::assertFileExists($savedPath);
            self::assertLessThanOrEqual(262_144, (int) filesize($savedPath));
        } finally {
            $this->removeDir($incDir);
        }

        // 4. AuthScaffolder revokes optional RememberMeManager on logout & throttles registration; AppScaffolder wires RateLimit + principalResolver
        $saasDir = $this->makeTempDir('saas_scaffold');
        try {
            (new AppScaffolder())->scaffold($saasDir, 'Acme SaaS', 'saas');
            $bootstrap = (string) file_get_contents($saasDir . '/app/bootstrap.php');
            $webCtrl = (string) file_get_contents($saasDir . '/src/Http/WebAuthController.php');
            $apiCtrl = (string) file_get_contents($saasDir . '/src/Http/ApiAuthController.php');
            self::assertStringContainsString('RateLimitMiddleware', $bootstrap);
            self::assertStringContainsString('principalResolver:', $bootstrap);
            self::assertStringContainsString('maxAccountAttempts: 0', $bootstrap);
            self::assertStringContainsString('RememberMeManager', $webCtrl);
            self::assertStringContainsString('revokeCookie', $webCtrl);
            self::assertStringContainsString('expiredCookie', $webCtrl);
            self::assertStringContainsString("'register:' . \$ip", $webCtrl);
            self::assertStringContainsString("'register:' . \$ip", $apiCtrl);
        } finally {
            $this->removeDir($saasDir);
        }

        // 5. ToolDefinition::fromCallable() & AgentRunner::runWithHandlers() enforce method-level #[RequiresApproval]
        $dispatcher = new Round4AnnotatedDispatcher();
        $readDef = ToolDefinition::fromCallable('read_item', 'Read', ['type' => 'object', 'properties' => []], $dispatcher->readItem(...));
        $deleteDef = ToolDefinition::fromCallable('delete_item', 'Delete', ['type' => 'object', 'properties' => []], $dispatcher->deleteItem(...));
        self::assertFalse($readDef->requiresApproval);
        self::assertTrue($deleteDef->requiresApproval);

        $dispatchTransport = new Round4ScriptedTransport([
            json_encode([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [
                            ['id' => 't1', 'type' => 'function', 'function' => ['name' => 'read_item', 'arguments' => '{}']],
                            ['id' => 't2', 'type' => 'function', 'function' => ['name' => 'delete_item', 'arguments' => '{}']],
                        ],
                    ],
                ]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10, 'total_tokens' => 20],
            ], JSON_THROW_ON_ERROR),
        ]);
        $handlerRunner = new AgentRunner(
            new AiClient(new OpenAiProvider('test-key', transport: $dispatchTransport)),
            new AgentPrincipal('agent-dispatch', 'worker', allowedTools: ['read_item', 'delete_item']),
        );
        $dispatchReceipt = $handlerRunner->runWithHandlers(
            [Message::user('Read then delete')],
            [
                new ToolDefinition('read_item', 'Read', ['type' => 'object', 'properties' => []]),
                new ToolDefinition('delete_item', 'Delete', ['type' => 'object', 'properties' => []]),
            ],
            [
                'read_item' => $dispatcher->readItem(...),
                'delete_item' => $dispatcher->deleteItem(...),
            ],
        );
        self::assertSame('awaiting_approval', $dispatchReceipt->status);
        self::assertSame('delete_item', $dispatchReceipt->pendingApproval?->name);
        self::assertSame(0, $dispatcher->readCalls, 'Dispatcher-backed approval gate must pause before executing earlier tools.');

        // 6. PolicyVerifier fails closed when app/bootstrap.php exists and fails to boot ($app === null)
        $brokenProj = $this->makeTempDir('policy_fail_closed');
        try {
            mkdir($brokenProj . '/app', 0o777, true);
            file_put_contents($brokenProj . '/app/bootstrap.php', "<?php\nthrow new \\RuntimeException('broken bootstrap');\n");
            $policyResult = (new PolicyVerifier())->verify($brokenProj, app: null);
            self::assertFalse($policyResult['passed']);
            $codes = array_column($policyResult['violations'], 'code');
            self::assertContains('KGN-POLICY-BOOTSTRAP-FAILED', $codes);
        } finally {
            $this->removeDir($brokenProj);
        }

        // 7. ChieBuilder::applyChanges() rejects duplicate canonical paths (collapsing approval identity)
        $chieDir = $this->makeTempDir('chie_dup');
        try {
            $chie = new ChieBuilder(new AiClient(new OpenAiProvider('test-key', transport: new Round4ScriptedTransport([]))));
            $chie->applyChanges([
                ['path' => 'src/Foo.php', 'action' => 'create', 'explanation' => 'safe', 'content' => "<?php\ndeclare(strict_types=1);\n"],
                ['path' => './src/Foo.php', 'action' => 'update', 'explanation' => 'overwrite', 'content' => "<?php\ndeclare(strict_types=1);\n// second\n"],
            ], ProjectContext::discover($chieDir, allowBootstrap: false), ['src/Foo.php']);
            self::fail('Expected ChieBuilder::applyChanges to reject duplicate canonical paths.');
        } catch (CoreException $e) {
            self::assertStringContainsString('duplicate', strtolower($e->getMessage()));
        } finally {
            $this->removeDir($chieDir);
        }

        // 8. TenantIsolationProver skips mutating probes under readOnly: true (used by read-only MCP)
        $mutated = false;
        $isoApp = App::create(dirname(__DIR__, 3));
        $isoApp->router()->post('/api/items/1/delete', static function () use (&$mutated): Response {
            $mutated = true;

            return Response::json(['deleted' => true]);
        });
        $isoApp->router()->get('/api/items/1', static fn(): Response => Response::json(['error' => 'forbidden'], 403));
        $owner = new Principal('owner-1', PrincipalKind::Human, [], ['tenant_id' => 't1']);
        $attacker = new Principal('attacker-2', PrincipalKind::Human, [], ['tenant_id' => 't2']);
        $isoReport = (new TenantIsolationProver())->prove(
            dirname(__DIR__, 3),
            $isoApp,
            probes: [
                new IsolationProbe('delete-probe', 'POST', '/api/items/1/delete', $owner, $attacker),
                new IsolationProbe('read-probe', 'GET', '/api/items/1', $owner, $attacker),
            ],
            readOnly: true,
        );
        self::assertFalse($mutated, 'Read-only isolation prover must never execute mutating POST probes.');
        self::assertSame(1, $isoReport['skipped_mutating_probes']);
        self::assertSame(1, $isoReport['tenant_probes_executed']);

        // 9. ProjectContext::discover(..., allowBootstrap: false) and KongonoMcpFactory::forApp(..., allowBootstrap: false) never execute app/bootstrap.php
        $untrustedDir = $this->makeTempDir('untrusted_repo');
        try {
            mkdir($untrustedDir . '/app', 0o777, true);
            $pwnMarker = $untrustedDir . '/pwned.txt';
            file_put_contents($untrustedDir . '/app/bootstrap.php', "<?php\nfile_put_contents(" . var_export($pwnMarker, true) . ", 'exec');\n");
            $ctx = ProjectContext::discover($untrustedDir, allowBootstrap: false);
            self::assertNull($ctx->app);
            KongonoMcpFactory::forApp($untrustedDir, allowBootstrap: false);
            self::assertFileDoesNotExist($pwnMarker, 'Static-only safe mode must never execute untrusted app/bootstrap.php.');
        } finally {
            $this->removeDir($untrustedDir);
        }

        // 10. DeployBlueprintGenerator deploy/deploy.sh requires pinned DEPLOY_REF (immutable SHA or tag) and supports signature verification
        $deployFiles = (new DeployBlueprintGenerator())->generate('acme', 'acme.example.test');
        $deploySh = $deployFiles['deploy/deploy.sh'];
        self::assertStringContainsString('DEPLOY_REF', $deploySh);
        self::assertStringContainsString('VERIFY_SIGNATURES', $deploySh);
        self::assertStringContainsString('git checkout --detach --force', $deploySh);
        self::assertStringNotContainsString('git reset --hard "origin/', $deploySh);
    }

    public function testAikidoWave2SecretScrubberSearchCodeTotpAtomicDevStudioAuthAndTerminalSanitization(): void
    {
        // 1. SecretScrubber redacts credentials under keys longer than 48 chars without corrupting Bearer [REDACTED]
        $longKey1 = 'enterprise_multi_region_production_cluster_billing_service_api_key';
        $longKey2 = 'superLongOrganizationInternalPlatformDatabaseMasterPassword';
        $longKey3 = 'ACME_GLOBAL_INFRASTRUCTURE_BACKUP_CLUSTER_SIGNING_SECRET_TOKEN';
        $rawText = sprintf(
            '%s="val-one-999" \'%s\' => \'val-two-888\' %s=val-three-777 Authorization: Bearer tok123456',
            $longKey1,
            $longKey2,
            $longKey3,
        );
        $scrubbed = SecretScrubber::scrub($rawText);
        self::assertStringNotContainsString('val-one-999', $scrubbed);
        self::assertStringNotContainsString('val-two-888', $scrubbed);
        self::assertStringNotContainsString('val-three-777', $scrubbed);
        self::assertStringNotContainsString('tok123456', $scrubbed);
        self::assertStringContainsString('Authorization: Bearer [REDACTED]', $scrubbed);

        // 2. ProjectContext::searchCode() scrubs entire file BEFORE searching (blocks multiline PEM line leakage & secret oracles)
        $searchDir = $this->makeTempDir('search_pem');
        try {
            $pemMiddle = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC9UNIQUEPEMMARKER';
            file_put_contents(
                $searchDir . '/cert_holder.txt',
                "header line\n-----BEGIN PRIVATE KEY-----\n{$pemMiddle}\n-----END PRIVATE KEY-----\nfooter safe_marker\n",
            );
            $ctx = ProjectContext::discover($searchDir, allowBootstrap: false);
            self::assertSame([], $ctx->searchCode('UNIQUEPEMMARKER'), 'searchCode must never match or return interior lines of a PEM private key.');
            $safeHits = $ctx->searchCode('safe_marker');
            self::assertCount(1, $safeHits);
        } finally {
            $this->removeDir($searchDir);
        }

        // 3. Totp::verifyAndReturnTimeStep() fails closed when given a non-atomic CacheInterface
        $totp = new Totp();
        $secret = Totp::generateSecret();
        $tNow = 1_700_000_060;
        $nonAtomicCache = new Round4InterceptingCache(new MemoryCache(), static function (string $_k): void {});
        try {
            $totp->verify($secret, $totp->code($secret, $tNow), timestamp: $tNow, replayCache: $nonAtomicCache);
            self::fail('Expected Totp to reject non-atomic replayCache with SecurityException.');
        } catch (SecurityException $e) {
            self::assertStringContainsString('AtomicCacheInterface', $e->getMessage());
        }

        // 4. DevInspector redacts multipart bodies based on Content-Type header even without Content-Disposition in body
        $inspector = new \Kongono\Http\Dev\DevInspector();
        $inspector->recordRequest(
            method: 'POST',
            path: '/upload',
            status: 200,
            durationMs: 1.5,
            headers: ['Content-Type' => 'multipart/form-data; boundary=----Boundary123'],
            body: '------Boundary123\r\nraw-password-value-without-disposition',
        );
        $traces = $inspector->traces();
        self::assertCount(1, $traces);
        self::assertSame('[MULTIPART BODY REDACTED]', $traces[0]['body']);

        // 5. DevDashboardMiddleware enforces optional $authorizer callback even on loopback clients
        $devApp = App::create(dirname(__DIR__, 3))->debug(true);
        $devAuthMw = new DevDashboardMiddleware(
            app: $devApp,
            inspector: $inspector,
            authorizer: static fn(ServerRequestInterface $r): bool => 'dev-ok' === $r->getHeaderLine('X-Dev-Auth'),
        );
        $noop = $this->handler(static fn(): ResponseInterface => Response::text('ok'));
        $denied = $devAuthMw->process(
            Request::create('GET', '/_kongono/api/traces', headers: ['Host' => '127.0.0.1:8000'], serverParams: ['REMOTE_ADDR' => '127.0.0.1']),
            $noop,
        );
        self::assertSame(403, $denied->getStatusCode());
        $allowed = $devAuthMw->process(
            Request::create('GET', '/_kongono/api/traces', headers: ['Host' => '127.0.0.1:8000', 'X-Dev-Auth' => 'dev-ok'], serverParams: ['REMOTE_ADDR' => '127.0.0.1']),
            $noop,
        );
        self::assertSame(200, $allowed->getStatusCode());

        // 6. KongoCli::sanitizeTerminalText() strips ANSI CSI, OSC, C1, and newline log-injection controls
        $refSan = new \ReflectionMethod(KongoCli::class, 'sanitizeTerminalText');
        $hostile = "safe\x1b[31mRED\x1b[0m\x1b]0;PWN\x07\u{009B}2J\r\nforged line";
        $sanSingle = $refSan->invoke(null, $hostile, true);
        self::assertIsString($sanSingle);
        self::assertStringNotContainsString("\x1b", $sanSingle);
        self::assertStringNotContainsString("\n", $sanSingle);
        self::assertStringNotContainsString("\r", $sanSingle);
        self::assertStringNotContainsString("\u{009B}", $sanSingle);
        self::assertStringContainsString('safeRED', $sanSingle);

        // 7. AuthScaffolder uses generic non-enumerating registration responses with dummy Argon2id timing guard
        $regDir = $this->makeTempDir('auth_enum');
        try {
            (new AuthScaffolder())->scaffold(ProjectContext::discover($regDir, allowBootstrap: false), mode: 'both');
            $webCtrl = (string) file_get_contents($regDir . '/src/Http/WebAuthController.php');
            $apiCtrl = (string) file_get_contents($regDir . '/src/Http/ApiAuthController.php');
            self::assertStringNotContainsString('An account with that email already exists.', $webCtrl);
            self::assertStringNotContainsString('An account with that email already exists.', $apiCtrl);
            self::assertStringContainsString('Unable to complete registration', $webCtrl);
            self::assertStringContainsString('Unable to complete registration', $apiCtrl);
        } finally {
            $this->removeDir($regDir);
        }
    }

    /** @param \Closure(ServerRequestInterface): ResponseInterface $fn */
    private function handler(\Closure $fn): RequestHandlerInterface
    {
        return new readonly class ($fn) implements RequestHandlerInterface {
            /** @param \Closure(ServerRequestInterface): ResponseInterface $fn */
            public function __construct(private \Closure $fn) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->fn)($request);
            }
        };
    }

    private function makeTempDir(string $prefix): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kgn_r4_' . $prefix . '_' . bin2hex(random_bytes(6));
        mkdir($dir, 0o777, true);

        return $dir;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        /** @var \SplFileInfo $item */
        foreach ($it as $item) {
            if ($item->isDir() && !$item->isLink()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}

/** @internal */
final readonly class Round4ObjectDefaultDto
{
    public function __construct(
        #[Confirmed('pin_confirmation')]
        public int $pin,
        public stdClass $meta = new stdClass(),
    ) {}
}

/** @internal */
final class Round4ScriptedTransport implements \Kongono\Ai\Transport\HttpTransportInterface
{
    /** @param list<string> $bodies */
    public function __construct(private array $bodies) {}

    public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): \Kongono\Ai\Transport\HttpResponse
    {
        $raw = array_shift($this->bodies) ?? '{}';

        return new \Kongono\Ai\Transport\HttpResponse(200, $raw, []);
    }
}

/** @internal */
final class Round4InMemoryConnection implements \Kongono\Database\ConnectionInterface
{
    /** @var list<string> */
    public array $executedSql = [];

    public function __construct(private readonly Dialect $dialect = Dialect::Sqlite) {}

    public function dialect(): Dialect
    {
        return $this->dialect;
    }

    public function query(string $sql, array $bindings = []): array
    {
        return [['id' => 10, 'org_id' => 1, 'title' => 'Roadmap']];
    }

    public function first(string $sql, array $bindings = []): ?array
    {
        return null;
    }

    public function execute(string $sql, array $bindings = []): int
    {
        $this->executedSql[] = $sql;

        return 0;
    }

    public function table(string $table, ?string $alias = null): QueryBuilder
    {
        return new QueryBuilder($table, $this->dialect, $this, $alias);
    }

    public function transaction(\Closure $callback): mixed
    {
        return $callback($this);
    }
}

/** @internal */
final readonly class Round4InterceptingCache implements CacheInterface
{
    /** @param \Closure(string): void $onSet */
    public function __construct(
        private CacheInterface $inner,
        private \Closure $onSet,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->inner->get($key, $default);
    }

    public function set(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $this->inner->set($key, $value, $ttlSeconds);
        ($this->onSet)($key);
    }

    public function has(string $key): bool
    {
        return $this->inner->has($key);
    }

    public function delete(string $key): void
    {
        $this->inner->delete($key);
    }

    public function clear(): void
    {
        $this->inner->clear();
    }

    public function remember(string $key, int $ttlSeconds, \Closure $factory): mixed
    {
        return $this->inner->remember($key, $ttlSeconds, $factory);
    }
}

/** @internal */
final class Round4AnnotatedDispatcher
{
    public int $readCalls = 0;

    public function readItem(ToolCall $_call): string
    {
        ++$this->readCalls;

        return 'item-data';
    }

    #[RequiresApproval]
    public function deleteItem(ToolCall $_call): string
    {
        return 'deleted';
    }
}
