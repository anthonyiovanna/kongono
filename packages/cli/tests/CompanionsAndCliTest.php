<?php

declare(strict_types=1);

namespace Kongono\Cli\Tests;

use Kongono\Ai\AiClient;
use Kongono\Ai\Provider\OpenAiProvider;
use Kongono\Ai\Transport\HttpResponse;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Cli\KongoCli;
use Kongono\Cli\Mcp\KongonoMcpFactory;
use PHPUnit\Framework\TestCase;

final class ScriptedTransport implements HttpTransportInterface
{
    /** @var list<HttpResponse> */
    private array $responses;

    public function __construct(HttpResponse ...$responses)
    {
        $this->responses = array_values($responses);
    }

    public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
    {
        return array_shift($this->responses) ?? new HttpResponse(500, '{}');
    }
}

final class CompanionsAndCliTest extends TestCase
{
    private string $starterAppRoot;

    protected function setUp(): void
    {
        $this->starterAppRoot = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'examples' . DIRECTORY_SEPARATOR . 'starter-app';
    }

    public function testRoutesGraphAndOfflineOshieLessonsWork(): void
    {
        $cli = new KongoCli(dirname(__DIR__, 3));

        [$code, $routesOut] = $cli->run(['routes', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $code);
        self::assertStringContainsString('/api/notes', $routesOut);
        self::assertStringContainsString('NoteController::apiCreate', $routesOut);

        [$gCode, $graphOut] = $cli->run(['graph', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $gCode);
        $decoded = json_decode($graphOut, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertSame('Kongono Starter App', $decoded['app']);

        [$lCode, $lessonsOut] = $cli->run(['oshie', '--lessons']);
        self::assertSame(0, $lCode);
        self::assertStringContainsString('routing', $lessonsOut);
        self::assertStringContainsString('views', $lessonsOut);
        self::assertStringContainsString('security', $lessonsOut);
        self::assertStringContainsString('storage', $lessonsOut);
        self::assertStringContainsString('jobs', $lessonsOut);

        [$wCode, $workOut] = $cli->run(['work', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $wCode);
        self::assertStringContainsString('Worker finished queue "default"', $workOut);

        [$sCode, $schedOut] = $cli->run(['schedule:run', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $sCode);
        self::assertStringContainsString('[OK] heartbeat', $schedOut);

        [$oaCode, $oaOut] = $cli->run(['openapi', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $oaCode);
        $oa = json_decode($oaOut, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($oa);
        self::assertSame('3.1.0', $oa['openapi']);
    }

    public function testKongoNewScaffoldsRunnableApp(): void
    {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-new-test-' . bin2hex(random_bytes(5));
        $cli = new KongoCli(dirname(__DIR__, 3));

        try {
            [$code, $out] = $cli->run(['new', $tempDir, '--name=Acme Cloud']);
            self::assertSame(0, $code);
            self::assertStringContainsString('Acme Cloud', $out);
            self::assertFileExists($tempDir . '/app/bootstrap.php');
            self::assertFileExists($tempDir . '/public/index.php');
            self::assertFileExists($tempDir . '/src/Http/HomeController.php');
            self::assertFileExists($tempDir . '/views/home.kgn.html');
            self::assertFileExists($tempDir . '/.env.example');
            self::assertFileExists($tempDir . '/public/worker.php');
            self::assertFileExists($tempDir . '/Caddyfile');
            self::assertFileExists($tempDir . '/Dockerfile');

            // Verify make:controller, make:dto, make:migration, and make:test in the freshly scaffolded app
            [$mcCode, $mcOut] = $cli->run(['make:controller', 'Post', '--app=' . $tempDir]);
            self::assertSame(0, $mcCode);
            self::assertStringContainsString('PostController.php', $mcOut);
            self::assertFileExists($tempDir . '/src/Http/PostController.php');

            [$mdCode] = $cli->run(['make:dto', 'CreatePostInput', '--app=' . $tempDir]);
            self::assertSame(0, $mdCode);
            self::assertFileExists($tempDir . '/src/Http/CreatePostInput.php');

            [$mmCode, $mmOut] = $cli->run(['make:migration', 'create_posts', '--app=' . $tempDir]);
            self::assertSame(0, $mmCode);
            self::assertStringContainsString('migrations/', $mmOut);

            [$mtCode, $mtOut] = $cli->run(['make:test', 'Home', '--app=' . $tempDir]);
            self::assertSame(0, $mtCode);
            self::assertStringContainsString('HomeTest.php', $mtOut);
            self::assertFileExists($tempDir . '/tests/HomeTest.php');

            // Verify key:generate and doctor
            [$kgCode, $kgOut] = $cli->run(['key:generate']);
            self::assertSame(0, $kgCode);
            self::assertStringStartsWith('APP_KEY=', $kgOut);

            [$docCode, $docOut] = $cli->run(['doctor', '--app=' . $this->starterAppRoot]);
            self::assertSame(0, $docCode);
            self::assertStringContainsString('PHP version:', $docOut);
            self::assertStringContainsString('ext-sodium', $docOut);
            self::assertStringContainsString('Kongono Starter App', $docOut);
        } finally {
            $this->removeDir($tempDir);
        }
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

    public function testOshieChieAndHanteiWorkWithLiveAiClient(): void
    {
        $ai = new AiClient(new OpenAiProvider('sk-test', transport: new ScriptedTransport(
            // 1. Oshie Q&A reply
            new HttpResponse(200, json_encode([
                'model' => 'gpt-4.1-mini',
                'choices' => [['message' => ['content' => 'Add a PSR-15 middleware via $app->middleware(...) in app/bootstrap.php.']]],
            ], JSON_THROW_ON_ERROR)),
            // 2. Chie builder reply (preview mode)
            new HttpResponse(200, json_encode([
                'model' => 'gpt-4.1-mini',
                'choices' => [['message' => ['content' => json_encode([
                    'summary' => 'Created HealthController',
                    'changes' => [[
                        'path' => 'src/Http/HealthController.php',
                        'action' => 'create',
                        'explanation' => 'Adds /health JSON endpoint',
                        'content' => "<?php\n\ndeclare(strict_types=1);\n",
                    ]],
                    'next_steps' => ['Register HealthController in app/bootstrap.php'],
                ], JSON_THROW_ON_ERROR)]]],
            ], JSON_THROW_ON_ERROR)),
            // 3. Hantei debug reply
            new HttpResponse(200, json_encode([
                'model' => 'gpt-4.1-mini',
                'choices' => [['message' => ['content' => 'Root cause: KGN-VALIDATION-422 triggered because title was under 3 chars.']]],
            ], JSON_THROW_ON_ERROR)),
        )));

        $cli = new KongoCli(dirname(__DIR__, 3), $ai);

        [$oCode, $oshieOut] = $cli->run(['oshie', 'How do I add middleware?', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $oCode);
        self::assertStringContainsString('middleware', $oshieOut);

        [$cCode, $chieOut] = $cli->run(['chie', 'Add a /health endpoint', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $cCode);
        self::assertStringContainsString('[PREVIEW]', $chieOut);
        self::assertStringContainsString('+++ b/src/Http/HealthController.php', $chieOut);
        self::assertStringContainsString('HealthController.php', $chieOut);

        [$hCode, $hanteiOut] = $cli->run(['hantei', 'debug', 'KGN-VALIDATION-422 on POST /api/notes', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $hCode);
        self::assertStringContainsString('Root cause', $hanteiOut);
    }

    public function testKongonoMcpFactoryExposesGovernedProjectTools(): void
    {
        $server = KongonoMcpFactory::forApp($this->starterAppRoot);

        $list = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":1,"method":"tools/list"}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($list);
        $names = array_column($list['result']['tools'], 'name');
        self::assertSame(KongonoMcpFactory::DEFAULT_TOOLS, $names);

        $graph = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"kongono_graph"}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($graph);
        self::assertFalse($graph['result']['isError']);
        self::assertStringContainsString('Kongono Starter App', $graph['result']['content'][0]['text']);

        $openapi = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"kongono_openapi"}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($openapi);
        self::assertFalse($openapi['result']['isError']);
        self::assertStringContainsString('"3.1.0"', $openapi['result']['content'][0]['text']);

        $lesson = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":4,"method":"tools/call","params":{"name":"kongono_oshie_lesson","arguments":{"topic":"routing"}}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($lesson);
        self::assertFalse($lesson['result']['isError']);
        self::assertStringContainsString('Routing & Controllers', $lesson['result']['content'][0]['text']);

        // Reading .env is explicitly blocked by kongono_read_file
        $envBlocked = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":5,"method":"tools/call","params":{"name":"kongono_read_file","arguments":{"path":".env"}}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($envBlocked);
        self::assertTrue($envBlocked['result']['isError']);
        self::assertStringContainsString('forbidden', $envBlocked['result']['content'][0]['text']);

        // Reading regular source file succeeds
        $readOk = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":6,"method":"tools/call","params":{"name":"kongono_read_file","arguments":{"path":"src/Http/NoteController.php"}}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($readOk);
        self::assertFalse($readOk['result']['isError']);
        self::assertStringContainsString('final readonly class NoteController', $readOk['result']['content'][0]['text']);
    }
}
