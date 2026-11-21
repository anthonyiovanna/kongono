<?php

declare(strict_types=1);

namespace StarterApp\Tests;

use Kongono\Http\App;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Testing\TestClient;
use Kongono\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

final class StarterAppTest extends TestCase
{
    protected function setUp(): void
    {
        $varDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'var';
        $this->removeDir($varDir);
    }

    protected function tearDown(): void
    {
        $varDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'var';
        $this->removeDir($varDir);
    }

    private function bootApp(): App
    {
        /** @var App $app */
        $app = require dirname(__DIR__) . '/app/bootstrap.php';

        return $app;
    }

    public function testHomePageStaticCssSessionFlashAndMiddlewareHeaders(): void
    {
        $app = $this->bootApp();
        $res = $app->handle(Request::create('GET', '/'));

        self::assertSame(200, $res->getStatusCode());
        self::assertStringContainsString('Kongono v1 Starter', (string) $res->getBody());
        self::assertStringContainsString('Welcome to Kongono v1', (string) $res->getBody());
        self::assertStringContainsString('Kongono/1.0', $res->getHeaderLine('X-Powered-By'));
        self::assertNotSame('', $res->getHeaderLine('X-Response-Time-Ms'));
        self::assertSame('nosniff', $res->getHeaderLine('X-Content-Type-Options'));
        self::assertSame('DENY', $res->getHeaderLine('X-Frame-Options'));
        self::assertSame('120', $res->getHeaderLine('X-RateLimit-Limit'));

        // Static CSS served directly by StaticFilesMiddleware
        $css = $app->handle(Request::create('GET', '/assets/app.css'));
        self::assertSame(200, $css->getStatusCode());
        self::assertStringContainsString('text/css', $css->getHeaderLine('Content-Type'));

        // Server-Timing & Prometheus /metrics endpoint
        self::assertStringContainsString('app;dur=', $res->getHeaderLine('Server-Timing'));
        $metricsRes = $app->handle(Request::create('GET', '/metrics'));
        self::assertSame(200, $metricsRes->getStatusCode());
        self::assertStringContainsString('kongono_http_requests_total', (string) $metricsRes->getBody());
    }

