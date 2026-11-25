<?php

declare(strict_types=1);

namespace Kongono\Cli\Tests;

use Kongono\Ai\AiClient;
use Kongono\Ai\Provider\OpenAiProvider;
use Kongono\Ai\ToolCall;
use Kongono\Ai\Transport\HttpResponse;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Cli\AppScaffolder;
use Kongono\Cli\Companions\CompanionTools;
use Kongono\Cli\Companions\OshieTutor;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Diagnostics\KongonoAppInspector;
use Kongono\Cli\Eval\AgentCodingBenchmark;
use Kongono\Cli\Knowledge\KongonoKnowledgeIndex;
use Kongono\Cli\KongoCli;
use Kongono\Cli\Mcp\KongonoMcpFactory;
use Kongono\Cli\Policy\PolicyVerifier;
use Kongono\Cli\Skills\SkillsInstaller;
use Kongono\Cli\Watch\WatchRunner;
use Kongono\Core\Dev\DevMailboxInterface;
use Kongono\Core\Dev\FileDevMailbox;
use Kongono\Http\App;
use Kongono\Http\Dev\DevDashboardMiddleware;
use Kongono\Http\Dev\DevHotReloadWatcher;
use Kongono\Http\Dev\DevInspector;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Mail\DevMailboxTransport;
use Kongono\Mail\Email;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class KongonoDayOneDxTest extends TestCase
{
    /** @var list<string> */
    private array $tmpDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tmpDirs as $dir) {
            $this->removeDir($dir);
        }
        $this->tmpDirs = [];
    }

    public function testHotReloadWatcherSeparatesCssAndCodeFingerprints(): void
    {
        $root = $this->makeTempDir();
        mkdir($root . '/public/css', 0o777, true);
        mkdir($root . '/views', 0o777, true);
        file_put_contents($root . '/public/css/app.css', 'body { color: #111; }');
        file_put_contents($root . '/views/home.kgn.html', '<h1>Hello</h1>');

        $watcher = new DevHotReloadWatcher();
        $first = $watcher->fingerprint($root);
        self::assertSame(2, $first['files_watched']);

        // Changing CSS changes ONLY css_hash (preserves code_hash for zero-reload stylesheet hot swap)
        file_put_contents($root . '/public/css/app.css', 'body { color: #222; background: #fff; }');
        touch($root . '/public/css/app.css', time() + 2);
        clearstatcache(true);
        $afterCss = $watcher->fingerprint($root);

        self::assertNotSame($first['css_hash'], $afterCss['css_hash']);
        self::assertSame($first['code_hash'], $afterCss['code_hash']);

        // Changing template changes ONLY code_hash
        file_put_contents($root . '/views/home.kgn.html', '<h1>Hello Kongono</h1>');
        touch($root . '/views/home.kgn.html', time() + 4);
        clearstatcache(true);
        $afterCode = $watcher->fingerprint($root);

        self::assertSame($afterCss['css_hash'], $afterCode['css_hash']);
        self::assertNotSame($afterCss['code_hash'], $afterCode['code_hash']);
    }

    public function testHotReloadEndpointsInjectInDebugAndFailClosedInProduction(): void
    {
        $root = $this->makeTempDir();
        mkdir($root . '/views', 0o777, true);
        file_put_contents($root . '/views/index.kgn.html', '<h1>Hi</h1>');

        $app = App::create(basePath: $root)->debug(true);
        $app->middleware(new DevDashboardMiddleware(
            app: $app,
            inspector: new DevInspector(),
            loopbackOnly: false,
            hotReload: true,
        ));
        $app->router()->get('/', static fn(): Response => Response::html('<!doctype html><html><body><h1>Home</h1></body></html>'));
        $app->router()->get('/api/ping', static fn(): Response => Response::json(['ok' => true]));

        // HTML gets hot-reload script injected cleanly before </body>
        $htmlRes = $app->handle(Request::create('GET', '/'));
        self::assertSame(200, $htmlRes->getStatusCode());
        self::assertStringContainsString("<script src=\"/_kongono/assets/hot-reload.js\" defer></script>\n</body>", (string) $htmlRes->getBody());

        // JSON response is never mutated
        $jsonRes = $app->handle(Request::create('GET', '/api/ping'));
        self::assertSame(200, $jsonRes->getStatusCode());
        self::assertStringNotContainsString('hot-reload.js', (string) $jsonRes->getBody());

        // Hot-reload client script, token endpoint, and SSE endpoint all work in debug mode
        $jsRes = $app->handle(Request::create('GET', '/_kongono/assets/hot-reload.js'));
        self::assertSame(200, $jsRes->getStatusCode());
        self::assertStringContainsString('/api/reload-token', (string) $jsRes->getBody());

        $tokRes = $app->handle(Request::create('GET', '/_kongono/api/reload-token'));
        self::assertSame(200, $tokRes->getStatusCode());
        $tok = json_decode((string) $tokRes->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($tok);
        self::assertArrayHasKey('css_hash', $tok);
        self::assertArrayHasKey('code_hash', $tok);
        self::assertSame(1, $tok['files_watched']);

        $sseRes = $app->handle(Request::create('GET', '/_kongono/live-reload'));
        self::assertSame(200, $sseRes->getStatusCode());
        self::assertStringContainsString('text/event-stream', $sseRes->getHeaderLine('Content-Type'));
        self::assertStringContainsString('event: state', (string) $sseRes->getBody());

        // Production (debug=false): zero script injection and all endpoints fail closed (404)
        $prodApp = App::create(basePath: $root)->debug(false);
        $prodApp->middleware(new DevDashboardMiddleware(
            app: $prodApp,
            inspector: new DevInspector(),
            loopbackOnly: false,
            hotReload: true,
        ));
        $prodApp->router()->get('/', static fn(): Response => Response::html('<!doctype html><html><body><h1>Prod</h1></body></html>'));

        $prodHtml = $prodApp->handle(Request::create('GET', '/'));
        self::assertSame(200, $prodHtml->getStatusCode());
        self::assertStringNotContainsString('hot-reload.js', (string) $prodHtml->getBody());
        self::assertSame(404, $prodApp->handle(Request::create('GET', '/_kongono/api/reload-token'))->getStatusCode());
        self::assertSame(404, $prodApp->handle(Request::create('GET', '/_kongono/live-reload'))->getStatusCode());
    }

    public function testDevMailboxScrubsSecretsAndSchemaExplorerEndpointsWorkInDebugOnly(): void
    {
        $root = $this->makeTempDir();
        $mailbox = new FileDevMailbox($root . '/storage/dev/mailbox.json');
        $transport = new DevMailboxTransport($mailbox);

        $dummyStripeKey = 'sk_' . 'live_1234567890abcdefghijklmnop';
        $email = Email::create()
            ->from('noreply@kongono.dev', 'Kongono')
            ->to('founder@example.com', 'Founder')
            ->subject('Welcome to Kongono')
            ->html('<h1>Welcome!</h1><p>Do not leak ' . $dummyStripeKey . '</p>')
            ->text('Welcome! Token: ' . $dummyStripeKey)
            ->attach('receipt.pdf', '%PDF-1.4 sample', 'application/pdf');

        $messageId = $transport->send($email);
        self::assertStringStartsWith('devmail_', $messageId);

        $app = App::create(basePath: $root)->debug(true);
        $app->instance(DevMailboxInterface::class, $mailbox);
        $app->middleware(new DevDashboardMiddleware(
            app: $app,
            inspector: new DevInspector(),
            loopbackOnly: false,
            schemaHandler: static fn(): array => [
                'connected' => true,
                'dialect' => 'sqlite',
                'tables' => [
                    [
                        'name' => 'users',
                        'rows' => 2,
                        'columns' => [
                            ['name' => 'id', 'type' => 'INTEGER', 'nullable' => false, 'default' => null, 'primary' => true],
                            ['name' => 'email', 'type' => 'TEXT', 'nullable' => false, 'default' => null, 'primary' => false],
                        ],
                    ],
                ],
            ],
        ));

        // Dev Studio HTML renders both new tabs
        $uiRes = $app->handle(Request::create('GET', '/_kongono'));
        self::assertSame(200, $uiRes->getStatusCode());
        $uiBody = (string) $uiRes->getBody();
        self::assertStringContainsString('id="tab-mailbox"', $uiBody);
        self::assertStringContainsString('id="tab-schema"', $uiBody);

        // Mailbox API returns captured message with scrubbed secret and attachment metadata
        $mailRes = $app->handle(Request::create('GET', '/_kongono/api/mailbox'));
        self::assertSame(200, $mailRes->getStatusCode());
        $mailData = json_decode((string) $mailRes->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($mailData);
        self::assertCount(1, $mailData['messages']);
        $msg = $mailData['messages'][0];
        self::assertSame('Welcome to Kongono', $msg['subject']);
        self::assertStringNotContainsString('sk_live_1234567890', (string) $msg['html']);
        self::assertStringContainsString('[REDACTED]', (string) $msg['html']);
        self::assertSame('receipt.pdf', $msg['attachments'][0]['filename']);

        // Schema API returns read-only table metadata
        $schemaRes = $app->handle(Request::create('GET', '/_kongono/api/schema'));
        self::assertSame(200, $schemaRes->getStatusCode());
        $schemaData = json_decode((string) $schemaRes->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($schemaData);
        self::assertTrue($schemaData['connected']);
        self::assertSame('users', $schemaData['tables'][0]['name']);

        // Clearing mailbox via same-origin Dev Studio header empties it
        $clearRes = $app->handle(Request::create('POST', '/_kongono/api/mailbox/clear', headers: ['X-Kongono-Dev' => '1']));
        self::assertSame(200, $clearRes->getStatusCode());
        self::assertSame([], $mailbox->messages());

        // Production fail-closed check
        $prodApp = App::create(basePath: $root)->debug(false);
        $prodApp->middleware(new DevDashboardMiddleware(app: $prodApp, inspector: new DevInspector(), loopbackOnly: false));
        self::assertSame(404, $prodApp->handle(Request::create('GET', '/_kongono/api/mailbox'))->getStatusCode());
        self::assertSame(404, $prodApp->handle(Request::create('GET', '/_kongono/api/schema'))->getStatusCode());
    }

    public function testScaffoldedAppShipsAllDayOneSuperpowersAndPassesVerification(): void
    {
        $target = $this->makeTempDir() . DIRECTORY_SEPARATOR . 'demo-day1';
        $scaffolder = new AppScaffolder();
        $files = $scaffolder->scaffold($target, 'demo-day1', 'minimal');

        self::assertFileExists($target . '/app/invariants.php');
        self::assertFileExists($target . '/.vscode/mcp.json');
        self::assertFileExists($target . '/AGENTS.md');
        self::assertContains('app/invariants.php', $files);
        self::assertContains('.vscode/mcp.json', $files);

        $composer = (string) file_get_contents($target . '/composer.json');
        self::assertStringContainsString('^1.19', $composer);

        $bootstrap = (string) file_get_contents($target . '/app/bootstrap.php');
        self::assertStringContainsString('->recordIncidents()', $bootstrap);

        $controller = (string) file_get_contents($target . '/src/Http/HomeController.php');
        self::assertStringContainsString('#[ReadOnlyDb]', $controller);

        $gitignore = (string) file_get_contents($target . '/.gitignore');
        self::assertStringContainsString('/storage/incidents/', $gitignore);
        self::assertStringContainsString('/storage/dev/', $gitignore);

        // Freshly scaffolded app immediately passes KongonoAppInspector and PolicyVerifier at 100/100
        $diag = (new KongonoAppInspector())->inspect($target);
        self::assertTrue($diag['passed'], 'Freshly scaffolded app failed diagnose: ' . json_encode($diag['findings']));
        self::assertSame(100, $diag['score']);

        $policy = (new PolicyVerifier())->verify($target);
        self::assertTrue($policy['passed'], 'Freshly scaffolded app failed policy:check: ' . json_encode($policy['violations']));
        self::assertSame(100, $policy['score']);
    }

    public function testWatchRunnerDiffsSnapshotsAndRunsSinglePassCliCheck(): void
    {
        $root = $this->makeTempDir();
        mkdir($root . '/src/Controller', 0o777, true);
        mkdir($root . '/views', 0o777, true);
        mkdir($root . '/storage', 0o777, true);
        file_put_contents($root . '/views/home.kgn.html', '<h1>Clean</h1>');
        file_put_contents($root . '/storage/ignored.php', '<?php // ignored');

        $runner = new WatchRunner();
        $snap1 = $runner->snapshot($root);
        self::assertArrayHasKey('views/home.kgn.html', $snap1);
        self::assertArrayNotHasKey('storage/ignored.php', $snap1);

        // Add one file, modify one file
        file_put_contents($root . '/views/about.kgn.html', '<h1>About</h1>');
        file_put_contents($root . '/views/home.kgn.html', '<h1>Clean v2</h1>');
        touch($root . '/views/home.kgn.html', time() + 2);
        clearstatcache(true);
        $snap2 = $runner->snapshot($root);

        $diff = $runner->diffSnapshots($snap1, $snap2);
        self::assertSame(['views/about.kgn.html', 'views/home.kgn.html'], $diff);

        // CLI `kongo watch --once --no-tests` passes cleanly on clean app
        $cli = new KongoCli($root);
        [$exitPass, $outPass] = $cli->run(['watch', '--app=' . $root, '--no-tests', '--once']);
        self::assertSame(0, $exitPass);
        self::assertStringContainsString('Kongono Watch — PASS', $outPass);
        self::assertStringContainsString('diagnose (100/100)', $outPass);

        // Introduce a broken view partial reference -> `kongo watch --once` fails with 5W1H + fix guidance
        file_put_contents($root . '/views/home.kgn.html', "@include('partials/missing-banner')\n<h1>Broken</h1>");
        [$exitFail, $outFail] = $cli->run(['watch', '--app=' . $root, '--no-tests', '--once']);
        self::assertSame(1, $exitFail);
        self::assertStringContainsString('Kongono Watch — FAIL', $outFail);
        self::assertStringContainsString('KGN-DIAG-MISSING-VIEW', $outFail);
    }

    public function testAgentKnowledgeDocsAndSkillsStaySyncedWithLiveFramework(): void
    {
        $repoRoot = dirname(__DIR__, 3);
        $llms = (string) file_get_contents($repoRoot . '/site/llms.txt');
        $llmsFull = (string) file_get_contents($repoRoot . '/site/llms-full.txt');

        self::assertStringContainsString('26-tool Model Context Protocol', $llms);
        self::assertStringContainsString('kongo prove', $llms);
        self::assertStringContainsString('#[ReadOnlyDb]', $llms);

        // Every bundled workflow skill must be indexed in llms.txt
        $skills = (new SkillsInstaller())->available();
        self::assertContains('kongono-self-verifying-proofs', $skills);
        foreach ($skills as $skill) {
            self::assertStringContainsString($skill, $llms, "Bundled skill {$skill} missing from site/llms.txt");
        }

        // Every live MCP tool registered by KongonoMcpFactory must be documented in llms-full.txt (drift guard!)
        $starterRoot = $repoRoot . '/examples/starter-app';
        $mcp = KongonoMcpFactory::forApp($starterRoot);
        $toolsResp = json_decode((string) $mcp->handleJson('{"jsonrpc":"2.0","id":1,"method":"tools/list"}'), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($toolsResp);
        $tools = $toolsResp['result']['tools'] ?? [];
        self::assertIsArray($tools);
        self::assertCount(26, $tools);
        foreach ($tools as $tool) {
            self::assertIsArray($tool);
            $name = is_string($tool['name'] ?? null) ? $tool['name'] : '';
            self::assertNotSame('', $name);
            self::assertStringContainsString($name, $llmsFull, "MCP tool {$name} missing from site/llms-full.txt");
        }

        // Built-in Oshie tutor ships the v1.20 self-verifying proofs lesson offline
        $oshie = new OshieTutor();
        $lesson = $oshie->teachLesson('proofs', new ProjectContext($starterRoot));
        self::assertStringContainsString('kongo prove', $lesson);
        self::assertStringContainsString('#[ReadOnlyDb]', $lesson);
        self::assertStringContainsString('recordIncidents()', $lesson);
    }

    public function testKnowledgeIndexPowersOfflineOshieCompanionToolsAndMcpSearch(): void
    {
        $repoRoot = dirname(__DIR__, 3);
        $starterRoot = $repoRoot . '/examples/starter-app';

        $index = new KongonoKnowledgeIndex($repoRoot);
        $hits = $index->search('How do I enforce #[ReadOnlyDb] and #[MaxQueries] on a controller route?', limit: 3);
        self::assertNotEmpty($hits);
        $combined = implode("\n", array_column($hits, 'content'));
        self::assertStringContainsString('#[ReadOnlyDb]', $combined);

        // Offline Oshie Q&A now includes retrieved v1.20 reference excerpts even without an AI API key
        $oshie = new OshieTutor();
        $offlineReply = $oshie->ask('How does BatchLoader prevent N+1 queries?', new ProjectContext($starterRoot));
        self::assertStringContainsString('Oshie AI Q&A requires an AI provider.', $offlineReply);
        self::assertStringContainsString('BatchLoader::hasMany', $offlineReply);

        // CompanionTools exposes search_kongono_docs for Chie & Hantei
        $toolResult = CompanionTools::execute(
            new ToolCall('call_1', 'search_kongono_docs', ['query' => 'app/invariants.php policy:check', 'limit' => 2]),
            new ProjectContext($starterRoot),
        );
        self::assertStringContainsString('Invariant::routesMatching', $toolResult);

        // Live MCP tool `kongono_search_docs` works over JSON-RPC 2.0
        $mcp = KongonoMcpFactory::forApp($starterRoot);
        $mcpRaw = (string) $mcp->handleJson('{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"kongono_search_docs","arguments":{"query":"#[ReadOnlyDb] CapabilityMiddleware","limit":2}}}');
        $mcpResp = json_decode($mcpRaw, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($mcpResp);
        self::assertFalse($mcpResp['result']['isError']);
        self::assertStringContainsString('CapabilityMiddleware', $mcpResp['result']['content'][0]['text']);

        // Live MCP tool `kongono_zokeibi_registry` reads app zokeibi.json + installed components + registry schemas
        $zokeibiApp = $this->makeTempDir();
        mkdir($zokeibiApp . '/views/components/ui', 0o777, true);
        file_put_contents($zokeibiApp . '/views/components/ui/button.kgn.html', '<button class="z-btn">OK</button>');
        file_put_contents($zokeibiApp . '/zokeibi.json', '{"theme":"indigo","radius":"rounded"}');
        $zokeibiMcp = KongonoMcpFactory::forApp($zokeibiApp);
        $kRaw = (string) $zokeibiMcp->handleJson('{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"kongono_zokeibi_registry","arguments":{}}}');
        $kResp = json_decode($kRaw, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($kResp);
        self::assertFalse($kResp['result']['isError']);
        $kPayload = json_decode((string) $kResp['result']['content'][0]['text'], true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($kPayload);
        self::assertSame(['button'], $kPayload['installed_components']);
        self::assertSame('indigo', $kPayload['zokeibi_json']['theme']);
    }

    public function testAgentCodingBenchmarkGradesReferenceExportsJsonlAndCatchesHallucinations(): void
    {
        $repoRoot = dirname(__DIR__, 3);
        $tmpDir = $this->makeTempDir();
        $jsonlPath = $tmpDir . DIRECTORY_SEPARATOR . 'kongono-verified.jsonl';

        // 1. Offline reference run passes 6/6 (100%) and exports verified ChatML JSONL trajectories
        $cli = new KongoCli($repoRoot);
        [$exitCode, $jsonOut] = $cli->run([
            'eval',
            '--agent-bench',
            '--export-jsonl=' . $jsonlPath,
            '--json',
            '--app=' . $repoRoot . '/examples/starter-app',
        ]);
        self::assertSame(0, $exitCode, $jsonOut);
        $decoded = json_decode($jsonOut, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertTrue($decoded['passed']);
        self::assertSame(6, $decoded['total']);
        self::assertSame(6, $decoded['passed_count']);

        self::assertFileExists($jsonlPath);
        $lines = array_values(array_filter(explode("\n", trim((string) file_get_contents($jsonlPath)))));
        self::assertCount(6, $lines);
        $firstRow = json_decode($lines[0], true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($firstRow);
        self::assertSame('readonly-capability-controller', $firstRow['task']);
        self::assertCount(3, $firstRow['messages']);

        // 2. Live benchmark catches a hallucinating LLM outputting legacy Laravel facades (`Route::get` / `DB::table`)
        $hallucinatingTransport = new class implements HttpTransportInterface {
            public function postJson(string $url, array $headers, string $jsonBody, int $timeoutSeconds = 60): HttpResponse
            {
                return new HttpResponse(200, json_encode([
                    'model' => 'gpt-4.1-mini',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => "<?php\nRoute::get('/api/notes', fn() => DB::table('notes')->get());"]]],
                    'usage' => ['prompt_tokens' => 40, 'completion_tokens' => 20, 'total_tokens' => 60],
                ], JSON_THROW_ON_ERROR));
            }
        };
        $badAi = new AiClient(new OpenAiProvider('sk-test', transport: $hallucinatingTransport));
        $badReport = (new AgentCodingBenchmark())->run($badAi);

        self::assertFalse($badReport->passed);
        self::assertSame(0, $badReport->passedCount);
        self::assertSame(6, $badReport->failedCount);
        self::assertNotEmpty($badReport->results[0]->failures);
        self::assertStringContainsString('Route::get(', implode("\n", $badReport->results[0]->failures));
    }

    private function makeTempDir(): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_day1_' . bin2hex(random_bytes(6));
        mkdir($dir, 0o777, true);
        $this->tmpDirs[] = $dir;

        return $dir;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }
}
