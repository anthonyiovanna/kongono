<?php

declare(strict_types=1);

namespace Kongono\Http\Tests;

use Kongono\Core\Cache\MemoryCache;
use Kongono\Http\App;
use Kongono\Http\Cookie;
use Kongono\Http\Middleware\StaticFilesMiddleware;
use Kongono\Http\OpenApi\OpenApiGenerator;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Post;
use Kongono\Http\Routing\Router;
use Kongono\Http\Session\CacheSessionStore;
use Kongono\Http\Session\Session;
use Kongono\Http\Session\SessionMiddleware;
use Kongono\Http\SseEvent;
use Kongono\Http\Testing\TestClient;
use Kongono\Http\UploadedFile;
use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\Required;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

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
    }
}
