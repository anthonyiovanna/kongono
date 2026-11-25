<?php

declare(strict_types=1);

namespace Kongono\Cli\Tests;

use Closure;
use Kongono\Cli\Impact\AppSnapshot;
use Kongono\Cli\Impact\ImpactAnalyzer;
use Kongono\Cli\KongoCli;
use Kongono\Cli\Mcp\KongonoMcpFactory;
use Kongono\Cli\Proof\MasterProver;
use Kongono\Cli\Security\IsolationProbe;
use Kongono\Cli\Security\TenantIsolationProver;
use Kongono\Cli\Shadow\ShadowDiffProver;
use Kongono\Cli\Upgrade\CodemodInterface;
use Kongono\Cli\Upgrade\UpgradeEngine;
use Kongono\Core\Capabilities\ExecutionCapabilities;
use Kongono\Core\Capabilities\QueryGuardableInterface;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\Dialect;
use Kongono\Database\QueryBuilder;
use Kongono\Http\App;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Get;
use Kongono\Security\Attributes\Authenticated;
use Kongono\Security\Attributes\MaxAiCost;
use Kongono\Security\Attributes\MaxQueries;
use Kongono\Security\Attributes\NoOutboundHttp;
use Kongono\Security\Attributes\ReadOnlyDb;
use Kongono\Security\Egress\SafeHttpClient;
use Kongono\Security\Egress\SafeHttpFake;
use Kongono\Security\Principal;
use Kongono\Security\PrincipalKind;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class GuardableFakeDb implements ConnectionInterface, QueryGuardableInterface
{
    /** @var list<string> */
    public array $executedSql = [];

    /** @var array<int, Closure(string): void> */
    private array $guards = [];

    private int $seq = 0;

    public function guardBeforeQuery(Closure $guard): Closure
    {
        $id = ++$this->seq;
        $this->guards[$id] = $guard;

        return function () use ($id): void {
            unset($this->guards[$id]);
        };
    }

    public function dialect(): Dialect
    {
        return Dialect::Postgres;
    }

    public function query(string $sql, array $bindings = []): array
    {
        foreach ($this->guards as $g) {
            $g($sql);
        }
        $this->executedSql[] = $sql;

        return [['id' => 1]];
    }

    public function first(string $sql, array $bindings = []): ?array
    {
        $rows = $this->query($sql, $bindings);

        return $rows[0] ?? null;
    }

    public function execute(string $sql, array $bindings = []): int
    {
        foreach ($this->guards as $g) {
            $g($sql);
        }
        $this->executedSql[] = $sql;

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
}

final readonly class SandboxedCapabilityController
{
    public function __construct(
        private ConnectionInterface $db,
        private SafeHttpClient $http,
    ) {}

    #[Get('/cap/read-ok')]
    #[ReadOnlyDb]
    public function readOk(): Response
    {
        $this->db->query('SELECT id FROM users');

        return Response::json(['ok' => true]);
    }

    #[Get('/cap/write-blocked')]
    #[ReadOnlyDb]
    public function writeBlocked(): Response
    {
        $this->db->execute('DELETE FROM users WHERE id = 1');

        return Response::json(['never' => true]);
    }

    #[Get('/cap/cte-write-blocked')]
    #[ReadOnlyDb]
    public function cteWriteBlocked(): Response
    {
        $this->db->execute('WITH target AS (SELECT 1) UPDATE users SET role = \'admin\'');

        return Response::json(['never' => true]);
    }

    #[Get('/cap/no-egress')]
    #[NoOutboundHttp]
    public function noEgress(): Response
    {
        $this->http->request('GET', 'https://api.example.com/exfiltrate');

        return Response::json(['never' => true]);
    }

    #[Get('/cap/egress-ok')]
    public function egressOk(): Response
    {
        $res = $this->http->request('GET', 'https://api.example.com/ok');

        return Response::json(['status' => $res['status']]);
    }

    #[Get('/cap/max-queries-fail')]
    #[MaxQueries(2)]
    public function maxQueriesFail(): Response
    {
        $this->db->query('SELECT 1');
        $this->db->query('SELECT 2');
        $this->db->query('SELECT 3');

        return Response::json(['never' => true]);
    }

    #[Get('/cap/max-ai-cost-fail')]
    #[MaxAiCost(0.05)]
    public function maxAiCostFail(Request $request): Response
    {
        $caps = $request->getAttribute(ExecutionCapabilities::class);
        if ($caps instanceof ExecutionCapabilities) {
            $caps->recordAiSpend(0.03);
            $caps->recordAiSpend(0.03);
        }

        return Response::json(['never' => true]);
    }
}

final readonly class OrderV1Dto
{
    public function __construct(
        public string $sku,
        public int $qty = 1,
    ) {}
}

final readonly class OrderV2Dto
{
    public function __construct(
        public string $qty,
        public string $currency,
    ) {}
}

final readonly class ImpactReportV1Controller
{
    #[Authenticated]
    #[ReadOnlyDb]
    public function index(): Response
    {
        return Response::text('reports');
    }
}

final readonly class ImpactReportV2Controller
{
    public function index(): Response
    {
        return Response::text('reports');
    }
}

final class KongonoNextFrontierTest extends TestCase
{
    public function testLeastPrivilegeRouteCapabilitiesEnforceAtDriverAndEgressLayers(): void
    {
        $tmp = $this->makeTempDir();

        try {
            $db = new GuardableFakeDb();
            $fake = new SafeHttpFake([
                'GET https://api.example.com/ok' => ['status' => 200, 'body' => 'ok'],
                'GET https://api.example.com/exfiltrate' => ['status' => 200, 'body' => 'leaked'],
            ]);
            $http = SafeHttpClient::fake($fake, allowedHosts: ['api.example.com']);

            $app = App::create($tmp, 'Capabilities Demo')->debug(true);
            $app->container()->instance(ConnectionInterface::class, $db);
            $app->container()->instance(SafeHttpClient::class, $http);
            $app->router()->controller(SandboxedCapabilityController::class);

            // 1. #[ReadOnlyDb] allows SELECT
            $okResp = $app->handle(Request::create('GET', '/cap/read-ok', ['Accept' => 'application/json']));
            self::assertSame(200, $okResp->getStatusCode());
            self::assertCount(1, $db->executedSql);

            // 2. #[ReadOnlyDb] blocks DELETE BEFORE reaching DB driver!
            $db->executedSql = [];
            $delResp = $app->handle(Request::create('GET', '/cap/write-blocked', ['Accept' => 'application/json']));
            self::assertSame(500, $delResp->getStatusCode());
            self::assertSame([], $db->executedSql);
            self::assertStringContainsString('ReadOnlyDb', (string) $delResp->getBody());

            // 3. #[ReadOnlyDb] blocks CTE write (`WITH ... UPDATE`) BEFORE reaching DB driver!
            $cteResp = $app->handle(Request::create('GET', '/cap/cte-write-blocked', ['Accept' => 'application/json']));
            self::assertSame(500, $cteResp->getStatusCode());
            self::assertSame([], $db->executedSql);

            // 4. #[NoOutboundHttp] blocks outbound HTTP BEFORE SafeHttpFake sees any request!
            $egressBlocked = $app->handle(Request::create('GET', '/cap/no-egress', ['Accept' => 'application/json']));
            self::assertSame(500, $egressBlocked->getStatusCode());
            self::assertStringContainsString('NoOutboundHttp', (string) $egressBlocked->getBody());
            self::assertSame(0, count($fake->recorded()));

            // 5. Persistent-worker cleanup proof: non-sandboxed route AFTER blocked route still succeeds cleanly!
            $egressOk = $app->handle(Request::create('GET', '/cap/egress-ok', ['Accept' => 'application/json']));
            self::assertSame(200, $egressOk->getStatusCode());
            self::assertSame(1, count($fake->recorded()));

            // 6. #[MaxQueries(2)] allows 2 queries and hard-blocks query #3!
            $db->executedSql = [];
            $maxQ = $app->handle(Request::create('GET', '/cap/max-queries-fail', ['Accept' => 'application/json']));
            self::assertSame(500, $maxQ->getStatusCode());
            self::assertCount(2, $db->executedSql);
            self::assertStringContainsString('MaxQueries(2)', (string) $maxQ->getBody());

            // 7. #[MaxAiCost(0.05)] hard-blocks cumulative AI spend exceeding cap!
            $maxAi = $app->handle(Request::create('GET', '/cap/max-ai-cost-fail', ['Accept' => 'application/json']));
            self::assertSame(500, $maxAi->getStatusCode());
            self::assertStringContainsString('MaxAiCost(0.0500)', (string) $maxAi->getBody());
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testTenantIsolationProverPassesIsolatedRoutesAndCatchesBolaLeaks(): void
    {
        $tmp = $this->makeTempDir();

        try {
            $tenantA = new Principal('user-a', PrincipalKind::Human, ['projects:read'], ['tenant_id' => 'acme']);
            $tenantB = new Principal('user-b', PrincipalKind::Human, ['projects:read'], ['tenant_id' => 'evilcorp']);

            $app = App::create($tmp, 'Multi-Tenant Demo');
            $app->router()->get('/projects/{id}', static function (Request $req, int $id): Response {
                $p = $req->getAttribute(Principal::class);
                if (!$p instanceof Principal) {
                    return Response::json(['error' => 'unauthorized'], 401);
                }
                if ('acme' !== ($p->attributes['tenant_id'] ?? null)) {
                    return Response::json(['error' => 'not_found'], 404);
                }

                return Response::json(['id' => $id, 'secret' => 'KGN-CANARY-ACME-991']);
            });
            $app->router()->get('/leaky/{id}', static function (Request $req, int $id): Response {
                $p = $req->getAttribute(Principal::class);
                if (!$p instanceof Principal) {
                    return Response::json(['error' => 'unauthorized'], 401);
                }

                // BUG: Forgot `tenant_id` check! Tenant B receives Tenant A's record!
                return Response::json(['id' => $id, 'secret' => 'KGN-CANARY-LEAK-777']);
            });

            $prover = new TenantIsolationProver();

            // 1. Properly isolated route passes 100/100
            $safeReport = $prover->prove($tmp, $app, [
                new IsolationProbe(
                    name: 'Tenant-scoped project read',
                    method: 'GET',
                    path: '/projects/42',
                    ownerPrincipal: $tenantA,
                    attackerPrincipal: $tenantB,
                    canaries: ['KGN-CANARY-ACME-991'],
                ),
            ]);
            self::assertTrue($safeReport['passed']);
            self::assertSame(100, $safeReport['score']);
            self::assertSame(0, $safeReport['leaks_detected']);

            // 2. Leaky BOLA/IDOR route is caught with CRITICAL KGN-ISOLATION-TENANT-LEAK!
            $leakyReport = $prover->prove($tmp, $app, [
                new IsolationProbe(
                    name: 'Unscoped BOLA endpoint',
                    method: 'GET',
                    path: '/leaky/42',
                    ownerPrincipal: $tenantA,
                    attackerPrincipal: $tenantB,
                    canaries: ['KGN-CANARY-LEAK-777'],
                ),
            ]);
            self::assertFalse($leakyReport['passed']);
            self::assertSame(1, $leakyReport['leaks_detected']);
            self::assertSame('KGN-ISOLATION-TENANT-LEAK', $leakyReport['findings'][0]['code']);
            self::assertStringContainsString('evilcorp', $leakyReport['findings'][0]['what']);

            // 3. MCP `kongono_prove_isolation` works via JSON-RPC
            $mcp = KongonoMcpFactory::forApp($tmp, $app);
            $rpcRaw = $mcp->handleJson('{"jsonrpc":"2.0","id":101,"method":"tools/call","params":{"name":"kongono_prove_isolation"}}');
            self::assertIsString($rpcRaw);
            $rpc = json_decode($rpcRaw, true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($rpc);
            self::assertFalse($rpc['result']['isError']);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testSemanticBlastRadiusImpactDetectsSecurityRegressionsAndBreakingDtos(): void
    {
        $tmp1 = $this->makeTempDir();
        $tmp2 = $this->makeTempDir();

        try {
            $this->writeFile($tmp1 . '/views/pages/old.kgn.html', '<h1>Old</h1>');
            $v1App = App::create($tmp1, 'Impact Demo');
            $v1App->router()->get('/reports', [ImpactReportV1Controller::class, 'index']);
            $v1App->router()->post('/orders', static fn(OrderV1Dto $dto): Response => Response::json(['sku' => $dto->sku]));
            $v1App->router()->delete('/legacy', static fn(): Response => Response::text('gone'));

            $baseline = AppSnapshot::capture($tmp1, $v1App);

            // Identical snapshot -> risk_level === 'none'
            $same = (new ImpactAnalyzer())->diff($baseline, $baseline);
            self::assertSame('none', $same['risk_level']);
            self::assertSame(0, $same['breaking_changes']);
            self::assertSame(0, $same['security_regressions']);

            // V2 introduces: security regression (lost #[Authenticated] & #[ReadOnlyDb]), removed DELETE /legacy,
            // breaking DTO changes (removed sku, int->string qty, optional->required qty, added required currency), removed view
            $v2App = App::create($tmp2, 'Impact Demo');
            $v2App->router()->get('/reports', [ImpactReportV2Controller::class, 'index']);
            $v2App->router()->post('/orders', static fn(OrderV2Dto $dto): Response => Response::json(['qty' => $dto->qty]));

            $current = AppSnapshot::capture($tmp2, $v2App);
            $diff = (new ImpactAnalyzer())->diff($baseline, $current);

            self::assertSame('critical', $diff['risk_level']);
            self::assertSame(2, $diff['security_regressions']);
            self::assertGreaterThanOrEqual(6, $diff['breaking_changes']);
            self::assertSame(['DELETE /legacy'], $diff['routes_removed']);
            self::assertSame(['pages/old'], $diff['views_removed']);

            $codes = array_column($diff['findings'], 'code');
            self::assertContains('KGN-IMPACT-SECURITY-REGRESSION', $codes);
            self::assertContains('KGN-IMPACT-ROUTE-REMOVED', $codes);
            self::assertContains('KGN-IMPACT-DTO-BREAKING', $codes);
            self::assertContains('KGN-IMPACT-VIEW-REMOVED', $codes);
        } finally {
            $this->deleteDir($tmp1);
            $this->deleteDir($tmp2);
        }
    }

    public function testShadowBehavioralEquivalenceProverDetectsShapeAndQueryRegressions(): void
    {
        $tmp = $this->makeTempDir();

        try {
            $prover = new ShadowDiffProver();
            /** @var ?Closure(string, float): void $listener */
            $listener = null;
            $binder = static function (Closure $cb) use (&$listener): Closure {
                $listener = $cb;

                return static function () use (&$listener): void {
                    $listener = null;
                };
            };
            $emit = static function (string $sql) use (&$listener): void {
                if (null !== $listener) {
                    $listener($sql, 0.2);
                }
            };

            $v1 = App::create($tmp, 'Shadow Demo');
            $v1->router()->get('/api/status', static function () use ($emit): Response {
                $emit('SELECT 1');

                return Response::json(['ok' => true, 'count' => 3]);
            });
            $v1->router()->get('/home', static fn(): Response => Response::html('<html><head><title>Home</title></head><body><h1>Dashboard</h1><form></form></body></html>'));

            $baseline = $prover->record($v1, attachQueryListener: $binder);

            // 1. Equivalent refactor (different value 99, identical shape & query count) -> EQUIVALENT!
            $v1Refactored = App::create($tmp, 'Shadow Demo');
            $v1Refactored->router()->get('/api/status', static function () use ($emit): Response {
                $emit('SELECT 1');

                return Response::json(['ok' => true, 'count' => 99]);
            });
            $v1Refactored->router()->get('/home', static fn(): Response => Response::html('<html><head><title>Home</title></head><body><h1>Dashboard</h1><form></form></body></html>'));

            $okDiff = $prover->diff($baseline, $v1Refactored, $binder);
            self::assertTrue($okDiff['equivalent']);
            self::assertSame(0, $okDiff['regressions']);

            // 2. Regressed refactor: dropped `ok`, changed `count` int->string, added 3 extra SQL queries, changed HTML title!
            $v2Broken = App::create($tmp, 'Shadow Demo');
            $v2Broken->router()->get('/api/status', static function () use ($emit): Response {
                $emit('SELECT 1');
                $emit('SELECT 2');
                $emit('SELECT 3');

                return Response::json(['count' => 'ninety-nine']);
            });
            $v2Broken->router()->get('/home', static fn(): Response => Response::html('<html><head><title>Oops</title></head><body><h1>Error</h1></body></html>'));

            $badDiff = $prover->diff($baseline, $v2Broken, $binder);
            self::assertFalse($badDiff['equivalent']);
            self::assertGreaterThanOrEqual(1, $badDiff['regressions']);
            $codes = array_column($badDiff['findings'], 'code');
            self::assertContains('KGN-SHADOW-JSON-SHAPE-DRIFT', $codes);
            self::assertContains('KGN-SHADOW-HTML-DRIFT', $codes);
            self::assertContains('KGN-SHADOW-QUERY-REGRESSION', $codes);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testVerifiableSelfUpgradingCodemodsPlanApplyVerifyAndRejectUnsafeTransforms(): void
    {
        $tmp = $this->makeTempDir();

        try {
            $phpPath = $tmp . '/src/LegacyService.php';
            $viewPath = $tmp . '/views/pages/legacy.kgn.html';

            $origPhp = "<?php\n\nnamespace Demo;\n\nfinal class LegacyService {}\n";
            $origView = <<<'HTML'
<form method="POST" action="/save">
  <input type="hidden" name="_token" value="{{ $csrf }}">
  <button k-on:click="open = true">Open</button>
  @for ($i = 0; $i < 3; $i++)
    <span>{{ $i }}</span>
  @endfor
</form>
HTML;
            $this->writeFile($phpPath, $origPhp);
            $this->writeFile($viewPath, $origView);

            $cli = new KongoCli(dirname(__DIR__, 3));

            // 1. `--plan` previews diffs without mutating files on disk
            [$planCode, $planOut] = $cli->run(['upgrade', '--app=' . $tmp, '--plan']);
            self::assertSame(0, $planCode);
            self::assertStringContainsString('PLAN', $planOut);
            self::assertStringContainsString('php85-strict-types', $planOut);
            self::assertStringContainsString('kongono-template-v1', $planOut);
            self::assertStringContainsString('template-for-to-foreach', $planOut);
            self::assertSame($origPhp, file_get_contents($phpPath));
            self::assertSame($origView, file_get_contents($viewPath));

            // 2. `--apply --verify` applies all 3 codemods AND proves post-upgrade diagnose=100/100 + policy=100/100!
            [$applyCode, $applyOut] = $cli->run(['upgrade', '--app=' . $tmp, '--apply', '--verify']);
            self::assertSame(0, $applyCode);
            self::assertStringContainsString('diagnose=100/100 (PASS)', $applyOut);
            self::assertStringContainsString('policy=100/100 (PASS)', $applyOut);

            self::assertStringContainsString('declare(strict_types=1);', (string) file_get_contents($phpPath));
            $upgradedView = (string) file_get_contents($viewPath);
            self::assertStringContainsString('name="_csrf"', $upgradedView);
            self::assertStringContainsString('set(open, true)', $upgradedView);
            self::assertStringContainsString('@foreach (range(0, 2) as $i)', $upgradedView);
            self::assertStringContainsString('@endforeach', $upgradedView);

            // 3. Safety invariant: broken codemod producing invalid PHP syntax is automatically skipped!
            $brokenCodemod = new class implements CodemodInterface {
                public function id(): string
                {
                    return 'broken-test';
                }

                public function title(): string
                {
                    return 'Intentionally invalid PHP transform';
                }

                public function targetVersion(): string
                {
                    return '9.9';
                }

                public function supportsFile(string $relativePath): bool
                {
                    return str_ends_with($relativePath, '.php');
                }

                public function transform(string $relativePath, string $content): array
                {
                    return ['changed' => true, 'updated' => '<?php class { broken syntax !!!', 'summary' => 'bad'];
                }
            };
            $safeRes = (new UpgradeEngine([$brokenCodemod]))->run($tmp, apply: true);
            self::assertSame(1, $safeRes['skipped_unsafe']);
            self::assertSame(0, $safeRes['files_changed']);
            self::assertStringContainsString('declare(strict_types=1);', (string) file_get_contents($phpPath));

            // 4. MCP `kongono_upgrade_plan` works via JSON-RPC
            $mcp = KongonoMcpFactory::forApp($tmp);
            $rpcRaw = $mcp->handleJson('{"jsonrpc":"2.0","id":104,"method":"tools/call","params":{"name":"kongono_upgrade_plan"}}');
            self::assertIsString($rpcRaw);
            $rpc = json_decode($rpcRaw, true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($rpc);
            self::assertFalse($rpc['result']['isError']);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testMasterProverDevStudioProofCenterAndVsCodeExtension(): void
    {
        $repoRoot = dirname(__DIR__, 3);
        $starterRoot = $repoRoot . DIRECTORY_SEPARATOR . 'examples' . DIRECTORY_SEPARATOR . 'starter-app';

        /** @var App $starterApp */
        $starterApp = require $starterRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';

        // 1. MasterProver returns Grade A+ (100/100 PASS) on examples/starter-app
        $report = (new MasterProver())->proveAll($starterRoot, $starterApp);
        self::assertTrue($report['passed']);
        self::assertSame(100, $report['overall_score']);
        self::assertSame('A+', $report['grade']);
        self::assertNotEmpty($report['route_capabilities']);

        // 2. CLI `kongo prove --json` exits 0 with Grade A+
        $cli = new KongoCli($repoRoot);
        [$proveCode, $proveJson] = $cli->run(['prove', '--app=' . $starterRoot, '--json']);
        self::assertSame(0, $proveCode);
        $decodedProve = json_decode($proveJson, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($decodedProve);
        self::assertSame('A+', $decodedProve['grade']);

        // 3. MCP `kongono_prove_all` returns Grade A+ over JSON-RPC
        $mcp = KongonoMcpFactory::forApp($starterRoot, $starterApp);
        $rpcRaw = $mcp->handleJson('{"jsonrpc":"2.0","id":109,"method":"tools/call","params":{"name":"kongono_prove_all"}}');
        self::assertIsString($rpcRaw);
        $rpc = json_decode($rpcRaw, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($rpc);
        self::assertFalse($rpc['result']['isError']);
        $mcpProve = json_decode((string) $rpc['result']['content'][0]['text'], true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($mcpProve);
        self::assertSame('A+', $mcpProve['grade']);

        // 4. `/_kongono` Dev Studio renders Proof Center AND `POST /_kongono/api/proofs` runs re-entrantly with 0 false-positive leaks!
        $starterApp->debug(true);
        $dashResp = $starterApp->handle(Request::create('GET', '/_kongono', serverParams: ['REMOTE_ADDR' => '127.0.0.1']));
        self::assertSame(200, $dashResp->getStatusCode());
        $dashHtml = (string) $dashResp->getBody();
        self::assertStringContainsString('data-tab="proofs"', $dashHtml);
        self::assertStringContainsString('Proof Center', $dashHtml);
        self::assertStringContainsString('Production Incident Time-Machine', $dashHtml);

        $proofsResp = $starterApp->handle(Request::create(
            'POST',
            '/_kongono/api/proofs',
            ['Content-Type' => 'application/json', 'X-Kongono-Dev' => '1'],
            '{}',
            serverParams: ['REMOTE_ADDR' => '127.0.0.1'],
        ));
        self::assertSame(200, $proofsResp->getStatusCode());
        $proofsJson = json_decode((string) $proofsResp->getBody(), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($proofsJson);
        self::assertTrue($proofsJson['passed']);
        self::assertSame(100, $proofsJson['overall_score']);
        self::assertSame('A+', $proofsJson['grade']);

        $incResp = $starterApp->handle(Request::create('GET', '/_kongono/api/incidents', serverParams: ['REMOTE_ADDR' => '127.0.0.1']));
        self::assertSame(200, $incResp->getStatusCode());

        // 5. Production fail-closed check: `debug(false)` completely disables `/_kongono/api/proofs`
        $starterApp->debug(false);
        $prodResp = $starterApp->handle(Request::create(
            'POST',
            '/_kongono/api/proofs',
            ['Content-Type' => 'application/json', 'X-Kongono-Dev' => '1'],
            '{}',
            serverParams: ['REMOTE_ADDR' => '127.0.0.1'],
        ));
        self::assertSame(404, $prodResp->getStatusCode());

        // 6. Zero-dependency VS Code extension manifests & TextMate grammar are valid JSON
        $extDir = $repoRoot . DIRECTORY_SEPARATOR . 'editors' . DIRECTORY_SEPARATOR . 'vscode-kongono';
        self::assertFileExists($extDir . DIRECTORY_SEPARATOR . 'extension.js');
        foreach (['package.json', 'language-configuration.json', 'syntaxes/kgn.tmLanguage.json'] as $relJson) {
            $parsed = json_decode((string) file_get_contents($extDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relJson)), true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($parsed);
        }
    }

    private function makeTempDir(): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-nf-' . bin2hex(random_bytes(6));
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
