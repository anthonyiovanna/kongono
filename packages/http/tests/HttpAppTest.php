<?php

declare(strict_types=1);

namespace Kongono\Http\Tests;

use Kongono\Core\Cache\MemoryCache;
use Kongono\Core\Clock\ClockInterface;
use Kongono\Core\Clock\FrozenClock;
use Kongono\Core\Features\FeatureManager;
use Kongono\Http\App;
use Kongono\Http\Cookie;
use Kongono\Http\Dev\DevDashboardMiddleware;
use Kongono\Http\Dev\DevInspector;
use Kongono\Http\Dev\TestExporter;
use Kongono\Http\DevErrorRenderer;
use Kongono\Http\HttpException;
use Kongono\Http\Live\LiveMiddleware;
use Kongono\Http\Middleware\CompressionMiddleware;
use Kongono\Http\Middleware\IdempotencyMiddleware;
use Kongono\Http\Middleware\StaticFilesMiddleware;
use Kongono\Http\OpenApi\ApiDocsRenderer;
use Kongono\Http\OpenApi\OpenApiGenerator;
use Kongono\Http\Realtime\EventBus;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\CacheControl;
use Kongono\Http\Routing\Feature;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Idempotent;
use Kongono\Http\Routing\Middleware;
use Kongono\Http\Routing\Post;
use Kongono\Http\Routing\Prefix;
use Kongono\Http\Routing\Router;
use Kongono\Http\Session\CacheSessionStore;
use Kongono\Http\Session\Session;
use Kongono\Http\Session\SessionMiddleware;
use Kongono\Http\SseEvent;
use Kongono\Http\Testing\TestClient;
use Kongono\Http\Testing\TestUpload;
use Kongono\Http\UploadedFile;
use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\Required;
use Kongono\View\ViewEngine;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use stdClass;

final readonly class AddTagHeaderMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)->withHeader('X-Attr-Middleware', 'applied');
    }
}

#[Prefix('/prefixed')]
#[Middleware(AddTagHeaderMiddleware::class)]
final readonly class PrefixedController
{
    #[Get('/hello', name: 'prefixed.hello')]
    public function hello(): Response
    {
        return Response::json(['ok' => true]);
    }
}

final readonly class SubscribeInput
{
    public function __construct(
        #[Required, Email]
        public string $email,
    ) {}
}

final readonly class AttributeDemoController
{
    #[Get('/items/{id:\d+}', name: 'items.show')]
    public function show(int $id): Response
    {
        return Response::json(['id' => $id]);
    }

    #[Post('/subscribe', name: 'subscribe')]
    public function subscribe(SubscribeInput $input): Response
    {
        return Response::json(['email' => $input->email], 201);
    }
}

final class HttpAppTest extends TestCase
{
    public function testRoutingGroupsAttributeControllersAndMethodNotAllowed(): void
    {
        $app = App::create(__DIR__)
            ->controller(AttributeDemoController::class)
            ->routes(static function (Router $r): void {
                $r->group('/v1', static function (Router $g): void {
                    $g->get('/ping', static fn(): array => ['ok' => true])->name('v1.ping');
                });
            });

        $ping = $app->handle(Request::create('GET', '/v1/ping'));
        self::assertSame(200, $ping->getStatusCode());
        self::assertSame('{"ok":true}', (string) $ping->getBody());

        $item = $app->handle(Request::create('GET', '/items/42'));
        self::assertSame(200, $item->getStatusCode());
        self::assertSame('{"id":42}', (string) $item->getBody());

        $methodNotAllowed = $app->handle(Request::create('DELETE', '/v1/ping'));
        self::assertSame(405, $methodNotAllowed->getStatusCode());
        self::assertStringContainsString('GET', $methodNotAllowed->getHeaderLine('Allow'));

        $notFound = $app->handle(Request::create('GET', '/does-not-exist'));
        self::assertSame(404, $notFound->getStatusCode());
    }

    public function testPersistentRuntimeResetsScopedServicesBetweenRequests(): void
    {
        $app = App::create(__DIR__)
            ->scoped('request_token', static fn(): stdClass => new stdClass())
            ->routes(static function (Router $r): void {
                $r->get('/id', static function (App $a): Response {
                    $obj = $a->container()->get('request_token');
                    $hash = is_object($obj) ? spl_object_id($obj) : 0;

                    return Response::json(['id' => $hash]);
                });
            });

        $first = json_decode((string) $app->handle(Request::create('GET', '/id'))->getBody(), true, 16, JSON_THROW_ON_ERROR);
        $second = json_decode((string) $app->handle(Request::create('GET', '/id'))->getBody(), true, 16, JSON_THROW_ON_ERROR);

        self::assertIsArray($first);
        self::assertIsArray($second);
        self::assertNotSame($first['id'], $second['id']);
        self::assertSame(0, $app->kernel()->activeOperationCount());
    }

    public function testSupportsStreamingResponseAndMultipartUpload(): void
    {
        $app = App::create(__DIR__)
            ->routes(static function (Router $r): void {
                $r->get('/stream', static fn(): Response => Response::stream(static function (): iterable {
                    yield 'hello ';
                    yield 'stream';
                }));
                $r->post('/upload', static function (Request $req): Response {
                    $f = $req->file('avatar');

                    return Response::json([
                        'name' => $f?->getClientFilename(),
                        'bytes' => $f?->getSize(),
                    ]);
                });
            });

        $streamRes = $app->handle(Request::create('GET', '/stream'));
        self::assertInstanceOf(Response::class, $streamRes);
        self::assertSame('hello stream', $streamRes->bodyString());

        $uploadReq = Request::create(
            'POST',
            '/upload',
            files: ['avatar' => UploadedFile::fromString('PNGDATA', 'me.png', 'image/png')],
        );
        $uploadRes = $app->handle($uploadReq);
        self::assertSame('{"name":"me.png","bytes":7}', (string) $uploadRes->getBody());
    }

    public function testSessionsFlashCookiesAndStaticFilesWithEtag(): void
    {
        $assetsDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-static-' . bin2hex(random_bytes(5));
        mkdir($assetsDir, 0o777, true);
        file_put_contents($assetsDir . DIRECTORY_SEPARATOR . 'site.css', 'body { color: #000; }');

        try {
            $store = new CacheSessionStore(new MemoryCache());
            $app = App::create(__DIR__)
                ->middleware(
                    new StaticFilesMiddleware($assetsDir, '/assets/'),
                    new SessionMiddleware($store),
                )
                ->routes(static function (Router $r): void {
                    $r->post('/flash', static function (Session $session): Response {
                        $session->flash('msg', 'Saved!');
                        $session->regenerate();

                        return Response::text('flashed')->withCookie(new Cookie('theme', 'dark', maxAge: 3600));
                    });
                    $r->get('/read', static fn(Session $session): array => ['msg' => $session->getFlash('msg')]);
                });

            // Static CSS + 304 ETag
            $css = $app->handle(Request::create('GET', '/assets/site.css'));
            self::assertSame(200, $css->getStatusCode());
            self::assertStringContainsString('text/css', $css->getHeaderLine('Content-Type'));
            $etag = $css->getHeaderLine('ETag');
            $notModified = $app->handle(Request::create('GET', '/assets/site.css', ['If-None-Match' => $etag]));
            self::assertSame(304, $notModified->getStatusCode());

            // Session flash + regenerate + cookie
            $flashed = $app->handle(Request::create('POST', '/flash'));
            $setCookies = $flashed->getHeader('Set-Cookie');
            self::assertNotEmpty($setCookies);
            $sessionId = '';
            foreach ($setCookies as $cookieLine) {
                if (1 === preg_match('/kongono_session=([^;]+)/', $cookieLine, $m)) {
                    $sessionId = $m[1];
                }
            }
            self::assertNotSame('', $sessionId);

            // Second request sees flash once
            $readReq = Request::create('GET', '/read')->withCookieParams(['kongono_session' => $sessionId]);
            self::assertInstanceOf(Request::class, $readReq);
            $readRes = $app->handle($readReq);
            self::assertSame('{"msg":"Saved!"}', (string) $readRes->getBody());

            // Third request flash is consumed
            $readAgain = $app->handle($readReq);
            self::assertSame('{"msg":null}', (string) $readAgain->getBody());
        } finally {
            @unlink($assetsDir . DIRECTORY_SEPARATOR . 'site.css');
            @rmdir($assetsDir);
        }
    }

    public function testDevErrorPageNamedUrlsAndFluentTestClient(): void
    {
        $app = App::create(__DIR__)
            ->debug(true)
            ->routes(static function (Router $r): void {
                $r->get('/users/{id:\d+}', static fn(int $id): array => ['user' => ['id' => $id]])->name('users.show');
                $r->get('/boom', static function (): Response {
                    throw new RuntimeException('Boom with Bearer secret-token-123456789');
                });
            });

        self::assertSame('/users/99?tab=posts', $app->router()->url('users.show', ['id' => 99], ['tab' => 'posts']));

        $client = new TestClient($app);
        $client->get('/users/7')
            ->assertOk()
            ->assertJsonPath('user.id', 7);

        $devErr = $client->withHeader('Authorization', 'Bearer top-secret-xyz')->get('/boom')
            ->assertStatus(500)
            ->assertSee('Kongono Dev Exception')
            ->assertSee('Source Context')
            ->assertSee('kongo hantei debug')
            ->assertDontSee('top-secret-xyz')
            ->assertDontSee('secret-token-123456789');

        self::assertStringContainsString('[REDACTED]', $devErr->body());

        // Production mode hides error internals completely
        $app->debug(false);
        $client->get('/boom')
            ->assertStatus(500)
            ->assertDontSee('Source Context')
            ->assertSee('An internal server error occurred.');
    }

    public function testSseResponseAndAutomaticOpenApi31Generation(): void
    {
        $app = App::create(__DIR__, name: 'OpenAPI Demo')
            ->controller(AttributeDemoController::class)
            ->routes(static function (Router $r): void {
                $r->get('/sse', static fn(): Response => Response::sse(static function (): iterable {
                    yield SseEvent::json(['ping' => 1], event: 'tick', id: '1');
                }));
            });

        $sse = $app->handle(Request::create('GET', '/sse'));
        self::assertInstanceOf(Response::class, $sse);
        self::assertStringContainsString('text/event-stream', $sse->getHeaderLine('Content-Type'));
        self::assertSame("id: 1\nevent: tick\ndata: {\"ping\":1}\n\n", $sse->bodyString());

        $spec = (new OpenApiGenerator())->generate($app);
        self::assertSame('3.1.0', $spec['openapi']);
        self::assertIsArray($spec['paths']);
        self::assertArrayHasKey('/items/{id}', $spec['paths']);
        self::assertArrayHasKey('/subscribe', $spec['paths']);
        self::assertIsArray($spec['components']);
        self::assertArrayHasKey('SubscribeInput', $spec['components']['schemas']);
        self::assertSame('email', $spec['components']['schemas']['SubscribeInput']['properties']['email']['format']);

        $docsHtml = (new ApiDocsRenderer())->render($app);
        self::assertStringContainsString('<!doctype html>', $docsHtml);
        self::assertStringContainsString('OpenAPI Demo', $docsHtml);
        self::assertStringContainsString('/subscribe', $docsHtml);
        self::assertStringContainsString('Send Request', $docsHtml);
        self::assertStringContainsString('bearerToken', $docsHtml);
    }

    public function testPrefixMiddlewareAttributesEventBusAndDevStudio(): void
    {
        $inspector = new DevInspector();
        $app = App::create(__DIR__, name: 'Studio Demo')
            ->debug(true)
            ->controller(PrefixedController::class);
        $app->middleware(new DevDashboardMiddleware(
            app: $app,
            inspector: $inspector,
            chieHandler: static fn(string $instruction): array => [
                'summary' => 'Planned: ' . $instruction,
                'diffs' => [['path' => 'src/Http/StatsController.php', 'action' => 'create', 'explanation' => 'Adds stats', 'diff' => '+<?php']],
            ],
        ));

        $client = new TestClient($app);

        // #[Prefix] + #[Middleware] attribute route
        $hello = $client->get('/prefixed/hello')->assertOk()->assertJsonPath('ok', true);
        self::assertSame('applied', $hello->header('X-Attr-Middleware'));

        // Dev Studio HTML + recorded trace API + Chie preview API + clear API
        $client->get('/_kongono')
            ->assertOk()
            ->assertSee('Kongono Dev Studio')
            ->assertSee('/prefixed/hello')
            ->assertSee('Preview Diff with Chie');

        $client->postJson('/_kongono/api/chie', ['instruction' => 'Add /stats'])
            ->assertOk()
            ->assertJsonPath('summary', 'Planned: Add /stats');
        $client->postJson('/_kongono/api/chie', ['instruction' => ''])->assertStatus(422);

        $traces = $client->get('/_kongono/api/traces')->assertOk()->json();
        self::assertCount(1, $traces);
        self::assertSame('/prefixed/hello', $traces[0]['path']);

        $client->postJson('/_kongono/api/clear', [])->assertOk();
        self::assertSame([], $client->get('/_kongono/api/traces')->assertOk()->json());

        // Production mode disables Dev Studio completely (fail-closed 404)
        $app->debug(false);
        $client->get('/_kongono')->assertStatus(404);

        // Real-time EventBus pub/sub + Last-Event-ID replay + unsubscribe
        $bus = new EventBus(maxHistoryPerChannel: 5);
        $received = [];
        $unsub = $bus->subscribe('notes', static function (SseEvent $ev) use (&$received): void {
            $received[] = $ev->event;
        });
        $e1 = $bus->publish('notes', 'note.created', ['id' => 1]);
        $bus->publish('notes', 'note.updated', ['id' => 1]);
        $unsub();
        $bus->publish('notes', 'note.deleted', ['id' => 1]);

        self::assertSame(['note.created', 'note.updated'], $received);
        $replayed = $bus->since('notes', $e1->id);
        self::assertCount(2, $replayed);
        self::assertSame('note.updated', $replayed[0]->event);
        self::assertSame('note.deleted', $replayed[1]->event);
    }

