<?php

declare(strict_types=1);

namespace Kongono\Cli\Tests;

use Closure;
use Kongono\Ai\AiClient;
use Kongono\Ai\Provider\OpenAiProvider;
use Kongono\Ai\TokenUsage;
use Kongono\Ai\Transport\HttpResponse;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Ai\Transport\StreamingHttpTransportInterface;
use Kongono\Cli\AppScaffolder;
use Kongono\Cli\Audit\SecurityAuditor;
use Kongono\Cli\CodeGenerator;
use Kongono\Cli\Companions\ChieBuilder;
use Kongono\Cli\Companions\CompanionEvalSuite;
use Kongono\Cli\Companions\CompanionSessionStore;
use Kongono\Cli\Companions\CompanionTelemetry;
use Kongono\Cli\Companions\DevStudioCompanions;
use Kongono\Cli\Companions\HanteiDebugger;
use Kongono\Cli\Companions\OshieTutor;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Database\MigrationLinter;
use Kongono\Cli\Deploy\DeployBlueprintGenerator;
use Kongono\Cli\KongoCli;
use Kongono\Cli\Mcp\KongonoMcpFactory;
use Kongono\Cli\Sdk\SdkGenerator;
use Kongono\Cli\Skills\SkillsInstaller;
use Kongono\Core\CoreException;
use Kongono\Http\App;
use Kongono\Http\Dev\DevInspector;
use Kongono\Http\Testing\TestClient;
use PHPUnit\Framework\TestCase;

final class ScriptedTransport implements HttpTransportInterface
{
    /** @var list<string> */
    public array $requests = [];

    /** @var list<HttpResponse> */
    private array $responses;

    public function __construct(HttpResponse ...$responses)
    {
        $this->responses = array_values($responses);
    }

    public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
    {
        $this->requests[] = $jsonBody;

        return array_shift($this->responses) ?? new HttpResponse(500, '{}');
    }
}

final class ScriptedStreamingTransport implements StreamingHttpTransportInterface
{
    /** @var list<string> */
    public array $requests = [];

    /** @var list<list<string>> */
    private array $streams;

    /** @param list<string> ...$streams */
    public function __construct(array ...$streams)
    {
        $this->streams = array_values($streams);
    }

    public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
    {
        $this->requests[] = $jsonBody;

        return new HttpResponse(200, json_encode([
            'model' => 'gpt-4.1-mini',
            'choices' => [['message' => ['role' => 'assistant', 'content' => 'Fallback non-stream reply.']]],
            'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 10],
        ], JSON_THROW_ON_ERROR));
    }

