<?php

declare(strict_types=1);

namespace Kongono\Cli\Tests;

use Closure;
use Kongono\Cli\Diagnostics\AppAutoFixer;
use Kongono\Cli\Diagnostics\KongonoAppInspector;
use Kongono\Cli\KongoCli;
use Kongono\Cli\Lsp\KongonoLanguageServer;
use Kongono\Cli\Mcp\KongonoMcpFactory;
use Kongono\Core\Cache\MemoryCache;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\Dialect;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Migrator;
use Kongono\Database\QueryBuilder;
use Kongono\Http\App;
use Kongono\Http\Live\LiveMiddleware;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Session\CacheSessionStore;
use Kongono\Http\Session\Session;
use Kongono\Http\Session\SessionMiddleware;
use Kongono\Security\Middleware\CsrfMiddleware;
use Kongono\Security\TokenSigner;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

interface UnboundPaymentGatewayInterface {}

final readonly class UnboundInterfaceController
{
    public function __construct(public UnboundPaymentGatewayInterface $gateway) {}

    public function charge(): Response
    {
        return Response::text('ok');
    }
}

final readonly class UnboundScalarController
{
    public function __construct(public string $secretApiKey) {}

    public function status(): Response
    {
        return Response::text($this->secretApiKey);
    }
}

final readonly class CyclicServiceA
{
    public function __construct(public CyclicServiceB $b) {}
}

final readonly class CyclicServiceB
{
    public function __construct(public CyclicServiceA $a) {}
}

final readonly class CyclicController
{
    public function __construct(public CyclicServiceA $a) {}

    public function ping(): Response
    {
        return Response::text('pong');
    }
}