    public function testCompressionMiddlewareAndCacheControlAttribute(): void
    {
        $app = App::create(__DIR__)
            ->middleware(new CompressionMiddleware(minBytes: 64))
            ->controller(CachedDemoController::class);

        $first = $app->handle(Request::create('GET', '/cached-feed', ['Accept-Encoding' => 'gzip']));
        self::assertSame(200, $first->getStatusCode());
        self::assertSame('public, max-age=120, stale-while-revalidate=30', $first->getHeaderLine('Cache-Control'));
        self::assertSame('gzip', $first->getHeaderLine('Content-Encoding'));
        self::assertStringContainsString('Accept-Encoding', $first->getHeaderLine('Vary'));
        $etag = $first->getHeaderLine('ETag');
        self::assertStringStartsWith('W/"', $etag);

        $decoded = gzdecode((string) $first->getBody());
        self::assertIsString($decoded);
        self::assertStringContainsString('Kongono fast response', $decoded);

        // Conditional GET with matching If-None-Match returns 304 Not Modified
        $notMod = $app->handle(Request::create('GET', '/cached-feed', ['If-None-Match' => $etag]));
        self::assertSame(304, $notMod->getStatusCode());
        self::assertSame('', (string) $notMod->getBody());
    }

    public function testIdempotentAndFeatureAttributesOnControllers(): void
    {
        $cache = new MemoryCache();
        $features = (new FeatureManager($cache))
            ->define('beta_api', static fn(?string $scope): bool => 'vip' === $scope);

        $counter = new \ArrayObject(['calls' => 0]);
        $app = App::create(__DIR__)
            ->instance(\Kongono\Core\Cache\CacheInterface::class, $cache)
            ->instance(FeatureManager::class, $features)
            ->instance(\ArrayObject::class, $counter)
            ->controller(CachedDemoController::class);

        $client = new TestClient($app);

        // First POST executes handler
        $p1 = $client->withHeader('Idempotency-Key', 'order-123')
            ->postJson('/orders', ['amount' => 50])
            ->assertCreated()
            ->assertJsonPath('call_number', 1);
        self::assertSame('', $p1->header('Idempotency-Replayed'));

        // Retry with same Idempotency-Key + same body replays cached 201 without re-executing handler
        $p2 = $client->withHeader('Idempotency-Key', 'order-123')
            ->postJson('/orders', ['amount' => 50])
            ->assertCreated()
            ->assertJsonPath('call_number', 1);
        self::assertSame('true', $p2->header('Idempotency-Replayed'));
        self::assertSame(1, $counter['calls']);

        // Reusing Idempotency-Key with a DIFFERENT payload fails closed 409 Conflict
        $client->withHeader('Idempotency-Key', 'order-123')
            ->postJson('/orders', ['amount' => 999])
            ->assertStatus(409);

        // #[Feature('beta_api')] fails closed 404 even when client spoofs X-Feature-Scope header;
        // activates ONLY when server-side scope attribute (_feature_scope / Principal) matches
        $client->get('/beta')->assertStatus(404);
        $client->withHeader('X-Feature-Scope', 'vip')->get('/beta')->assertStatus(404);

        $scopedRes = $app->handle(Request::create('GET', '/beta')->withAttribute('_feature_scope', 'vip'));
        self::assertSame(200, $scopedRes->getStatusCode());
        self::assertStringContainsString('"beta":true', (string) $scopedRes->getBody());
    }

    public function testViewsSupportsAutoReloadFlagAndInstanceSync(): void
    {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-http-view-' . bin2hex(random_bytes(5));
        mkdir($tempDir . DIRECTORY_SEPARATOR . 'views', 0o777, true);
        mkdir($tempDir . DIRECTORY_SEPARATOR . 'cache', 0o777, true);
        file_put_contents($tempDir . '/views/hello.kgn.html', '<p>@t("greet")</p>');

        try {
            $app = App::create($tempDir)
                ->views($tempDir . '/views', $tempDir . '/cache', autoReload: false);

            $engine = $app->container()->make(ViewEngine::class);
            self::assertFalse($engine->isAutoReload());

            $app->instance(ViewEngine::class, $engine->withTranslator(static fn(string $key): string => 'Hello ' . $key));
            $response = $app->view('hello');

            self::assertSame(200, $response->getStatusCode());
            self::assertSame('<p>Hello greet</p>', (string) $response->getBody());
            self::assertFalse($app->container()->make(ViewEngine::class)->isAutoReload());
        } finally {
            foreach (glob($tempDir . '/cache/*') ?: [] as $f) {
                @unlink($f);
            }
            @unlink($tempDir . '/views/hello.kgn.html');
            @rmdir($tempDir . '/cache');
            @rmdir($tempDir . '/views');
            @rmdir($tempDir);
        }
    }

    public function testApiDocsAndDevStudioEmitValidInlineJavaScriptAndTestClientSupportsStreams(): void
    {
        $app = App::create(__DIR__, name: 'JS Safety App')
            ->debug(true)
            ->routes(static function (Router $r): void {
                $r->get('/stream', static fn(): Response => Response::sse(static function (): iterable {
                    yield SseEvent::json(['ok' => true], event: 'ping', id: '1');
                }));
                $r->post('/form', static fn(Request $req): Response => Response::json(['name' => $req->input('name')]));
                $r->patch('/item', static fn(Request $req): Response => Response::json(['patched' => $req->input('v')]));
            });

        $docsHtml = (new ApiDocsRenderer())->render($app);
        self::assertStringContainsString("'\\n'", $docsHtml);
        self::assertStringNotContainsString("'\n'", $docsHtml);

        $renderer = new \Kongono\Http\Dev\DevDashboardRenderer();
        $studioHtml = $renderer->render($app, new DevInspector());
        $studioJs = $renderer->studioScript();
        self::assertStringContainsString('/_kongono/assets/studio.css', $studioHtml);
        self::assertStringContainsString('/_kongono/assets/studio.js', $studioHtml);
        self::assertStringNotContainsString('<style>', $studioHtml);
        self::assertStringContainsString("join('\\n')", $studioJs);
        self::assertStringNotContainsString("join('\n')", $studioJs);

        $client = new TestClient($app);
        $client->get('/stream')->assertOk()->assertSee('event: ping');
        $client->postForm('/form', ['name' => 'Ada'])->assertOk()->assertJsonPath('name', 'Ada');
        $client->patchJson('/item', ['v' => 42])->assertOk()->assertJsonPath('patched', 42);
    }

    public function testAotCompiledRouterTableExportsAndHydratesWithoutReflection(): void
    {
        $router = new Router();
        $router->controller(CachedDemoController::class);
        $router->get('/items/{id:\d+}', [CachedDemoController::class, 'feed'])->name('items.show');
        $router->get('/inline-closure', static fn(): Response => Response::text('hi'));

        $compiled = $router->exportCompiledTable();
        self::assertSame(2, $compiled['version']);
        self::assertSame(1, $compiled['skipped_closures']);
        self::assertArrayHasKey('/cached-feed', $compiled['static']);
        self::assertArrayHasKey('GET', $compiled['static']['/cached-feed']);
        self::assertNotEmpty($compiled['static']['/cached-feed']['GET']['middleware_attributes']);
        self::assertCount(1, $compiled['dynamic']);
        self::assertSame('#\A/items/(?P<id>\d+)\z#', $compiled['dynamic'][0]['regex']);

        $phpCode = $router->exportCompiledPhp();
        self::assertStringStartsWith("<?php\n\ndeclare(strict_types=1);", $phpCode);

        // Hydrate a fresh Router purely from the compiled table and verify O(1) static + dynamic + middleware attributes + 405
        $hydrated = (new Router())->loadCompiledTable($compiled);
        $staticMatch = $hydrated->match('GET', '/cached-feed');
        self::assertSame('cached.feed', $staticMatch->route->routeName());
        self::assertSame([], $staticMatch->params);
        self::assertCount(1, $staticMatch->route->routeMiddlewareAttributes());

        $dynamicMatch = $hydrated->match('GET', '/items/42');
        self::assertSame('items.show', $dynamicMatch->route->routeName());
        self::assertSame(['id' => '42'], $dynamicMatch->params);

        // Wrong HTTP method on static route throws 405 with Allow header
        try {
            $hydrated->match('DELETE', '/cached-feed');
            self::fail('Expected 405 Method Not Allowed.');
        } catch (HttpException $e) {
            self::assertSame(405, $e->statusCode);
            self::assertStringContainsString('GET', $e->headers['Allow'] ?? '');
        }
    }

    public function testKongonoLiveMiddlewareServesCspSafeRuntimeAndFragmentHelpers(): void
    {
        $app = App::create(__DIR__)
            ->middleware(new LiveMiddleware())
            ->routes(static function (Router $r): void {
                $r->post('/counter', static function (Request $req): Response {
                    if ($req->isLive()) {
                        return Response::fragment('<span>Count: 2</span>', pushUrl: '/counter?v=2');
                    }

                    return Response::html('<html><body><span>Count: 2</span></body></html>');
                });
            });

        $client = new TestClient($app);

        // /_kongono/live.js returns strict-CSP JS runtime with ETag + 304 support
        $js = $client->get('/_kongono/live.js')->assertOk();
        self::assertStringContainsString('application/javascript', $js->header('Content-Type'));
        self::assertSame('nosniff', $js->header('X-Content-Type-Options'));
        self::assertStringContainsString('X-Kongono-Live', $js->body());
        self::assertStringContainsString('kgn-stream', $js->body());
        self::assertStringContainsString('EventSource', $js->body());
        self::assertStringNotContainsString('eval(', $js->body());
        $etag = $js->header('ETag');
        self::assertStringStartsWith('W/"', $etag);

        $client->withHeader('If-None-Match', $etag)->get('/_kongono/live.js')->assertStatus(304);

        // Request::isLive() + Response::fragment() partial swap vs full HTML fallback
        $liveRes = $client->withHeader('X-Kongono-Live', 'true')->postForm('/counter', [])->assertOk();
        self::assertSame('<span>Count: 2</span>', $liveRes->body());
        self::assertSame('fragment', $liveRes->header('X-Kongono-Live'));
        self::assertSame('/counter?v=2', $liveRes->header('X-Kongono-Push-Url'));
        self::assertSame('X-Kongono-Live', $liveRes->header('Vary'));

        $fullRes = $client->postForm('/counter', [])->assertOk();
        self::assertStringContainsString('<html><body>', $fullRes->body());
    }

    public function testDevStudioTimeTravelReplayTestExportAndNPlusOneCapture(): void
    {
        $inspector = new DevInspector(nPlusOneThreshold: 3);
        $hits = new \ArrayObject(['count' => 0]);

        $app = App::create(__DIR__)->debug(true);
        $app->middleware(new DevDashboardMiddleware($app, $inspector));
        $app->routes(static function (Router $r) use ($inspector, $hits): void {
            $r->post('/echo', static function (Request $req) use ($inspector, $hits): Response {
                $hits['count'] = ($hits['count'] ?? 0) + 1;
                for ($i = 1; $i <= 3; ++$i) {
                    $inspector->recordQuery("SELECT * FROM notes WHERE id = {$i}", 0.4);
                }

                return Response::json(['received' => $req->input('msg'), 'hit' => $hits['count']], 201);
            });
        });

        $client = new TestClient($app);

        // Execute initial request with an Authorization header that MUST be scrubbed
        $client->withHeader('Authorization', 'Bearer super-secret-jwt-token')
            ->postJson('/echo?mode=fast', ['msg' => 'hello'])
            ->assertCreated()
            ->assertJsonPath('hit', 1);

        $traces = $inspector->traces();
        self::assertCount(1, $traces);
        $traceId = $traces[0]['id'];
        self::assertSame('[REDACTED]', $traces[0]['headers']['Authorization']);
        self::assertCount(1, $traces[0]['n_plus_one']);
        self::assertSame(3, $traces[0]['n_plus_one'][0]['count']);

        // Time-travel replay re-runs the captured request in-process!
        $replay = $client->postJson('/_kongono/api/replay/' . $traceId, [])->assertOk();
        $replay->assertJsonPath('replayed_id', $traceId)
            ->assertJsonPath('original_status', 201)
            ->assertJsonPath('replay_status', 201);
        self::assertSame(2, $hits['count']);

        // 1-Click Export as PHPUnit 13 regression test (redacted Authorization omitted)
        $exported = $client->get('/_kongono/api/export-test/' . $traceId)->assertOk()->body();
        self::assertStringContainsString('public function testReplayPostEchoTrace' . $traceId . '(): void', $exported);
        self::assertStringContainsString("->postJson('/echo?mode=fast'", $exported);
        self::assertStringContainsString('->assertCreated();', $exported);
        self::assertStringNotContainsString('super-secret-jwt-token', $exported);
    }