    public function postJsonStream(string $url, array $headers, string $jsonBody, Closure $onChunk, int $timeoutSeconds = 120): int
    {
        $this->requests[] = $jsonBody;
        $chunks = array_shift($this->streams) ?? [];
        foreach ($chunks as $chunk) {
            $onChunk($chunk);
        }

        return 200;
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
            self::assertFileExists($tempDir . '/AGENTS.md');
            self::assertFileExists($tempDir . '/CLAUDE.md');
            self::assertFileExists($tempDir . '/.mcp.json');

            $agentsMd = (string) file_get_contents($tempDir . '/AGENTS.md');
            self::assertStringContainsString('Acme Cloud', $agentsMd);
            self::assertStringContainsString('https://anthonyiovanna.github.io/kongono/llms.txt', $agentsMd);
            self::assertStringContainsString('declare(strict_types=1);', $agentsMd);
            self::assertStringContainsString('@AGENTS.md', (string) file_get_contents($tempDir . '/CLAUDE.md'));
            self::assertStringContainsString('GROQ_API_KEY', (string) file_get_contents($tempDir . '/.env.example'));

            /** @var array{mcpServers: array{kongono: array{command: string, args: list<string>}}} $mcpConfig */
            $mcpConfig = json_decode((string) file_get_contents($tempDir . '/.mcp.json'), true, 16, JSON_THROW_ON_ERROR);
            self::assertSame('php', $mcpConfig['mcpServers']['kongono']['command']);
            self::assertSame(['vendor/bin/kongo', 'mcp'], $mcpConfig['mcpServers']['kongono']['args']);

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

            [$msCode, $msOut] = $cli->run(['make:seeder', 'Users', '--app=' . $tempDir]);
            self::assertSame(0, $msCode);
            self::assertStringContainsString('UsersSeeder.php', $msOut);
            self::assertFileExists($tempDir . '/seeders/UsersSeeder.php');

            // Verify key:generate, doctor, and optimize
            [$kgCode, $kgOut] = $cli->run(['key:generate']);
            self::assertSame(0, $kgCode);
            self::assertStringStartsWith('APP_KEY=', $kgOut);

            [$docCode, $docOut] = $cli->run(['doctor', '--app=' . $this->starterAppRoot]);
            self::assertSame(0, $docCode);
            self::assertStringContainsString('PHP version:', $docOut);
            self::assertStringContainsString('ext-sodium', $docOut);
            self::assertStringContainsString('Kongono Starter App', $docOut);

            [$optCode, $optOut] = $cli->run(['optimize', '--app=' . $this->starterAppRoot]);
            self::assertSame(0, $optCode);
            self::assertStringContainsString('Precompiled', $optOut);
            self::assertStringContainsString('routes.compiled.php', $optOut);
            self::assertFileExists($this->starterAppRoot . '/var/cache/routes.compiled.php');
            self::assertFileExists($this->starterAppRoot . '/var/cache/graph.json');
            self::assertFileExists($this->starterAppRoot . '/var/cache/openapi.json');

            [$ccCode, $ccOut] = $cli->run(['cache:clear', '--app=' . $this->starterAppRoot]);
            self::assertSame(0, $ccCode);
            self::assertStringContainsString('Cleared', $ccOut);
            self::assertFileDoesNotExist($this->starterAppRoot . '/var/cache/graph.json');
            $this->removeDir($this->starterAppRoot . '/var');

            [$rjCode, $rjOut] = $cli->run(['routes', '--json', '--app=' . $this->starterAppRoot]);
            self::assertSame(0, $rjCode);
            $routeList = json_decode($rjOut, true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($routeList);
            self::assertNotEmpty($routeList);

            // Verify env:check fails when .env is missing keys from .env.example and passes once populated
            [$ecFail, $ecOutFail] = $cli->run(['env:check', '--app=' . $tempDir]);
            self::assertSame(1, $ecFail);
            self::assertStringContainsString('[MISS] APP_KEY', $ecOutFail);

            copy($tempDir . '/.env.example', $tempDir . '/.env');
            [$ecOk, $ecOutOk] = $cli->run(['env:check', '--app=' . $tempDir]);
            self::assertSame(0, $ecOk);
            self::assertStringContainsString('[OK  ] APP_KEY', $ecOutOk);

            // Verify version and single-shot tinker --eval against starter app
            [$vCode, $vOut] = $cli->run(['version']);
            self::assertSame(0, $vCode);
            self::assertStringContainsString('v1.20.3', $vOut);

            // Verify kongo audit (both on starter-app with live routes and scaffolded app) & kongo deploy:init
            [$auditCode, $auditOut] = $cli->run(['audit', '--json', '--app=' . $this->starterAppRoot]);
            self::assertSame(0, $auditCode);
            $auditJson = json_decode($auditOut, true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($auditJson);
            self::assertTrue($auditJson['passed']);
            self::assertSame(0, $auditJson['symfony_packages']);
            self::assertGreaterThan(10, $auditJson['routes_scanned']);

            [$depCode, $depOut] = $cli->run(['deploy:init', '--app=' . $tempDir, '--domain=demo.kongono.dev', '--postgres', '--redis', '--worker', '--force']);
            self::assertSame(0, $depCode);
            self::assertStringContainsString('[WRITE] Dockerfile', $depOut);
            self::assertFileExists($tempDir . '/Dockerfile');
            self::assertFileExists($tempDir . '/Caddyfile');
            self::assertFileExists($tempDir . '/docker-compose.prod.yml');
            self::assertFileExists($tempDir . '/deploy/provision.sh');
            self::assertFileExists($tempDir . '/deploy/deploy.sh');

            // Shell / Caddyfile injection via --domain or --branch MUST fail closed
            [$badDepCode, $badDepOut] = $cli->run(['deploy:init', '--app=' . $tempDir, '--domain=evil.com;rm -rf /', '--force']);
            self::assertSame(1, $badDepCode);
            self::assertStringContainsString('Unsafe deployment domain', $badDepOut);
            [$badBranchCode, $badBranchOut] = $cli->run(['deploy:init', '--app=' . $tempDir, '--domain=demo.kongono.dev', '--branch=main$(id)', '--force']);
            self::assertSame(1, $badBranchCode);
            self::assertStringContainsString('Unsafe Git branch', $badBranchOut);

            [$tkCode, $tkOut] = $cli->run(['tinker', '--app=' . $this->starterAppRoot, '--eval=$graph->appName']);
            self::assertSame(0, $tkCode);
            self::assertSame("Kongono Starter App\n", $tkOut);

            [$tkCountCode, $tkCountOut] = $cli->run(['tinker', '--app=' . $this->starterAppRoot, '--eval=count($graph->routes)']);
            self::assertSame(0, $tkCountCode);
            self::assertGreaterThan(10, (int) trim($tkCountOut));
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

        // Reading .env and private key / .git files is explicitly blocked by kongono_read_file
        foreach (['.env', 'certs/server.pem', 'keys/id_rsa', '.git/config'] as $idx => $blockedPath) {
            $blockedRes = json_decode((string) $server->handleJson(json_encode([
                'jsonrpc' => '2.0',
                'id' => 50 + $idx,
                'method' => 'tools/call',
                'params' => ['name' => 'kongono_read_file', 'arguments' => ['path' => $blockedPath]],
            ], JSON_THROW_ON_ERROR)), true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($blockedRes);
            self::assertTrue($blockedRes['result']['isError'], 'Expected MCP read block for ' . $blockedPath);
            self::assertStringContainsString('forbidden', $blockedRes['result']['content'][0]['text']);
        }

        // Reading regular source file succeeds
        $readOk = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":6,"method":"tools/call","params":{"name":"kongono_read_file","arguments":{"path":"src/Http/NoteController.php"}}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($readOk);
        self::assertFalse($readOk['result']['isError']);
        self::assertStringContainsString('final readonly class NoteController', $readOk['result']['content'][0]['text']);

        // v1.2.0 MCP tools: kongono_audit, kongono_test_route, kongono_deploy_preview
        $auditMcp = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":7,"method":"tools/call","params":{"name":"kongono_audit"}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($auditMcp);
        self::assertFalse($auditMcp['result']['isError']);
        self::assertStringContainsString('"symfony_packages": 0', $auditMcp['result']['content'][0]['text']);

        $routeTest = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":8,"method":"tools/call","params":{"name":"kongono_test_route","arguments":{"method":"GET","path":"/health"}}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($routeTest);
        self::assertFalse($routeTest['result']['isError']);
        self::assertStringContainsString('"status": 200', $routeTest['result']['content'][0]['text']);
        self::assertStringContainsString('"active_ops_after": 0', $routeTest['result']['content'][0]['text']);

        $deployPrev = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":9,"method":"tools/call","params":{"name":"kongono_deploy_preview","arguments":{"domain":"prod.kongono.dev"}}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($deployPrev);
        self::assertFalse($deployPrev['result']['isError']);
        self::assertStringContainsString('prod.kongono.dev', $deployPrev['result']['content'][0]['text']);

        // v1.3.0 MCP tool: kongono_sdk_generate
        $sdkMcp = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":10,"method":"tools/call","params":{"name":"kongono_sdk_generate","arguments":{"language":"swift","client_name":"StarterApi"}}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($sdkMcp);
        self::assertFalse($sdkMcp['result']['isError']);
        self::assertStringContainsString('public final class StarterApi: Sendable', $sdkMcp['result']['content'][0]['text']);
        self::assertStringContainsString('public struct CreateNoteInput: Codable, Sendable', $sdkMcp['result']['content'][0]['text']);

        // v1.4.0 MCP tools: kongono_eval & kongono_migrate_lint
        $evalMcp = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":11,"method":"tools/call","params":{"name":"kongono_eval"}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($evalMcp);
        self::assertFalse($evalMcp['result']['isError']);
        self::assertStringContainsString('"pass_rate": 100', $evalMcp['result']['content'][0]['text']);

        $lintMcp = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":12,"method":"tools/call","params":{"name":"kongono_migrate_lint"}}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($lintMcp);
        self::assertFalse($lintMcp['result']['isError']);
        self::assertStringContainsString('"passed": true', $lintMcp['result']['content'][0]['text']);
    }

    public function testSdkGenerateOutputsTypedSdksForAllFourTargets(): void
    {
        $cli = new KongoCli(dirname(__DIR__, 3));

        // TypeScript
        [$tsCode, $tsOut] = $cli->run(['sdk:generate', '--lang=ts', '--name=NotesClient', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $tsCode);
        self::assertStringContainsString('export interface CreateNoteInput {', $tsOut);
        self::assertStringContainsString('export class NotesClient {', $tsOut);
        self::assertStringContainsString('Idempotency-Key', $tsOut);
        self::assertStringContainsString('body: CreateNoteInput', $tsOut);

        // Swift (iOS/macOS)
        [$swCode, $swOut] = $cli->run(['sdk:generate', '--lang=swift', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $swCode);
        self::assertStringContainsString('public struct CreateNoteInput: Codable, Sendable', $swOut);
        self::assertStringContainsString('async throws -> Data', $swOut);

        // Kotlin (Android)
        [$ktCode, $ktOut] = $cli->run(['sdk:generate', '--lang=kotlin', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $ktCode);
        self::assertStringContainsString('data class CreateNoteInput(', $ktOut);
        self::assertStringContainsString('class KongonoClient(', $ktOut);
        self::assertStringContainsString('fun toJson(): String', $ktOut);

        // Dart (Flutter) + --out write
        $outFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_sdk_' . bin2hex(random_bytes(4)) . '.dart';
        try {
            [$dtCode, $dtOut] = $cli->run(['sdk:generate', '--lang=dart', '--out=' . $outFile, '--app=' . $this->starterAppRoot]);
            self::assertSame(0, $dtCode);
            self::assertStringContainsString('Generated DART SDK', $dtOut);
            self::assertFileExists($outFile);
            $dartCode = (string) file_get_contents($outFile);
            self::assertStringContainsString('final class CreateNoteInput {', $dartCode);
            self::assertStringContainsString('Map<String, dynamic> toJson()', $dartCode);
            self::assertStringContainsString('final class KongonoClient {', $dartCode);
        } finally {
            @unlink($outFile);
        }

        // Unsupported language fails closed
        [$badCode, $badOut] = $cli->run(['sdk:generate', '--lang=cobol', '--app=' . $this->starterAppRoot]);
        self::assertSame(1, $badCode);
        self::assertStringContainsString('Unsupported SDK language', $badOut);
    }

    public function testAuditAttackModeRunsInProcessRedTeamProbes(): void
    {
        $cli = new KongoCli(dirname(__DIR__, 3));

        [$code, $out] = $cli->run(['audit', '--attack', '--strict', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $code);
        self::assertStringContainsString('Score: 100/100 (PASS)', $out);
        self::assertStringContainsString('Red-Team Attack Simulation:', $out);
        self::assertStringContainsString('ssrf_egress_defense', $out);
        self::assertStringContainsString('prompt_guard_canary', $out);
        self::assertStringContainsString('path_traversal_env_exposure', $out);
        self::assertStringContainsString('security_headers_baseline', $out);
        self::assertStringContainsString('error_nondisclosure', $out);

        [$jCode, $jsonOut] = $cli->run(['audit', '--attack', '--json', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $jCode);
        $decoded = json_decode($jsonOut, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertTrue($decoded['passed']);
        self::assertCount(5, $decoded['attack_probes']);
    }

    public function testMigrateLintCatchesZeroDowntimeHazardsAndPassesCleanMigrations(): void
    {
        $cli = new KongoCli(dirname(__DIR__, 3));
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-mig-lint-' . bin2hex(random_bytes(4));
        mkdir($tmp . DIRECTORY_SEPARATOR . 'migrations', 0o777, true);

        try {
            // Safe migration (down() DROP TABLE must not trigger false positive!)
            file_put_contents($tmp . '/migrations/001_safe.php', <<<'PHP'
<?php
// up
$db->execute('CREATE TABLE items (id BIGSERIAL PRIMARY KEY, name TEXT NOT NULL DEFAULT \'\')');
$db->execute('CREATE INDEX CONCURRENTLY idx_items_name ON items (name)');
function down($db): void { $db->execute('DROP TABLE items'); }
PHP);

            [$okCode, $okOut] = $cli->run(['migrate:lint', '--app=' . $tmp]);
            self::assertSame(0, $okCode);
            self::assertStringContainsString('All migrations passed zero-downtime safety checks', $okOut);

            // Hazardous migration: locking index + ADD COLUMN NOT NULL without DEFAULT + DROP COLUMN
            file_put_contents($tmp . '/migrations/002_unsafe.php', <<<'PHP'
<?php
$db->execute('ALTER TABLE items ADD COLUMN tenant_id BIGINT NOT NULL;');
$db->execute('CREATE INDEX idx_items_tenant ON items (tenant_id);');
$db->execute('ALTER TABLE items DROP COLUMN legacy_code;');
PHP);

            [$badCode, $badJson] = $cli->run(['migrate:lint', '--json', '--app=' . $tmp]);
            self::assertSame(1, $badCode);
            $report = json_decode($badJson, true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($report);
            self::assertFalse($report['passed']);
            self::assertSame(1, $report['counts']['critical']);
            self::assertSame(2, $report['counts']['warning']);
            $codes = array_column($report['findings'], 'code');
            self::assertContains('KGN-MIG-NOT-NULL-NO-DEFAULT', $codes);
            self::assertContains('KGN-MIG-LOCKING-INDEX', $codes);
            self::assertContains('KGN-MIG-DROP-COLUMN', $codes);
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testKongoEvalRunsRedTeamBatteryAndCustomEvalSuite(): void
    {
        $cli = new KongoCli(dirname(__DIR__, 3));

        // Offline red-team battery runs out of the box with zero API keys
        [$code, $out] = $cli->run(['eval', '--redteam', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $code);
        self::assertStringContainsString('12/12 passed (100.0%)', $out);
        self::assertStringContainsString('xml_untrusted_breakout', $out);

        [$jCode, $json] = $cli->run(['eval', '--redteam', '--json', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $jCode);
        $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertTrue($decoded['passed']);
        self::assertSame(12, $decoded['total']);

        // Custom evals/suite.php with scripted AI client
        $ai = new AiClient(new OpenAiProvider('sk-test', transport: new ScriptedTransport(
            new HttpResponse(200, json_encode([
                'model' => 'gpt-4.1-mini',
                'choices' => [['message' => ['content' => 'Kongono is built for PHP 8.5+']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 8],
            ], JSON_THROW_ON_ERROR)),
        )));
        $aiCli = new KongoCli(dirname(__DIR__, 3), $ai);
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-eval-' . bin2hex(random_bytes(4));
        mkdir($tmp . DIRECTORY_SEPARATOR . 'evals', 0o777, true);
        file_put_contents($tmp . '/evals/suite.php', <<<'PHP'
<?php
declare(strict_types=1);
use Kongono\Ai\Eval\EvalCase;
return [
    new EvalCase(name: 'smoke_eval', prompt: 'What PHP version?', expectedSubstrings: ['PHP 8.5'], maxTokens: 50),
];
PHP);

        try {
            [$cCode, $cOut] = $aiCli->run(['eval', '--app=' . $tmp]);
            self::assertSame(0, $cCode);
            self::assertStringContainsString('1/1 passed (100.0%)', $cOut);
            self::assertStringContainsString('smoke_eval', $cOut);
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testRound3CliScaffolderProjectContextMcpAuditorDeployLinterAndSdkHardening(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-cli-r3-' . bin2hex(random_bytes(5));
        mkdir($tmp, 0o777, true);

        try {
            // 1. AppScaffolder escapes backslash-quote injection in app name and creates .dockerignore
            $appDir = $tmp . DIRECTORY_SEPARATOR . 'scaffolded';
            (new AppScaffolder())->scaffold($appDir, "MyApp\\';system('id');//");
            self::assertFileExists($appDir . DIRECTORY_SEPARATOR . '.dockerignore');
            $appPhp = (string) file_get_contents($appDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php');
            // Verify PHP tokenizer finds zero injected `system` identifier tokens in generated bootstrap.php
            $systemCalls = array_filter(\PhpToken::tokenize($appPhp), static fn(\PhpToken $t): bool => T_STRING === $t->id && 'system' === strtolower($t->text));
            self::assertSame([], $systemCalls);
            $envExample = (string) file_get_contents($appDir . DIRECTORY_SEPARATOR . '.env.example');
            self::assertStringContainsString('APP_KEY=REPLACE_WITH_kongo_key_generate', $envExample);

            // 2. CodeGenerator rejects injected PHP namespace strings
            $ctx = ProjectContext::discover($appDir);
            try {
                (new CodeGenerator())->makeController($ctx, 'Order', "App\\Http; system('id'); //");
                self::fail('Expected invalid PHP namespace to fail.');
            } catch (CoreException $e) {
                self::assertStringContainsString('Unsafe PHP namespace', $e->getMessage());
            }

            // 3. ProjectContext blocks reading secret files & writing into .git / .env / vendor
            file_put_contents($appDir . DIRECTORY_SEPARATOR . 'auth.json', '{"token":"secret"}');
            try {
                $ctx->readFileSafe('auth.json');
                self::fail('Expected ProjectContext::readFileSafe to block auth.json.');
            } catch (CoreException $e) {
                self::assertStringContainsString('forbidden', $e->getMessage());
            }
            try {
                $ctx->writeFileContained('.git/hooks/pre-commit', '#!/bin/sh');
                self::fail('Expected ProjectContext::writeFileContained to block .git/hooks.');
            } catch (CoreException $e) {
                self::assertStringContainsString('forbidden', $e->getMessage());
            }

            // 4. HanteiDebugger refuses non-PHP file extensions for test generation
            $hantei = new HanteiDebugger(
                new AiClient(new OpenAiProvider('sk-test', transport: new ScriptedTransport())),
            );
            try {
                $hantei->generateTests('composer.json', $ctx);
                self::fail('Expected HanteiDebugger::generateTests to reject non-PHP path.');
            } catch (CoreException $e) {
                self::assertStringContainsString('.php', $e->getMessage());
            }

            // 5. KongonoMcpFactory blocks /mcp/ recursion and mutating POST in kongono_test_route
            $mcp = KongonoMcpFactory::forApp($this->starterAppRoot);
            $mcpLoop = json_decode((string) $mcp->handleJson('{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"kongono_test_route","arguments":{"method":"GET","path":"//mcp/"}}}'), true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($mcpLoop);
            self::assertTrue($mcpLoop['result']['isError']);
            $mcpPost = json_decode((string) $mcp->handleJson('{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"kongono_test_route","arguments":{"method":"POST","path":"/notes"}}}'), true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($mcpPost);
            self::assertSame(-32602, $mcpPost['error']['code'] ?? null);

            // 6. SecurityAuditor catches templates containing 2+ unescaped {!! ... !!} tags & weak APP_KEY
            if (!is_dir($appDir . DIRECTORY_SEPARATOR . 'views')) {
                mkdir($appDir . DIRECTORY_SEPARATOR . 'views', 0o777, true);
            }
            file_put_contents($appDir . '/views/unsafe.kgn.html', '{!! $a !!} {!! $b !!}');
            file_put_contents($appDir . '/.env', "APP_ENV=production\nAPP_KEY=base64:CHANGE_ME # comment\n");
            $auditReport = (new SecurityAuditor())->audit($appDir);
            $auditCodes = array_column($auditReport['findings'], 'code');
            self::assertContains('KGN-AUDIT-RAW-HTML', $auditCodes);
            self::assertContains('KGN-AUDIT-WEAK-KEY', $auditCodes);

            // 7. DeployBlueprintGenerator blocks leading-hyphen git branch injection & generates .dockerignore
            $deployGen = new DeployBlueprintGenerator();
            $blueprints = $deployGen->generate('demo', 'demo.kongono.dev');
            self::assertArrayHasKey('.dockerignore', $blueprints);
            self::assertStringContainsString('POSTGRES_PASSWORD:?', $blueprints['docker-compose.prod.yml']);
            try {
                $deployGen->generate('demo', 'demo.kongono.dev', gitBranch: '--upload-pack=id');
                self::fail('Expected leading-hyphen git branch to be rejected.');
            } catch (\InvalidArgumentException $e) {
                self::assertStringContainsString('Unsafe Git branch', $e->getMessage());
            }

            // 8. MigrationLinter catches hazards in up() even when down() precedes up()
            mkdir($tmp . '/migs', 0o777, true);
            file_put_contents($tmp . '/migs/001_order.php', <<<'PHP'
<?php
// function down($db) {} comment must not disable linting
class M {
    public function down($db): void { $db->execute('DROP TABLE safe_rollback'); }
    public function up($db): void { $db->execute('TRUNCATE TABLE users;'); }
}
PHP);
            $migReport = (new MigrationLinter())->lintDirectory($tmp . '/migs');
            self::assertFalse($migReport['passed']);
            self::assertSame(['KGN-MIG-TRUNCATE'], array_column($migReport['findings'], 'code'));

            // 9. SdkGenerator escapes hostile paths/descriptions and emits Swift encodePart() + Kotlin escapeJsonString()
            $sdkSpec = [
                'info' => ['title' => "Demo */ system('id'); /*", 'version' => '1.0.0'],
                'components' => [
                    'schemas' => [
                        'NoteInput' => [
                            'type' => 'object',
                            'required' => ['title'],
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                        ],
                    ],
                ],
                'paths' => [
                    '/notes/{id}' => [
                        'get' => ['operationId' => 'notes.show', 'summary' => "Show\n*/ alert(1) /*"],
                    ],
                ],
            ];
            $sdkGen = new SdkGenerator();
            $tsSdk = $sdkGen->generateFromSpec($sdkSpec, 'ts');
            self::assertStringNotContainsString("*/ system('id')", $tsSdk);
            $swiftSdk = $sdkGen->generateFromSpec($sdkSpec, 'swift');
            self::assertStringContainsString('encodePart(', $swiftSdk);
            $kotlinSdk = $sdkGen->generateFromSpec($sdkSpec, 'kotlin');
            self::assertStringContainsString('escapeJsonString(', $kotlinSdk);
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testRound4ProjectContextChiePromptGuardMigrationLinterAndScaffolderDefaults(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-cli-r4-' . bin2hex(random_bytes(5));
        mkdir($tmp, 0o777, true);

        try {
            // 1. AppScaffolder fails closed with APP_DEBUG=false in bootstrap.php, .env.example, and Dockerfile
            $appDir = $tmp . DIRECTORY_SEPARATOR . 'app';
            (new AppScaffolder())->scaffold($appDir, 'SecureApp');
            $bootstrap = (string) file_get_contents($appDir . '/app/bootstrap.php');
            $envExample = (string) file_get_contents($appDir . '/.env.example');
            $dockerfile = (string) file_get_contents($appDir . '/Dockerfile');
            self::assertStringContainsString("Config::env('APP_DEBUG') ?? 'false'", $bootstrap);
            self::assertStringContainsString('APP_DEBUG=false', $envExample);
            self::assertStringContainsString('APP_DEBUG=false', $dockerfile);

            // 2. ProjectContext blocks reading root .docker/.aws/.ssh secrets and writing composer.json/.github/Dockerfile
            mkdir($appDir . '/.docker', 0o777, true);
            mkdir($appDir . '/.aws', 0o777, true);
            mkdir($appDir . '/.ssh', 0o777, true);
            file_put_contents($appDir . '/.docker/config.json', '{"auths":{}}');
            file_put_contents($appDir . '/.aws/credentials', '[default]');
            file_put_contents($appDir . '/.ssh/id_rsa', 'PRIVATE');

            $ctx = ProjectContext::discover($appDir);
            foreach (['.docker/config.json', '.aws/credentials', '.ssh/id_rsa'] as $secretPath) {
                try {
                    $ctx->readFileSafe($secretPath);
                    self::fail('Expected ProjectContext::readFileSafe to block ' . $secretPath);
                } catch (CoreException $e) {
                    self::assertStringContainsString('forbidden', $e->getMessage());
                }
            }
            foreach (['composer.json', '.github/workflows/ci.yml', 'Dockerfile'] as $writeBlocked) {
                try {
                    $ctx->writeFileContained($writeBlocked, 'pwned');
                    self::fail('Expected ProjectContext::writeFileContained to block ' . $writeBlocked);
                } catch (CoreException $e) {
                    self::assertStringContainsString('forbidden', $e->getMessage());
                }
            }

            // 3. ChieBuilder refuses --apply writes outside safe app source dirs (e.g. app/bootstrap.php, composer.json)
            foreach (['composer.json', 'app/bootstrap.php'] as $maliciousTarget) {
                $planJson = json_encode([
                    'summary' => 'Inject backdoor',
                    'changes' => [[
                        'path' => $maliciousTarget,
                        'action' => 'update',
                        'explanation' => 'override',
                        'content' => '<?php // malicious',
                    ]],
                    'next_steps' => [],
                ], JSON_THROW_ON_ERROR);
                $chie = new ChieBuilder(new AiClient(new OpenAiProvider('sk-test', transport: new ScriptedTransport(
                    new HttpResponse(200, json_encode([
                        'model' => 'gpt-4.1-mini',
                        'choices' => [['message' => ['role' => 'assistant', 'content' => $planJson]]],
                    ], JSON_THROW_ON_ERROR)),
                ))));
                try {
                    $chie->build('Add feature', $ctx, apply: true);
                    self::fail('Expected ChieBuilder --apply to reject target: ' . $maliciousTarget);
                } catch (CoreException $e) {
                    self::assertStringContainsString('refused to write', $e->getMessage());
                }
            }

            // 4. MigrationLinter handles multi-statement ALTER TABLE, no false positive on NOT NULL DEFAULT, and inspects helper methods
            mkdir($tmp . '/migs1', 0o777, true);
            mkdir($tmp . '/migs2', 0o777, true);
            mkdir($tmp . '/migs3', 0o777, true);
            file_put_contents($tmp . '/migs1/001_safe_default.php', <<<'PHP'
<?php
class M1 {
    public function up($db): void {
        $db->execute("ALTER TABLE users ADD COLUMN role VARCHAR(32) NOT NULL DEFAULT 'user';");
    }
}
PHP);
            file_put_contents($tmp . '/migs2/002_multi_stmt_hazard.php', <<<'PHP'
<?php
class M2 {
    public function up($db): void {
        $db->execute("ALTER TABLE a ADD COLUMN x TEXT DEFAULT 'ok'; ALTER TABLE b ADD COLUMN y TEXT NOT NULL;");
    }
}
PHP);
            file_put_contents($tmp . '/migs3/003_helper_truncate.php', <<<'PHP'
<?php
class M3 {
    public function up($db): void {
        $this->wipe($db);
    }
    private function wipe($db): void {
        $db->execute('TRUNCATE TABLE audit_logs;');
    }
}
PHP);
            $linter = new MigrationLinter();
            $r1 = $linter->lintDirectory($tmp . '/migs1');
            self::assertTrue($r1['passed']);
            self::assertSame([], $r1['findings']);

            $r2 = $linter->lintDirectory($tmp . '/migs2');
            self::assertFalse($r2['passed']);
            self::assertContains('KGN-MIG-NOT-NULL-NO-DEFAULT', array_column($r2['findings'], 'code'));

            $r3 = $linter->lintDirectory($tmp . '/migs3');
            self::assertFalse($r3['passed']);
            self::assertContains('KGN-MIG-TRUNCATE', array_column($r3['findings'], 'code'));

            // 5. Hardened container blueprints enforce non-root user, read_only, cap_drop ALL, no-new-privileges, TRUSTED_PROXIES, and request_body max_size
            self::assertStringContainsString('USER kongono', $dockerfile);
            self::assertStringContainsString('TRUSTED_PROXIES=""', $dockerfile);

            $bp = (new DeployBlueprintGenerator())->generate('acme', 'acme.kongono.dev', worker: true);
            self::assertStringContainsString('USER kongono', $bp['Dockerfile']);
            self::assertStringContainsString('read_only: true', $bp['docker-compose.prod.yml']);
            self::assertStringContainsString('no-new-privileges:true', $bp['docker-compose.prod.yml']);
            self::assertStringContainsString("cap_drop:\n      - ALL", $bp['docker-compose.prod.yml']);
            self::assertStringContainsString('max_size 10MB', $bp['Caddyfile']);
            self::assertStringContainsString('TRUSTED_PROXIES=', $bp['.env.production.example']);
            self::assertStringContainsString('"work", "--daemon"', $bp['docker-compose.prod.yml']);
            self::assertStringContainsString('S3_BUCKET=', $bp['.env.production.example']);
            self::assertStringContainsString('99-kongono-security.ini', $bp['Dockerfile']);
            self::assertStringContainsString('session.upload_progress.enabled=Off', $bp['Dockerfile']);
            self::assertStringContainsString('99-kongono-security.ini', $dockerfile);

            // 6. SecurityAuditor flags unpatched PHP < 8.5.13 (PHP2Shell / phpAfterShell) as CRITICAL
            self::assertTrue(SecurityAuditor::isVulnerablePhpVersion('8.5.9'));
            self::assertTrue(SecurityAuditor::isVulnerablePhpVersion('8.5.12'));
            self::assertFalse(SecurityAuditor::isVulnerablePhpVersion('8.5.13'));
            self::assertFalse(SecurityAuditor::isVulnerablePhpVersion('8.6.0'));
            $vulnAudit = (new SecurityAuditor())->audit($appDir, phpVersionOverride: '8.5.9');
            self::assertFalse($vulnAudit['passed']);
            self::assertContains('KGN-AUDIT-PHP-CVE', array_column($vulnAudit['findings'], 'code'));

            // 7. BridgeScaffolder supports React 19, Vue 3, and Svelte 5 + TypeScriptPropsGenerator emits strict TS types
            $bridgeGen = new \Kongono\Cli\Bridge\BridgeScaffolder();
            foreach (['react', 'vue', 'svelte'] as $fw) {
                $files = $bridgeGen->generate($fw);
                self::assertArrayHasKey('vite.config.ts', $files);
                self::assertArrayHasKey('resources/js/kongono-bridge.d.ts', $files);
            }

            $cli = new KongoCli(dirname(__DIR__, 3));
            [$bCode, $bOut] = $cli->run(['bridge:init', 'react', '--app=' . $appDir]);
            self::assertSame(0, $bCode);
            self::assertStringContainsString('resources/js/app.tsx', $bOut);
            self::assertFileExists($appDir . '/resources/js/app.tsx');

            [$tCode, $tOut] = $cli->run(['bridge:types', '--app=' . $this->starterAppRoot]);
            self::assertSame(0, $tCode);
            self::assertStringContainsString('export interface CreateNoteInput', $tOut);

            $arrayDts = (new \Kongono\Cli\Bridge\TypeScriptPropsGenerator())->generate([BridgeOrderPayloadDto::class]);
            self::assertStringContainsString('export interface BridgeLineItemDto {', $arrayDts);
            self::assertStringContainsString('readonly items: BridgeLineItemDto[];', $arrayDts);
            self::assertStringContainsString('readonly tags: string[];', $arrayDts);

            // 8. `kongo ui:add` scaffolds zero-npm strict-CSP accessible UI primitives that pass SecurityAuditor cleanly
            [$uCode, $uOut] = $cli->run(['ui:add', 'all', '--app=' . $appDir]);
            self::assertSame(0, $uCode);
            self::assertStringContainsString('views/components/k-modal.kgn.html', $uOut);
            self::assertStringContainsString('views/components/k-kanban.kgn.html', $uOut);
            self::assertFileExists($appDir . '/public/assets/kongono-ui.css');
            self::assertFileExists($appDir . '/views/components/k-dropdown.kgn.html');
            $uiAudit = (new SecurityAuditor())->audit($appDir);
            self::assertNotContains('KGN-AUDIT-RAW-HTML', array_column($uiAudit['findings'], 'code'));
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testMigratePretendFreshProductionGuardAndAlterMigrationScaffold(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-mig-v111-' . bin2hex(random_bytes(5));
        mkdir($tmp . '/app', 0o777, true);
        mkdir($tmp . '/migrations', 0o777, true);
        mkdir($tmp . '/seeders', 0o777, true);

        $stateFile = $tmp . '/db_state.json';
        file_put_contents($stateFile, json_encode([
            'tables' => ['legacy_table'],
            'applied' => [],
            'executed' => [],
            'seeded' => 0,
        ], JSON_THROW_ON_ERROR));

        $bootstrapCode = <<<PHP
<?php
declare(strict_types=1);

use Kongono\Database\ConnectionInterface;
use Kongono\Database\Dialect;
use Kongono\Database\QueryBuilder;
use Kongono\Http\App;

\$statePath = {$this->exportPhpString($stateFile)};

\$conn = new class (\$statePath) implements ConnectionInterface {
    public function __construct(private readonly string \$statePath) {}

    /** @return array{tables: list<string>, applied: list<string>, executed: list<string>, seeded: int} */
    private function load(): array
    {
        /** @var array{tables: list<string>, applied: list<string>, executed: list<string>, seeded: int} */
        return json_decode((string) file_get_contents(\$this->statePath), true, 16, JSON_THROW_ON_ERROR);
    }

    /** @param array{tables: list<string>, applied: list<string>, executed: list<string>, seeded: int} \$data */
    private function save(array \$data): void
    {
        file_put_contents(\$this->statePath, json_encode(\$data, JSON_THROW_ON_ERROR));
    }

    public function dialect(): Dialect
    {
        return Dialect::Postgres;
    }

    public function query(string \$sql, array \$bindings = []): array
    {
        \$state = \$this->load();
        if (str_contains(\$sql, 'FROM pg_tables')) {
            return array_map(static fn(string \$t): array => ['name' => \$t], \$state['tables']);
        }
        return array_map(static fn(string \$id): array => ['id' => \$id], \$state['applied']);
    }

    public function first(string \$sql, array \$bindings = []): ?array
    {
        return null;
    }

    public function execute(string \$sql, array \$bindings = []): int
    {
        \$state = \$this->load();
        \$state['executed'][] = \$sql;
        if (str_starts_with(\$sql, 'INSERT INTO "kongono_migrations"') && is_string(\$bindings[0] ?? null)) {
            \$state['applied'][] = \$bindings[0];
        } elseif (str_starts_with(\$sql, 'DROP TABLE IF EXISTS ')) {
            \$state['tables'] = [];
            \$state['applied'] = [];
        } elseif (str_starts_with(\$sql, 'CREATE TABLE IF NOT EXISTS "orders"')) {
            \$state['tables'][] = 'orders';
        } elseif (str_starts_with(\$sql, 'INSERT INTO "orders"')) {
            ++\$state['seeded'];
        }
        \$this->save(\$state);
        return 1;
    }

    public function table(string \$table, ?string \$alias = null): QueryBuilder
    {
        return new QueryBuilder(\$table, Dialect::Postgres, \$this, \$alias);
    }

    public function transaction(\Closure \$callback): mixed
    {
        return \$callback(\$this);
    }
};

return App::create(dirname(__DIR__), name: 'Migration CLI Test')
    ->instance(ConnectionInterface::class, \$conn);
PHP;
        file_put_contents($tmp . '/app/bootstrap.php', $bootstrapCode);

        file_put_contents($tmp . '/migrations/20261122000001_create_orders.php', <<<'PHP'
<?php
declare(strict_types=1);

use Kongono\Database\ConnectionInterface;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Schema\Blueprint;
use Kongono\Database\Schema\Schema;

return new class () implements MigrationInterface {
    public function id(): string { return '20261122000001_create_orders'; }
    public function up(ConnectionInterface $db): void
    {
        Schema::create($db, 'orders', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->decimal('total', 12, 2)->default(0);
            $table->json('meta')->default('{}');
        });
    }
    public function down(ConnectionInterface $db): void
    {
        Schema::dropIfExists($db, 'orders');
    }
};
PHP);

        file_put_contents($tmp . '/seeders/OrdersSeeder.php', <<<'PHP'
<?php
declare(strict_types=1);

use Kongono\Database\ConnectionInterface;
use Kongono\Database\SeederInterface;

return new class () implements SeederInterface {
    public function run(ConnectionInterface $db): void
    {
        $db->table('orders')->insert(['id' => '00000000-0000-0000-0000-000000000001', 'total' => '99.50', 'meta' => '{}']);
    }
};
PHP);

        $cli = new KongoCli(dirname(__DIR__, 3));
        $prevEnv = getenv('APP_ENV');
        $prevServerEnv = $_ENV['APP_ENV'] ?? null;

        try {
            // 1. kongo make:migration --table=orders scaffolds Schema::table() cleanly
            [$mkCode, $mkOut] = $cli->run(['make:migration', 'add_tax_to_orders', '--table=orders', '--app=' . $tmp]);
            self::assertSame(0, $mkCode);
            self::assertStringContainsString('add_tax_to_orders.php', $mkOut);
            $generatedAlter = glob($tmp . '/migrations/*_add_tax_to_orders.php');
            self::assertIsArray($generatedAlter);
            self::assertCount(1, $generatedAlter);
            $alterContents = (string) file_get_contents($generatedAlter[0]);
            self::assertStringContainsString("Schema::table(\$db, 'orders'", $alterContents);
            // Clean up generated empty alter migration before pretend/fresh assertions
            @unlink($generatedAlter[0]);

            // 2. kongo migrate --pretend outputs exact SQL and performs ZERO database mutations
            [$pCode, $pOut] = $cli->run(['migrate', '--pretend', '--app=' . $tmp]);
            self::assertSame(0, $pCode);
            self::assertStringContainsString('Migration dry-run preview (no changes executed):', $pOut);
            self::assertStringContainsString('20261122000001_create_orders', $pOut);
            self::assertStringContainsString('"id" UUID PRIMARY KEY NOT NULL', $pOut);
            self::assertStringContainsString('"total" NUMERIC(12,2) NOT NULL DEFAULT 0', $pOut);
            self::assertStringContainsString('"meta" JSONB NOT NULL DEFAULT \'{}\'', $pOut);

            $stateAfterPretend = json_decode((string) file_get_contents($stateFile), true, 16, JSON_THROW_ON_ERROR);
            self::assertIsArray($stateAfterPretend);
            self::assertSame([], $stateAfterPretend['executed']);

            // 3. kongo migrate --pretend --json returns structured preview JSON
            [$pjCode, $pjOut] = $cli->run(['migrate', '--pretend', '--json', '--app=' . $tmp]);
            self::assertSame(0, $pjCode);
            $pjData = json_decode($pjOut, true, 16, JSON_THROW_ON_ERROR);
            self::assertIsArray($pjData);
            self::assertTrue($pjData['pretend']);
            self::assertSame('pgsql', $pjData['dialect']);
            self::assertArrayHasKey('20261122000001_create_orders', $pjData['migrations']);

            // 4. Hard production guard blocks `kongo migrate:fresh` when APP_ENV=production without --force
            putenv('APP_ENV=production');
            $_ENV['APP_ENV'] = 'production';
            [$prodCode, $prodOut] = $cli->run(['migrate:fresh', '--app=' . $tmp]);
            self::assertSame(1, $prodCode);
            self::assertStringContainsString('Refusing to run `kongo migrate:fresh` in production', $prodOut);

            // Confirm zero writes happened while blocked
            $stateBlocked = json_decode((string) file_get_contents($stateFile), true, 16, JSON_THROW_ON_ERROR);
            self::assertIsArray($stateBlocked);
            self::assertSame([], $stateBlocked['executed']);

            // 5. Passing --force overrides the production guard and --seed runs seeders on the same connection
            [$forceCode, $forceOut] = $cli->run(['migrate:fresh', '--seed', '--force', '--app=' . $tmp]);
            self::assertSame(0, $forceCode);
            self::assertStringContainsString('Dropped 1 table(s).', $forceOut);
            self::assertStringContainsString('20261122000001_create_orders', $forceOut);
            self::assertStringContainsString('OrdersSeeder.php', $forceOut);

            $stateAfterFresh = json_decode((string) file_get_contents($stateFile), true, 16, JSON_THROW_ON_ERROR);
            self::assertIsArray($stateAfterFresh);
            self::assertSame(1, $stateAfterFresh['seeded']);
            self::assertSame(['20261122000001_create_orders'], $stateAfterFresh['applied']);

            // 6. kongo migrate --pretend after migration is applied reports 0 statements
            [$upToDateCode, $upToDateOut] = $cli->run(['migrate', '--pretend', '--app=' . $tmp]);
            self::assertSame(0, $upToDateCode);
            self::assertStringContainsString('already up to date (pretend mode: 0 statements)', $upToDateOut);

            // 7. MigrationLinter catches fluent `$table->dropColumn(...)` in up() while ignoring it in down()
            file_put_contents($tmp . '/migrations/20261122000002_fluent_drop.php', <<<'PHP'
<?php
declare(strict_types=1);
class FluentDrop {
    public function up($db): void {
        \Kongono\Database\Schema\Schema::table($db, 'orders', static function ($table): void {
            $table->dropColumn('legacy');
        });
    }
    public function down($db): void {
        \Kongono\Database\Schema\Schema::table($db, 'orders', static function ($table): void {
            $table->string('legacy')->nullable();
        });
    }
}
PHP);
            $lint = (new MigrationLinter())->lintDirectory($tmp . '/migrations');
            self::assertContains('KGN-MIG-DROP-COLUMN', array_column($lint['findings'], 'code'));
        } finally {
            if (false === $prevEnv) {
                putenv('APP_ENV');
            } else {
                putenv('APP_ENV=' . $prevEnv);
            }
            if (null === $prevServerEnv) {
                unset($_ENV['APP_ENV']);
            } else {
                $_ENV['APP_ENV'] = $prevServerEnv;
            }
            $this->removeDir($tmp);
        }
    }

    public function testQueuePriorityWorkAndDeadLetterCliCommands(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-queue-cli-' . bin2hex(random_bytes(5));
        mkdir($tmp . '/app', 0o775, true);
        $stateFile = $tmp . '/queue-state.json';

        $bootstrapCode = <<<'PHP'
<?php
declare(strict_types=1);

use Kongono\Http\App;
use Kongono\Jobs\Job;
use Kongono\Jobs\JobOutcome;
use Kongono\Jobs\JobStatus;
use Kongono\Jobs\Queue;
use Kongono\Jobs\QueueDriverInterface;
use Kongono\Jobs\Worker;

$statePath = __STATE_FILE__;

$driver = new class ($statePath) implements QueueDriverInterface {
    public function __construct(private string $file) {}

    /** @return array{jobs: array<string, array<string, mixed>>, log: list<string>} */
    private function readState(): array
    {
        if (!is_file($this->file)) {
            return ['jobs' => [], 'log' => []];
        }
        $decoded = json_decode((string) file_get_contents($this->file), true);
        if (!is_array($decoded)) {
            return ['jobs' => [], 'log' => []];
        }

        return [
            'jobs' => is_array($decoded['jobs'] ?? null) ? $decoded['jobs'] : [],
            'log' => is_array($decoded['log'] ?? null) ? $decoded['log'] : [],
        ];
    }

    /** @param array{jobs: array<string, array<string, mixed>>, log: list<string>} $state */
    private function writeState(array $state): void
    {
        file_put_contents($this->file, json_encode($state, JSON_THROW_ON_ERROR));
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Job
    {
        return new Job(
            id: (string) $row['id'],
            queue: (string) $row['queue'],
            type: (string) $row['type'],
            payload: is_array($row['payload'] ?? null) ? $row['payload'] : [],
            attempt: (int) ($row['attempt'] ?? 0),
            maxAttempts: (int) ($row['max_attempts'] ?? 1),
            generation: (int) ($row['generation'] ?? 0),
            leaseToken: is_string($row['lease_token'] ?? null) ? $row['lease_token'] : null,
            availableAt: (int) ($row['available_at'] ?? 0),
            leaseUntil: is_int($row['lease_until'] ?? null) ? $row['lease_until'] : null,
            status: JobStatus::from((string) ($row['status'] ?? 'pending')),
            lastError: is_string($row['last_error'] ?? null) ? $row['last_error'] : null,
            uniqueKey: is_string($row['unique_key'] ?? null) ? $row['unique_key'] : null,
        );
    }

    public function push(string $queue, string $type, array $payload = [], int $delaySeconds = 0, int $maxAttempts = 3, ?int $now = null, ?string $uniqueKey = null): Job
    {
        $state = $this->readState();
        $id = 'job_' . bin2hex(random_bytes(4));
        $row = [
            'id' => $id, 'queue' => $queue, 'type' => $type, 'payload' => $payload,
            'attempt' => 0, 'max_attempts' => $maxAttempts, 'generation' => 0,
            'lease_token' => null, 'available_at' => ($now ?? time()) + $delaySeconds,
            'lease_until' => null, 'status' => 'pending', 'last_error' => null, 'unique_key' => $uniqueKey,
        ];
        $state['jobs'][$id] = $row;
        $this->writeState($state);

        return $this->hydrate($row);
    }

    public function reserve(string $queue, int $leaseSeconds = 60, ?int $now = null): ?Job
    {
        $state = $this->readState();
        foreach ($state['jobs'] as $id => $row) {
            if ($row['queue'] === $queue && 'pending' === $row['status']) {
                $row['status'] = 'reserved';
                $row['attempt'] = ((int) $row['attempt']) + 1;
                $row['generation'] = ((int) $row['generation']) + 1;
                $row['lease_token'] = 'tok_' . $row['generation'];
                $state['jobs'][$id] = $row;
                $this->writeState($state);

                return $this->hydrate($row);
            }
        }

        return null;
    }

    public function acknowledge(Job $job): bool
    {
        $state = $this->readState();
        if (!isset($state['jobs'][$job->id])) {
            return false;
        }
        $state['jobs'][$job->id]['status'] = 'completed';
        $state['jobs'][$job->id]['lease_token'] = null;
        $state['log'][] = $job->queue . ':' . $job->id;
        $this->writeState($state);

        return true;
    }

    public function releaseForRetry(Job $job, int $delaySeconds, ?string $error = null, ?int $now = null): bool
    {
        $state = $this->readState();
        if (!isset($state['jobs'][$job->id])) {
            return false;
        }
        $state['jobs'][$job->id]['status'] = 'pending';
        $state['jobs'][$job->id]['last_error'] = $error;
        $this->writeState($state);

        return true;
    }

    public function markFailed(Job $job, JobStatus $terminalStatus, ?string $error = null): bool
    {
        $state = $this->readState();
        if (!isset($state['jobs'][$job->id])) {
            return false;
        }
        $state['jobs'][$job->id]['status'] = $terminalStatus->value;
        $state['jobs'][$job->id]['last_error'] = $error;
        $this->writeState($state);

        return true;
    }

    public function stats(string $queue): array
    {
        return ['pending' => 0, 'reserved' => 0, 'completed' => 0, 'failed' => 0, 'dead' => 0];
    }

    public function prune(string $queue, int $olderThanTimestamp): int
    {
        return 0;
    }

    public function listFailed(string $queue, int $limit = 100): array
    {
        $state = $this->readState();
        $out = [];
        foreach ($state['jobs'] as $row) {
            if ($row['queue'] === $queue && ('failed' === $row['status'] || 'dead' === $row['status'])) {
                $out[] = $this->hydrate($row);
                if (count($out) >= $limit) {
                    break;
                }
            }
        }

        return $out;
    }

    public function retryFailed(string $queue, ?string $jobId = null, ?int $now = null): int
    {
        $state = $this->readState();
        $count = 0;
        foreach ($state['jobs'] as $id => $row) {
            if ($row['queue'] !== $queue || ('failed' !== $row['status'] && 'dead' !== $row['status'])) {
                continue;
            }
            if (null !== $jobId && $id !== $jobId) {
                continue;
            }
            $row['status'] = 'pending';
            $row['attempt'] = 0;
            $row['last_error'] = null;
            $state['jobs'][$id] = $row;
            ++$count;
        }
        $this->writeState($state);

        return $count;
    }

    public function flushFailed(string $queue): int
    {
        $state = $this->readState();
        $removed = 0;
        foreach ($state['jobs'] as $id => $row) {
            if ($row['queue'] === $queue && ('failed' === $row['status'] || 'dead' === $row['status'])) {
                unset($state['jobs'][$id]);
                ++$removed;
            }
        }
        $this->writeState($state);

        return $removed;
    }
};

$app = App::create(dirname(__DIR__), name: 'Queue CLI Test');
$app->container()->singleton(QueueDriverInterface::class, static fn(): QueueDriverInterface => $driver);
$app->container()->singleton(Queue::class, static fn(): Queue => new Queue($driver));
$app->container()->singleton(Worker::class, static function () use ($driver): Worker {
    return (new Worker($driver))
        ->register('ok.task', static fn(): JobOutcome => JobOutcome::Completed)
        ->register('fail.task', static fn(): JobOutcome => JobOutcome::Failed);
});

return $app;
PHP;

        file_put_contents($tmp . '/app/bootstrap.php', str_replace('__STATE_FILE__', $this->exportPhpString($stateFile), $bootstrapCode));
        file_put_contents($stateFile, json_encode([
            'jobs' => [
                'job_def_1' => [
                    'id' => 'job_def_1', 'queue' => 'default', 'type' => 'ok.task', 'payload' => [],
                    'attempt' => 0, 'max_attempts' => 1, 'generation' => 0, 'lease_token' => null,
                    'available_at' => 0, 'lease_until' => null, 'status' => 'pending', 'last_error' => null, 'unique_key' => null,
                ],
                'job_crit_1' => [
                    'id' => 'job_crit_1', 'queue' => 'critical', 'type' => 'ok.task', 'payload' => [],
                    'attempt' => 0, 'max_attempts' => 1, 'generation' => 0, 'lease_token' => null,
                    'available_at' => 0, 'lease_until' => null, 'status' => 'pending', 'last_error' => null, 'unique_key' => null,
                ],
                'job_dead_a' => [
                    'id' => 'job_dead_a', 'queue' => 'default', 'type' => 'ok.task', 'payload' => [],
                    'attempt' => 1, 'max_attempts' => 1, 'generation' => 1, 'lease_token' => null,
                    'available_at' => 0, 'lease_until' => null, 'status' => 'dead', 'last_error' => 'Gateway 502', 'unique_key' => null,
                ],
                'job_dead_b' => [
                    'id' => 'job_dead_b', 'queue' => 'default', 'type' => 'fail.task', 'payload' => [],
                    'attempt' => 1, 'max_attempts' => 1, 'generation' => 1, 'lease_token' => null,
                    'available_at' => 0, 'lease_until' => null, 'status' => 'failed', 'last_error' => 'Timeout', 'unique_key' => null,
                ],
            ],
            'log' => [],
        ], JSON_THROW_ON_ERROR));

        $cli = new KongoCli(dirname(__DIR__, 3));

        try {
            // 1. Multi-queue priority work: critical MUST execute before default
            [$wCode, $wOut] = $cli->run(['work', '--queue=critical,default', '--app=' . $tmp]);
            self::assertSame(0, $wCode);
            self::assertStringContainsString('Worker finished queue "critical,default": processed=2 completed=2', $wOut);
            $afterWork = json_decode((string) file_get_contents($stateFile), true, 16, JSON_THROW_ON_ERROR);
            self::assertIsArray($afterWork);
            self::assertSame(['critical:job_crit_1', 'default:job_def_1'], $afterWork['log']);

            // 2. kongo queue:failed (text & --json) lists both failed/dead jobs
            [$fCode, $fOut] = $cli->run(['queue:failed', '--app=' . $tmp]);
            self::assertSame(0, $fCode);
            self::assertStringContainsString('job_dead_a', $fOut);
            self::assertStringContainsString('Gateway 502', $fOut);
            self::assertStringContainsString('job_dead_b', $fOut);

            [$fjCode, $fjOut] = $cli->run(['queue:failed', '--json', '--limit=1', '--app=' . $tmp]);
            self::assertSame(0, $fjCode);
            $fj = json_decode($fjOut, true, 16, JSON_THROW_ON_ERROR);
            self::assertIsArray($fj);
            self::assertCount(1, $fj);

            // 3. kongo queue:retry <id> replays job_dead_a, and worker completes it
            [$rCode, $rOut] = $cli->run(['queue:retry', 'job_dead_a', '--app=' . $tmp]);
            self::assertSame(0, $rCode);
            self::assertStringContainsString('Requeued 1 failed job(s) in queue "default"', $rOut);

            [$w2Code, $w2Out] = $cli->run(['work', '--app=' . $tmp]);
            self::assertSame(0, $w2Code);
            self::assertStringContainsString('processed=1 completed=1', $w2Out);

            // 4. kongo queue:flush permanently clears remaining failed job_dead_b
            [$flCode, $flOut] = $cli->run(['queue:flush', '--app=' . $tmp]);
            self::assertSame(0, $flCode);
            self::assertStringContainsString('Flushed 1 failed job(s) from queue "default"', $flOut);

            [$emptyCode, $emptyOut] = $cli->run(['queue:failed', '--app=' . $tmp]);
            self::assertSame(0, $emptyCode);
            self::assertStringContainsString('No failed or dead-lettered jobs', $emptyOut);
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testSkillsInstallerAndCliInstallBundledWorkflowSkillsSafely(): void
    {
        $installer = new SkillsInstaller();
        $expected = [
            'kongono-ai-and-rag',
            'kongono-auth-and-security',
            'kongono-feature-builder',
            'kongono-live-and-bridge',
            'kongono-queues-and-ops',
            'kongono-self-verifying-proofs',
        ];
        self::assertSame($expected, $installer->available());

        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-skills-test-' . bin2hex(random_bytes(5));
        mkdir($tmp, 0o775, true);
        $cli = new KongoCli(dirname(__DIR__, 3));

        try {
            // 1. Install into both .agents/skills and .claude/skills
            [$code, $out] = $cli->run(['skills:install', '--target=both', '--app=' . $tmp]);
            self::assertSame(0, $code);
            self::assertStringContainsString('[INSTALLED] .agents/skills/kongono-feature-builder/SKILL.md', $out);
            self::assertStringContainsString('[INSTALLED] .claude/skills/kongono-feature-builder/SKILL.md', $out);

            foreach ($expected as $skill) {
                $agentFile = $tmp . '/.agents/skills/' . $skill . '/SKILL.md';
                $claudeFile = $tmp . '/.claude/skills/' . $skill . '/SKILL.md';
                self::assertFileExists($agentFile);
                self::assertFileExists($claudeFile);

                $content = (string) file_get_contents($agentFile);
                self::assertStringStartsWith("---\nname: {$skill}\ndescription:", $content);
            }

            // 2. Re-running reports UNCHANGED when files already match
            [$unchangedCode, $unchangedOut] = $cli->run(['skills:install', '--target=agents', '--app=' . $tmp]);
            self::assertSame(0, $unchangedCode);
            self::assertStringContainsString('[UNCHANGED] .agents/skills/kongono-ai-and-rag/SKILL.md', $unchangedOut);

            // 3. Local edits are preserved without --force, and overwritten when --force is passed
            $editedPath = $tmp . '/.agents/skills/kongono-ai-and-rag/SKILL.md';
            file_put_contents($editedPath, "# custom local edit\n");

            [$skipCode, $skipOut] = $cli->run(['skills:install', '--target=agents', '--app=' . $tmp]);
            self::assertSame(0, $skipCode);
            self::assertStringContainsString('[SKIPPED]   .agents/skills/kongono-ai-and-rag/SKILL.md', $skipOut);
            self::assertSame("# custom local edit\n", (string) file_get_contents($editedPath));

            [$forceCode, $forceOut] = $cli->run(['skills:install', '--target', 'agents', '--force', '--app=' . $tmp]);
            self::assertSame(0, $forceCode);
            self::assertStringContainsString('[UPDATED]   .agents/skills/kongono-ai-and-rag/SKILL.md', $forceOut);
            self::assertStringStartsWith("---\nname: kongono-ai-and-rag\n", (string) file_get_contents($editedPath));

            // 4. Unsupported target fails cleanly
            [$badCode, $badOut] = $cli->run(['skills:install', '--target=unknown', '--app=' . $tmp]);
            self::assertSame(1, $badCode);
            self::assertStringContainsString('Unsupported skills target', $badOut);
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testMakeGeneratorsResourceAuthInitAndStarterPresets(): void
    {
        $repoRoot = dirname(__DIR__, 3);
        $cli = new KongoCli($repoRoot);
        $baseTmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-scaffold-suite-' . bin2hex(random_bytes(5));
        mkdir($baseTmp, 0o775, true);

        try {
            // 1. Scaffold minimal app and exercise all 6 new make:* generators + make:resource + auth:init
            $minDir = $baseTmp . DIRECTORY_SEPARATOR . 'minimal-app';
            [$newCode] = $cli->run(['new', $minDir, '--name=Minimal Demo']);
            self::assertSame(0, $newCode);

            foreach ([
                ['make:job', 'SendInvoice', 'src/Jobs/SendInvoiceJob.php'],
                ['make:policy', 'Invoice', 'src/Security/InvoicePolicy.php'],
                ['make:middleware', 'AuditTrace', 'src/Http/Middleware/AuditTraceMiddleware.php'],
                ['make:factory', 'Invoice', 'src/Testing/InvoiceFactory.php'],
                ['make:rule', 'SlugFormat', 'src/Validation/SlugFormat.php'],
            ] as [$cmd, $arg, $expectedPath]) {
                [$c, $o] = $cli->run([$cmd, $arg, '--app=' . $minDir]);
                self::assertSame(0, $c, $o);
                self::assertFileExists($minDir . '/' . $expectedPath);
            }

            [$mailCode, $mailOut] = $cli->run(['make:mail', 'Welcome', '--app=' . $minDir]);
            self::assertSame(0, $mailCode, $mailOut);
            self::assertFileExists($minDir . '/src/Mail/WelcomeMail.php');
            self::assertFileExists($minDir . '/views/mail/welcome.kgn.html');

            [$resCode, $resOut] = $cli->run(['make:resource', 'Category', '--app=' . $minDir]);
            self::assertSame(0, $resCode, $resOut);
            self::assertFileExists($minDir . '/src/Http/CategoryController.php');
            self::assertFileExists($minDir . '/src/Repository/CategoryRepository.php');
            self::assertFileExists($minDir . '/src/Http/Dto/CategoryDto.php');
            self::assertFileExists($minDir . '/src/Http/Dto/CreateCategoryInput.php');
            self::assertFileExists($minDir . '/src/Http/Dto/UpdateCategoryInput.php');
            self::assertFileExists($minDir . '/tests/CategoryResourceTest.php');
            self::assertStringContainsString('categories', (string) file_get_contents($minDir . '/src/Repository/CategoryRepository.php'));

            // auth:init skip/force lifecycle
            [$a1Code, $a1Out] = $cli->run(['auth:init', '--mode=web', '--app=' . $minDir]);
            self::assertSame(0, $a1Code, $a1Out);
            self::assertFileExists($minDir . '/src/Http/WebAuthController.php');
            self::assertFileDoesNotExist($minDir . '/src/Http/ApiAuthController.php');

            [$a2Code, $a2Out] = $cli->run(['auth:init', '--mode=both', '--app=' . $minDir]);
            self::assertSame(0, $a2Code, $a2Out);
            self::assertStringContainsString('[SKIPPED]', $a2Out);
            self::assertFileExists($minDir . '/src/Http/ApiAuthController.php');

            [$a3Code, $a3Out] = $cli->run(['auth:init', '--mode=both', '--force', '--app=' . $minDir]);
            self::assertSame(0, $a3Code, $a3Out);
            self::assertStringContainsString('[OVERWRITTEN]', $a3Out);

            [$aBadCode] = $cli->run(['auth:init', '--mode=invalid', '--app=' . $minDir]);
            self::assertSame(1, $aBadCode);

            // Lint every generated PHP file in minimal-app
            $this->assertDirectoryPhpSyntaxValid($minDir);

            // 2. Scaffold --starter=api and boot it end-to-end in an isolated subprocess
            $apiDir = $baseTmp . DIRECTORY_SEPARATOR . 'api-app';
            [$apiCode, $apiOut] = $cli->run(['new', $apiDir, '--name=Pulse API', '--starter=api']);
            self::assertSame(0, $apiCode, $apiOut);
            self::assertFileExists($apiDir . '/src/Http/ApiAuthController.php');
            self::assertFileDoesNotExist($apiDir . '/src/Http/WebAuthController.php');
            $this->assertDirectoryPhpSyntaxValid($apiDir);
            $this->assertStarterBootsAndPassesFlow($repoRoot, $apiDir, 'api');

            // 3. Scaffold --starter=saas and boot it end-to-end in an isolated subprocess
            $saasDir = $baseTmp . DIRECTORY_SEPARATOR . 'saas-app';
            [$saasCode, $saasOut] = $cli->run(['new', $saasDir, '--name=Kanso Cloud', '--starter=saas']);
            self::assertSame(0, $saasCode, $saasOut);
            self::assertFileExists($saasDir . '/src/Http/WebAuthController.php');
            self::assertFileExists($saasDir . '/src/Http/ApiAuthController.php');
            self::assertFileExists($saasDir . '/.agents/skills/kongono-feature-builder/SKILL.md');
            $this->assertDirectoryPhpSyntaxValid($saasDir);
            $this->assertStarterBootsAndPassesFlow($repoRoot, $saasDir, 'saas');

            // 4. Reject unknown starter preset
            [$badStarterCode] = $cli->run(['new', $baseTmp . DIRECTORY_SEPARATOR . 'bad-app', '--starter=nope']);
            self::assertSame(1, $badStarterCode);
        } finally {
            $this->removeDir($baseTmp);
        }
    }

    private function assertDirectoryPhpSyntaxValid(string $dir): void
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file instanceof \SplFileInfo || 'php' !== $file->getExtension()) {
                continue;
            }
            $cmd = [PHP_BINARY, '-l', $file->getPathname()];
            $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($proc);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit = proc_close($proc);
            self::assertSame(0, $exit, sprintf('Syntax error in %s: %s %s', $file->getPathname(), (string) $stdout, (string) $stderr));
        }
    }

    private function assertStarterBootsAndPassesFlow(string $repoRoot, string $appDir, string $preset): void
    {
        $script = <<<'PHP'
<?php
declare(strict_types=1);
$repoRoot = $argv[1];
$appDir = $argv[2];
$preset = $argv[3];

require $repoRoot . '/vendor/autoload.php';
spl_autoload_register(static function (string $class) use ($appDir): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 4)) . '.php';
    $path = $appDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $rel;
    if (is_file($path)) {
        require $path;
    }
});

/** @var Kongono\Http\App $app */
$app = require $appDir . '/app/bootstrap.php';
$client = new Kongono\Http\Testing\TestClient($app);

$client->get('/health')->assertOk();
$client->get('/api/auth/me')->assertUnauthorized();
$client->get('/_ops')->assertUnauthorized();

$reg = $client->postJson('/api/auth/register', [
    'name' => 'Ada Lovelace',
    'email' => 'ada@example.test',
    'password' => 'correct-horse-battery-staple',
    'passwordConfirmation' => 'correct-horse-battery-staple',
])->assertCreated()->json();

$token = (string) ($reg['token'] ?? '');
$tokenId = (string) ($reg['token_id'] ?? '');
if (!str_starts_with($token, 'kgn_pat_')) {
    fwrite(STDERR, "Expected kgn_pat_ token\n");
    exit(2);
}

$client->withBearerToken($token)->get('/api/auth/me')->assertOk()->assertJsonPath('attributes.email', 'ada@example.test');
$client->withBearerToken($token)->delete('/api/auth/tokens/' . $tokenId)->assertOk();
$client->withBearerToken($token)->get('/api/auth/me')->assertUnauthorized();

$opsAdmin = new Kongono\Security\Principal('ops-1', Kongono\Security\PrincipalKind::Human, ['ops.view', 'ops.manage']);
$client->actingAs($opsAdmin)->get('/_ops')->assertOk();

if ('saas' === $preset) {
    $client->get('/')->assertOk()->assertSee('Kongono');
    $client->get('/login')->assertOk()->assertSee('Sign In');
    $client->get('/register')->assertOk()->assertSee('Create Account');
    $client->get('/dashboard')->assertUnauthorized();
}

echo "STARTER_OK:" . $preset . "\n";
PHP;

        $probeFile = $appDir . DIRECTORY_SEPARATOR . '_probe.php';
        file_put_contents($probeFile, $script);
        $proc = proc_open([PHP_BINARY, $probeFile, $repoRoot, $appDir, $preset], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($proc);
        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);
        @unlink($probeFile);

        self::assertSame(0, $exit, sprintf('Starter "%s" probe failed: %s %s', $preset, $stdout, $stderr));
        self::assertStringContainsString('STARTER_OK:' . $preset, $stdout);
    }

    public function testOshieChieHanteiHardeningMultiTurnToolLoopsAndDevStudioBridge(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-trio-test-' . bin2hex(random_bytes(5));
        (new AppScaffolder())->scaffold($tmp, 'Trio Demo');

        try {
            $ctx = ProjectContext::discover($tmp);

            // 1. Oshie: 13 offline lessons, multi-turn converse() REPL, and PromptGuard canary blocking
            $offlineOshie = new OshieTutor();
            $lessons = $offlineOshie->listLessons();
            self::assertCount(13, $lessons);
            foreach (['routing', 'views', 'live-bridge', 'validation', 'database', 'auth', 'security', 'storage', 'jobs', 'testing', 'ai', 'ops-deploy', 'proofs'] as $id) {
                self::assertArrayHasKey($id, $lessons);
                self::assertStringContainsString('# Lesson:', $offlineOshie->teachLesson($id, $ctx));
            }

            $replTransport = new ScriptedTransport(
                new HttpResponse(200, json_encode(['model' => 'gpt-4.1-mini', 'choices' => [['message' => ['role' => 'assistant', 'content' => 'Turn 1: use #[Get].']]]], JSON_THROW_ON_ERROR)),
                new HttpResponse(200, json_encode(['model' => 'gpt-4.1-mini', 'choices' => [['message' => ['role' => 'assistant', 'content' => 'Turn 2: inject Request.']]]], JSON_THROW_ON_ERROR)),
            );
            $onlineOshie = new OshieTutor(new AiClient(new OpenAiProvider('sk-test', transport: $replTransport)));
            $turn1 = $onlineOshie->converse('How do routes work?', [], $ctx);
            self::assertSame('Turn 1: use #[Get].', $turn1['reply']);
            self::assertCount(3, $turn1['history']);
            self::assertStringContainsString('<untrusted_data', $turn1['history'][0]->content);

            $turn2 = $onlineOshie->converse('And Request?', $turn1['history'], $ctx);
            self::assertSame('Turn 2: inject Request.', $turn2['reply']);
            self::assertCount(5, $turn2['history']);

            // Canary leak in Oshie ask() must fail closed
            $canaryEchoTransport = new class implements HttpTransportInterface {
                public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
                {
                    preg_match('/kgn_canary_[a-f0-9]{24}/i', $jsonBody, $m);
                    $leaked = $m[0] ?? 'none';

                    return new HttpResponse(200, json_encode(['model' => 'gpt-4.1-mini', 'choices' => [['message' => ['role' => 'assistant', 'content' => 'Leaked ' . $leaked]]]], JSON_THROW_ON_ERROR));
                }
            };
            try {
                (new OshieTutor(new AiClient(new OpenAiProvider('sk-test', transport: $canaryEchoTransport))))->ask('Hi', $ctx);
                self::fail('Expected Oshie PromptGuard to block canary exfiltration.');
            } catch (CoreException $e) {
                self::assertStringContainsString('PromptGuard', $e->getMessage());
            }

            // 2. Chie: full multi-step tool loop (list_files -> read_file -> prose + fenced JSON -> --apply write)
            $validPhp = "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Http;\n\nfinal readonly class PingController {}\n";
            $chieTransport = new ScriptedTransport(
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => [
                        'role' => 'assistant',
                        'content' => '',
                        'tool_calls' => [
                            ['id' => 'call_1', 'type' => 'function', 'function' => ['name' => 'list_files', 'arguments' => '{}']],
                            ['id' => 'call_2', 'type' => 'function', 'function' => ['name' => 'read_file', 'arguments' => '{"path":"src/Http/HomeController.php"}']],
                        ],
                    ]]],
                ], JSON_THROW_ON_ERROR)),
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => [
                        'role' => 'assistant',
                        'content' => "Sure! Here is your build plan:\n```json\n" . json_encode([
                            'summary' => 'Added PingController',
                            'changes' => [[
                                'path' => 'src/Http/PingController.php',
                                'action' => 'create',
                                'explanation' => 'Health ping controller',
                                'content' => $validPhp,
                            ]],
                            'next_steps' => ['Register PingController'],
                        ], JSON_THROW_ON_ERROR) . "\n```\nHope that helps!",
                    ]]],
                ], JSON_THROW_ON_ERROR)),
            );
            $chie = new ChieBuilder(new AiClient(new OpenAiProvider('sk-test', transport: $chieTransport)));
            $applied = $chie->build('Add a PingController', $ctx, apply: true);
            self::assertSame(['src/Http/PingController.php'], $applied['applied']);
            self::assertFileExists($tmp . '/src/Http/PingController.php');
            self::assertCount(2, $chieTransport->requests);

            // Chie rejects broken PHP syntax before writing on --apply
            $brokenTransport = new ScriptedTransport(
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => [
                        'role' => 'assistant',
                        'content' => json_encode([
                            'summary' => 'Broken file',
                            'changes' => [[
                                'path' => 'src/Http/BrokenController.php',
                                'action' => 'create',
                                'explanation' => 'Oops',
                                'content' => "<?php\nfinal class Broken { function bad( {\n",
                            ]],
                            'next_steps' => [],
                        ], JSON_THROW_ON_ERROR),
                    ]]],
                ], JSON_THROW_ON_ERROR)),
            );
            try {
                (new ChieBuilder(new AiClient(new OpenAiProvider('sk-test', transport: $brokenTransport))))->build('Break it', $ctx, apply: true);
                self::fail('Expected Chie --apply to reject broken PHP syntax.');
            } catch (CoreException $e) {
                self::assertStringContainsString('syntax validation', $e->getMessage());
            }
            self::assertFileDoesNotExist($tmp . '/src/Http/BrokenController.php');

            // 3. Hantei: failing runCheck() scrubs secrets & invokes AI diagnosis, generateTests(--apply) writes valid PHP
            $hanteiTransport = new ScriptedTransport(
                new HttpResponse(200, json_encode(['model' => 'gpt-4.1-mini', 'choices' => [['message' => ['role' => 'assistant', 'content' => 'Root cause: non-zero exit.']]]], JSON_THROW_ON_ERROR)),
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => [
                        'role' => 'assistant',
                        'content' => "Here is the PHPUnit test:\n```php\n<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Tests;\n\nuse PHPUnit\\Framework\\TestCase;\n\nfinal class HomeControllerTest extends TestCase\n{\n    public function testOk(): void { self::assertTrue(true); }\n}\n```\n",
                    ]]],
                ], JSON_THROW_ON_ERROR)),
            );
            $hantei = new HanteiDebugger(new AiClient(new OpenAiProvider('sk-test', transport: $hanteiTransport)));
            $checkRes = $hantei->runCheck([PHP_BINARY, '-r', 'fwrite(STDERR, "Boom gsk_1234567890abcdefghijKLMN"); exit(1);'], $ctx);
            self::assertSame(1, $checkRes['exit_code']);
            self::assertStringNotContainsString('gsk_1234567890abcdefghijKLMN', $checkRes['output']);
            self::assertStringContainsString('[REDACTED]', $checkRes['output']);
            self::assertSame('Root cause: non-zero exit.', $checkRes['diagnosis']);

            $genRes = $hantei->generateTests('src/Http/HomeController.php', $ctx, apply: true);
            self::assertTrue($genRes['written']);
            self::assertSame('tests/Http/HomeControllerTest.php', $genRes['target_path']);
            self::assertFileExists($tmp . '/tests/Http/HomeControllerTest.php');

            // Hantei canary exfiltration check
            try {
                (new HanteiDebugger(new AiClient(new OpenAiProvider('sk-test', transport: $canaryEchoTransport))))->diagnose('Error 500', $ctx);
                self::fail('Expected Hantei PromptGuard to block canary exfiltration.');
            } catch (CoreException $e) {
                self::assertStringContainsString('PromptGuard', $e->getMessage());
            }

            // 4. DevStudioCompanions wires all 3 browser endpoints cleanly (and fail-closes when debug=false)
            $studioApp = App::create($tmp, name: 'Studio Demo')->config(['app' => ['debug' => true]]);
            $studioInspector = new DevInspector();
            $studioApp->middleware(DevStudioCompanions::middleware($studioApp, $studioInspector, $tmp));
            $studioClient = new TestClient($studioApp);

            $studioClient->postJson('/_kongono/api/oshie', ['lesson' => 'testing'])
                ->assertOk()
                ->assertSee('TestClient');
            $studioClient->postJson('/_kongono/api/hantei', ['diagnostic' => 'Failed with gsk_1234567890abcdefghijKLMN'])
                ->assertOk()
                ->assertSee('[REDACTED]');
            $studioClient->postJson('/_kongono/api/chie', ['instruction' => 'Add /ping'])
                ->assertOk()
                ->assertSee('GROQ_API_KEY');

            $prodApp = App::create($tmp, name: 'Prod Demo')->config(['app' => ['debug' => false]]);
            $prodApp->middleware(DevStudioCompanions::middleware($prodApp, new DevInspector(), $tmp));
            (new TestClient($prodApp))->postJson('/_kongono/api/oshie', ['lesson' => 'routing'])->assertNotFound();
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testCompanionTelemetryCostEstimationAndHardBudgetCaps(): void
    {
        // Ollama is strictly $0.00 (local)
        $ollama = CompanionTelemetry::fromUsage('ollama', 'qwen2.5-coder:14b', new TokenUsage(10_000, 5_000), 123.4);
        self::assertSame(0.0, $ollama->estimatedCostUsd);
        self::assertStringContainsString('$0.00 (local)', $ollama->formatFooter());

        // Cloud provider cost estimation across all supported families
        self::assertGreaterThan(0.0, CompanionTelemetry::estimateCostUsd('openai', 'gpt-4.1-mini', 100_000, 50_000));
        self::assertGreaterThan(0.0, CompanionTelemetry::estimateCostUsd('anthropic', 'claude-sonnet-4-5', 100_000, 50_000));
        self::assertGreaterThan(0.0, CompanionTelemetry::estimateCostUsd('gemini', 'gemini-2.5-flash', 100_000, 50_000));
        self::assertGreaterThan(0.0, CompanionTelemetry::estimateCostUsd('groq', 'llama-3.3-70b-versatile', 100_000, 50_000));

        // Merge accumulates tokens, latency, and USD cost while preserving budget cap
        $a = CompanionTelemetry::fromUsage('openai', 'gpt-4.1-mini', new TokenUsage(1_000, 500), 100.0, 0.05);
        $b = CompanionTelemetry::fromUsage('openai', 'gpt-4.1-mini', new TokenUsage(2_000, 1_500), 150.0, 0.05);
        $merged = $a->merge($b);
        self::assertSame(3_000, $merged->inputTokens);
        self::assertSame(2_000, $merged->outputTokens);
        self::assertSame(5_000, $merged->totalTokens());
        self::assertSame(250.0, $merged->durationMs);
        self::assertSame(0.05, $merged->maxCostUsd);
        self::assertStringContainsString('cap $0.0500', $merged->formatFooter());
        self::assertSame($merged->formatFooter(), $merged->toArray()['footer']);

        // Hard budget enforcement blocks Oshie, Hantei, and Chie when cost exceeds maxCostUsd
        $ctx = ProjectContext::discover($this->starterAppRoot);
        $expensiveResponse = new HttpResponse(200, json_encode([
            'model' => 'gpt-4.1',
            'choices' => [['message' => ['role' => 'assistant', 'content' => 'Expensive reply']]],
            'usage' => ['prompt_tokens' => 500_000, 'completion_tokens' => 100_000],
        ], JSON_THROW_ON_ERROR));

        $aiOshie = new AiClient(new OpenAiProvider('sk-test', transport: new ScriptedTransport($expensiveResponse)));
        try {
            (new OshieTutor($aiOshie))->askWithTelemetry('Explain routing', $ctx, maxCostUsd: 0.01);
            self::fail('Expected Oshie to enforce hard maxCostUsd budget.');
        } catch (CoreException $e) {
            self::assertStringContainsString('exceeded configured cost budget', $e->getMessage());
        }

        $aiHantei = new AiClient(new OpenAiProvider('sk-test', transport: new ScriptedTransport($expensiveResponse)));
        try {
            (new HanteiDebugger($aiHantei))->diagnoseWithTelemetry('500 error', $ctx, maxCostUsd: 0.01);
            self::fail('Expected Hantei to enforce hard maxCostUsd budget.');
        } catch (CoreException $e) {
            self::assertStringContainsString('exceeded configured cost budget', $e->getMessage());
        }
    }

    public function testLiveStreamingTraceAwareOshieAndHanteiInCliAndDevStudio(): void
    {
        $sseFrames = static fn(array $tokens, int $inTok = 200, int $outTok = 40): array => array_merge(
            array_map(
                static fn(string $t): string => 'data: ' . json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['delta' => ['content' => $t]]],
                ], JSON_THROW_ON_ERROR) . "\n\n",
                $tokens,
            ),
            [
                'data: ' . json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['delta' => [], 'finish_reason' => 'stop']],
                    'usage' => ['prompt_tokens' => $inTok, 'completion_tokens' => $outTok],
                ], JSON_THROW_ON_ERROR) . "\n\n",
                "data: [DONE]\n\n",
            ],
        );

        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_stream_' . bin2hex(random_bytes(4));
        try {
            (new AppScaffolder())->scaffold($tmp, 'Stream Demo');
            $ctx = ProjectContext::discover($tmp);

            // Record a real slow request with N+1 SQL queries in DevInspector
            $inspector = new DevInspector(nPlusOneThreshold: 3);
            $inspector->beginRequest();
            for ($i = 1; $i <= 4; ++$i) {
                $inspector->recordQuery('SELECT * FROM notes WHERE user_id = ' . $i, 12.5);
            }
            $inspector->recordRequest(
                method: 'GET',
                path: '/api/notes',
                status: 500,
                durationMs: 245.5,
                error: 'Timeout with bearer gsk_1234567890abcdefghijKLMN',
                query: 'page=1',
            );

            // 1. Oshie live streaming + automatic live trace awareness + CLI --stream
            $oshieTransport = new ScriptedStreamingTransport(
                $sseFrames(['Use ', 'BatchLoader::hasMany()', ' to fix N+1!']),
                $sseFrames(['Streamed ', 'CLI ', 'answer.']),
            );
            $oshieAi = new AiClient(new OpenAiProvider('sk-test', transport: $oshieTransport));
            $receivedDeltas = [];
            $oshieRes = (new OshieTutor($oshieAi))->askStream(
                'Why is /api/notes slow?',
                $ctx,
                static function (string $delta) use (&$receivedDeltas): void {
                    $receivedDeltas[] = $delta;
                },
                $inspector->traces(),
            );
            self::assertNotEmpty($receivedDeltas);
            self::assertSame('Use BatchLoader::hasMany() to fix N+1!', implode('', $receivedDeltas));
            self::assertSame('Use BatchLoader::hasMany() to fix N+1!', $oshieRes['reply']);
            self::assertNotNull($oshieRes['telemetry']);
            self::assertSame(240, $oshieRes['telemetry']->totalTokens());
            // Verify trace summary & N+1 warning were injected inside UntrustedInput and scrubbed
            self::assertStringContainsString('/api/notes', $oshieTransport->requests[0]);
            self::assertStringContainsString('n+1=1', $oshieTransport->requests[0]);
            self::assertStringNotContainsString('gsk_1234567890abcdefghijKLMN', $oshieTransport->requests[0]);

            $cliChunks = [];
            $cli = new KongoCli(
                dirname(__DIR__, 3),
                aiOverride: $oshieAi,
                streamWriter: static function (string $c) use (&$cliChunks): void {
                    $cliChunks[] = $c;
                },
            );
            [$oCode, $oOut] = $cli->run(['oshie', 'Explain BatchLoader', '--stream', '--app=' . $tmp]);
            self::assertSame(0, $oCode);
            self::assertSame('Streamed CLI answer.', implode('', $cliChunks));
            self::assertStringContainsString('[openai/gpt-4.1-mini', $oOut);

            // 2. Hantei CLI --stream
            $hanteiTransport = new ScriptedStreamingTransport(
                $sseFrames(['Fix ', 'SQL ', 'index.']),
            );
            $hanteiChunks = [];
            $hCli = new KongoCli(
                dirname(__DIR__, 3),
                aiOverride: new AiClient(new OpenAiProvider('sk-test', transport: $hanteiTransport)),
                streamWriter: static function (string $c) use (&$hanteiChunks): void {
                    $hanteiChunks[] = $c;
                },
            );
            [$hCode, $hOut] = $hCli->run(['hantei', 'debug', 'Slow query on notes', '--stream', '--app=' . $tmp]);
            self::assertSame(0, $hCode);
            self::assertSame('Fix SQL index.', implode('', $hanteiChunks));
            self::assertStringContainsString('[openai/gpt-4.1-mini', $hOut);

            // 3. Dev Studio SSE endpoints + POST /_kongono/api/hantei/trace/{id}
            $studioTransport = new ScriptedStreamingTransport(
                $sseFrames(['Oshie ', 'SSE ', 'works!']),
                $sseFrames(['Hantei ', 'SSE ', 'works!']),
            );
            $studioAi = new AiClient(new OpenAiProvider('sk-test', transport: $studioTransport));
            $studioApp = App::create($tmp, name: 'Studio SSE')->config(['app' => ['debug' => true]]);
            $studioApp->middleware(DevStudioCompanions::middleware($studioApp, $inspector, $tmp, aiOverride: $studioAi));
            $client = new TestClient($studioApp);

            $oshieSse = $client->postJson('/_kongono/api/oshie/stream', ['question' => 'Why slow?']);
            $oshieSse->assertOk();
            self::assertStringContainsString('text/event-stream', $oshieSse->header('Content-Type'));
            self::assertStringContainsString("event: delta\ndata: {\"text\":\"Oshie SSE works!\"}", $oshieSse->body());
            self::assertStringContainsString("event: done\n", $oshieSse->body());
            self::assertStringContainsString('"total_tokens":240', $oshieSse->body());

            $hanteiSse = $client->postJson('/_kongono/api/hantei/stream', ['diagnostic' => 'HTTP 500 on /api/notes']);
            $hanteiSse->assertOk();
            self::assertStringContainsString('text/event-stream', $hanteiSse->header('Content-Type'));
            self::assertStringContainsString("event: delta\ndata: {\"text\":\"Hantei SSE works!\"}", $hanteiSse->body());
            self::assertStringContainsString("event: done\n", $hanteiSse->body());

            $traceDiag = $client->postJson('/_kongono/api/hantei/trace/1', []);
            $traceDiag->assertOk();
            $traceJson = $traceDiag->json();
            self::assertIsArray($traceJson);
            self::assertSame(1, $traceJson['trace_id']);
            self::assertStringContainsString('Fallback non-stream reply.', (string) $traceJson['diagnosis']);
            self::assertStringContainsString('TestClient', (string) $traceJson['regression_test']);
            self::assertStringContainsString('testReplayGetApiNotesTrace1', (string) $traceJson['regression_test']);
            self::assertStringContainsString('/api/notes', (string) $traceJson['regression_test']);
            self::assertIsArray($traceJson['telemetry']);

            $client->postJson('/_kongono/api/hantei/trace/999', [])->assertNotFound();
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testChieSelfVerifyingBuildLoopRepairsAndRollsBackAtomically(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_verify_' . bin2hex(random_bytes(4));
        try {
            (new AppScaffolder())->scaffold($tmp, 'Verify Demo');
            $ctx = ProjectContext::discover($tmp);

            // Create a deterministic PHP verification script asserting src/Calc.php returns 42
            file_put_contents(
                $tmp . '/verify_calc.php',
                "<?php\nrequire __DIR__ . '/src/Calc.php';\nif (App\\Calc::answer() !== 42) { fwrite(STDERR, 'Expected 42, got ' . App\\Calc::answer() . ' leak=gsk_1234567890abcdefghijKLMN'); exit(1); }\necho 'OK 42';\n",
            );

            $planJson = static fn(string $summary, array $changes): HttpResponse => new HttpResponse(200, json_encode([
                'model' => 'gpt-4.1-mini',
                'choices' => [['message' => [
                    'role' => 'assistant',
                    'content' => json_encode([
                        'summary' => $summary,
                        'changes' => $changes,
                        'next_steps' => ['Done'],
                    ], JSON_THROW_ON_ERROR),
                ]]],
                'usage' => ['prompt_tokens' => 300, 'completion_tokens' => 120],
            ], JSON_THROW_ON_ERROR));

            // 1. Attempt 1 returns 41 -> fails check -> Attempt 2 receives scrubbed failure and fixes to 42!
            $repairTransport = new ScriptedTransport(
                $planJson('Initial Calc', [[
                    'path' => 'src/Calc.php',
                    'action' => 'create',
                    'explanation' => 'Creates Calc',
                    'content' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nfinal readonly class Calc { public static function answer(): int { return 41; } }\n",
                ]]),
                $planJson('Fixed Calc to return 42', [[
                    'path' => 'src/Calc.php',
                    'action' => 'update',
                    'explanation' => 'Fixes return value to 42',
                    'content' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nfinal readonly class Calc { public static function answer(): int { return 42; } }\n",
                ]]),
            );
            $repairAi = new AiClient(new OpenAiProvider('sk-test', transport: $repairTransport));
            $report = (new ChieBuilder($repairAi))->buildAndVerify(
                instruction: 'Add App\\Calc::answer() returning 42',
                context: $ctx,
                hantei: new HanteiDebugger($repairAi),
                verifyCommand: [PHP_BINARY, $tmp . '/verify_calc.php'],
                maxRepairs: 2,
            );

            self::assertTrue($report['verified']);
            self::assertFalse($report['rolled_back']);
            self::assertSame(2, $report['attempts']);
            self::assertSame(840, $report['telemetry']->totalTokens());
            // Secret from stderr must have been scrubbed before being sent to Chie attempt 2!
            self::assertStringContainsString('Expected 42, got 41', $repairTransport->requests[1]);
            self::assertStringNotContainsString('gsk_1234567890abcdefghijKLMN', $repairTransport->requests[1]);
            self::assertStringContainsString('[REDACTED]', $repairTransport->requests[1]);

            // 2. MigrationLinter gate catches dangerous migration DDL during --verify and triggers repair
            $migTransport = new ScriptedTransport(
                $planJson('Unsafe migration', [[
                    'path' => 'migrations/002_bad.php',
                    'action' => 'create',
                    'explanation' => 'Adds NOT NULL column without default',
                    'content' => "<?php\n\ndeclare(strict_types=1);\n\$sql = 'ALTER TABLE users ADD COLUMN role TEXT NOT NULL;';\nreturn \$sql;\n",
                ]]),
                $planJson('Safe migration', [[
                    'path' => 'migrations/002_bad.php',
                    'action' => 'update',
                    'explanation' => 'Safe additive migration with default',
                    'content' => "<?php\n\ndeclare(strict_types=1);\n\$sql = 'ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT \"member\";';\nreturn \$sql;\n",
                ]]),
            );
            $migAi = new AiClient(new OpenAiProvider('sk-test', transport: $migTransport));
            $migReport = (new ChieBuilder($migAi))->buildAndVerify(
                instruction: 'Add migration',
                context: $ctx,
                hantei: new HanteiDebugger($migAi),
                verifyCommand: [PHP_BINARY, '-r', 'echo "PASS";'],
                maxRepairs: 2,
            );
            self::assertTrue($migReport['verified']);
            self::assertSame(2, $migReport['attempts']);
            self::assertStringContainsString('Migration lint errors', $migTransport->requests[1]);

            // 3. Atomic rollback restores pre-existing file & deletes newly created file when repairs are exhausted
            $originalKeep = "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nfinal readonly class Keep { public const int V = 1; }\n";
            file_put_contents($tmp . '/src/Keep.php', $originalKeep);

            $failTransport = new ScriptedTransport(
                $planJson('Attempt 1', [
                    ['path' => 'src/Keep.php', 'action' => 'update', 'explanation' => 'Break Keep', 'content' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nfinal readonly class Keep { public const int V = 99; }\n"],
                    ['path' => 'src/TempNew.php', 'action' => 'create', 'explanation' => 'Temp file', 'content' => "<?php\n\ndeclare(strict_types=1);\n"],
                ]),
                $planJson('Attempt 2 still fails', [
                    ['path' => 'src/Keep.php', 'action' => 'update', 'explanation' => 'Still broken', 'content' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nfinal readonly class Keep { public const int V = 100; }\n"],
                ]),
            );
            $failAi = new AiClient(new OpenAiProvider('sk-test', transport: $failTransport));
            $failReport = (new ChieBuilder($failAi))->buildAndVerify(
                instruction: 'Update Keep',
                context: $ctx,
                hantei: new HanteiDebugger($failAi),
                verifyCommand: [PHP_BINARY, '-r', 'fwrite(STDERR, "Still failing"); exit(1);'],
                maxRepairs: 1,
                rollbackOnFailure: true,
            );
            self::assertFalse($failReport['verified']);
            self::assertTrue($failReport['rolled_back']);
            self::assertSame(2, $failReport['attempts']);
            self::assertSame($originalKeep, file_get_contents($tmp . '/src/Keep.php'));
            self::assertFileDoesNotExist($tmp . '/src/TempNew.php');

            // 4. CLI requires --apply when --verify is passed
            $cli = new KongoCli(dirname(__DIR__, 3), $failAi);
            [$badCode, $badOut] = $cli->run(['chie', 'Add endpoint', '--verify', '--app=' . $tmp]);
            self::assertSame(1, $badCode);
            self::assertStringContainsString('--verify requires --apply', $badOut);
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testChieInteractiveApprovalAndHanteiQueueFailed(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_int_' . bin2hex(random_bytes(4));
        try {
            (new AppScaffolder())->scaffold($tmp, 'Interactive Demo');

            $threeFilesPlan = static fn(): HttpResponse => new HttpResponse(200, json_encode([
                'model' => 'gpt-4.1-mini',
                'choices' => [['message' => [
                    'role' => 'assistant',
                    'content' => json_encode([
                        'summary' => 'Proposed three files',
                        'changes' => [
                            ['path' => 'src/Alpha.php', 'action' => 'create', 'explanation' => 'Alpha', 'content' => "<?php\n\ndeclare(strict_types=1);\n"],
                            ['path' => 'src/Beta.php', 'action' => 'create', 'explanation' => 'Beta', 'content' => "<?php\n\ndeclare(strict_types=1);\n"],
                            ['path' => 'src/Gamma.php', 'action' => 'create', 'explanation' => 'Gamma', 'content' => "<?php\n\ndeclare(strict_types=1);\n"],
                        ],
                        'next_steps' => ['Run tests'],
                    ], JSON_THROW_ON_ERROR),
                ]]],
                'usage' => ['prompt_tokens' => 150, 'completion_tokens' => 80],
            ], JSON_THROW_ON_ERROR));

            // 1. Selective interactive approval: y (Alpha), n (Beta), a (Gamma -> all remaining)
            $answers = ['y', 'n', 'a'];
            $promptsSeen = [];
            $cli = new KongoCli(
                dirname(__DIR__, 3),
                aiOverride: new AiClient(new OpenAiProvider('sk-test', transport: new ScriptedTransport($threeFilesPlan(), $threeFilesPlan()))),
                promptReader: static function (string $prompt) use (&$answers, &$promptsSeen): string {
                    $promptsSeen[] = $prompt;

                    return array_shift($answers) ?? 'q';
                },
            );

            [$iCode, $iOut] = $cli->run(['chie', 'Create three helpers', '--interactive', '--app=' . $tmp]);
            self::assertSame(0, $iCode);
            self::assertCount(3, $promptsSeen);
            self::assertFileExists($tmp . '/src/Alpha.php');
            self::assertFileDoesNotExist($tmp . '/src/Beta.php');
            self::assertFileExists($tmp . '/src/Gamma.php');
            self::assertStringContainsString('[WRITTEN] (create) src/Alpha.php', $iOut);
            self::assertStringContainsString('[SKIPPED] (create) src/Beta.php', $iOut);
            self::assertStringContainsString('[WRITTEN] (create) src/Gamma.php', $iOut);
            self::assertStringContainsString('Applied 2 of 3 proposed file(s).', $iOut);
            self::assertStringContainsString('[openai/gpt-4.1-mini', $iOut);

            // 2. Pressing [q]uit aborts cleanly with zero files written
            @unlink($tmp . '/src/Alpha.php');
            @unlink($tmp . '/src/Gamma.php');
            $answers = ['q'];
            [$qCode, $qOut] = $cli->run(['chie', 'Create three helpers', '--interactive', '--app=' . $tmp]);
            self::assertSame(0, $qCode);
            self::assertStringContainsString('Aborted by user', $qOut);
            self::assertFileDoesNotExist($tmp . '/src/Alpha.php');

            // 3. Hantei queue-failed diagnoses dead-lettered background jobs and scrubs payload secrets
            $queueAppDir = $tmp . DIRECTORY_SEPARATOR . 'queue_app';
            mkdir($queueAppDir . '/app', 0o775, true);
            $bootstrapPhp = <<<'PHP'
<?php
declare(strict_types=1);

use Kongono\Http\App;
use Kongono\Jobs\JobStatus;
use Kongono\Jobs\MemoryQueueDriver;
use Kongono\Jobs\QueueDriverInterface;

$driver = new MemoryQueueDriver();
$job = $driver->push('emails', 'App\\Jobs\\SendInvoiceJob', [
    'invoice_id' => 42,
    'api_key' => 'gsk_1234567890abcdefghijKLMN',
], maxAttempts: 1);
$reserved = $driver->reserve('emails');
if (null !== $reserved) {
    $driver->markFailed($reserved, JobStatus::Dead, 'SMTP 535 Authentication failed with secret gsk_1234567890abcdefghijKLMN');
}

$app = App::create(__DIR__ . '/..', name: 'Queue Demo');
$app->container()->instance(QueueDriverInterface::class, $driver);
return $app;
PHP;
            file_put_contents($queueAppDir . '/app/bootstrap.php', $bootstrapPhp);

            $qfTransport = new ScriptedTransport(
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => 'Root cause: invalid SMTP credentials in Mailer transport.']]],
                    'usage' => ['prompt_tokens' => 180, 'completion_tokens' => 45],
                ], JSON_THROW_ON_ERROR)),
            );
            $qCli = new KongoCli(
                dirname(__DIR__, 3),
                aiOverride: new AiClient(new OpenAiProvider('sk-test', transport: $qfTransport)),
            );

            [$qfCode, $qfOut] = $qCli->run(['hantei', 'queue-failed', '--queue=emails', '--app=' . $queueAppDir]);
            self::assertSame(0, $qfCode);
            self::assertStringContainsString('SendInvoiceJob', $qfOut);
            self::assertStringContainsString('invalid SMTP credentials', $qfOut);
            self::assertStringContainsString('[openai/gpt-4.1-mini', $qfOut);
            // Secret must NEVER leak to AI provider or stdout
            self::assertStringNotContainsString('gsk_1234567890abcdefghijKLMN', $qfTransport->requests[0]);
            self::assertStringNotContainsString('gsk_1234567890abcdefghijKLMN', $qfOut);
            self::assertStringContainsString('[REDACTED]', $qfTransport->requests[0]);

            // Empty queue returns cleanly
            [$emptyCode, $emptyOut] = $qCli->run(['hantei', 'queue-failed', '--queue=default', '--app=' . $queueAppDir]);
            self::assertSame(0, $emptyCode);
            self::assertStringContainsString('No failed jobs found', $emptyOut);

            // Unknown explicit job ID returns exit 1
            [$missCode, $missOut] = $qCli->run(['hantei', 'queue-failed', 'missing-id', '--queue=emails', '--app=' . $queueAppDir]);
            self::assertSame(1, $missCode);
            self::assertStringContainsString('not found', $missOut);
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testSearchCodeLineReadsAndLiveSchemaToolsForChieAndHantei(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_tools_' . bin2hex(random_bytes(4));
        try {
            (new AppScaffolder())->scaffold($tmp, 'Tools Demo');
            file_put_contents($tmp . '/.env', "SECRET_KEY=gsk_1234567890abcdefghijKLMN\n");
            file_put_contents(
                $tmp . '/src/InvoiceService.php',
                "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nfinal readonly class InvoiceService\n{\n    public function calculateTotal(int \$cents): int\n    {\n        // secret=gsk_1234567890abcdefghijKLMN\n        return \$cents + 100;\n    }\n}\n",
            );

            // Wire a ConnectionInterface returning SQLite schema metadata (tables, columns, indexes — strictly zero row queries!)
            $db = $this->fakeSqliteSchemaConnection([
                'invoices' => [
                    'columns' => [
                        ['name' => 'id', 'type' => 'INTEGER', 'notnull' => 1, 'dflt_value' => null, 'pk' => 1],
                        ['name' => 'amount_cents', 'type' => 'INTEGER', 'notnull' => 1, 'dflt_value' => null, 'pk' => 0],
                        ['name' => 'status', 'type' => 'TEXT', 'notnull' => 1, 'dflt_value' => "'draft'", 'pk' => 0],
                    ],
                    'indexes' => [['name' => 'idx_invoices_status']],
                ],
            ]);

            $app = App::create($tmp, name: 'Tools Demo');
            $app->container()->instance(\Kongono\Database\ConnectionInterface::class, $db);
            $ctx = new ProjectContext($tmp, $app->graph(), $app);

            // 1. ProjectContext::searchCode skips .env, scrubs secrets, and respects directory filter
            $hits = $ctx->searchCode('calculateTotal', 'src');
            self::assertCount(1, $hits);
            self::assertSame('src/InvoiceService.php', $hits[0]['file']);
            self::assertSame(9, $hits[0]['line']);

            // Raw secret value MUST NOT be searchable as an existence oracle (scrubbed pre-search)
            self::assertSame([], $ctx->searchCode('gsk_1234567890abcdefghijKLMN'));
            // Searching surrounding label skips .env and returns scrubbed src match
            $secretHits = $ctx->searchCode('secret=');
            self::assertCount(1, $secretHits);
            self::assertSame('src/InvoiceService.php', $secretHits[0]['file']);
            self::assertStringContainsString('[REDACTED]', $secretHits[0]['text']);
            self::assertStringNotContainsString('gsk_1234567890abcdefghijKLMN', $secretHits[0]['text']);

            // 2. readFileLines returns numbered bounded range
            $slice = $ctx->readFileLines('src/InvoiceService.php', 7, 13);
            self::assertStringContainsString('7: final readonly class InvoiceService', $slice);
            self::assertStringContainsString('12:         return $cents + 100;', $slice);
            self::assertStringNotContainsString('gsk_1234567890abcdefghijKLMN', $slice);

            // 3. inspectSchema exposes metadata (tables/columns/indexes) with ZERO row data!
            $schema = $ctx->inspectSchema();
            self::assertSame('sqlite', $schema['dialect']);
            self::assertArrayHasKey('invoices', $schema['tables']);
            $colNames = array_column($schema['tables']['invoices']['columns'], 'name');
            self::assertSame(['id', 'amount_cents', 'status'], $colNames);
            self::assertContains('idx_invoices_status', $schema['tables']['invoices']['indexes']);
            self::assertStringNotContainsString('TOP_SECRET_CUSTOMER_ROW', json_encode($schema, JSON_THROW_ON_ERROR));

            // 4. Hantei autonomously invokes search_code -> read_file(start_line, end_line) -> inspect_schema
            $hanteiTransport = new ScriptedTransport(
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => [
                        'role' => 'assistant',
                        'content' => '',
                        'tool_calls' => [
                            ['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'search_code', 'arguments' => '{"query":"calculateTotal","directory":"src"}']],
                            ['id' => 'c2', 'type' => 'function', 'function' => ['name' => 'read_file', 'arguments' => '{"path":"src/InvoiceService.php","start_line":8,"end_line":14}']],
                            ['id' => 'c3', 'type' => 'function', 'function' => ['name' => 'inspect_schema', 'arguments' => '{}']],
                        ],
                    ]]],
                    'usage' => ['prompt_tokens' => 200, 'completion_tokens' => 40],
                ], JSON_THROW_ON_ERROR)),
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => 'Root cause: InvoiceService.php:12 adds 100 to amount_cents.']]],
                    'usage' => ['prompt_tokens' => 350, 'completion_tokens' => 30],
                ], JSON_THROW_ON_ERROR)),
            );
            $hanteiRes = (new HanteiDebugger(new AiClient(new OpenAiProvider('sk-test', transport: $hanteiTransport))))
                ->diagnoseWithTelemetry('Assertion failed: InvoiceService::calculateTotal off by 100', $ctx);

            self::assertSame(['search_code', 'read_file', 'inspect_schema'], $hanteiRes['tools_used']);
            self::assertStringContainsString('InvoiceService.php:12', $hanteiRes['diagnosis']);
            self::assertStringContainsString('amount_cents', $hanteiTransport->requests[1]);
            self::assertStringContainsString('idx_invoices_status', $hanteiTransport->requests[1]);
            self::assertStringNotContainsString('TOP_SECRET_CUSTOMER_ROW', $hanteiTransport->requests[1]);

            // 5. Chie can also invoke inspect_schema and search_code before emitting a plan
            $chieTransport = new ScriptedTransport(
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => [
                        'role' => 'assistant',
                        'content' => '',
                        'tool_calls' => [
                            ['id' => 'ch1', 'type' => 'function', 'function' => ['name' => 'inspect_schema', 'arguments' => '{}']],
                        ],
                    ]]],
                    'usage' => ['prompt_tokens' => 150, 'completion_tokens' => 20],
                ], JSON_THROW_ON_ERROR)),
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => json_encode([
                        'summary' => 'Added InvoiceRepository matching invoices table',
                        'changes' => [[
                            'path' => 'src/InvoiceRepository.php',
                            'action' => 'create',
                            'explanation' => 'Queries invoices.amount_cents',
                            'content' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nfinal readonly class InvoiceRepository {}\n",
                        ]],
                        'next_steps' => [],
                    ], JSON_THROW_ON_ERROR)]]],
                    'usage' => ['prompt_tokens' => 250, 'completion_tokens' => 60],
                ], JSON_THROW_ON_ERROR)),
            );
            $chiePlan = (new ChieBuilder(new AiClient(new OpenAiProvider('sk-test', transport: $chieTransport))))
                ->build('Create InvoiceRepository', $ctx, apply: false);
            self::assertSame('Added InvoiceRepository matching invoices table', $chiePlan['summary']);
            self::assertStringContainsString('amount_cents', $chieTransport->requests[1]);
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testMultiGateVerifyHanteiFixAndHanteiReview(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_gates_' . bin2hex(random_bytes(4));
        try {
            (new AppScaffolder())->scaffold($tmp, 'Gates Demo');
            $ctx = ProjectContext::discover($tmp);

            $planResp = static fn(string $summary, array $changes): HttpResponse => new HttpResponse(200, json_encode([
                'model' => 'gpt-4.1-mini',
                'choices' => [['message' => [
                    'role' => 'assistant',
                    'content' => json_encode([
                        'summary' => $summary,
                        'changes' => $changes,
                        'next_steps' => [],
                    ], JSON_THROW_ON_ERROR),
                ]]],
                'usage' => ['prompt_tokens' => 200, 'completion_tokens' => 90],
            ], JSON_THROW_ON_ERROR));

            // 1. Multi-gate buildAndVerify: extra gate (`phpstan-sim`) fails attempt 1 -> Chie repairs -> all gates PASS
            file_put_contents(
                $tmp . '/check_strict.php',
                "<?php\n\$src = (string) file_get_contents(__DIR__ . '/src/Math.php');\nif (!str_contains(\$src, ': int')) { fwrite(STDERR, 'Missing strict int return type in src/Math.php'); exit(1); }\necho 'STRICT OK';\n",
            );
            $multiTransport = new ScriptedTransport(
                $planResp('Untyped Math', [[
                    'path' => 'src/Math.php',
                    'action' => 'create',
                    'explanation' => 'Initial Math',
                    'content' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nfinal readonly class Math { public static function add(int \$a, int \$b) { return \$a + \$b; } }\n",
                ]]),
                $planResp('Strictly typed Math', [[
                    'path' => 'src/Math.php',
                    'action' => 'update',
                    'explanation' => 'Adds : int return type',
                    'content' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nfinal readonly class Math { public static function add(int \$a, int \$b): int { return \$a + \$b; } }\n",
                ]]),
            );
            $multiAi = new AiClient(new OpenAiProvider('sk-test', transport: $multiTransport));
            $multiReport = (new ChieBuilder($multiAi))->buildAndVerify(
                instruction: 'Create App\\Math::add()',
                context: $ctx,
                hantei: new HanteiDebugger($multiAi),
                verifyCommand: [PHP_BINARY, '-r', 'echo "TESTS OK";'],
                maxRepairs: 2,
                extraVerifyCommands: [['name' => 'phpstan', 'command' => [PHP_BINARY, $tmp . '/check_strict.php']]],
                runSecurityAudit: true,
            );
            self::assertTrue($multiReport['verified']);
            self::assertSame(2, $multiReport['attempts']);
            $gateNames = array_column($multiReport['gates'], 'name');
            self::assertSame(['syntax', 'phpstan', 'tests', 'security-audit'], $gateNames);

            // 2. Hantei -> Chie closed-loop diagnoseAndFix (`kongo hantei fix`)
            file_put_contents(
                $tmp . '/src/Discount.php',
                "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nfinal readonly class Discount { public static function apply(int \$price): int { return \$price + 10; } }\n",
            );
            file_put_contents(
                $tmp . '/verify_discount.php',
                "<?php\nrequire __DIR__ . '/src/Discount.php';\nif (App\\Discount::apply(100) !== 90) { fwrite(STDERR, 'Expected 90, got ' . App\\Discount::apply(100)); exit(1); }\n",
            );
            $fixTransport = new ScriptedTransport(
                // Hantei diagnosis call
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => 'Root cause: src/Discount.php adds 10 instead of subtracting 10.']]],
                    'usage' => ['prompt_tokens' => 150, 'completion_tokens' => 40],
                ], JSON_THROW_ON_ERROR)),
                // Chie fix call
                $planResp('Subtract discount instead of adding', [[
                    'path' => 'src/Discount.php',
                    'action' => 'update',
                    'explanation' => 'Fix sign in Discount::apply()',
                    'content' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App;\n\nfinal readonly class Discount { public static function apply(int \$price): int { return \$price - 10; } }\n",
                ]]),
            );
            $fixAi = new AiClient(new OpenAiProvider('sk-test', transport: $fixTransport));
            $fixReport = (new HanteiDebugger($fixAi))->diagnoseAndFix(
                failureText: 'Expected 90, got 110 in Discount::apply(100)',
                context: $ctx,
                chie: new ChieBuilder($fixAi),
                verifyCommand: [PHP_BINARY, $tmp . '/verify_discount.php'],
                apply: true,
                verify: true,
            );
            self::assertTrue($fixReport['verified']);
            self::assertStringContainsString('adds 10 instead of subtracting 10', $fixReport['diagnosis']);
            self::assertSame(['src/Discount.php'], $fixReport['applied']);
            self::assertGreaterThan(0, $fixReport['telemetry']->totalTokens());

            // 3. Hantei reviewDiff catches mutable static leaks, superglobals, legacy APIs, and hardcoded secrets + emits Markdown & SARIF 2.1.0
            $badDiff = <<<'DIFF'
