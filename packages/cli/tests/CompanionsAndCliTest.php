<?php

declare(strict_types=1);

namespace Kongono\Cli\Tests;

use Kongono\Ai\AiClient;
use Kongono\Ai\Provider\OpenAiProvider;
use Kongono\Ai\Transport\HttpResponse;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Cli\AppScaffolder;
use Kongono\Cli\Audit\SecurityAuditor;
use Kongono\Cli\CodeGenerator;
use Kongono\Cli\Companions\ChieBuilder;
use Kongono\Cli\Companions\HanteiDebugger;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Database\MigrationLinter;
use Kongono\Cli\Deploy\DeployBlueprintGenerator;
use Kongono\Cli\KongoCli;
use Kongono\Cli\Mcp\KongonoMcpFactory;
use Kongono\Cli\Sdk\SdkGenerator;
use Kongono\Core\CoreException;
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
            self::assertStringContainsString('v1.11.0', $vOut);

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
            self::assertTrue($mcpPost['result']['isError']);

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

    private function exportPhpString(string $value): string
    {
        return var_export($value, true);
    }
}