    public function testHttpSecurityHardeningCrlfRedirectsCrossUserIdempotencyAndStaticSandbox(): void
    {
        // 1. Response CRLF header injection & unsafe javascript: redirect schemes fail closed
        try {
            Response::text('ok')->withHeader('X-Test', "ok\r\nSet-Cookie: evil=1");
            self::fail('Expected CRLF header injection to be rejected.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('CRLF', $e->getMessage());
        }

        try {
            Response::redirect('javascript:alert(1)');
            self::fail('Expected javascript: redirect scheme to be rejected.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('URL scheme rejected', $e->getMessage());
        }

        try {
            Response::fragment('<p>hi</p>', redirect: 'data:text/html,<script>alert(1)</script>');
            self::fail('Expected data: fragment redirect to be rejected.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('URL scheme rejected', $e->getMessage());
        }

        // 2. Cookie strict validation (SameSite=None requires Secure; CRLF/semicolon rejected)
        try {
            new Cookie('sid', 'abc', secure: false, sameSite: 'None');
            self::fail('Expected SameSite=None without Secure to fail.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('secure: true', $e->getMessage());
        }

        // 3. Cross-user Idempotency-Key isolation (User B cannot replay User A's cached response!)
        $cache = new MemoryCache();
        $calls = new \ArrayObject(['n' => 0]);
        $app = App::create(__DIR__)
            ->middleware(new \Kongono\Http\Middleware\IdempotencyMiddleware($cache))
            ->routes(static function (Router $r) use ($calls): void {
                $r->post('/checkout', static function (Request $req) use ($calls): Response {
                    $calls['n'] = ($calls['n'] ?? 0) + 1;

                    return Response::json([
                        'actor' => $req->header('Authorization'),
                        'call' => $calls['n'],
                    ], 201);
                });
            });

        $rA = $app->handle(Request::json('POST', '/checkout', ['plan' => 'pro'], [
            'Authorization' => 'Bearer user-a-token',
            'Idempotency-Key' => 'shared-key-1',
        ]));
        $rB = $app->handle(Request::json('POST', '/checkout', ['plan' => 'pro'], [
            'Authorization' => 'Bearer user-b-token',
            'Idempotency-Key' => 'shared-key-1',
        ]));
        self::assertSame(201, $rA->getStatusCode());
        self::assertSame(201, $rB->getStatusCode());
        self::assertSame('', $rB->getHeaderLine('Idempotency-Replayed'));
        self::assertStringContainsString('user-b-token', (string) $rB->getBody());
        self::assertSame(2, $calls['n']);

        // 4. StaticFilesMiddleware blocks dotfiles, adds nosniff, and sandboxes SVG scripts
        $pub = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-static-sec-' . bin2hex(random_bytes(4));
        mkdir($pub, 0o777, true);
        file_put_contents($pub . '/.env.txt', 'APP_KEY=secret');
        file_put_contents($pub . '/logo.svg', '<svg><script>alert(1)</script></svg>');

        try {
            $staticApp = App::create(__DIR__)
                ->middleware(new StaticFilesMiddleware($pub, '/assets/'));

            self::assertSame(404, $staticApp->handle(Request::create('GET', '/assets/.env.txt'))->getStatusCode());
            self::assertSame(404, $staticApp->handle(Request::create('GET', '/assets/%2e%2e/secret.txt'))->getStatusCode());

            $svg = $staticApp->handle(Request::create('GET', '/assets/logo.svg'));
            self::assertSame(200, $svg->getStatusCode());
            self::assertSame('nosniff', $svg->getHeaderLine('X-Content-Type-Options'));
            self::assertStringContainsString('sandbox', $svg->getHeaderLine('Content-Security-Policy'));
        } finally {
            @unlink($pub . '/.env.txt');
            @unlink($pub . '/logo.svg');
            @rmdir($pub);
        }

        // 5. SessionMiddleware lazily avoids emitting Set-Cookie on untouched anonymous requests,
        // and automatically sets Secure + Cache-Control private when a session is started on HTTPS
        $sessApp = App::create(__DIR__)
            ->middleware(new SessionMiddleware(new CacheSessionStore(new MemoryCache()), secure: false))
            ->routes(static function (Router $r): void {
                $r->get('/anon', static fn(): Response => Response::text('anon'));
                $r->get('/tls', static function (Session $session): Response {
                    $session->set('tls', true);

                    return Response::text('ok');
                });
            });
        $anonRes = $sessApp->handle(Request::create('GET', 'https://app.kongono.dev/anon'));
        self::assertFalse($anonRes->hasHeader('Set-Cookie'));
        $tlsRes = $sessApp->handle(Request::create('GET', 'https://app.kongono.dev/tls'));
        self::assertStringContainsString('Secure', $tlsRes->getHeaderLine('Set-Cookie'));
        self::assertStringContainsString('private', $tlsRes->getHeaderLine('Cache-Control'));

        // 6. HttpCacheMiddleware never marks Set-Cookie or Authorization responses as public (CDN cache poisoning guard)
        $cacheApp = App::create(__DIR__)
            ->middleware(new \Kongono\Http\Middleware\HttpCacheMiddleware(maxAge: 300, public: true))
            ->routes(static function (Router $r): void {
                $r->get('/with-cookie', static fn(): Response => Response::text('secret')->withCookie(new Cookie('sid', '123')));
                $r->get('/authed', static fn(): Response => Response::text('private-profile'));
            });
        $cookieCached = $cacheApp->handle(Request::create('GET', '/with-cookie'));
        self::assertSame('private, no-store', $cookieCached->getHeaderLine('Cache-Control'));

        $authCached = $cacheApp->handle(Request::create('GET', '/authed', ['Authorization' => 'Bearer tok']));
        self::assertStringStartsWith('private,', $authCached->getHeaderLine('Cache-Control'));
        self::assertStringContainsString('Authorization', $authCached->getHeaderLine('Vary'));

        // 7. SseEvent neutralizes bare CR (\r) frame injection
        $sse = (new SseEvent("line1\revent: forged\rdata: pwned", event: "ping\r\nfake", id: "1\rid:2"))->format();
        self::assertStringContainsString("id: 1id:2\n", $sse);
        self::assertStringContainsString("event: pingfake\n", $sse);
        self::assertStringContainsString("data: line1\ndata: event: forged\ndata: data: pwned\n\n", $sse);
    }

    public function testRound3HttpAotRoutesDtoPrecedenceDevStudioBreachIdempotencyAndSessionHardening(): void
    {
        // 1. AOT route table v2 preserves #[CacheControl]/#[Middleware] attributes and rejects legacy v1 tables
        $router = new Router();
        $router->controller(CachedDemoController::class);
        $compiled = $router->exportCompiledTable();
        self::assertSame(2, $compiled['version']);

        $restored = new Router();
        $restored->loadCompiledTable($compiled);
        $matched = $restored->match('GET', '/cached-feed');
        self::assertNotNull($matched);
        self::assertNotEmpty($matched->route->routeMiddlewareAttributes());

        try {
            (new Router())->loadCompiledTable(['version' => 1, 'static' => [], 'dynamic' => []]);
            self::fail('Expected legacy v1 AOT route cache to fail closed.');
        } catch (\Kongono\Core\CoreException $e) {
            self::assertStringContainsString('version', $e->getMessage());
        }

        // 2. TestExporter neutralizes PHP close-tag breakout and weird HTTP methods
        $exported = TestExporter::exportPhpUnitTest([
            'id' => 7,
            'method' => "GET';system('id');//",
            'path' => '/x?a=' . '?' . '> <' . '?php system("id");',
            'status' => 200,
        ]);
        self::assertStringContainsString('?_> <_?php', $exported);
        // Verify PHP's own tokenizer finds ZERO T_CLOSE_TAG tokens in the exported PHP file
        $closeTags = array_filter(\PhpToken::tokenize($exported), static fn(\PhpToken $t): bool => T_CLOSE_TAG === $t->id);
        self::assertSame([], $closeTags);

        // 3. DevDashboardMiddleware blocks non-loopback & cross-origin/simple POSTs; DevInspector scrubs JSON password
        $inspector = new DevInspector();
        $devApp = App::create(__DIR__)
            ->config(['app' => ['debug' => true]])
            ->routes(static function (Router $r): void {
                $r->post('/login', static fn(): Response => Response::json(['ok' => true]));
            });
        $devApp->middleware(new DevDashboardMiddleware($devApp, $inspector));
        // Non-loopback access denied (403)
        self::assertSame(403, $devApp->handle(Request::create('GET', '/_kongono', serverParams: ['REMOTE_ADDR' => '198.51.100.5']))->getStatusCode());
        // Cross-site POST to /_kongono/api/clear denied (403)
        self::assertSame(403, $devApp->handle(Request::create('POST', '/_kongono/api/clear', ['Sec-Fetch-Site' => 'cross-site'], serverParams: ['REMOTE_ADDR' => '127.0.0.1']))->getStatusCode());
        // Simple form POST without X-Kongono-Dev denied (403)
        self::assertSame(403, $devApp->handle(Request::create('POST', '/_kongono/api/clear', ['Content-Type' => 'application/x-www-form-urlencoded'], serverParams: ['REMOTE_ADDR' => '127.0.0.1']))->getStatusCode());
        // Valid same-origin POST with X-Kongono-Dev succeeds (200)
        self::assertSame(200, $devApp->handle(Request::create('POST', '/_kongono/api/clear', ['X-Kongono-Dev' => '1'], serverParams: ['REMOTE_ADDR' => '127.0.0.1']))->getStatusCode());

        // DevInspector scrubs JSON password body
        $devApp->handle(Request::create('POST', '/login', ['Content-Type' => 'application/json'], '{"email":"ada@example.com","password":"top_secret_pw"}', ['REMOTE_ADDR' => '127.0.0.1']));
        $traces = $inspector->traces();
        self::assertNotEmpty($traces);
        self::assertStringNotContainsString('top_secret_pw', $traces[0]['body']);

        // 4. DevErrorRenderer strips shell metacharacters from Hantei command
        $renderedErr = (new DevErrorRenderer())->render(
            Request::create('GET', '/err'),
            new RuntimeException('Boom $(touch /tmp/pwn) `id` ; rm -rf /'),
        );
        self::assertSame(1, preg_match('#<code>(php bin/kongo hantei debug [^<]+)</code>#', $renderedErr, $hanteiMatch));
        $hanteiCmd = html_entity_decode($hanteiMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        self::assertStringNotContainsString('$(touch', $hanteiCmd);
        self::assertStringNotContainsString('`id`', $hanteiCmd);
        self::assertStringNotContainsString(';', $hanteiCmd);

        // 5. Route param precedence prevents JSON body from shadowing {id} in validated DTO
        $dtoApp = App::create(__DIR__)->routes(static function (Router $r): void {
            $r->post('/items/{id}', static fn(RoutePrecedenceInputDto $input): Response => Response::json(['id' => $input->id, 'title' => $input->title]));
        });
        $dtoRes = $dtoApp->handle(Request::create('POST', '/items/42', ['Content-Type' => 'application/json'], '{"id":"999","title":"Safe"}'));
        self::assertSame(200, $dtoRes->getStatusCode());
        self::assertSame('{"id":"42","title":"Safe"}', (string) $dtoRes->getBody());

        // 6. Response rejects tab/control-obfuscated javascript: URLs and C0 control chars in headers
        foreach (["java\tscript:alert(1)", "\x01javascript:alert(1)"] as $badRedirect) {
            try {
                Response::redirect($badRedirect);
                self::fail('Expected Response::redirect to reject: ' . $badRedirect);
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }

        // 7. CompressionMiddleware skips BREACH-sensitive Set-Cookie/Cookie/no-transform responses & respects q=0
        $compApp = App::create(__DIR__)
            ->middleware(new CompressionMiddleware(minBytes: 16))
            ->routes(static function (Router $r): void {
                $r->get('/pub', static fn(): Response => Response::text(str_repeat('Kongono compressed text ', 20)));
                $r->get('/secret-html', static fn(): Response => Response::html(str_repeat('<p>csrf-token</p>', 20))->withCookie(new Cookie('sid', '1')));
                $r->get('/notransform', static fn(): Response => Response::text(str_repeat('raw bytes ', 20))->withHeader('Cache-Control', 'no-transform'));
            });
        $pubComp = $compApp->handle(Request::create('GET', '/pub', ['Accept-Encoding' => 'gzip']));
        self::assertSame('gzip', $pubComp->getHeaderLine('Content-Encoding'));
        $qZero = $compApp->handle(Request::create('GET', '/pub', ['Accept-Encoding' => 'gzip;q=0']));
        self::assertSame('', $qZero->getHeaderLine('Content-Encoding'));
        $breachSkip = $compApp->handle(Request::create('GET', '/secret-html', ['Accept-Encoding' => 'gzip']));
        self::assertSame('', $breachSkip->getHeaderLine('Content-Encoding'));
        $noTrans = $compApp->handle(Request::create('GET', '/notransform', ['Accept-Encoding' => 'gzip']));
        self::assertSame('', $noTrans->getHeaderLine('Content-Encoding'));

        // 8. IdempotencyMiddleware normalizes /pay/ vs /pay and fingerprints query string
        $idemCalls = 0;
        $idemApp = App::create(__DIR__)
            ->middleware(new IdempotencyMiddleware(new MemoryCache()))
            ->routes(static function (Router $r) use (&$idemCalls): void {
                $r->post('/pay', static function () use (&$idemCalls): Response {
                    ++$idemCalls;

                    return Response::json(['n' => $idemCalls], 201);
                });
            });
        $r1 = $idemApp->handle(Request::create('POST', '/pay?amount=10', ['Idempotency-Key' => 'idem-r3-1']));
        self::assertSame(201, $r1->getStatusCode());
        // Same path with trailing slash & same query replays cached response
        $r2 = $idemApp->handle(Request::create('POST', '/pay/?amount=10', ['Idempotency-Key' => 'idem-r3-1']));
        self::assertSame(201, $r2->getStatusCode());
        self::assertSame('true', $r2->getHeaderLine('Idempotency-Replayed'));
        self::assertSame(1, $idemCalls);
        // Same key with DIFFERENT query string triggers 409 fingerprint mismatch
        $r3 = $idemApp->handle(Request::create('POST', '/pay?amount=9999', ['Idempotency-Key' => 'idem-r3-1']));
        self::assertSame(409, $r3->getStatusCode());

        // 9. Session tracks ALL previous IDs across multiple regenerate() calls & supports invalidate()
        $session = new Session('orig-id', ['u' => 1]);
        $session->regenerate();
        $midId = $session->id();
        $session->regenerate();
        self::assertContains('orig-id', $session->previousIds());
        self::assertContains($midId, $session->previousIds());
        $afterSecond = $session->id();
        $session->invalidate();
        self::assertFalse($session->has('u'));
        self::assertTrue($session->isInvalidated());
        self::assertContains($afterSecond, $session->previousIds());
    }

    public function testRound4PrincipalForgeryDefenseLiveXssDnsRebindingIdempotentFailClosedAndRouterHardening(): void
    {
        // 1. CRITICAL: Unauthenticated query/JSON params CANNOT forge Kongono\Security\Principal or Session via DTO hydration
        $authApp = App::create(__DIR__)->routes(static function (Router $r): void {
            $r->get('/optional-me', static fn(?\Kongono\Security\Principal $user = null): Response => Response::json(['user' => $user?->id]));
            $r->get('/required-me', static fn(\Kongono\Security\Principal $user): Response => Response::json(['user' => $user->id]));
        });
        $forgedOpt = $authApp->handle(Request::create('GET', '/optional-me?id=root&kind=system&capabilities[]=admin.all'));
        self::assertSame(200, $forgedOpt->getStatusCode());
        self::assertSame('{"user":null}', (string) $forgedOpt->getBody());

        $forgedReq = $authApp->handle(Request::create('GET', '/required-me?id=root&kind=system&capabilities[]=admin.all'));
        self::assertSame(401, $forgedReq->getStatusCode());

        // 2. LiveMiddleware sends Accept: text/html, enforces same-origin & Content-Type before HTML swap
        $liveJs = LiveMiddleware::scriptSource();
        self::assertStringContainsString('"Accept": "text/html"', $liveJs);
        self::assertStringContainsString('ct.startsWith("text/html")', $liveJs);
        self::assertStringContainsString('isSameOriginUrl', $liveJs);

        // 3. DevDashboardMiddleware blocks DNS rebinding (non-loopback Host header even when REMOTE_ADDR=127.0.0.1)
        $inspector = new DevInspector();
        $devApp = App::create(__DIR__)->config(['app' => ['debug' => true]]);
        $devApp->middleware(new DevDashboardMiddleware($devApp, $inspector));
        $rebindRes = $devApp->handle(Request::create('GET', 'http://evil.com:8000/_kongono', ['Host' => 'evil.com:8000'], serverParams: ['REMOTE_ADDR' => '127.0.0.1']));
        self::assertSame(403, $rebindRes->getStatusCode());

        // 4. #[Idempotent] fails closed when CacheInterface is not bound in the container
        $unboundIdemApp = App::create(__DIR__)->controller(CachedDemoController::class);
        $unboundRes = $unboundIdemApp->handle(Request::create('POST', '/orders', ['Idempotency-Key' => 'k1']));
        self::assertSame(500, $unboundRes->getStatusCode());

        // 5. Router::compilePathRegex() escapes literal '.' in static segments & rejects AOT export of fluent middlewareAttributes()
        $router = new Router();
        $router->get('/api/v1.0/users/{id}', static fn(): Response => Response::text('ok'));
        self::assertNotNull($router->match('GET', '/api/v1.0/users/42'));
        try {
            $router->match('GET', '/api/v1X0/users/42');
            self::fail('Expected literal dot in /api/v1.0/users/{id} not to match v1X0.');
        } catch (HttpException $e) {
            self::assertSame(404, $e->statusCode);
        }

        // 6. IdempotencyMiddleware handles invalid UTF-8 cookie bytes without HTTP 500 and never caches 401/429
        $idemApp = App::create(__DIR__)
            ->middleware(new IdempotencyMiddleware(new MemoryCache()))
            ->routes(static function (Router $r): void {
                $r->post('/auth-check', static fn(): Response => Response::text('denied', 401));
            });
        $badCookieReq = Request::create('POST', '/auth-check', ['Idempotency-Key' => 'utf8-test'])
            ->withCookieParams(['raw' => "\xFF\xFE"]);
        self::assertSame(401, $idemApp->handle($badCookieReq)->getStatusCode());
        // Second call with same key is NOT served from cache (no Idempotency-Replayed header)
        $second401 = $idemApp->handle($badCookieReq);
        self::assertSame(401, $second401->getStatusCode());
        self::assertSame('', $second401->getHeaderLine('Idempotency-Replayed'));

        // 7. Response deduplicates case-variant headers, rejects CRLF in status/protocol, and Uri::__toString() separates authority/path
        $resp = Response::html('<p>ok</p>', 200, ['content-type' => 'text/plain']);
        self::assertSame([], $resp->withoutHeader('Content-Type')->getHeaders());
        try {
            $resp->withStatus(200, "OK\r\nX-Evil: 1");
            self::fail('Expected CRLF in reasonPhrase to be rejected.');
        } catch (\InvalidArgumentException) {
            self::addToAssertionCount(1);
        }
        $uri = new \Kongono\Http\Uri(scheme: 'http', host: 'example.com', path: '.evil.com/x');
        self::assertSame('http://example.com/.evil.com/x', (string) $uri);

        // 8. OpenApiGenerator never exposes Principal schema & ApiDocsRenderer blocks javascript: specUrl
        $spec = (new OpenApiGenerator())->generate($authApp);
        self::assertArrayNotHasKey('components', $spec);
        try {
            (new ApiDocsRenderer())->render($authApp, 'javascript:alert(1)');
            self::fail('Expected ApiDocsRenderer to reject javascript: specUrl.');
        } catch (\InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        // 9. EventBus enforces maxChannels bound (prevents worker OOM)
        $bus = new EventBus(maxHistoryPerChannel: 5, maxChannels: 2);
        $bus->publish('c1', 'e', ['a' => 1]);
        $bus->publish('c2', 'e', ['a' => 2]);
        $bus->publish('c3', 'e', ['a' => 3]);
        self::assertSame([], $bus->since('c1'));
        self::assertCount(1, $bus->since('c3'));
    }

    public function testChunkedDynamicRouterMatchingRedisEventBusAndCachedAppDispatch(): void
    {
        // 1. Group-chunked PCRE2 (*MARK:i) router matches accurately across 64 dynamic routes (4 chunks of 16) + 405 Allow
        $router = new Router();
        for ($i = 0; $i < 64; ++$i) {
            $router->get("/r{$i}/{tenant:[a-z]+}/{id:[0-9]+}", static fn(): Response => Response::text("get-{$i}"))->name("r{$i}");
            $router->post("/r{$i}/{tenant:[a-z]+}/{id:[0-9]+}", static fn(): Response => Response::text("post-{$i}"));
        }

        $m0 = $router->match('GET', '/r0/acme/1');
        self::assertSame(['tenant' => 'acme', 'id' => '1'], $m0->params);
        $m31 = $router->match('POST', '/r31/northstar/77');
        self::assertSame(['tenant' => 'northstar', 'id' => '77'], $m31->params);
        $m63 = $router->match('GET', '/r63/kurogane/999');
        self::assertSame(['tenant' => 'kurogane', 'id' => '999'], $m63->params);

        try {
            $router->match('DELETE', '/r45/acme/42');
            self::fail('Expected 405 Method Not Allowed.');
        } catch (HttpException $e) {
            self::assertSame(405, $e->statusCode);
            self::assertStringContainsString('GET', $e->headers['Allow'] ?? '');
            self::assertStringContainsString('POST', $e->headers['Allow'] ?? '');
        }

        // 2. RedisEventBus publishes via atomic Lua EVAL and replays Last-Event-ID via ZRANGEBYSCORE
        $domain = '\\' === DIRECTORY_SEPARATOR ? STREAM_PF_INET : STREAM_PF_UNIX;
        $pair = stream_socket_pair($domain, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($pair);
        [$clientSock, $serverSock] = $pair;

        $envelopeJson = json_encode(['id' => '7', 'event' => 'note.created', 'data' => '{"id":99}'], JSON_THROW_ON_ERROR);
        fwrite(
            $serverSock,
            ":7\r\n"
            . "*1\r\n\$" . strlen($envelopeJson) . "\r\n" . $envelopeJson . "\r\n",
        );

        try {
            $redis = \Kongono\Core\Cache\RedisClient::fromStream($clientSock);
            $redisBus = new \Kongono\Http\Realtime\RedisEventBus($redis);
            $published = $redisBus->publish('notes', 'note.created', ['id' => 99]);
            self::assertSame('7', $published->id);
            self::assertSame('note.created', $published->event);

            $replayed = $redisBus->since('notes', '6');
            self::assertCount(1, $replayed);
            self::assertSame('7', $replayed[0]->id);
            self::assertSame('{"id":99}', $replayed[0]->data);
            $redis->close();
        } finally {
            fclose($serverSock);
        }

        // 3. Cached controller + DTO dispatch executes identically across repeated warm requests
        $app = App::create(__DIR__)
            ->routes(static function (Router $r): void {
                $r->post('/items/{id}', static fn(RoutePrecedenceInputDto $dto): Response => Response::json(['id' => $dto->id, 'title' => $dto->title]));
            });
        for ($i = 1; $i <= 5; ++$i) {
            $res = $app->handle(Request::json('POST', "/items/item-{$i}", ['id' => 'ignored', 'title' => "Title {$i}"]));
            self::assertSame(200, $res->getStatusCode());
            self::assertSame("{\"id\":\"item-{$i}\",\"title\":\"Title {$i}\"}", (string) $res->getBody());
        }
    }

    public function testLive2RuntimeMorphingZeroEvalAndKongonoBridgeProtocol(): void
    {
        // 1. Live 2.5 runtime: zero eval / zero new Function, includes keyed morphing, safe AST + pipes,
        //    speculative prefetch, out-of-band morph, k-persist, offline queue, sortable DnD, and hybrid island props
        $liveJs = LiveMiddleware::scriptSource();
        self::assertStringContainsString('version: "2.5.0"', $liveJs);
        self::assertStringContainsString('morphChildren', $liveJs);
        self::assertStringContainsString('BLOCKED_KEYS', $liveJs);
        self::assertStringContainsString('startViewTransition', $liveJs);
        self::assertStringContainsString('kgn-optimistic-text', $liveJs);
        self::assertStringContainsString('compileExpr', $liveJs);
        self::assertStringContainsString('PIPES.currency', $liveJs);
        self::assertStringContainsString('kgn-prefetch', $liveJs);
        self::assertStringContainsString('kgn-oob', $liveJs);
        self::assertStringContainsString('k-persist', $liveJs);
        self::assertStringContainsString('kgn-offline', $liveJs);
        self::assertStringContainsString('kgn-sortable', $liveJs);
        self::assertStringContainsString('kongono:island-props', $liveJs);
        self::assertStringNotContainsString('eval(', $liveJs);
        self::assertStringNotContainsString('new Function', $liveJs);

        $frag = Response::fragment(
            '<div id="c">ok</div>',
            swap: 'morph',
            oob: ['<span id="badge" kgn-oob="morph">5</span>'],
        );
        self::assertSame('morph', $frag->getHeaderLine('X-Kongono-Swap'));
        self::assertStringContainsString('kgn-oob="morph"', (string) $frag->getBody());
        try {
            Response::fragment('<div>x</div>', swap: 'evil_swap');
            self::fail('Expected invalid swap mode to fail.');
        } catch (\InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        // 1b. Single-action Response::adaptive() serves JSON, Live fragment, or full SSR HTML lazily
        $htmlCalls = 0;
        $fragCalls = 0;
        $jsonCalls = 0;
        $adaptiveApp = App::create(__DIR__)->routes(static function (Router $r) use (&$htmlCalls, &$fragCalls, &$jsonCalls): void {
            $r->get('/lots/{id}', static function (Request $req, int $id) use (&$htmlCalls, &$fragCalls, &$jsonCalls): Response {
                return Response::adaptive(
                    request: $req,
                    html: static function () use (&$htmlCalls, $id): string {
                        ++$htmlCalls;

                        return "<html><body>Full Lot {$id}</body></html>";
                    },
                    fragment: static function () use (&$fragCalls, $id): string {
                        ++$fragCalls;

                        return "<section id=\"lot\">Fragment Lot {$id}</section>";
                    },
                    json: static function () use (&$jsonCalls, $id): array {
                        ++$jsonCalls;

                        return ['id' => $id, 'kind' => 'lot'];
                    },
                    oob: ['<div id="ticker" kgn-oob="morphInner">Live</div>'],
                );
            });
        });

        $apiRes = $adaptiveApp->handle(Request::create('GET', '/lots/9', ['Accept' => 'application/json']));
        self::assertSame(200, $apiRes->getStatusCode());
        self::assertStringContainsString('application/json', $apiRes->getHeaderLine('Content-Type'));
        self::assertSame('{"id":9,"kind":"lot"}', (string) $apiRes->getBody());
        self::assertSame(1, $jsonCalls);
        self::assertSame(0, $htmlCalls);
        self::assertSame(0, $fragCalls);

        $liveRes = $adaptiveApp->handle(Request::create('GET', '/lots/9', ['X-Kongono-Live' => 'true', 'X-Kongono-Prefetch' => 'true', 'Accept' => 'text/html']));
        self::assertSame(200, $liveRes->getStatusCode());
        self::assertSame('morph', $liveRes->getHeaderLine('X-Kongono-Swap'));
        self::assertStringContainsString('Fragment Lot 9', (string) $liveRes->getBody());
        self::assertStringContainsString('kgn-oob="morphInner"', (string) $liveRes->getBody());
        self::assertSame(1, $fragCalls);
        self::assertSame(0, $htmlCalls);
        self::assertTrue(Request::create('GET', '/lots/9', ['X-Kongono-Prefetch' => 'true'])->isPrefetch());

        $fullRes = $adaptiveApp->handle(Request::create('GET', '/lots/9', ['Accept' => 'text/html']));
        self::assertSame(200, $fullRes->getStatusCode());
        self::assertStringContainsString('Full Lot 9', (string) $fullRes->getBody());
        self::assertSame(1, $htmlCalls);

        // 2. Kongono Bridge (React/Vue/Svelte): initial HTML, SPA JSON, lazy partial reloads, hybrid islands, 409/422/303
        $expensiveCalls = 0;
        $bridgeMw = new \Kongono\Http\Bridge\BridgeMiddleware(
            version: 'v-bundle-42',
            sharedProps: static fn(): array => ['appName' => 'Kongono'],
        );

        $app = App::create(__DIR__)
            ->middleware($bridgeMw)
            ->routes(static function (Router $r) use (&$expensiveCalls): void {
                $r->get('/projects/{id}', static function (int $id) use (&$expensiveCalls): Response {
                    return Response::page('Projects/Show', [
                        'id' => $id,
                        'counter' => 99,
                        'expensive' => static function () use (&$expensiveCalls): string {
                            ++$expensiveCalls;

                            return 'computed-heavy';
                        },
                    ]);
                });
                $r->post('/projects', static fn(RoutePrecedenceInputDto $dto): Response => Response::page('Projects/Show', ['title' => $dto->title]));
                $r->delete('/projects/{id}', static fn(): Response => Response::redirect('/projects'));
            });

        // Bridge JS runtime served with ETag, mountIslands, + zero eval
        $jsRes = $app->handle(Request::create('GET', '/_kongono/bridge.js'));
        self::assertSame(200, $jsRes->getStatusCode());
        self::assertSame('public, max-age=0, must-revalidate', $jsRes->getHeaderLine('Cache-Control'));
        $jsVer = $app->handle(Request::create('GET', '/_kongono/bridge.js?v=1.10.1'));
        self::assertStringContainsString('immutable', $jsVer->getHeaderLine('Cache-Control'));
        self::assertStringContainsString('window.KongonoBridge', (string) $jsRes->getBody());
        self::assertStringContainsString('mountIslands', (string) $jsRes->getBody());
        self::assertStringContainsString('kongono:island-props', (string) $jsRes->getBody());
        self::assertStringNotContainsString('eval(', (string) $jsRes->getBody());
        self::assertSame(304, $app->handle(Request::create('GET', '/_kongono/bridge.js', ['If-None-Match' => $jsRes->getHeaderLine('ETag')]))->getStatusCode());

        // Initial browser GET returns strict HTML shell with escaped data-page & evaluates lazy prop
        $htmlRes = $app->handle(Request::create('GET', '/projects/7'));
        self::assertSame(200, $htmlRes->getStatusCode());
        self::assertStringContainsString('text/html', $htmlRes->getHeaderLine('Content-Type'));
        self::assertStringContainsString('data-page="', (string) $htmlRes->getBody());
        self::assertStringContainsString('Projects/Show', (string) $htmlRes->getBody());
        self::assertSame(1, $expensiveCalls);

        // Partial reload evaluates ONLY requested prop `counter` and SKIPS `expensive` closure!
        $partialRes = $app->handle(Request::create('GET', '/projects/7', [
            'X-Kongono-Bridge' => 'true',
            'X-Kongono-Bridge-Version' => 'v-bundle-42',
            'X-Kongono-Bridge-Partial-Component' => 'Projects/Show',
            'X-Kongono-Bridge-Partial-Data' => 'counter',
        ]));
        self::assertSame(200, $partialRes->getStatusCode());
        self::assertSame('true', $partialRes->getHeaderLine('X-Kongono-Bridge'));
        self::assertSame(1, $expensiveCalls); // Expensive closure was NOT invoked!
        $partialJson = json_decode((string) $partialRes->getBody(), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($partialJson);
        self::assertSame(['counter' => 99], $partialJson['props']);

        // Stale bundle version on GET -> 409 Conflict with X-Kongono-Bridge-Location
        $staleRes = $app->handle(Request::create('GET', '/projects/7?tab=tasks', [
            'X-Kongono-Bridge' => 'true',
            'X-Kongono-Bridge-Version' => 'v-bundle-old',
        ]));
        self::assertSame(409, $staleRes->getStatusCode());
        self::assertSame('/projects/7?tab=tasks', $staleRes->getHeaderLine('X-Kongono-Bridge-Location'));

        // ValidationException on Bridge POST -> 422 JSON errors
        $valRes = $app->handle(Request::create('POST', '/projects', ['X-Kongono-Bridge' => 'true', 'Content-Type' => 'application/json'], '{}'));
        self::assertSame(422, $valRes->getStatusCode());
        self::assertSame('true', $valRes->getHeaderLine('X-Kongono-Bridge'));
        self::assertStringContainsString('"errors"', (string) $valRes->getBody());

        // 302 redirect on Bridge DELETE -> 303 See Other
        $delRes = $app->handle(Request::create('DELETE', '/projects/7', ['X-Kongono-Bridge' => 'true']));
        self::assertSame(303, $delRes->getStatusCode());

        // Unsafe component path fails closed
        $this->expectException(\InvalidArgumentException::class);
        Response::page('../SecretAdmin');
    }

    public function testHtmlFormValidationFlashesScrubbedOldInputAndRedirectsSameOriginOnly(): void
    {
        $tmpViews = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-flash-views-' . bin2hex(random_bytes(5));
        mkdir($tmpViews, 0o777, true);
        file_put_contents($tmpViews . '/signup.kgn.html', <<<'HTML'
<form method="post" action="/signup">
  <input name="email" value="@old('email')">
  <input name="password" value="@old('password')">
  <input name="_csrf" value="@old('_csrf')">
  @error('email')
  <p class="field-error">{{ $message }}</p>
  @enderror
</form>
HTML);

        try {
            $store = new \Kongono\Http\Session\CacheSessionStore(new \Kongono\Core\Cache\MemoryCache());
            $app = App::create(__DIR__, name: 'Form Flash Test')
                ->views($tmpViews, $tmpViews . '/cache')
                ->middleware(new \Kongono\Http\Session\SessionMiddleware($store))
                ->routes(static function (Router $r): void {
                    $r->get('/signup', static fn(\Kongono\View\ViewEngine $views): Response => Response::html($views->render('signup')));
                    $r->post('/signup', static fn(HtmlSignupFormDto $input): Response => Response::html('OK:' . $input->email));
                    $r->post('/api/orders', static fn(OpenApiArrayOrderDto $input): Response => Response::json(['ok' => true, 'count' => count($input->items)]));
                });

            // 1. Invalid HTML form POST with verified same-origin Referer -> 303 See Other + flashed errors & scrubbed old()
            $post = Request::create(
                method: 'POST',
                uri: 'http://app.test/signup',
                headers: ['Referer' => 'http://app.test/signup?step=1'],
                parsedBody: [
                    'email' => 'bad-email',
                    'password' => 'TopSecret123!',
                    '_csrf' => 'csrf-secret-value',
                    'api_token' => 'tok-live-999',
                ],
            );
            $res1 = $app->handle($post);
            self::assertSame(303, $res1->getStatusCode());
            self::assertSame('/signup?step=1', $res1->getHeaderLine('Location'));

            $setCookie = $res1->getHeaderLine('Set-Cookie');
            self::assertSame(1, preg_match('/kongono_session=([a-f0-9]+)/', $setCookie, $cm));
            $sessionId = $cm[1];

            // 2. Follow-up GET /signup receives flashed @error('email') and @old('email'), while password/_csrf/api_token are SCRUBBED!
            $get1 = Request::create('GET', 'http://app.test/signup?step=1')->withCookieParams(['kongono_session' => $sessionId]);
            $res2 = $app->handle($get1);
            self::assertSame(200, $res2->getStatusCode());
            $html1 = (string) $res2->getBody();
            self::assertStringContainsString('value="bad-email"', $html1);
            self::assertStringContainsString('<p class="field-error">The email field must be a valid email address.</p>', $html1);
            self::assertStringNotContainsString('TopSecret123!', $html1);
            self::assertStringNotContainsString('csrf-secret-value', $html1);
            self::assertStringNotContainsString('tok-live-999', $html1);

            // 3. Subsequent GET /signup sees ZERO flash state (one-request flash consumed; persistent-worker safe!)
            $get2 = Request::create('GET', 'http://app.test/signup')->withCookieParams(['kongono_session' => $sessionId]);
            $res3 = $app->handle($get2);
            self::assertSame(200, $res3->getStatusCode());
            $html2 = (string) $res3->getBody();
            self::assertStringNotContainsString('bad-email', $html2);
            self::assertStringNotContainsString('field-error', $html2);

            // 4. Open-redirect defense: cross-origin, scheme-relative, and userinfo Referers NEVER redirect (fail closed to 422)
            foreach (['https://evil.test/phish', '//evil.test/phish', 'http://app.test@evil.test/signup'] as $evilRef) {
                $evilPost = Request::create(
                    method: 'POST',
                    uri: 'http://app.test/signup',
                    headers: ['Referer' => $evilRef],
                    parsedBody: ['email' => 'bad'],
                );
                $evilRes = $app->handle($evilPost);
                self::assertSame(422, $evilRes->getStatusCode(), 'Cross-origin Referer must not redirect: ' . $evilRef);
            }

            // 5. Kongono Live requests stay 422 even with same-origin Referer
            $livePost = Request::create(
                method: 'POST',
                uri: 'http://app.test/signup',
                headers: ['Referer' => 'http://app.test/signup', 'X-Kongono-Live' => 'true'],
                parsedBody: ['email' => 'bad'],
            );
            self::assertSame(422, $app->handle($livePost)->getStatusCode());

            // 6. OpenApiGenerator emits typed ArrayOf item $ref, date, ipv4, and uuid formats
            $spec = (new \Kongono\Http\OpenApi\OpenApiGenerator())->generate($app);
            $components = is_array($spec['components'] ?? null) ? $spec['components'] : [];
            $schemas = is_array($components['schemas'] ?? null) ? $components['schemas'] : [];
            self::assertArrayHasKey('OpenApiArrayOrderDto', $schemas);
            self::assertArrayHasKey('OpenApiLineItemDto', $schemas);
            $orderSchema = is_array($schemas['OpenApiArrayOrderDto']) ? $schemas['OpenApiArrayOrderDto'] : [];
            $orderProps = is_array($orderSchema['properties'] ?? null) ? $orderSchema['properties'] : [];
            $itemsProp = is_array($orderProps['items'] ?? null) ? $orderProps['items'] : [];
            $dateProp = is_array($orderProps['deliveryDate'] ?? null) ? $orderProps['deliveryDate'] : [];
            $ipProp = is_array($orderProps['clientIp'] ?? null) ? $orderProps['clientIp'] : [];
            self::assertSame(['$ref' => '#/components/schemas/OpenApiLineItemDto'], $itemsProp['items'] ?? null);
            self::assertSame('date', $dateProp['format'] ?? null);
            self::assertSame('ipv4', $ipProp['format'] ?? null);
        } finally {
            $entries = @scandir($tmpViews);
            if (is_array($entries)) {
                foreach ($entries as $e) {
                    if ('.' === $e || '..' === $e) {
                        continue;
                    }
                    $p = $tmpViews . DIRECTORY_SEPARATOR . $e;
                    if (is_dir($p)) {
                        $childEntries = scandir($p);
                        if (is_array($childEntries)) {
                            foreach ($childEntries as $ce) {
                                if ('.' !== $ce && '..' !== $ce) {
                                    @unlink($p . DIRECTORY_SEPARATOR . $ce);
                                }
                            }
                        }
                        @rmdir($p);
                    } else {
                        @unlink($p);
                    }
                }
            }
            @rmdir($tmpViews);
        }
    }

    public function testSignedLocalStorageDownloadStreamsSafelyAndSandboxesInlineSvg(): void
    {
        $storage = new \Kongono\Storage\MemoryStorage();
        $disks = new \Kongono\Storage\StorageDisks(['private' => $storage], default: 'private');
        $urls = new \Kongono\Storage\SignedLocalUrl('storage-signing-secret-key-at-least-32-bytes', '/_storage/download');

        $pdfBytes = "%PDF-1.7\n%Kongono invoice stream test\n" . str_repeat('0123456789', 100);
        $storage->put('invoices/2026/inv-42.pdf', $pdfBytes, 'application/pdf');

        $app = App::create(__DIR__, name: 'Storage Download Test')
            ->instance(\Kongono\Storage\StorageDisks::class, $disks)
            ->instance(\Kongono\Storage\SignedLocalUrl::class, $urls)
            ->routes(static function (Router $r): void {
                $r->get('/_storage/download', static function (
                    Request $request,
                    \Kongono\Storage\StorageDisks $disks,
                    \Kongono\Storage\SignedLocalUrl $urls,
                ): Response {
                    $verified = $urls->verify((string) $request->getUri());
                    if (null === $verified) {
                        throw new HttpException(403, 'Invalid or expired download link.');
                    }

                    return Response::fromStorage($disks->disk($verified['disk']), $verified['key'], $verified['name']);
                });
            });

        $signedUrl = $urls->temporaryDownloadUrl('invoices/2026/inv-42.pdf', expiresSeconds: 300, downloadName: 'Invoice #42.pdf', disk: 'private');
        $ok = $app->handle(Request::create('GET', $signedUrl));
        self::assertSame(200, $ok->getStatusCode());
        self::assertSame('application/pdf', $ok->getHeaderLine('Content-Type'));
        self::assertSame('nosniff', $ok->getHeaderLine('X-Content-Type-Options'));
        self::assertStringContainsString('attachment; filename="Invoice #42.pdf"', $ok->getHeaderLine('Content-Disposition'));
        self::assertInstanceOf(Response::class, $ok);
        self::assertTrue($ok->isStreaming());
        self::assertSame($pdfBytes, $ok->bodyString());

        // Tampered link fails closed with 403
        $tampered = $app->handle(Request::create('GET', str_replace('inv-42.pdf', 'inv-99.pdf', $signedUrl)));
        self::assertSame(403, $tampered->getStatusCode());

        // Missing file returns 404
        $missingUrl = $urls->temporaryDownloadUrl('invoices/missing.pdf', expiresSeconds: 300, disk: 'private');
        self::assertSame(404, $app->handle(Request::create('GET', $missingUrl))->getStatusCode());

        // Inline SVG download forces CSP sandbox + strips CRLF/quote filename injection
        $svgRes = Response::download('<svg></svg>', "evil\"\r\nX-Injected: 1.svg", 'image/svg+xml', inline: true);
        self::assertSame('sandbox', $svgRes->getHeaderLine('Content-Security-Policy'));
        self::assertStringNotContainsString("\r", $svgRes->getHeaderLine('Content-Disposition'));
        self::assertStringContainsString('inline; filename=', $svgRes->getHeaderLine('Content-Disposition'));
    }

    public function testTestClientActingAsStatefulCsrfUploadsRedirectsAndRichTestResponseAssertions(): void
    {
        $signer = new \Kongono\Security\TokenSigner('test-client-v115-csrf-secret-key-32b!');
        $sessionStore = new CacheSessionStore(new MemoryCache());
        $memStorage = new \Kongono\Storage\MemoryStorage();
        $clock = FrozenClock::at('2026-11-22 15:30:00');

        $app = App::create(__DIR__)
            ->instance(ClockInterface::class, $clock)
            ->middleware(
                new SessionMiddleware($sessionStore, cookieName: 'ktest_sid', secure: false),
                new \Kongono\Security\Middleware\CsrfMiddleware($signer, sessionCookieName: 'ktest_sid', exemptPrefixes: ['/api/']),
            )
            ->routes(static function (Router $r) use ($signer, $memStorage): void {
                $r->get('/form', static function (Session $session) use ($signer): Response {
                    $csrf = $signer->generateCsrfToken($session->id());

                    return Response::html(sprintf(
                        '<html><head><meta name="csrf-token" content="%s"></head><body><h1>Step 1</h1><p>Step 2</p></body></html>',
                        $csrf,
                    ));
                });

                $r->post('/form', static function (Request $req, Session $session): Response {
                    $name = is_string($req->input('name')) ? $req->input('name') : 'anon';
                    $session->set('saved_name', $name);

                    return Response::redirect('/done', 303);
                });

                $r->get('/done', static function (Session $session, ClockInterface $clk): Response {
                    $saved = is_string($session->get('saved_name')) ? $session->get('saved_name') : '';

                    return Response::html('<main>Saved: ' . $saved . ' @ ' . $clk->now()->format('H:i') . '</main>');
                });

                $r->post('/live/counter', static fn(): Response => Response::fragment(
                    '<div id="count">42</div>',
                    swap: 'morph',
                    oob: ['<span id="badge" kgn-oob="morph">42</span>'],
                ));

                $r->get('/api/secret', static fn(\Kongono\Security\Principal $user): Response => Response::json([
                    'actor' => $user->id,
                    'items' => [
                        ['id' => 1, 'label' => 'Alpha'],
                        ['id' => 2, 'label' => 'Beta'],
                    ],
                ]))->middlewareAttributes(new \Kongono\Security\Attributes\Can('reports.read'));

                $r->post('/api/validate', static fn(HtmlSignupFormDto $dto): Response => Response::json(['email' => $dto->email]));

                $r->post('/api/upload', static function (Request $req) use ($memStorage): Response {
                    $file = $req->file('avatar');
                    if (null === $file) {
                        throw new HttpException(400, 'Missing avatar.');
                    }
                    $uploader = new \Kongono\Storage\UploadedFileStore($memStorage, ['image/png', 'image/jpeg', 'application/pdf']);
                    $stored = $uploader->store($file, prefix: 'avatars');

                    return Response::json(['key' => $stored->meta->key, 'mime' => $stored->meta->contentType], 201);
                });

                $r->get('/api/download', static fn(): Response => Response::download('hello world', 'report.txt', 'text/plain'));
                $r->post('/api/raw', static fn(Request $req): Response => Response::text('raw:' . (string) $req->getBody()));
            });

        // 1. Stateful cookies + automatic CSRF meta extraction + followRedirects() + ClockInterface DI
        $browser = (new TestClient($app))->withStatefulCookies();
        $formRes = $browser->get('/form')
            ->assertOk()
            ->assertCookie('ktest_sid')
            ->assertHeaderMissing('X-Kongono-Swap')
            ->assertSeeInOrder(['<h1>Step 1</h1>', '<p>Step 2</p>']);
        self::assertNotNull($browser->csrfToken());
        self::assertArrayHasKey('ktest_sid', $formRes->cookies());

        $clock->advanceMinutes(15);
        $redirectRes = $browser->postForm('/form', ['name' => 'Ada'])
            ->assertRedirect('/done');
        $browser->followRedirects($redirectRes)
            ->assertOk()
            ->assertSee('Saved: Ada @ 15:45');

        // 2. Live 2.5 helpers: withLive(), assertLiveSwap(), assertLiveOob()
        $browser->withLive()->postForm('/live/counter', [])
            ->assertOk()
            ->assertLiveSwap('morph')
            ->assertLiveOob('badge');

        // 3. actingAs(Principal): unauthenticated -> 401, missing ability -> 403, authorized -> 200 + JSON assertions
        $api = new TestClient($app);
        $api->get('/api/secret')->assertUnauthorized();

        $guest = new \Kongono\Security\Principal('u-1', \Kongono\Security\PrincipalKind::Human, ['other.read']);
        $api->actingAs($guest)->get('/api/secret')->assertForbidden();

        $reader = new \Kongono\Security\Principal('u-2', \Kongono\Security\PrincipalKind::Human, ['reports.read']);
        $api->actingAs($reader)->get('/api/secret')
            ->assertOk()
            ->assertJsonPath('actor', 'u-2')
            ->assertJsonCount(2, 'items')
            ->assertJsonContains(['actor' => 'u-2', 'items' => [0 => ['label' => 'Alpha']]])
            ->assertJsonStructure(['actor', 'items' => ['*' => ['id', 'label']]]);

        // 4. assertValidationError() on 422 DTO validation failure
        $api->postJson('/api/validate', ['email' => 'not-an-email', 'password' => '123'])
            ->assertValidationError('email')
            ->assertValidationError('password');

        // 5. postMultipart() + TestUpload::fakePng/fakeJpeg/fakePdf passing real UploadedFileStore magic-byte checks
        $api->postMultipart('/api/upload', ['avatar' => TestUpload::fakePng('me.png')])
            ->assertCreated()
            ->assertJsonPath('mime', 'image/png');
        $api->postMultipart('/api/upload', ['avatar' => TestUpload::fakeJpeg('pic.jpg')])
            ->assertCreated()
            ->assertJsonPath('mime', 'image/jpeg');
        $api->postMultipart('/api/upload', ['avatar' => TestUpload::fakePdf('doc.pdf')])
            ->assertCreated()
            ->assertJsonPath('mime', 'application/pdf');

        // 6. assertDownload() and TestClient::request() (used by Dev Studio TestExporter)
        $api->get('/api/download')->assertOk()->assertDownload('report.txt');
        $api->request('POST', '/api/raw', body: 'ping')->assertOk()->assertSee('raw:ping');
    }

    public function testOpsDashboardCapabilityGateLiveMorphJsonSnapshotAndQueueActions(): void
    {
        $queueDriver = new \Kongono\Jobs\MemoryQueueDriver();
        $queue = new \Kongono\Jobs\Queue($queueDriver);
        $deadJob = $queue->dispatch('billing.sync', ['id' => 1], queue: 'critical', maxAttempts: 1);
        $deadJob2 = $queue->dispatch('billing.sync', ['id' => 2], queue: 'critical', maxAttempts: 1);

        $worker = (new \Kongono\Jobs\Worker($queueDriver))
            ->register('billing.sync', static function (): never {
                throw new RuntimeException('Gateway failed with sk_live_1234567890abcdef1234');
            });
        $worker->run('critical');

        $health = (new \Kongono\Core\Health\HealthCheck())->check('postgres', static fn(): bool => true);
        $metrics = new \Kongono\Core\Telemetry\Metrics();
        $metrics->increment('http_requests_total', ['route' => '/api/orders'], 5.0);
        $metrics->observe('http_duration_ms', 12.5);

        $batch = new \Kongono\Jobs\Batch\JobBatch(
            id: 'batch_ops_1',
            name: 'Nightly Invoices',
            totalJobs: 10,
            pendingJobs: 2,
            processedJobs: 8,
            failedJobs: 0,
            cancelled: false,
            allowFailures: true,
            createdAt: time(),
        );

        $opsApp = App::create(__DIR__, name: 'Acme Ops')
            ->middleware(new \Kongono\Http\Ops\OpsDashboardMiddleware(
                appName: 'Acme Ops',
                queueDriver: $queueDriver,
                queues: ['critical', 'default'],
                healthCheck: $health,
                metrics: $metrics,
                batchesProvider: static fn(): array => [$batch],
            ));

        $client = new TestClient($opsApp);

        // 1. Fail-closed Gate authorization: unauthenticated -> 401, missing capability -> 403
        $client->get('/_ops')->assertUnauthorized();
        $basicUser = new \Kongono\Security\Principal('u-1', \Kongono\Security\PrincipalKind::Human, ['orders.read']);
        $client->actingAs($basicUser)->get('/_ops')->assertForbidden();

        // 2. ops.view can inspect HTML (strict CSP, no-store, scrubbed secret), Live morph, and JSON snapshot
        $viewer = new \Kongono\Security\Principal('u-view', \Kongono\Security\PrincipalKind::Human, ['ops.view']);
        $client->actingAs($viewer)->get('/_ops')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertSee('Acme Ops — Ops Dashboard')
            ->assertSee('Nightly Invoices')
            ->assertSee('http_requests_total')
            ->assertSee('[REDACTED]')
            ->assertDontSee('sk_live_1234567890abcdef1234');

        $client->actingAs($viewer)->withLive()->get('/_ops')
            ->assertOk()
            ->assertLiveSwap('morph')
            ->assertSee('ops-dashboard-root');

        $client->actingAs($viewer)->get('/_ops', ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('app', 'Acme Ops')
            ->assertJsonPath('health.status', 'ok')
            ->assertJsonPath('queues.critical.dead', 2)
            ->assertJsonCount(2, 'failed_jobs')
            ->assertJsonPath('batches.0.progress', 80);

        // 3. Viewer WITHOUT ops.manage cannot retry or flush queues (403)
        $client->actingAs($viewer)->postJson('/_ops/queues/retry', ['queue' => 'critical', 'job_id' => $deadJob->id])
            ->assertForbidden();

        // 4. Manager WITH ops.manage can retry a single job, reject unlisted queues (400), and flush remaining dead jobs
        $manager = new \Kongono\Security\Principal('u-ops', \Kongono\Security\PrincipalKind::Human, ['ops.view', 'ops.manage']);
        $client->actingAs($manager)->postJson('/_ops/queues/retry', ['queue' => 'unlisted_queue'])
            ->assertStatus(400);

        $client->actingAs($manager)->postJson('/_ops/queues/retry', ['queue' => 'critical', 'job_id' => $deadJob->id])
            ->assertOk()
            ->assertJsonPath('requeued', 1);
        self::assertSame(1, $queueDriver->stats('critical')['pending']);
        self::assertSame(1, $queueDriver->stats('critical')['dead']);

        // Flush remaining dead job ($deadJob2) via Live morph POST
        $client->actingAs($manager)->withLive()->postForm('/_ops/queues/flush', ['queue' => 'critical'])
            ->assertOk()
            ->assertLiveSwap('morph')
            ->assertSee('Flushed 1 failed job(s)');
        self::assertSame(0, $queueDriver->stats('critical')['dead']);
        self::assertNotEmpty($deadJob2->id);
    }

    public function testPolishedProductionErrorPagesCustomViewsAndUpgradedDevErrorRenderer(): void
    {
        // 1. Default polished production 404, 422, and 500 pages are nondisclosing, zero-JS, and secret-scrubbed
        $prodApp = App::create(__DIR__, name: 'Acme Prod')->config(['app' => ['debug' => false]])->routes(static function (Router $r): void {
            $r->post('/signup', static fn(HtmlSignupFormDto $dto): Response => Response::text($dto->email));
            $r->get('/boom', static function (): Response {
                throw new RuntimeException('DB crashed with secret gsk_1234567890abcdefghijKLMN');
            });
        });

        $prodClient = new \Kongono\Http\Testing\TestClient($prodApp);
        $prodClient->get('/missing-page')
            ->assertStatus(404)
            ->assertSee('Page Not Found')
            ->assertSee('HTTP 404')
            ->assertSee('Acme Prod')
            ->assertDontSee('DEV HINT')
            ->assertDontSee('<script');

        $prodClient->postForm('/signup', ['email' => 'not-an-email', 'password' => 'short'])
            ->assertStatus(422)
            ->assertSee('Validation Failed')
            ->assertSee('email')
            ->assertSee('password')
            ->assertDontSee('<script');

        $prodClient->get('/boom')
            ->assertStatus(500)
            ->assertSee('Internal Server Error')
            ->assertDontSee('gsk_1234567890abcdefghijKLMN')
            ->assertDontSee('RuntimeException')
            ->assertDontSee('<script');

        // 2. Custom `views/errors/{status}.kgn.html` and fallback `views/errors/error.kgn.html` override defaults cleanly
        $tmpRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_errviews_' . bin2hex(random_bytes(4));
        mkdir($tmpRoot . '/views/errors', 0o775, true);
        mkdir($tmpRoot . '/cache', 0o775, true);
        try {
            file_put_contents($tmpRoot . '/views/errors/404.kgn.html', '<h1>Custom 404 for {{ $path }}</h1>');
            file_put_contents($tmpRoot . '/views/errors/error.kgn.html', '<h1>Fallback {{ $status }}: {{ $message }}</h1>');

            $customApp = App::create($tmpRoot, name: 'Custom Views App')
                ->config(['app' => ['debug' => false]])
                ->views($tmpRoot . '/views', $tmpRoot . '/cache')
                ->routes(static function (Router $r): void {
                    $r->get('/denied', static function (): Response {
                        throw new HttpException(403, 'Admins only');
                    });
                });

            $customClient = new \Kongono\Http\Testing\TestClient($customApp);
            $customClient->get('/nope')->assertStatus(404)->assertSee('Custom 404 for /nope');
            $customClient->get('/denied')->assertStatus(403)->assertSee('Fallback 403: Admins only');
        } finally {
            foreach (glob($tmpRoot . '/cache/*.php') ?: [] as $cachedFile) {
                @unlink($cachedFile);
            }
            @unlink($tmpRoot . '/views/errors/404.kgn.html');
            @unlink($tmpRoot . '/views/errors/error.kgn.html');
            @rmdir($tmpRoot . '/views/errors');
            @rmdir($tmpRoot . '/views');
            @rmdir($tmpRoot . '/cache');
            @rmdir($tmpRoot);
        }

        // 3. Upgraded DevErrorRenderer: Quick Fix suggestions, previous exception chain, pure-CSS multi-frame explorer, & scrubbed LLM prompt packet
        $rootCause = new RuntimeException('SQLSTATE[42P01]: relation "invoices" does not exist (token gsk_1234567890abcdefghijKLMN)');
        $wrapped = new \Kongono\Core\CoreException(
            'Failed to load invoices: relation "invoices" does not exist',
            previous: $rootCause,
        );
        $renderer = new DevErrorRenderer();
        $req = Request::create('GET', '/invoices?filter=open', [
            'Authorization' => 'Bearer sk_live_secret_header_value',
            'Accept' => 'text/html',
        ]);
        $devHtml = $renderer->render($req, $wrapped, dirname(__DIR__, 3), 'Billing App');

        self::assertStringContainsString('Plain-English Diagnosis', $devHtml);
        self::assertStringContainsString('Missing Database Table', $devHtml);
        self::assertStringContainsString('migrate', $devHtml);
        self::assertStringContainsString('Caused by #1', $devHtml);
        self::assertStringContainsString('Stack Trace &amp; Source Context', $devHtml);
        self::assertStringContainsString('kgn-frame', $devHtml);
        self::assertStringContainsString('kgn-show-vendor', $devHtml);
        self::assertStringContainsString('kgn-tab-hantei', $devHtml);
        self::assertStringContainsString('tk-kw', $devHtml);
        self::assertStringContainsString('Diagnose Live with Hantei', $devHtml);
        self::assertStringContainsString('Copy for LLM', $devHtml);
        self::assertStringContainsString('/_kongono/assets/dev-error.js', $devHtml);
        self::assertStringContainsString('kgn-llm-prompt', $devHtml);
        self::assertStringContainsString('[REDACTED]', $devHtml);
        self::assertStringNotContainsString('gsk_1234567890abcdefghijKLMN', $devHtml);
        self::assertStringNotContainsString('sk_live_secret_header_value', $devHtml);

        $llmPacket = $renderer->buildLlmPrompt($req, $wrapped, dirname(__DIR__, 3));
        self::assertStringContainsString('# Kongono Exception Report', $llmPacket);
        self::assertStringContainsString('## Failing Code', $llmPacket);
        self::assertStringContainsString('## Stack Frames', $llmPacket);
        self::assertStringNotContainsString('gsk_1234567890abcdefghijKLMN', $llmPacket);
        self::assertStringNotContainsString('sk_live_secret_header_value', $llmPacket);

        // 4. DevDashboardMiddleware serves `/_kongono/assets/dev-error.js` in debug mode (404 in prod)
        $inspector = new DevInspector();
        $devApp = App::create(__DIR__, name: 'Dev JS')->config(['app' => ['debug' => true]]);
        $devApp->middleware(new DevDashboardMiddleware($devApp, $inspector));
        (new \Kongono\Http\Testing\TestClient($devApp))->get('/_kongono/assets/dev-error.js')
            ->assertOk()
            ->assertSee('/api/hantei/stream');
    }

    public function testDevStudioStrictCspExternalAssetsMemoryTelemetryAndScrubbedResponses(): void
    {
        $inspector = new DevInspector(nPlusOneThreshold: 2);
        $secretToken = 'sk_' . 'live_9876543210abcdefghijklmnop';
        $app = App::create(__DIR__, name: 'Batch1 Studio')
            ->debug(true)
            ->routes(static function (Router $r) use ($inspector, $secretToken): void {
                $r->get('/api/items', static function () use ($inspector, $secretToken): Response {
                    $inspector->recordQuery('SELECT * FROM items WHERE id = 1', 1.2);
                    $inspector->recordQuery('SELECT * FROM items WHERE id = 2', 1.5);

                    return Response::json(
                        ['items' => ['a', 'b'], 'api_key' => $secretToken],
                        200,
                        ['Set-Cookie' => 'session=secret_cookie_val; HttpOnly', 'X-Trace-Tag' => 'batch1'],
                    );
                });
                $r->get('/bin', static fn(): Response => Response::text("\x89PNG\r\n", 200, ['Content-Type' => 'image/png']));
            });
        $app->middleware(new DevDashboardMiddleware($app, $inspector));

        $client = new TestClient($app);

        // 1. Capture live request with N+1 queries, memory delta, and scrubbed response headers/body
        $apiRes = $client->get('/api/items')->assertOk()->assertJsonPath('items.0', 'a');
        self::assertSame('batch1', $apiRes->header('X-Trace-Tag'));
        $client->get('/bin')->assertOk();

        $traces = $client->get('/_kongono/api/traces')->assertOk()->json();
        self::assertIsArray($traces);
        self::assertCount(2, $traces);

        // Newest-first: index 0 is /bin (binary skipped), index 1 is /api/items
        self::assertSame('/bin', $traces[0]['path']);
        self::assertSame('', $traces[0]['response_body']);

        $apiTrace = $traces[1];
        self::assertSame('/api/items', $apiTrace['path']);
        self::assertIsNumeric($apiTrace['memory_delta_kb']);
        self::assertCount(1, $apiTrace['n_plus_one']);
        self::assertSame('[REDACTED]', $apiTrace['response_headers']['Set-Cookie'] ?? null);
        self::assertSame('batch1', $apiTrace['response_headers']['X-Trace-Tag'] ?? null);
        self::assertStringNotContainsString($secretToken, (string) $apiTrace['response_body']);
        self::assertStringContainsString('[REDACTED]', (string) $apiTrace['response_body']);

        // 2. Strict CSP HTML with zero inline styles/scripts + external CSS/JS assets
        $studioRes = $client->get('/_kongono')->assertOk();
        $csp = $studioRes->header('Content-Security-Policy');
        self::assertStringContainsString("script-src 'self'", $csp);
        self::assertStringContainsString("style-src 'self'", $csp);
        self::assertStringNotContainsString('unsafe-inline', $csp);

        $html = $studioRes->body();
        self::assertStringContainsString('/_kongono/assets/studio.css', $html);
        self::assertStringContainsString('/_kongono/assets/studio.js', $html);
        self::assertStringContainsString('id="kgn-studio-data"', $html);
        self::assertStringContainsString('id="traceDrawer"', $html);
        self::assertStringContainsString('id="liveTailBtn"', $html);
        self::assertStringContainsString('id="themeToggleBtn"', $html);
        self::assertStringContainsString('id="shortcutsModal"', $html);
        self::assertStringContainsString('id="proofFindingsList"', $html);
        self::assertStringNotContainsString('<style>', $html);
        self::assertStringNotContainsString('style="', $html);

        $cssRes = $client->get('/_kongono/assets/studio.css')->assertOk();
        self::assertStringContainsString('text/css', $cssRes->header('Content-Type'));
        self::assertStringContainsString('.mail-frame-mobile', $cssRes->body());

        $jsRes = $client->get('/_kongono/assets/studio.js')->assertOk();
        self::assertStringContainsString('application/javascript', $jsRes->header('Content-Type'));
        self::assertStringContainsString('extractEmailTokens', $jsRes->body());
        self::assertStringContainsString('buildCurl', $jsRes->body());

        // 3. Production fail-closed check: assets return 404 when debug=false
        $app->debug(false);
        $client->get('/_kongono/assets/studio.css')->assertStatus(404);
        $client->get('/_kongono/assets/studio.js')->assertStatus(404);

        // 4. File-backed DevInspector persists traces across stateless requests (kongo serve / PHP-FPM)
        $traceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_traces_' . bin2hex(random_bytes(4)) . '.json';
        try {
            $writer = new DevInspector(maxTraces: 2, storageFile: $traceFile);
            $writer->recordRequest('GET', '/first', 200, 1.5, memoryDeltaKb: 12.4, responseBody: '{"ok":true}');
            $writer->recordRequest('GET', '/second', 201, 2.5, memoryDeltaKb: 8.2, responseBody: '{"id":2}');
            $writer->recordRequest('GET', '/third', 500, 3.5, error: 'boom', memoryDeltaKb: 16.0);

            $reader = new DevInspector(maxTraces: 2, storageFile: $traceFile);
            $persisted = $reader->traces();
            self::assertCount(2, $persisted);
            self::assertSame(3, $persisted[0]['id']);
            self::assertSame('/third', $persisted[0]['path']);
            self::assertSame('boom', $persisted[0]['error']);
            self::assertSame(2, $persisted[1]['id']);
            self::assertSame('/second', $persisted[1]['path']);
            self::assertNotNull($reader->findTrace(2));

            $reader->clear();
            self::assertSame([], (new DevInspector(storageFile: $traceFile))->traces());
        } finally {
            @unlink($traceFile);
        }
    }

    public function testDevStudioBatch2FloatingHudTryRouteAndNondisclosingRuntime(): void
    {
        $inspector = new DevInspector();
        $secretRespToken = 'sk_' . 'live_1234567890abcdefghijklmnop';
        $app = App::create(__DIR__, name: 'Batch2 Studio')
            ->debug(true)
            ->routes(static function (Router $r) use ($secretRespToken): void {
                $r->get('/home', static fn(): Response => Response::html('<!doctype html><html><head><title>Home</title></head><body><h1>Hi</h1></body></html>'));
                $r->post('/api/echo', static function (Request $req) use ($secretRespToken): Response {
                    return Response::json(
                        [
                            'received' => $req->jsonBody(),
                            'hdr' => $req->getHeaderLine('X-Custom'),
                            'api_key' => 'secret_field_val',
                            'leak_guard' => $secretRespToken,
                        ],
                        201,
                        ['Set-Cookie' => 'kgn_sess=supersecret', 'X-Echo' => 'ok'],
                    );
                });
                $r->post('/api/signup-sample', static fn(HtmlSignupFormDto $dto): Response => Response::json(['email' => $dto->email], 201));
            });
        $app->middleware(new DevDashboardMiddleware($app, $inspector, devHud: true));

        $client = new TestClient($app);

        // 1. Floating Dev HUD injected cleanly into HTML (strict-CSP external CSS/JS, never JSON)
        $homeHtml = $client->get('/home')->assertOk()->body();
        self::assertStringContainsString('/_kongono/assets/dev-hud.css', $homeHtml);
        self::assertStringContainsString('/_kongono/assets/dev-hud.js', $homeHtml);
        self::assertStringContainsString('data-trace-id="1"', $homeHtml);
        self::assertStringContainsString('data-status="200"', $homeHtml);

        $hudCss = $client->get('/_kongono/assets/dev-hud.css')->assertOk();
        self::assertStringContainsString('text/css', $hudCss->header('Content-Type'));
        self::assertStringContainsString('.kgn-hud', $hudCss->body());

        $hudJs = $client->get('/_kongono/assets/dev-hud.js')->assertOk();
        self::assertStringContainsString('application/javascript', $hudJs->header('Content-Type'));
        self::assertStringContainsString('kgn-dev-hud', $hudJs->body());

        // 2. Studio bootstrap includes DTO sample template for /api/signup-sample & Runtime tab markup
        $studioHtml = $client->get('/_kongono')->assertOk()->body();
        self::assertStringContainsString('id="tab-runtime"', $studioHtml);
        self::assertStringContainsString('id="tryRouteDrawer"', $studioHtml);
        self::assertStringContainsString('dev@example.com', $studioHtml);

        // 3. Inline Try Route executes live route, scrubs secrets, records trace, and blocks recursion
        $tryRes = $client->postJson('/_kongono/api/try-route', [
            'method' => 'POST',
            'path' => '/api/echo',
            'headers' => ['X-Custom' => 'hello-studio'],
            'body' => '{"title":"Batch 2"}',
        ])->assertOk()->json();
        self::assertIsArray($tryRes);
        self::assertSame(201, $tryRes['status']);
        self::assertSame('ok', $tryRes['response_headers']['X-Echo'] ?? null);
        self::assertSame('[REDACTED]', $tryRes['response_headers']['Set-Cookie'] ?? null);
        self::assertStringContainsString('hello-studio', (string) $tryRes['response_body']);
        self::assertStringNotContainsString($secretRespToken, (string) $tryRes['response_body']);
        self::assertStringNotContainsString('secret_field_val', (string) $tryRes['response_body']);
        self::assertStringContainsString('[REDACTED]', (string) $tryRes['response_body']);
        self::assertIsInt($tryRes['trace_id']);

        $client->postJson('/_kongono/api/try-route', ['method' => 'GET', 'path' => '/_kongono/api/runtime'])->assertStatus(400);
        $client->postJson('/_kongono/api/try-route', ['method' => 'TRACE', 'path' => '/home'])->assertStatus(422);

        // 4. Runtime & Env endpoint is strictly nondisclosing (never leaks secret values)
        $fakeApiKey = 'sk-' . 'proj-NEVER_LEAK_THIS_OPENAI_SECRET_999';
        $prevEnv = $_ENV['OPENAI_API_KEY'] ?? null;
        $_ENV['OPENAI_API_KEY'] = $fakeApiKey;
        try {
            $rtResp = $client->get('/_kongono/api/runtime')->assertOk();
            self::assertStringNotContainsString($fakeApiKey, $rtResp->body());
            $rtJson = $rtResp->json();
            self::assertIsArray($rtJson);
            self::assertSame(PHP_VERSION, $rtJson['php']['version']);
            self::assertNotEmpty($rtJson['extensions']);
            self::assertNotEmpty($rtJson['subsystems']);
            $openaiEntry = array_values(array_filter(
                $rtJson['secret_env'],
                static fn(array $row): bool => 'OPENAI_API_KEY' === $row['key'],
            ));
            self::assertCount(1, $openaiEntry);
            self::assertTrue($openaiEntry[0]['configured']);
            self::assertArrayNotHasKey('value', $openaiEntry[0]);
        } finally {
            if (null === $prevEnv) {
                unset($_ENV['OPENAI_API_KEY']);
            } else {
                $_ENV['OPENAI_API_KEY'] = $prevEnv;
            }
        }

        // 5. Production fail-closed: debug=false disables HUD injection and returns 404 for all Batch 2 endpoints
        $app->debug(false);
        $prodHome = $client->get('/home')->assertOk()->body();
        self::assertStringNotContainsString('dev-hud.js', $prodHome);
        self::assertStringNotContainsString('dev-hud.css', $prodHome);
        $client->get('/_kongono/assets/dev-hud.css')->assertStatus(404);
        $client->get('/_kongono/assets/dev-hud.js')->assertStatus(404);
        $client->get('/_kongono/api/runtime')->assertStatus(404);
        $client->postJson('/_kongono/api/try-route', ['method' => 'GET', 'path' => '/home'])->assertStatus(404);
    }

    public function testTargetedCellSsePatchesAndLiveStreamReconnectResync(): void
    {
        // 1. SseEvent::patch emits surgical kgn:patch event with Last-Event-ID support
        $single = SseEvent::patch('#bid-1234', '$2,450', mode: 'text', id: 'evt-42');
        $wire = $single->format();
        self::assertStringContainsString("id: evt-42\n", $wire);
        self::assertStringContainsString("event: kgn:patch\n", $wire);
        self::assertStringContainsString('"target":"#bid-1234"', $wire);
        self::assertStringContainsString('"mode":"text"', $wire);
        self::assertStringContainsString('"content":"$2,450"', $wire);
        self::assertStringContainsString('"flash":true', $wire);

        // 2. SseEvent::batchPatches packs multiple targeted cell updates into one SSE frame
        $batch = SseEvent::batchPatches([
            ['target' => '#bid-1234', 'content' => '$2,500'],
            ['target' => '#status-1234', 'mode' => 'inner', 'content' => '<span class="z-badge">Hot</span>', 'flash' => false],
        ], id: 'evt-43');
        $batchWire = $batch->format();
        self::assertStringContainsString("id: evt-43\n", $batchWire);
        self::assertStringContainsString("event: kgn:patch\n", $batchWire);
        self::assertStringContainsString('#bid-1234', $batchWire);
        self::assertStringContainsString('#status-1234', $batchWire);

        // 3. Invalid patch mode or empty target fails closed
        try {
            SseEvent::patch('#bid-1', 'x', mode: 'eval');
            self::fail('Expected invalid SSE patch mode to throw.');
        } catch (\InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        // 4. LiveMiddleware script handles kgn:patch, kgn-patch-flash, and kgn-stream-resync-url reconnect sync
        $liveJs = LiveMiddleware::scriptSource();
        self::assertStringContainsString('es.addEventListener("kgn:patch"', $liveJs);
        self::assertStringContainsString('kgn-stream-resync-url', $liveJs);
        self::assertStringContainsString('kgn-patch-flash', $liveJs);
        self::assertStringContainsString('kongono:sse-patch', $liveJs);
        self::assertStringContainsString('kongono:stream-reconnected', $liveJs);
    }
}

final readonly class HtmlSignupFormDto
{
    public function __construct(
        #[Required, \Kongono\Validation\Attributes\Email]
        public string $email,
        #[Required, \Kongono\Validation\Attributes\Min(8)]
        public string $password,
    ) {}
}

final readonly class OpenApiLineItemDto
{
    public function __construct(
        #[Required]
        public string $sku,
        #[Required]
        public int $qty,
    ) {}
}

final readonly class OpenApiArrayOrderDto
{
    /** @param list<OpenApiLineItemDto> $items */
    public function __construct(
        #[Required, \Kongono\Validation\Attributes\ArrayOf(OpenApiLineItemDto::class)]
        public array $items,
        #[Required, \Kongono\Validation\Attributes\Date]
        public string $deliveryDate,
        #[Required, \Kongono\Validation\Attributes\Ip(version: 'v4')]
        public string $clientIp,
    ) {}
}

final readonly class RoutePrecedenceInputDto
{
    public function __construct(
        #[Required]
        public string $id,
        #[Required]
        public string $title,
    ) {}
}

final readonly class CachedDemoController
{
    /** @param \ArrayObject<string, int>|null $counter */
    public function __construct(
        private ?\ArrayObject $counter = null,
    ) {}

    #[Get('/cached-feed', name: 'cached.feed')]
    #[CacheControl(maxAge: 120, public: true, staleWhileRevalidate: 30)]
    public function feed(): Response
    {
        return Response::json(['items' => array_fill(0, 20, 'Kongono fast response')]);
    }

    #[Post('/orders', name: 'orders.create')]
    #[Idempotent(ttlSeconds: 3600)]
    public function createOrder(): Response
    {
        $n = 1;
        if (null !== $this->counter) {
            $n = ($this->counter['calls'] ?? 0) + 1;
            $this->counter['calls'] = $n;
        }

        return Response::json(['call_number' => $n], 201);
    }

    #[Get('/beta', name: 'beta.index')]
    #[Feature('beta_api')]
    public function beta(): Response
    {
        return Response::json(['beta' => true]);
    }
}