diff --git a/src/Http/LeakyController.php b/src/Http/LeakyController.php
--- /dev/null
+++ b/src/Http/LeakyController.php
@@ -0,0 +1,8 @@
+<?php
+declare(strict_types=1);
+final class LeakyController {
+    private static array $requestCache = [];
+    public function handle(): void {
+        $x = $_POST['id'];
+        $k = 'gsk_1234567890abcdefghijKLMN';
+        Route::get('/bad', fn() => $x . $k);
+    }
+}
DIFF;
            $reviewAi = new AiClient(new OpenAiProvider('sk-test', transport: new ScriptedTransport(
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => 'Critical: Remove mutable static $requestCache to stay FrankenPHP-safe.']]],
                    'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 30],
                ], JSON_THROW_ON_ERROR)),
            )));
            $reviewReport = (new HanteiDebugger($reviewAi))->reviewDiff($badDiff, $ctx);
            self::assertFalse($reviewReport['passed']);
            $rules = array_column($reviewReport['findings'], 'rule');
            self::assertContains('KGN-REV-MUTABLE-STATIC', $rules);
            self::assertContains('KGN-REV-SUPERGLOBAL', $rules);
            self::assertContains('KGN-REV-SECRET-LEAK', $rules);
            self::assertContains('KGN-REV-LEGACY-API', $rules);

            $md = HanteiDebugger::formatReviewMarkdown($reviewReport);
            self::assertStringContainsString('## Hantei Code Review — FAILED', $md);
            self::assertStringContainsString('FrankenPHP-safe', $md);

            $sarif = json_decode(HanteiDebugger::formatReviewSarif($reviewReport), true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($sarif);
            self::assertSame('2.1.0', $sarif['version']);
            self::assertSame('Kongono Hantei', $sarif['runs'][0]['tool']['driver']['name']);
            self::assertGreaterThanOrEqual(4, count($sarif['runs'][0]['results']));

            // Clean diff passes offline with zero findings
            $cleanReport = (new HanteiDebugger(null))->reviewDiff("+++ b/src/Clean.php\n@@ -0,0 +1,2 @@\n+<?php\n+declare(strict_types=1);\n", $ctx);
            self::assertTrue($cleanReport['passed']);
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testPersistentCompanionSessionsMcpToolsAndCompanionEvals(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_sess_' . bin2hex(random_bytes(4));
        try {
            (new AppScaffolder())->scaffold($tmp, 'Sessions Demo');

            // 1. Oshie progress persistence + tailored recommendations
            $offlineCli = new KongoCli(dirname(__DIR__, 3));
            [$lCode] = $offlineCli->run(['oshie', '--lesson', 'routing', '--app=' . $tmp]);
            self::assertSame(0, $lCode);
            self::assertFileExists($tmp . '/var/kongono/companions/.gitignore');

            [$pCode, $pOut] = $offlineCli->run(['oshie', '--progress', '--app=' . $tmp]);
            self::assertSame(0, $pCode);
            self::assertStringContainsString('(1/13 lessons, 0/6 exercises verified)', $pOut);
            self::assertStringContainsString('[DONE] routing', $pOut);
            self::assertStringContainsString('[TODO] views', $pOut);
            self::assertStringContainsString('Recommended next exercises', $pOut);

            // 2. Chie --continue loads prior session context + fails cleanly when no prior session exists
            $contTransport = new ScriptedTransport(
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => json_encode([
                        'summary' => 'Created StatsController',
                        'changes' => [['path' => 'src/StatsController.php', 'action' => 'create', 'explanation' => 'Initial stats', 'content' => "<?php\n\ndeclare(strict_types=1);\n"]],
                        'next_steps' => [],
                    ], JSON_THROW_ON_ERROR)]]],
                    'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 50],
                ], JSON_THROW_ON_ERROR)),
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => json_encode([
                        'summary' => 'Added rate limiting to StatsController',
                        'changes' => [['path' => 'src/StatsController.php', 'action' => 'update', 'explanation' => 'Added throttle', 'content' => "<?php\n\ndeclare(strict_types=1);\n"]],
                        'next_steps' => [],
                    ], JSON_THROW_ON_ERROR)]]],
                    'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 60],
                ], JSON_THROW_ON_ERROR)),
            );
            $contCli = new KongoCli(dirname(__DIR__, 3), new AiClient(new OpenAiProvider('sk-test', transport: $contTransport)));

            // Before first run, --continue returns exit 1 cleanly
            [$noPrevCode, $noPrevOut] = $contCli->run(['chie', 'Add pagination', '--continue', '--app=' . $tmp]);
            self::assertSame(1, $noPrevCode);
            self::assertStringContainsString('No previous Chie session', $noPrevOut);

            // First run saves session, second run with --continue injects prior plan summary
            [$c1Code] = $contCli->run(['chie', 'Build /api/stats', '--app=' . $tmp]);
            self::assertSame(0, $c1Code);
            [$c2Code, $c2Out] = $contCli->run(['chie', 'Now add rate limiting', '--continue', '--app=' . $tmp]);
            self::assertSame(0, $c2Code);
            self::assertStringContainsString('Added rate limiting', $c2Out);
            self::assertStringContainsString('Created StatsController', $contTransport->requests[1]);

            // 3. Hantei history records secret-scrubbed diagnoses
            [$hdCode] = $offlineCli->run(['hantei', 'debug', 'Failed with gsk_1234567890abcdefghijKLMN', '--app=' . $tmp]);
            self::assertSame(0, $hdCode);
            [$hhCode, $hhOut] = $offlineCli->run(['hantei', 'history', '--app=' . $tmp]);
            self::assertSame(0, $hhCode);
            self::assertStringContainsString('(debug)', $hhOut);
            self::assertStringContainsString('[REDACTED]', $hhOut);
            self::assertStringNotContainsString('gsk_1234567890abcdefghijKLMN', $hhOut);

            // Verify CompanionSessionStore directly
            $store = new CompanionSessionStore($tmp);
            self::assertNotNull($store->lastChieSession());
            self::assertCount(1, $store->hanteiHistory());

            // 4. New MCP tools: kongono_inspect_schema, kongono_hantei_diagnose, kongono_chie_preview
            $db = $this->fakeSqliteSchemaConnection([
                'widgets' => [
                    'columns' => [
                        ['name' => 'id', 'type' => 'INTEGER', 'notnull' => 1, 'dflt_value' => null, 'pk' => 1],
                        ['name' => 'slug', 'type' => 'TEXT', 'notnull' => 1, 'dflt_value' => null, 'pk' => 0],
                    ],
                    'indexes' => [],
                ],
            ]);
            $mcpApp = App::create($tmp, name: 'MCP Demo');
            $mcpApp->container()->instance(\Kongono\Database\ConnectionInterface::class, $db);
            $mcpAi = new AiClient(new OpenAiProvider('sk-test', transport: new ScriptedTransport(
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => 'MCP Hantei root cause: missing slug index.']]],
                    'usage' => ['prompt_tokens' => 80, 'completion_tokens' => 20],
                ], JSON_THROW_ON_ERROR)),
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => json_encode([
                        'summary' => 'Preview WidgetController',
                        'changes' => [['path' => 'src/WidgetController.php', 'action' => 'create', 'explanation' => 'Lists widgets', 'content' => "<?php\n\ndeclare(strict_types=1);\n"]],
                        'next_steps' => ['Register WidgetController'],
                    ], JSON_THROW_ON_ERROR)]]],
                    'usage' => ['prompt_tokens' => 90, 'completion_tokens' => 40],
                ], JSON_THROW_ON_ERROR)),
            )));
            $server = KongonoMcpFactory::forApp($tmp, $mcpApp, $mcpAi);

            $schemaCall = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":20,"method":"tools/call","params":{"name":"kongono_inspect_schema"}}'), true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($schemaCall);
            self::assertFalse($schemaCall['result']['isError']);
            self::assertStringContainsString('"widgets"', $schemaCall['result']['content'][0]['text']);

            $hanteiCall = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":21,"method":"tools/call","params":{"name":"kongono_hantei_diagnose","arguments":{"diagnostic":"Slow lookup on widgets.slug"}}}'), true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($hanteiCall);
            self::assertFalse($hanteiCall['result']['isError']);
            self::assertStringContainsString('missing slug index', $hanteiCall['result']['content'][0]['text']);

            $chieCall = json_decode((string) $server->handleJson('{"jsonrpc":"2.0","id":22,"method":"tools/call","params":{"name":"kongono_chie_preview","arguments":{"instruction":"Add WidgetController"}}}'), true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($chieCall);
            self::assertFalse($chieCall['result']['isError']);
            self::assertStringContainsString('WidgetController.php', $chieCall['result']['content'][0]['text']);
            self::assertFileDoesNotExist($tmp . '/src/WidgetController.php'); // Strictly read-only preview!

            // 5. Companion eval benchmark (`kongo eval --companions` both offline & live)
            [$evalOffCode, $evalOffOut] = $offlineCli->run(['eval', '--companions', '--json', '--app=' . $tmp]);
            self::assertSame(0, $evalOffCode);
            $evalOffJson = json_decode($evalOffOut, true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($evalOffJson);
            self::assertTrue($evalOffJson['passed']);
            self::assertSame(6, $evalOffJson['total']);

            $liveEvalAi = new AiClient(new OpenAiProvider('sk-test', transport: new ScriptedTransport(
                // 1. Oshie live eval
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => '#[Get("/api/ping")] #[Authenticated] public function ping(): Response { return Response::json(["ok" => true]); }']]],
                    'usage' => ['prompt_tokens' => 80, 'completion_tokens' => 30],
                ], JSON_THROW_ON_ERROR)),
                // 2. Chie live eval
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => json_encode([
                        'summary' => 'Added PingService',
                        'changes' => [['path' => 'src/Service/PingService.php', 'action' => 'create', 'explanation' => 'Ping service', 'content' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Service;\n\nfinal readonly class PingService { public function ping(): string { return 'pong'; } }\n"]],
                        'next_steps' => [],
                    ], JSON_THROW_ON_ERROR)]]],
                    'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 50],
                ], JSON_THROW_ON_ERROR)),
                // 3. Hantei live eval
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => 'KGN-VALIDATION-422 failed validation on POST /api/notes.']]],
                    'usage' => ['prompt_tokens' => 90, 'completion_tokens' => 25],
                ], JSON_THROW_ON_ERROR)),
                // 4. Hantei failed-job live eval
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => 'SendReceiptJob failed due to SMTP 535 authentication error.']]],
                    'usage' => ['prompt_tokens' => 90, 'completion_tokens' => 25],
                ], JSON_THROW_ON_ERROR)),
            )));
            $liveReport = (new CompanionEvalSuite())->run(ProjectContext::discover($tmp), $liveEvalAi);
            self::assertTrue($liveReport->passed);
            self::assertSame(6, $liveReport->passedCount);
            self::assertGreaterThan(0, $liveReport->totalTokens);
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testChieSurgicalPatchesAgentsMdGroundingOshieExercisesOpsDiagnoseAndHanteiWatch(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_v119_' . bin2hex(random_bytes(4));
        try {
            (new AppScaffolder())->scaffold($tmp, 'V119 Demo');

            // 1. Auto-load AGENTS.md team conventions into promptSummary() (scrubbed + wrapped in UntrustedInput)
            file_put_contents(
                $tmp . '/AGENTS.md',
                "# Team Rules\n- Always suffix money variables with _cents.\n- Never leak gsk_1234567890abcdefghijKLMN.\n",
            );
            $ctx = ProjectContext::discover($tmp);
            $summary = $ctx->promptSummary();
            self::assertStringContainsString('Project conventions (AGENTS.md):', $summary);
            self::assertStringContainsString('suffix money variables with _cents', $summary);
            self::assertStringNotContainsString('gsk_1234567890abcdefghijKLMN', $summary);
            self::assertStringContainsString('[REDACTED]', $summary);

            // 2. Chie surgical search-and-replace patches (`action: "patch"`) preserve unrelated lines & fail closed on non-unique/missing blocks
            $existingFile = <<<'PHP'
<?php

declare(strict_types=1);

namespace App;

final readonly class PricingService
{
    public const int TAX_BPS = 500;

    public function subtotalCents(int $unitCents, int $qty): int
    {
        return $unitCents * $qty;
    }

    public function label(): string
    {
        return 'standard';
    }
}
PHP;
            file_put_contents($tmp . '/src/PricingService.php', $existingFile . "\n");

            $patchTransport = new ScriptedTransport(
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => json_encode([
                        'summary' => 'Patched TAX_BPS and label cleanly',
                        'changes' => [[
                            'path' => 'src/PricingService.php',
                            'action' => 'patch',
                            'explanation' => 'Update tax rate to 825 bps without touching subtotalCents()',
                            'edits' => [
                                ['search' => 'public const int TAX_BPS = 500;', 'replace' => 'public const int TAX_BPS = 825;'],
                                ['search' => "return 'standard';", 'replace' => "return 'enterprise';"],
                            ],
                        ]],
                        'next_steps' => [],
                    ], JSON_THROW_ON_ERROR)]]],
                    'usage' => ['prompt_tokens' => 110, 'completion_tokens' => 45],
                ], JSON_THROW_ON_ERROR)),
            );
            $patchPlan = (new ChieBuilder(new AiClient(new OpenAiProvider('sk-test', transport: $patchTransport))))
                ->build('Update tax rate and label', $ctx, apply: true);

            self::assertSame(['src/PricingService.php'], $patchPlan['applied']);
            $patchedDisk = (string) file_get_contents($tmp . '/src/PricingService.php');
            self::assertStringContainsString('TAX_BPS = 825;', $patchedDisk);
            self::assertStringContainsString("return 'enterprise';", $patchedDisk);
            self::assertStringContainsString('return $unitCents * $qty;', $patchedDisk);
            // AGENTS.md rule was delivered to Chie inside UntrustedInput!
            self::assertStringContainsString('suffix money variables with _cents', $patchTransport->requests[0]);

            // Ambiguous / missing search block fails closed
            try {
                ChieBuilder::applyPatchEdits("foo\nfoo\n", [['search' => 'foo', 'replace' => 'bar']], 'src/PricingService.php');
                self::fail('Expected ambiguous patch search block to fail closed.');
            } catch (CoreException $e) {
                self::assertStringContainsString('matched 2 times', $e->getMessage());
            }
            try {
                ChieBuilder::applyPatchEdits("foo\n", [['search' => 'missing', 'replace' => 'bar']], 'src/PricingService.php');
                self::fail('Expected missing patch search block to fail closed.');
            } catch (CoreException $e) {
                self::assertStringContainsString('not found', $e->getMessage());
            }

            // 3. Oshie verifiable hands-on exercises (`--exercises` & `--exercise <id>`)
            $cli = new KongoCli(dirname(__DIR__, 3));
            [$exListCode, $exListOut] = $cli->run(['oshie', '--exercises', '--app=' . $tmp]);
            self::assertSame(0, $exListCode);
            self::assertStringContainsString('routing', $exListOut);
            self::assertStringContainsString('database', $exListOut);

            [$routeExCode, $routeExOut] = $cli->run(['oshie', '--exercise', 'routing', '--app=' . $this->starterAppRoot]);
            self::assertSame(0, $routeExCode);
            self::assertStringContainsString('(PASS)', $routeExOut);

            [$viewsExCode, $viewsExOut] = $cli->run(['oshie', '--exercise', 'views', '--app=' . $tmp]);
            self::assertSame(0, $viewsExCode);
            self::assertStringContainsString('(PASS)', $viewsExOut);
            self::assertStringContainsString('recorded in your Oshie progress', $viewsExOut);

            [$dbExCode, $dbExOut] = $cli->run(['oshie', '--exercise', 'database', '--app=' . $tmp]);
            self::assertSame(1, $dbExCode);
            self::assertStringContainsString('(NEEDS WORK)', $dbExOut);
            self::assertStringContainsString('make:migration', $dbExOut);

            [$progCode, $progOut] = $cli->run(['oshie', '--progress', '--app=' . $tmp]);
            self::assertSame(0, $progCode);
            self::assertStringContainsString('1/6 exercises verified', $progOut);

            // 4. Gate-protected /_ops 1-click Hantei DLQ diagnosis
            $driver = new \Kongono\Jobs\MemoryQueueDriver();
            $driver->push('emails', 'App\\Jobs\\WelcomeMailJob', ['email' => 'a@b.test', 'token' => 'gsk_1234567890abcdefghijKLMN'], maxAttempts: 1);
            $reserved = $driver->reserve('emails');
            self::assertNotNull($reserved);
            $driver->markFailed($reserved, \Kongono\Jobs\JobStatus::Dead, 'SMTP timeout with key gsk_1234567890abcdefghijKLMN');

            $gate = new \Kongono\Security\Gate();
            $gate->define('ops.view', static fn(\Kongono\Security\Principal $p): bool => $p->hasCapability('ops.view'));
            $gate->define('ops.manage', static fn(\Kongono\Security\Principal $p): bool => $p->hasCapability('ops.manage'));

            $opsApp = App::create($tmp, name: 'Ops AI Demo');
            $opsAi = new AiClient(new OpenAiProvider('sk-test', transport: new ScriptedTransport(
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => 'Root cause: SMTP upstream timed out — check Resend/SMTP host.']]],
                    'usage' => ['prompt_tokens' => 110, 'completion_tokens' => 24],
                ], JSON_THROW_ON_ERROR)),
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => 'Live morph diagnosis: check SMTP credentials.']]],
                    'usage' => ['prompt_tokens' => 110, 'completion_tokens' => 20],
                ], JSON_THROW_ON_ERROR)),
            )));
            $opsApp->middleware(new \Kongono\Http\Ops\OpsDashboardMiddleware(
                appName: 'Ops AI Demo',
                queueDriver: $driver,
                queues: ['emails'],
                gate: $gate,
                diagnoseJobHandler: DevStudioCompanions::opsJobDiagnoser($opsApp, $tmp, $opsAi),
            ));

            // Unauthenticated -> fail-closed 401; authenticated without ops.view -> fail-closed 403
            (new TestClient($opsApp))->postJson('/_ops/queues/diagnose', ['queue' => 'emails', 'job_id' => $reserved->id])->assertUnauthorized();
            (new TestClient($opsApp))->actingAs(new \Kongono\Security\Principal('u0', \Kongono\Security\PrincipalKind::Human, ['notes.read']))
                ->postJson('/_ops/queues/diagnose', ['queue' => 'emails', 'job_id' => $reserved->id])->assertForbidden();

            $viewer = new \Kongono\Security\Principal('u1', \Kongono\Security\PrincipalKind::Human, ['ops.view']);
            $opsClient = (new TestClient($opsApp))->actingAs($viewer);

            // HTML dashboard shows the Diagnose button on dead-lettered rows
            $opsClient->get('/_ops')->assertOk()->assertSee('/_ops/queues/diagnose')->assertSee('Diagnose');

            // JSON diagnose returns scrubbed root cause + telemetry footer
            $diagJsonRes = $opsClient->postJson('/_ops/queues/diagnose', ['queue' => 'emails', 'job_id' => $reserved->id]);
            $diagJsonRes->assertOk();
            $diagPayload = $diagJsonRes->json();
            self::assertIsArray($diagPayload);
            self::assertSame('diagnose', $diagPayload['action']);
            self::assertSame($reserved->id, $diagPayload['job_id']);
            self::assertStringContainsString('SMTP upstream timed out', (string) $diagPayload['diagnosis']);
            self::assertStringContainsString('[openai/gpt-4.1-mini', (string) $diagPayload['footer']);

            // Live 2.5 morph fragment also renders the diagnosis banner cleanly
            $opsClient->withLive()->postForm('/_ops/queues/diagnose', ['queue' => 'emails', 'job_id' => $reserved->id])
                ->assertOk()
                ->assertSee('Live morph diagnosis');

            // 5. CLI `kongo hantei watch` (log mode + queue DLQ mode with --once)
            mkdir($tmp . '/logs', 0o775, true);
            file_put_contents(
                $tmp . '/logs/app.log',
                "[2026-11-22] INFO Booted cleanly\n[2026-11-22] ERROR KGN-DB-500 Deadlock detected with token gsk_1234567890abcdefghijKLMN\n",
            );
            $watchTransport = new ScriptedTransport(
                new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => 'Wrap concurrent updates in deterministic lock ordering.']]],
                    'usage' => ['prompt_tokens' => 95, 'completion_tokens' => 22],
                ], JSON_THROW_ON_ERROR)),
            );
            $watchCli = new KongoCli(dirname(__DIR__, 3), new AiClient(new OpenAiProvider('sk-test', transport: $watchTransport)));
            [$wCode, $wOut] = $watchCli->run(['hantei', 'watch', '--log=logs/app.log', '--once', '--app=' . $tmp]);
            self::assertSame(0, $wCode);
            self::assertStringContainsString('[Hantei Watch — logs/app.log (1 error line(s))]', $wOut);
            self::assertStringContainsString('deterministic lock ordering', $wOut);
            self::assertStringNotContainsString('gsk_1234567890abcdefghijKLMN', $watchTransport->requests[0]);
            self::assertStringContainsString('[REDACTED]', $watchTransport->requests[0]);
        } finally {
            $this->removeDir($tmp);
        }
    }

    /**
     * @param array<string, array{columns: list<array<string, mixed>>, indexes: list<array<string, mixed>>}> $tables
     */
    private function fakeSqliteSchemaConnection(array $tables): \Kongono\Database\ConnectionInterface
    {
        return new readonly class ($tables) implements \Kongono\Database\ConnectionInterface {
            /**
             * @param array<string, array{columns: list<array<string, mixed>>, indexes: list<array<string, mixed>>}> $tables
             */
            public function __construct(private array $tables) {}

            public function dialect(): \Kongono\Database\Dialect
            {
                return \Kongono\Database\Dialect::Sqlite;
            }

            public function query(string $sql, array $bindings = []): array
            {
                if (str_contains($sql, 'sqlite_master')) {
                    return array_map(static fn(string $name): array => ['name' => $name], array_keys($this->tables));
                }
                if (1 === preg_match('/PRAGMA\s+table_info\("([^"]+)"\)/', $sql, $m)) {
                    return $this->tables[$m[1]]['columns'] ?? [];
                }
                if (1 === preg_match('/PRAGMA\s+index_list\("([^"]+)"\)/', $sql, $m)) {
                    return $this->tables[$m[1]]['indexes'] ?? [];
                }

                throw new \RuntimeException('Unexpected non-schema SQL executed during inspectSchema(): ' . $sql);
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                $rows = $this->query($sql, $bindings);

                return $rows[0] ?? null;
            }

            public function execute(string $sql, array $bindings = []): int
            {
                return 0;
            }

            public function table(string $table, ?string $alias = null): \Kongono\Database\QueryBuilder
            {
                return new \Kongono\Database\QueryBuilder($table, $this->dialect(), $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                return $callback($this);
            }
        };
    }

    private function exportPhpString(string $value): string
    {
        return var_export($value, true);
    }
}

final readonly class BridgeLineItemDto
{
    public function __construct(
        public string $sku,
        public int $qty,
    ) {}
}

final readonly class BridgeOrderPayloadDto
{
    /**
     * @param list<BridgeLineItemDto> $items
     * @param list<string>            $tags
     */
    public function __construct(
        #[\Kongono\Validation\Attributes\ArrayOf(BridgeLineItemDto::class)]
        public array $items,
        #[\Kongono\Validation\Attributes\ArrayOf('string')]
        public array $tags,
    ) {}
}