    public function testNotesJsonCrudValidationStorageAndJobs(): void
    {
        $app = $this->bootApp();

        // Validation error on short title
        $invalid = $app->handle(Request::json('POST', '/api/notes', ['title' => 'Hi', 'body' => '']));
        self::assertSame(422, $invalid->getStatusCode());
        $errData = json_decode((string) $invalid->getBody(), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($errData);
        self::assertArrayHasKey('title', $errData['fields']);
        self::assertArrayHasKey('body', $errData['fields']);

        // Create valid note (also enqueues background job)
        $created = $app->handle(Request::json('POST', '/api/notes', ['title' => 'Ship v1', 'body' => 'Kongono is alive!']));
        self::assertSame(201, $created->getStatusCode());
        $note = json_decode((string) $created->getBody(), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($note);
        self::assertSame(3, $note['id']);
        self::assertSame('Ship v1', $note['title']);
        self::assertNotEmpty($note['queued_job_id']);

        // Process background job via Worker
        $worked = $app->handle(Request::create('POST', '/api/jobs/run', ['Accept' => 'application/json']));
        self::assertSame(200, $worked->getStatusCode());
        $workReport = json_decode((string) $worked->getBody(), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($workReport);
        self::assertSame(1, $workReport['completed']);

        // Fetch note
        $fetched = $app->handle(Request::create('GET', '/api/notes/3', ['Accept' => 'application/json']));
        self::assertSame(200, $fetched->getStatusCode());

        // Delete note
        $deleted = $app->handle(Request::create('DELETE', '/api/notes/3', ['Accept' => 'application/json']));
        self::assertSame(204, $deleted->getStatusCode());

        // Multipart upload stored atomically via LocalStorage & downloaded back
        $upload = $app->handle(Request::create(
            'POST',
            '/api/upload',
            headers: ['Accept' => 'application/json'],
            files: ['document' => UploadedFile::fromString('hello world', 'hello.txt', 'text/plain')],
        ));
        self::assertSame(201, $upload->getStatusCode());
        $meta = json_decode((string) $upload->getBody(), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($meta);
        self::assertSame('hello.txt', $meta['filename']);
        self::assertSame(11, $meta['size']);
        self::assertNotEmpty($meta['version']);

        $downloaded = $app->handle(Request::create('GET', '/api/files/hello.txt'));
        self::assertSame(200, $downloaded->getStatusCode());
        self::assertSame('hello world', (string) $downloaded->getBody());
        self::assertSame('"' . $meta['version'] . '"', $downloaded->getHeaderLine('ETag'));

        // Live ApplicationGraph endpoint
        $graphRes = $app->handle(Request::create('GET', '/api/graph'));
        self::assertSame(200, $graphRes->getStatusCode());
        self::assertStringContainsString('Kongono Starter App', (string) $graphRes->getBody());

        // Verify created notes persist across fresh app restarts via LocalStorage
        $created2 = $app->handle(Request::json('POST', '/api/notes', ['title' => 'Persistent Note', 'body' => 'Survives restart!']));
        self::assertSame(201, $created2->getStatusCode());
        $freshApp = $this->bootApp();
        $listAfterRestart = $freshApp->handle(Request::create('GET', '/api/notes', ['Accept' => 'application/json']));
        self::assertStringContainsString('Persistent Note', (string) $listAfterRestart->getBody());
    }

    public function testAuthFlowOpenApiAndSseEndToEnd(): void
    {
        $app = $this->bootApp();
        $client = new TestClient($app);

        // Unauthenticated /api/auth/me fails closed 401
        $client->get('/api/auth/me', ['Accept' => 'application/json'])->assertStatus(401);

        // Register creates user, sends compiled welcome email, returns signed bearer token
        $reg = $client->postJson('/api/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@kongono.dev',
            'password' => 'super-secure-pass-123',
        ])->assertCreated()->assertJsonPath('user.email', 'ada@kongono.dev');

        $token = $reg->json()['token'] ?? '';
        self::assertIsString($token);
        self::assertNotSame('', $token);

        // Login verifies Argon2id password and returns token
        $client->postJson('/api/auth/login', [
            'email' => 'ada@kongono.dev',
            'password' => 'super-secure-pass-123',
        ])->assertOk()->assertJsonPath('user.name', 'Ada Lovelace');

        // Authenticated /api/auth/me injects verified Principal
        $client->withBearerToken($token)
            ->get('/api/auth/me', ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('kind', 'human');

        // Live OpenAPI 3.1 spec auto-generated from PHP 8.5 controllers & DTOs (with #[CacheControl] + ETag/304 + gzip)
        $openApiRes = $client->get('/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertSee('RegisterInput')
            ->assertSee('CreateNoteInput');
        self::assertStringContainsString('max-age=300', $openApiRes->header('Cache-Control'));
        $etag = $openApiRes->header('ETag');
        self::assertNotSame('', $etag);
        $client->get('/openapi.json', ['If-None-Match' => $etag])->assertStatus(304);

        $gzipped = $app->handle(Request::create('GET', '/openapi.json', ['Accept-Encoding' => 'gzip']));
        self::assertSame('gzip', $gzipped->getHeaderLine('Content-Encoding'));
        self::assertIsString(gzdecode((string) $gzipped->getBody()));

        // Live Server-Sent Events stream
        $sse = $app->handle(Request::create('GET', '/api/events'));
        self::assertInstanceOf(Response::class, $sse);
        self::assertStringContainsString('text/event-stream', $sse->getHeaderLine('Content-Type'));
        self::assertStringContainsString('event: ready', $sse->bodyString());
    }

    public function testDocsHealthAndMcpEndpointsWorkEndToEnd(): void
    {
        $app = $this->bootApp();
        $client = new TestClient($app);

        // Zero-CDN Interactive /docs API Explorer
        $client->get('/docs')
            ->assertOk()
            ->assertSee('<!doctype html>')
            ->assertSee('Kongono Starter App')
            ->assertSee('/api/notes')
            ->assertSee('Send Request');

        // Structured /health readiness probe
        $client->get('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('healthy', true)
            ->assertJsonPath('checks.cache.ok', true)
            ->assertJsonPath('checks.storage.ok', true);

        // Live MCP JSON-RPC 2.0 endpoint over HTTP (POST /mcp)
        $init = $client->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [],
        ])->assertOk();
        self::assertSame('kongono-framework', $init->json()['result']['serverInfo']['name']);

        $list = $client->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
        ])->assertOk();
        $toolNames = array_column($list->json()['result']['tools'], 'name');
        self::assertContains('kongono_graph', $toolNames);
        self::assertContains('kongono_openapi', $toolNames);

        $call = $client->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => ['name' => 'kongono_graph', 'arguments' => []],
        ])->assertOk();
        self::assertFalse($call->json()['result']['isError']);
        self::assertStringContainsString('Kongono Starter App', $call->json()['result']['content'][0]['text']);

        // Built-in /_kongono Dev Studio (enabled in debug mode) captures recent requests
        $client->get('/_kongono')
            ->assertOk()
            ->assertSee('Kongono Dev Studio')
            ->assertSee('Kongono Starter App');
        $traces = $client->get('/_kongono/api/traces')->assertOk()->json();
        self::assertNotEmpty($traces);

        // Creating a note publishes onto EventBus and replays over /api/events SSE
        $client->postJson('/api/notes', ['title' => 'Live Event', 'body' => 'Streams via EventBus'])->assertCreated();
        $sse = $app->handle(Request::create('GET', '/api/events', ['Last-Event-ID' => '0']));
        self::assertInstanceOf(Response::class, $sse);
        self::assertStringContainsString('event: note.created', $sse->bodyString());
        self::assertStringContainsString('Live Event', $sse->bodyString());
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $entries = scandir($dir);
        if (false === $entries) {
            return;
        }
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