final class KongonoAppInspectorTest extends TestCase
{
    public function testHealthyAppScores100WithZeroFindings(): void
    {
        $tmp = $this->makeTempDir();

        try {
            $this->writeFile($tmp . '/views/layouts/app.kgn.html', "<!doctype html><html><body>@yield('content')@liveScript</body></html>");
            $this->writeFile($tmp . '/views/components/card.kgn.html', '<div class="card">{!! $slot !!}</div>');
            $this->writeFile(
                $tmp . '/views/pages/home.kgn.html',
                <<<'HTML'
@extends('layouts/app')
@section('title', 'Home')
@section('content')
<div @state(['open' => false])>
  <button k-on:click="toggle(open)">Toggle</button>
  <div k-show="open">
    <section @state(['tab' => 'a'])>
      <x-card>
        <form method="POST" action="/save">
          <input type="hidden" name="_csrf" value="{{ $csrf }}">
        </form>
        <pre><code><x-ui.example-in-code /></code></pre>
      </x-card>
    </section>
  </div>
</div>
@endsection
HTML,
            );
            $this->writeFile(
                $tmp . '/src/HomeController.php',
                <<<'PHP'
<?php
declare(strict_types=1);

namespace Demo;

use Kongono\Http\Response;
use Kongono\View\ViewEngine;

final readonly class HomeController
{
    public const string VERSION = '1.0';

    public function __construct(private ViewEngine $views) {}

    public static function helperName(): string
    {
        return self::class;
    }

    public function index(): Response
    {
        $markdown = new class () {
            public function render(string $md): string
            {
                return $md;
            }
        };
        $markdown->render('not-a-view-template');

        return Response::html($this->views->render('pages/home'));
    }
}
PHP,
            );

            $app = App::create($tmp, 'Healthy Demo');
            $cache = new MemoryCache();
            $app->middleware(
                new SessionMiddleware(new CacheSessionStore($cache)),
                new CsrfMiddleware(signer: new TokenSigner(str_repeat('k', 32))),
                new LiveMiddleware(),
            );
            $app->router()->get('/', static fn(): Response => Response::text('ok'));
            $app->router()->post('/save', static fn(): Response => Response::text('saved'));
            $app->router()->get('/orders/{id}', static fn(Request $req, int $id): Response => Response::text((string) $id));

            $report = (new KongonoAppInspector())->inspect($tmp, $app);

            self::assertSame(100, $report['score']);
            self::assertTrue($report['passed']);
            self::assertSame(0, $report['counts']['error']);
            self::assertSame(0, $report['counts']['warning']);
            self::assertSame([], $report['findings']);
            self::assertSame(3, $report['routes_checked']);
            self::assertSame(3, $report['views_checked']);
            self::assertSame(1, $report['php_files_checked']);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testDetectsAllFrameworkNativeRulesWith5W1H(): void
    {
        $tmp = $this->makeTempDir();

        try {
            $this->writeFile($tmp . '/views/layouts/app.kgn.html', '<html>@yield("content")</html>');
            $this->writeFile($tmp . '/views/pages/home.kgn.html', '<h1>Home</h1>');
            $this->writeFile($tmp . '/views/components/badge.kgn.html', '<span>{!! $slot !!}</span>');

            // Deliberately buggy template exercising Rules 2, 3, 4, 5 (all 3 sub-rules), 6b (@extends), and 7 (missing component)
            $this->writeFile(
                $tmp . '/views/pages/broken.kgn.html',
                <<<'HTML'
@extends('layouts/ap')
@section('title', 'Catalog (Beta)')
@section('content')
<div @state(['open' => false, 'cat' => 'all'])>
  <button k-on:click="open = true">Open</button>
  <section @state(['view' => 'preview']) k-show="cat === 'all'">Wrong scope order</section>
  <form method="POST" action="/submit">
    <input type="hidden" name="_token" value="{{ $csrf }}">
  </form>
  @if ($a) A @endif @if ($b) B @endif
  @for ($i = 0; $i < 3; $i++)
    <span>{{ $i }}</span>
  @endfor
  <x-ui.modal title="Hello" />
  <x-badg>Typo</x-badg>
</div>
@endsection
HTML,
            );

            // Deliberately buggy PHP controller exercising Rules 6 (missing view) & 9 (mutable static property)
            $this->writeFile(
                $tmp . '/src/BrokenController.php',
                <<<'PHP'
<?php
declare(strict_types=1);

namespace Demo;

use Kongono\Http\Response;
use Kongono\View\ViewEngine;

final class BrokenController
{
    private static array $leakedUsers = [];

    public function __construct(private readonly ViewEngine $views) {}

    public function show(int $id): Response
    {
        self::$leakedUsers[] = $id;
        return Response::html($this->views->render('pages/hom'));
    }
}
PHP,
            );

            // Live App exercising Rule 1 (CsrfMiddleware BEFORE SessionMiddleware + missing LiveMiddleware),
            // Rule 8 (route param {orderId} vs $id), and Rule 10 (duplicate GET /health)
            $app = App::create($tmp, 'Broken Demo');
            $cache = new MemoryCache();
            $app->middleware(
                new CsrfMiddleware(signer: new TokenSigner(str_repeat('k', 32))),
                new SessionMiddleware(new CacheSessionStore($cache)),
            );
            $app->router()->get('/health', static fn(): Response => Response::text('first'));
            $app->router()->get('/health', static fn(): Response => Response::text('second'));
            $app->router()->get('/orders/{orderId}', static fn(Request $req, int $id): Response => Response::text((string) $id));

            $report = (new KongonoAppInspector())->inspect($tmp, $app);

            self::assertFalse($report['passed']);
            self::assertLessThan(100, $report['score']);

            $byCode = [];
            foreach ($report['findings'] as $f) {
                $byCode[$f['code']][] = $f;
                // Every single finding must satisfy the 5W1H contract
                self::assertNotSame('', $f['who']);
                self::assertNotSame('', $f['what']);
                self::assertNotSame('', $f['why']);
                self::assertNotSame('', $f['where']);
                self::assertNotEmpty($f['how_to_fix']);
            }

            // Rule 1: Middleware ordering + missing LiveMiddleware
            self::assertArrayHasKey('KGN-DIAG-MW-ORDER', $byCode);
            self::assertArrayHasKey('KGN-DIAG-LIVE-MW-MISSING', $byCode);

            // Rule 2: Live 2.5 assignment instead of set(...)
            self::assertArrayHasKey('KGN-DIAG-LIVE-ASSIGN', $byCode);
            self::assertStringContainsString('set(open, true)', (string) $byCode['KGN-DIAG-LIVE-ASSIGN'][0]['code_fix']);

            // Rule 3: k-show on same element as @state(['view' => 'preview']) (even with => inside @state!)
            self::assertArrayHasKey('KGN-DIAG-LIVE-SHOW-SCOPE', $byCode);

            // Rule 4: _token instead of _csrf
            self::assertArrayHasKey('KGN-DIAG-CSRF-FIELD', $byCode);
            self::assertStringContainsString('name="_csrf"', (string) $byCode['KGN-DIAG-CSRF-FIELD'][0]['code_fix']);

            // Rule 5: TemplateCompiler gotchas
            self::assertArrayHasKey('KGN-DIAG-TEMPLATE-MULTI-IF', $byCode);
            self::assertArrayHasKey('KGN-DIAG-TEMPLATE-SECTION-PAREN', $byCode);
            self::assertArrayHasKey('KGN-DIAG-TEMPLATE-FOR', $byCode);

            // Rule 6: Missing views (both @extends('layouts/ap') and $this->views->render('pages/hom'))
            self::assertArrayHasKey('KGN-DIAG-MISSING-VIEW', $byCode);
            self::assertGreaterThanOrEqual(2, count($byCode['KGN-DIAG-MISSING-VIEW']));
            $allViewSuggestions = array_merge(...array_column($byCode['KGN-DIAG-MISSING-VIEW'], 'did_you_mean'));
            self::assertContains('pages/home', $allViewSuggestions);
            self::assertContains('layouts/app', $allViewSuggestions);

            // Rule 7: Missing components (<x-ui.modal> suggests zokeibi add modal; <x-badg> suggests <x-badge>)
            self::assertArrayHasKey('KGN-DIAG-MISSING-COMPONENT', $byCode);
            $zokeibiFindings = array_values(array_filter(
                $byCode['KGN-DIAG-MISSING-COMPONENT'],
                static fn(array $f): bool => 'php vendor/bin/zokeibi add modal' === $f['command'],
            ));
            self::assertCount(1, $zokeibiFindings);
            $allCompSuggestions = array_merge(...array_column($byCode['KGN-DIAG-MISSING-COMPONENT'], 'did_you_mean'));
            self::assertContains('<x-badge>', $allCompSuggestions);

            // Rule 8: Route param reflection mismatch ({orderId} vs $id)
            self::assertArrayHasKey('KGN-DIAG-ROUTE-PARAM', $byCode);
            self::assertStringContainsString('$id', $byCode['KGN-DIAG-ROUTE-PARAM'][0]['why']);

            // Rule 9: Mutable static property leak
            self::assertArrayHasKey('KGN-DIAG-PERSISTENT-STATIC', $byCode);
            self::assertStringContainsString('$leakedUsers', $byCode['KGN-DIAG-PERSISTENT-STATIC'][0]['what']);

            // Rule 10: Duplicate route
            self::assertArrayHasKey('KGN-DIAG-DUPLICATE-ROUTE', $byCode);
            self::assertStringContainsString('GET /health', $byCode['KGN-DIAG-DUPLICATE-ROUTE'][0]['what']);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testDetectsCsrfWithoutSessionMiddleware(): void
    {
        $tmp = $this->makeTempDir();

        try {
            $app = App::create($tmp, 'Csrf Only');
            $app->middleware(new CsrfMiddleware(signer: new TokenSigner(str_repeat('s', 32))));

            $report = (new KongonoAppInspector())->inspect($tmp, $app);
            $codes = array_column($report['findings'], 'code');

            self::assertContains('KGN-DIAG-MW-CSRF-NO-SESSION', $codes);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testKongoCliDiagnoseCommandAndTypoHintsAndMcpTool(): void
    {
        $cli = new KongoCli(dirname(__DIR__, 3));

        // 1. Levenshtein "Did you mean" on unknown CLI commands
        [$typoCode, $typoOut] = $cli->run(['rouets']);
        self::assertSame(1, $typoCode);
        self::assertStringContainsString('Did you mean: `kongo routes`?', $typoOut);

        [$typoDiagCode, $typoDiagOut] = $cli->run(['diagnoze']);
        self::assertSame(1, $typoDiagCode);
        self::assertStringContainsString('Did you mean: `kongo diagnose`?', $typoDiagOut);

        // 2. Help text documents `diagnose`
        [$helpCode, $helpOut] = $cli->run(['help']);
        self::assertSame(0, $helpCode);
        self::assertStringContainsString('diagnose [--app=path]', $helpOut);

        // 3. `kongo diagnose --json` on an app with a known issue + boot-time exception formatting
        $tmp = $this->makeTempDir();
        try {
            $this->writeFile($tmp . '/views/pages/index.kgn.html', '<button k-on:click="count = 1">Bad</button>');

            [$diagCode, $diagJson] = $cli->run(['diagnose', '--app=' . $tmp, '--json']);
            self::assertSame(1, $diagCode);
            $decoded = json_decode($diagJson, true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($decoded);
            self::assertFalse($decoded['passed']);
            self::assertSame('KGN-DIAG-LIVE-ASSIGN', $decoded['findings'][0]['code']);

            // 4. MCP `kongono_diagnose` tool returns identical 5W1H JSON via JSON-RPC
            $mcp = KongonoMcpFactory::forApp($tmp);
            $rawResp = $mcp->handleJson('{"jsonrpc":"2.0","id":77,"method":"tools/call","params":{"name":"kongono_diagnose"}}');
            self::assertIsString($rawResp);
            $rpc = json_decode($rawResp, true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($rpc);
            self::assertFalse($rpc['result']['isError']);
            $mcpReport = json_decode((string) $rpc['result']['content'][0]['text'], true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($mcpReport);
            self::assertSame('KGN-DIAG-LIVE-ASSIGN', $mcpReport['findings'][0]['code']);
            self::assertStringContainsString('set(count, 1)', (string) $mcpReport['findings'][0]['code_fix']);

            // 5. Boot-time exception inside app/bootstrap.php renders plain-English 5W1H CLI output
            $this->writeFile($tmp . '/app/bootstrap.php', "<?php\nthrow new \\RuntimeException('SQLSTATE[HY000]: General error: 1 no such table: invoices');\n");
            [$bootCode, $bootOut] = $cli->run(['diagnose', '--app=' . $tmp]);
            self::assertSame(1, $bootCode);
            self::assertStringContainsString('KGN-ERR-DB-NO-TABLE', $bootOut);
            self::assertStringContainsString('Why:', $bootOut);
            self::assertStringContainsString('How to fix:', $bootOut);
            self::assertStringContainsString('kongo migrate', $bootOut);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testDetectsFourDeeperFrameworkRulesDiSyntaxFormCsrfAndMigrations(): void
    {
        $tmp = $this->makeTempDir();

        try {
            // Rule 12: Template syntax error (unclosed @if)
            $this->writeFile(
                $tmp . '/views/pages/unclosed.kgn.html',
                "<div>\n  @if (\$show)\n    <p>Missing endif</p>\n</div>\n",
            );

            // Rule 13: Browser POST form missing `_csrf` (while API, Live, and GET forms must NOT false-positive)
            $this->writeFile(
                $tmp . '/views/pages/forms.kgn.html',
                <<<'HTML'
<form method="POST" action="/profile">
  <input type="text" name="display_name">
  <button type="submit">Save</button>
</form>
<form method="POST" action="/api/webhooks"><button>API OK</button></form>
<form method="POST" action="/live" k-on:submit="save()"><button>Live OK</button></form>
<form method="GET" action="/search"><input name="q"></form>
HTML,
            );

            // Rule 14: Pending database migration in migrations/*.php
            $this->writeFile(
                $tmp . '/migrations/001_create_widgets.php',
                <<<'PHP'
<?php
declare(strict_types=1);

use Kongono\Database\ConnectionInterface;
use Kongono\Database\MigrationInterface;

return new class () implements MigrationInterface {
    public function id(): string { return '001_create_widgets'; }
    public function up(ConnectionInterface $db): void { $db->execute('CREATE TABLE widgets (id INTEGER PRIMARY KEY)'); }
    public function down(ConnectionInterface $db): void { $db->execute('DROP TABLE IF EXISTS widgets'); }
};
PHP,
            );

            $applied = [];
            $db = new class ($applied) implements ConnectionInterface {
                /** @param list<string> $applied */
                public function __construct(public array &$applied) {}

                public function dialect(): Dialect
                {
                    return Dialect::Postgres;
                }

                public function query(string $sql, array $bindings = []): array
                {
                    return array_map(static fn(string $id): array => ['id' => $id], $this->applied);
                }

                public function first(string $sql, array $bindings = []): ?array
                {
                    return null;
                }

                public function execute(string $sql, array $bindings = []): int
                {
                    if (str_starts_with($sql, 'INSERT INTO "kongono_migrations"') && is_string($bindings[0] ?? null)) {
                        $this->applied[] = $bindings[0];
                    }

                    return 1;
                }

                public function table(string $table, ?string $alias = null): QueryBuilder
                {
                    return new QueryBuilder($table, Dialect::Postgres, $this, $alias);
                }

                public function transaction(Closure $callback): mixed
                {
                    return $callback($this);
                }
            };
            $app = App::create($tmp, 'Deep Rules Demo');
            $cache = new MemoryCache();
            $app->container()->instance(ConnectionInterface::class, $db);
            $app->middleware(
                new SessionMiddleware(new CacheSessionStore($cache)),
                new CsrfMiddleware(signer: new TokenSigner(str_repeat('d', 32))),
            );

            // Rule 11: Unbound interface controller, unbound scalar controller, cyclic DI controller, and unbound interface action param
            $app->router()->get('/charge', [UnboundInterfaceController::class, 'charge']);
            $app->router()->get('/status', [UnboundScalarController::class, 'status']);
            $app->router()->get('/cycle', [CyclicController::class, 'ping']);
            $app->router()->get('/action-di', static fn(UnboundPaymentGatewayInterface $gw): Response => Response::text('ok'));

            $report = (new KongonoAppInspector())->inspect($tmp, $app);
            $byCode = [];
            foreach ($report['findings'] as $f) {
                $byCode[$f['code']][] = $f;
            }

            // Rule 11: All 4 DI problems caught
            self::assertArrayHasKey('KGN-DIAG-DI-UNRESOLVABLE', $byCode);
            self::assertCount(4, $byCode['KGN-DIAG-DI-UNRESOLVABLE']);
            $diWhats = implode("\n", array_column($byCode['KGN-DIAG-DI-UNRESOLVABLE'], 'what'));
            self::assertStringContainsString('UnboundPaymentGatewayInterface', $diWhats);
            self::assertStringContainsString('secretApiKey', $diWhats);
            self::assertStringContainsString('Circular DI dependency', $diWhats);

            // Rule 12: Template syntax error caught via PhpToken::tokenize(..., TOKEN_PARSE)
            self::assertArrayHasKey('KGN-DIAG-TEMPLATE-SYNTAX', $byCode);
            self::assertSame('views/pages/unclosed.kgn.html', $byCode['KGN-DIAG-TEMPLATE-SYNTAX'][0]['file']);

            // Rule 13: Only the plain browser POST /profile form flagged (not /api/, k-on:submit, or GET)
            self::assertArrayHasKey('KGN-DIAG-FORM-NO-CSRF', $byCode);
            self::assertCount(1, $byCode['KGN-DIAG-FORM-NO-CSRF']);
            self::assertStringContainsString('/profile', $byCode['KGN-DIAG-FORM-NO-CSRF'][0]['what']);

            // Rule 14: Pending migration detected
            self::assertArrayHasKey('KGN-DIAG-MIGRATIONS-PENDING', $byCode);
            self::assertStringContainsString('001_create_widgets', $byCode['KGN-DIAG-MIGRATIONS-PENDING'][0]['what']);
            self::assertSame('php vendor/bin/kongo migrate', $byCode['KGN-DIAG-MIGRATIONS-PENDING'][0]['command']);

            // Verify Session action parameter without SessionMiddleware triggers Rule 11
            $noSessionApp = App::create($tmp, 'No Session App');
            $noSessionApp->router()->get('/me', static fn(Session $session): Response => Response::text($session->id()));
            $noSessionReport = (new KongonoAppInspector())->inspect($tmp, $noSessionApp);
            $noSessionCodes = array_column($noSessionReport['findings'], 'code');
            self::assertContains('KGN-DIAG-DI-UNRESOLVABLE', $noSessionCodes);

            // Verify applying migration + binding interface eliminates those findings cleanly
            $migration = require $tmp . '/migrations/001_create_widgets.php';
            self::assertInstanceOf(MigrationInterface::class, $migration);
            (new Migrator($db))->migrate([$migration]);

            $cleanApp = App::create($tmp, 'Bound Clean App');
            $cleanApp->container()->instance(ConnectionInterface::class, $db);
            $cleanApp->container()->instance(UnboundPaymentGatewayInterface::class, new class implements UnboundPaymentGatewayInterface {});
            $cleanApp->router()->get('/charge', [UnboundInterfaceController::class, 'charge']);
            $cleanReport = (new KongonoAppInspector())->inspect($tmp, $cleanApp);
            $cleanCodes = array_column($cleanReport['findings'], 'code');
            self::assertNotContains('KGN-DIAG-DI-UNRESOLVABLE', $cleanCodes);
            self::assertNotContains('KGN-DIAG-MIGRATIONS-PENDING', $cleanCodes);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testAutoFixerDryRunAndApplyFixesTemplatesSafely(): void
    {
        $tmp = $this->makeTempDir();

        try {
            $viewPath = $tmp . '/views/pages/form.kgn.html';
            $original = <<<'HTML'
{{-- commented example: <input type="hidden" name="_token"> --}}
<div @state(['open' => false, 'step' => 1])>
  <button k-on:click="open = true; step = 2">Next</button>
  <form method="POST" action="/save">
    <input type="hidden" name="_token" value="{{ $csrf }}">
  </form>
</div>
HTML;
            $this->writeFile($viewPath, $original);

            $cli = new KongoCli(dirname(__DIR__, 3));

            // 1. `--fix --dry-run` previews diff without altering file on disk
            [$dryCode, $dryOut] = $cli->run(['diagnose', '--app=' . $tmp, '--fix', '--dry-run']);
            self::assertSame(1, $dryCode);
            self::assertStringContainsString('Autofix dry-run preview: 2 fix(es) across 1 file(s).', $dryOut);
            self::assertStringContainsString('--- a/views/pages/form.kgn.html', $dryOut);
            self::assertStringContainsString('+  <button k-on:click="set(open, true); set(step, 2)">Next</button>', $dryOut);
            self::assertStringContainsString('+    <input type="hidden" name="_csrf" value="{{ $csrf }}">', $dryOut);
            self::assertSame($original, file_get_contents($viewPath));

            // 2. `--fix` applies changes and re-diagnosis passes at 100/100!
            [$fixCode, $fixOut] = $cli->run(['diagnose', '--app=' . $tmp, '--fix']);
            self::assertSame(0, $fixCode);
            self::assertStringContainsString('Autofix applied: 2 fix(es) across 1 file(s).', $fixOut);
            self::assertStringContainsString('Score: 100/100 (PASS)', $fixOut);

            $updated = (string) file_get_contents($viewPath);
            // Commented line untouched; active lines rewritten
            self::assertStringContainsString('{{-- commented example: <input type="hidden" name="_token"> --}}', $updated);
            self::assertStringContainsString('k-on:click="set(open, true); set(step, 2)"', $updated);
            self::assertStringContainsString('name="_csrf"', $updated);

            // 3. Re-running AppAutoFixer is idempotent (0 changes)
            $second = (new AppAutoFixer())->fixDirectory($tmp);
            self::assertSame(0, $second['files_changed']);
            self::assertSame(0, $second['total_fixes']);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testLanguageServerStdioProtocolDiagnosticsHoverAndQuickFix(): void
    {
        $tmp = $this->makeTempDir();

        try {
            $this->writeFile($tmp . '/views/pages/home.kgn.html', '<h1>Home</h1>');
            $docPath = $tmp . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'edit.kgn.html';
            $uri = 'file:///' . ltrim(str_replace('\\', '/', $docPath), '/');

            $buggyBuffer = "<form method=\"POST\" action=\"/save\">\n  <input type=\"hidden\" name=\"_token\" value=\"{{ \$csrf }}\">\n  <button k-on:click=\"open = true\">Open</button>\n</form>\n";
            $fixedBuffer = "<form method=\"POST\" action=\"/save\">\n  <input type=\"hidden\" name=\"_csrf\" value=\"{{ \$csrf }}\">\n  <button k-on:click=\"set(open, true)\">Open</button>\n</form>\n";

            $messages = [
                ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['processId' => null, 'rootUri' => null, 'capabilities' => []]],
                ['jsonrpc' => '2.0', 'method' => 'initialized', 'params' => []],
                ['jsonrpc' => '2.0', 'method' => 'textDocument/didOpen', 'params' => ['textDocument' => ['uri' => $uri, 'languageId' => 'html', 'version' => 1, 'text' => $buggyBuffer]]],
                ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'textDocument/hover', 'params' => ['textDocument' => ['uri' => $uri], 'position' => ['line' => 1, 'character' => 10]]],
                ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'textDocument/codeAction', 'params' => ['textDocument' => ['uri' => $uri], 'range' => ['start' => ['line' => 1, 'character' => 0], 'end' => ['line' => 2, 'character' => 40]], 'context' => ['diagnostics' => []]]],
                ['jsonrpc' => '2.0', 'method' => 'textDocument/didChange', 'params' => ['textDocument' => ['uri' => $uri, 'version' => 2], 'contentChanges' => [['text' => $fixedBuffer]]]],
                ['jsonrpc' => '2.0', 'method' => 'textDocument/didClose', 'params' => ['textDocument' => ['uri' => $uri]]],
                ['jsonrpc' => '2.0', 'id' => 4, 'method' => 'shutdown', 'params' => null],
                ['jsonrpc' => '2.0', 'method' => 'exit', 'params' => null],
            ];

            $in = fopen('php://memory', 'r+b');
            $out = fopen('php://memory', 'r+b');
            self::assertIsResource($in);
            self::assertIsResource($out);

            foreach ($messages as $m) {
                $json = json_encode($m, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                fwrite($in, sprintf("Content-Length: %d\r\n\r\n%s", strlen($json), $json));
            }
            rewind($in);

            $server = new KongonoLanguageServer($tmp);
            $exitCode = $server->serveStreams($in, $out);
            self::assertSame(0, $exitCode);

            rewind($out);
            $replies = $this->readLspFrames($out);
            fclose($in);
            fclose($out);

            // 1. Initialize reply advertises hoverProvider & quickfix codeActionProvider
            self::assertSame(1, $replies[0]['id']);
            self::assertTrue($replies[0]['result']['capabilities']['hoverProvider']);
            self::assertSame(['quickfix'], $replies[0]['result']['capabilities']['codeActionProvider']['codeActionKinds']);
            self::assertSame('kongono-lsp', $replies[0]['result']['serverInfo']['name']);

            // 2. didOpen published both KGN-DIAG-CSRF-FIELD and KGN-DIAG-LIVE-ASSIGN diagnostics
            self::assertSame('textDocument/publishDiagnostics', $replies[1]['method']);
            $openCodes = array_column($replies[1]['params']['diagnostics'], 'code');
            self::assertContains('KGN-DIAG-CSRF-FIELD', $openCodes);
            self::assertContains('KGN-DIAG-LIVE-ASSIGN', $openCodes);
            self::assertSame('Kongono', $replies[1]['params']['diagnostics'][0]['source']);

            // 3. Hover on line 1 returns Markdown 5W1H explanation
            self::assertSame(2, $replies[2]['id']);
            self::assertSame('markdown', $replies[2]['result']['contents']['kind']);
            self::assertStringContainsString('KGN-DIAG-CSRF-FIELD', $replies[2]['result']['contents']['value']);
            self::assertStringContainsString('**Why:**', $replies[2]['result']['contents']['value']);
            self::assertStringContainsString('**How to fix:**', $replies[2]['result']['contents']['value']);

            // 4. codeAction returns one-click quickfix WorkspaceEdits for both lines
            self::assertSame(3, $replies[3]['id']);
            self::assertCount(2, $replies[3]['result']);
            self::assertSame('quickfix', $replies[3]['result'][0]['kind']);
            self::assertStringContainsString('name="_csrf"', $replies[3]['result'][0]['edit']['changes'][$uri][0]['newText']);
            self::assertStringContainsString('set(open, true)', $replies[3]['result'][1]['edit']['changes'][$uri][0]['newText']);

            // 5. didChange with fixed buffer clears diagnostics to []
            self::assertSame('textDocument/publishDiagnostics', $replies[4]['method']);
            self::assertSame([], $replies[4]['params']['diagnostics']);

            // 6. didClose publishes [] and shutdown returns null result
            self::assertSame('textDocument/publishDiagnostics', $replies[5]['method']);
            self::assertSame([], $replies[5]['params']['diagnostics']);
            self::assertSame(4, $replies[6]['id']);
            self::assertNull($replies[6]['result']);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testServeStartupDiagnosticsBannerAndHelp(): void
    {
        $cli = new KongoCli(dirname(__DIR__, 3));

        [$helpCode, $helpOut] = $cli->run(['help']);
        self::assertSame(0, $helpCode);
        self::assertStringContainsString('lsp [--app=path]', $helpOut);
        self::assertStringContainsString('[--fix] [--dry-run]', $helpOut);

        $tmp = $this->makeTempDir();
        try {
            $this->writeFile($tmp . '/views/pages/ok.kgn.html', '<h1>Clean</h1>');
            $cleanBanner = $cli->formatStartupDiagnostics($tmp);
            self::assertStringContainsString('[diagnose] 100/100 PASS', $cleanBanner);

            $this->writeFile($tmp . '/views/pages/bad.kgn.html', '<input type="hidden" name="_token">');
            $badBanner = $cli->formatStartupDiagnostics($tmp);
            self::assertStringContainsString('[diagnose] Score:', $badBanner);
            self::assertStringContainsString('KGN-DIAG-CSRF-FIELD', $badBanner);
            self::assertStringContainsString('kongo diagnose --fix', $badBanner);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    /**
     * @param resource $stream
     * @return list<array<string, mixed>>
     */
    private function readLspFrames($stream): array
    {
        $frames = [];
        while (!feof($stream)) {
            $length = null;
            while (true) {
                $line = fgets($stream);
                if (false === $line) {
                    return $frames;
                }
                $trimmed = trim($line);
                if ('' === $trimmed) {
                    break;
                }
                if (1 === preg_match('/\AContent-Length:\s*(\d+)\z/i', $trimmed, $m)) {
                    $length = (int) $m[1];
                }
            }
            if (null === $length || $length <= 0) {
                break;
            }
            $body = fread($stream, $length);
            if (false === $body) {
                break;
            }
            $decoded = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                /** @var array<string, mixed> $decoded */
                $frames[] = $decoded;
            }
        }

        return $frames;
    }

    private function makeTempDir(): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-diag-' . bin2hex(random_bytes(6));
        mkdir($dir, 0o777, true);

        return $dir;
    }

    private function writeFile(string $path, string $content): void
    {
        $parent = dirname($path);
        if (!is_dir($parent)) {
            mkdir($parent, 0o777, true);
        }
        file_put_contents($path, $content);
    }

    private function deleteDir(string $dir): void
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
