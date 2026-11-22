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
            self::assertStringContainsString('v1.4.1', $vOut);

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

        [$code, $out] = $cli->run(['audit', '--attack', '--app=' . $this->starterAppRoot]);
        self::assertSame(0, $code);
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
}
