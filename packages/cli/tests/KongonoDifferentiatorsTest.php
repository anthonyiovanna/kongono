<?php

declare(strict_types=1);

namespace Kongono\Cli\Tests;

use Closure;
use Kongono\Cli\Diagnostics\IncidentReplayer;
use Kongono\Cli\Diagnostics\KongonoAppInspector;
use Kongono\Cli\KongoCli;
use Kongono\Cli\Lsp\KongonoLanguageServer;
use Kongono\Cli\Mcp\KongonoMcpFactory;
use Kongono\Cli\Perf\PerfProver;
use Kongono\Cli\Policy\Invariant;
use Kongono\Cli\Policy\PolicyVerifier;
use Kongono\Core\Diagnostics\FileIncidentStore;
use Kongono\Http\App;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Security\Attributes\Authenticated;
use Kongono\Security\Attributes\VerifyWebhook;
use Kongono\Validation\Attributes\Required;
use PhpToken;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class RegisterAccountDto
{
    public function __construct(
        #[Required]
        public string $email,
        #[Required]
        public string $password,
        public string $role = 'member',
    ) {}
}

final readonly class PolicyAdminController
{
    #[Authenticated]
    public function protectedDashboard(): Response
    {
        return Response::text('admin ok');
    }

    public function exposedUsers(): Response
    {
        return Response::text('unprotected');
    }

    #[VerifyWebhook('STRIPE_SECRET')]
    public function verifiedWebhook(): Response
    {
        return Response::text('verified');
    }

    public function unverifiedWebhook(): Response
    {
        return Response::text('unverified');
    }
}

final class KongonoDifferentiatorsTest extends TestCase
{
    public function testFullStackContractProofCatchesLinkFormDtoAndViewVarDrift(): void
    {
        $tmp = $this->makeTempDir();

        try {
            $this->writeFile(
                $tmp . '/views/pages/register.kgn.html',
                <<<'HTML'
<nav>
  <a href="/settings">Settings</a>
  <a href="/settngs">Typo Link</a>
  <a href="/logo.svg">Static Asset</a>
  <a href="/users/{{ $user->id }}">Dynamic Link</a>
</nav>
<h1>Welcome {{ $usr->name }}</h1>
@foreach ($orders as $order)
  <p>{{ $order }}</p>
@endforeach
<form method="POST" action="/about">
  <button type="submit">Wrong Method</button>
</form>
<form method="POST" action="/register">
  <input type="text" name="emial">
  <input type="text" name="role" value="member">
  <button type="submit">Create</button>
</form>
HTML,
            );

            $this->writeFile(
                $tmp . '/src/RegisterController.php',
                <<<'PHP'
<?php
declare(strict_types=1);

namespace Demo;

use Kongono\Http\Response;
use Kongono\View\ViewEngine;

final readonly class RegisterController
{
    public function __construct(private ViewEngine $views) {}

    public function form(): Response
    {
        return Response::html($this->views->render('pages/register', [
            'user' => (object) ['id' => 1, 'name' => 'Ada'],
            'orders' => ['#1001'],
        ]));
    }
}
PHP,
            );

            $app = App::create($tmp, 'Contract Demo');
            $app->router()->get('/settings', static fn(): Response => Response::text('settings'));
            $app->router()->get('/about', static fn(): Response => Response::text('about'));
            $app->router()->post('/register', static fn(RegisterAccountDto $dto): Response => Response::json(['ok' => true, 'email' => $dto->email]));

            $report = (new KongonoAppInspector())->inspect($tmp, $app);
            $byCode = [];
            foreach ($report['findings'] as $f) {
                $byCode[$f['code']][] = $f;
            }

            // Rule 15a: Broken `<a href="/settngs">` caught with Did-You-Mean `/settings` (static asset & dynamic link ignored!)
            self::assertArrayHasKey('KGN-DIAG-BROKEN-LINK', $byCode);
            self::assertCount(1, $byCode['KGN-DIAG-BROKEN-LINK']);
            self::assertContains('/settings', $byCode['KGN-DIAG-BROKEN-LINK'][0]['did_you_mean']);

            // Rule 15b: `<form method="POST" action="/about">` caught as 405 mismatch
            self::assertArrayHasKey('KGN-DIAG-FORM-ROUTE', $byCode);
            self::assertStringContainsString('POST /about', $byCode['KGN-DIAG-FORM-ROUTE'][0]['what']);

            // Rule 16: Form <-> DTO catches misspelled `emial` (suggests `email`) AND missing required `email` & `password` (NOT optional `role`)
            self::assertArrayHasKey('KGN-DIAG-FORM-DTO-MISMATCH', $byCode);
            self::assertContains('email', $byCode['KGN-DIAG-FORM-DTO-MISMATCH'][0]['did_you_mean']);
            self::assertArrayHasKey('KGN-DIAG-FORM-DTO-MISSING', $byCode);
            $missingWhats = implode("\n", array_column($byCode['KGN-DIAG-FORM-DTO-MISSING'], 'what'));
            self::assertStringContainsString('name="email"', $missingWhats);
            self::assertStringContainsString('name="password"', $missingWhats);
            self::assertStringNotContainsString('name="role"', $missingWhats);

            // Rule 17: Controller <-> View catches `$usr` typo and suggests `$user`
            self::assertArrayHasKey('KGN-DIAG-VIEW-VAR-TYPO', $byCode);
            self::assertContains('$user', $byCode['KGN-DIAG-VIEW-VAR-TYPO'][0]['did_you_mean']);

            // LSP also emits live red squiggles for Form <-> DTO drift on unsaved buffers!
            $lsp = new KongonoLanguageServer($tmp, $app);
            $uri = 'file:///' . ltrim(str_replace('\\', '/', $tmp . '/views/pages/unsaved.kgn.html'), '/');
            $replies = $lsp->handleMessage([
                'jsonrpc' => '2.0',
                'method' => 'textDocument/didOpen',
                'params' => [
                    'textDocument' => [
                        'uri' => $uri,
                        'text' => "<form method=\"POST\" action=\"/register\"><input name=\"emial\"></form>\n",
                    ],
                ],
            ]);
            self::assertCount(1, $replies);
            $lspCodes = array_column($replies[0]['params']['diagnostics'], 'code');
            self::assertContains('KGN-DIAG-FORM-DTO-MISMATCH', $lspCodes);
            self::assertContains('KGN-DIAG-FORM-DTO-MISSING', $lspCodes);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testProductionIncidentTimeMachineRecordsSanitizesReplaysAndGeneratesTest(): void
    {
        $tmp = $this->makeTempDir();

        try {
            $flagFile = $tmp . DIRECTORY_SEPARATOR . 'fixed.flag';
            $flagExport = var_export($flagFile, true);
            $this->writeFile(
                $tmp . '/app/bootstrap.php',
                <<<PHP
<?php
declare(strict_types=1);

use Kongono\Http\App;
use Kongono\Http\Request;
use Kongono\Http\Response;

\$app = App::create(dirname(__DIR__), 'Incident Time Machine Demo')
    ->debug(false)
    ->recordIncidents();

\$app->router()->post('/checkout', static function (Request \$req): Response {
    if (!is_file({$flagExport})) {
        throw new \\RuntimeException('Division by zero in tax calculator');
    }
    return Response::json(['ok' => true, 'sku' => \$req->input('sku')]);
});

return \$app;
PHP,
            );

            // 1. Trigger a production 500 with secrets in headers & body
            /** @var App $prodApp */
            $prodApp = require $tmp . '/app/bootstrap.php';
            $resp = $prodApp->handle(Request::json(
                'POST',
                '/checkout?coupon=SAVE10',
                ['sku' => 'WIDGET-9', 'password' => 'hunter2', 'card_number' => '4111-1111'],
                [
                    'Authorization' => 'Bearer sk_live_supersecret999',
                    'Cookie' => 'kongono_session=deadbeef',
                    'X-Tenant' => 'acme',
                ],
            ));

            self::assertSame(500, $resp->getStatusCode());
            $refId = $resp->getHeaderLine('X-Kongono-Error-Id');
            self::assertMatchesRegularExpression('/\AKGN-\d{8}-[A-F0-9]{8}\z/', $refId);
            // Zero-leak production response!
            $prodBody = (string) $resp->getBody();
            self::assertStringNotContainsString('Division by zero', $prodBody);
            self::assertStringNotContainsString('hunter2', $prodBody);

            // 2. Verify stored incident scrubbed ALL secrets & rejects path traversal
            $store = new FileIncidentStore($tmp . '/storage/incidents');
            self::assertNull($store->find('../etc/passwd'));
            $record = $store->find($refId);
            self::assertNotNull($record);
            self::assertSame(500, $record->statusCode);
            self::assertSame('POST', $record->method);
            self::assertSame('/checkout', $record->path);
            self::assertArrayNotHasKey('Authorization', $record->headers);
            self::assertArrayNotHasKey('Cookie', $record->headers);
            self::assertSame('acme', $record->headers['X-Tenant'] ?? null);
            self::assertSame('[REDACTED]', $record->body['password'] ?? null);
            self::assertSame('[REDACTED]', $record->body['card_number'] ?? null);
            self::assertSame('WIDGET-9', $record->body['sku'] ?? null);

            $cli = new KongoCli(dirname(__DIR__, 3));

            // 3. `kongo incident:list` & `kongo incident:show`
            [$listCode, $listOut] = $cli->run(['incident:list', '--app=' . $tmp]);
            self::assertSame(0, $listCode);
            self::assertStringContainsString($refId, $listOut);

            [$showCode, $showOut] = $cli->run(['incident:show', $refId, '--app=' . $tmp]);
            self::assertSame(0, $showCode);
            self::assertStringContainsString('Division by zero', $showOut);
            self::assertStringContainsString('incident:replay ' . $refId, $showOut);

            // 4. `kongo incident:replay` BEFORE fix -> STILL FAILING (exit 1)
            [$failReplayCode, $failReplayOut] = $cli->run(['incident:replay', $refId, '--app=' . $tmp]);
            self::assertSame(1, $failReplayCode);
            self::assertStringContainsString('STILL FAILING', $failReplayOut);
            self::assertStringContainsString('HTTP 500 -> HTTP 500', $failReplayOut);

            // 5. Apply fix -> `kongo incident:replay` -> RESOLVED (HTTP 500 -> HTTP 200, exit 0)!
            file_put_contents($flagFile, 'fixed');
            [$okReplayCode, $okReplayOut] = $cli->run(['incident:replay', $refId, '--app=' . $tmp]);
            self::assertSame(0, $okReplayCode);
            self::assertStringContainsString('RESOLVED', $okReplayOut);
            self::assertStringContainsString('HTTP 500 -> HTTP 200', $okReplayOut);

            // 6. MCP `kongono_incident_replay` blocks mutating POST replay under default read-only mcp-reader role,
            // and succeeds when explicitly opted in with allowMutatingReplay: true
            /** @var App $reloadedApp */
            $reloadedApp = require $tmp . '/app/bootstrap.php';
            $readOnlyMcp = KongonoMcpFactory::forApp($tmp, $reloadedApp);
            $blockedRaw = $readOnlyMcp->handleJson(json_encode([
                'jsonrpc' => '2.0',
                'id' => 90,
                'method' => 'tools/call',
                'params' => ['name' => 'kongono_incident_replay', 'arguments' => ['reference' => $refId]],
            ], JSON_THROW_ON_ERROR));
            self::assertIsString($blockedRaw);
            $blockedRpc = json_decode($blockedRaw, true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($blockedRpc);
            self::assertTrue($blockedRpc['result']['isError']);

            $mcp = KongonoMcpFactory::forApp($tmp, $reloadedApp, allowMutatingReplay: true);
            $rpcRaw = $mcp->handleJson(json_encode([
                'jsonrpc' => '2.0',
                'id' => 91,
                'method' => 'tools/call',
                'params' => ['name' => 'kongono_incident_replay', 'arguments' => ['reference' => $refId]],
            ], JSON_THROW_ON_ERROR));
            self::assertIsString($rpcRaw);
            $rpc = json_decode($rpcRaw, true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($rpc);
            self::assertFalse($rpc['result']['isError']);
            $mcpReplay = json_decode((string) $rpc['result']['content'][0]['text'], true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($mcpReplay);
            self::assertTrue($mcpReplay['resolved']);
            self::assertSame(200, $mcpReplay['replayed_status']);

            // 7. `kongo incident:test <REF> --apply` generates valid PHP 8.5 PHPUnit 13 regression test
            [$testCode, $testOut] = $cli->run(['incident:test', $refId, '--app=' . $tmp, '--apply']);
            self::assertSame(0, $testCode);
            self::assertStringContainsString('tests/Incidents/Incident', $testOut);
            $generatedFiles = glob($tmp . '/tests/Incidents/*.php');
            self::assertIsArray($generatedFiles);
            self::assertCount(1, $generatedFiles);
            $genPhp = (string) file_get_contents($generatedFiles[0]);
            self::assertStringContainsString('assertLessThan(500', $genPhp);
            // Proves generated PHP 8.5 file parses cleanly with zero syntax errors
            PhpToken::tokenize($genPhp, TOKEN_PARSE);

            // Direct IncidentReplayer unit check
            $direct = (new IncidentReplayer())->replay($reloadedApp, $record);
            self::assertTrue($direct['resolved']);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testExecutableArchitectureAndSecurityInvariantsPolicyVerifier(): void
    {
        $tmp = $this->makeTempDir();

        try {
            // Forbidden cross-layer import: App\Billing -> App\Admin
            $this->writeFile(
                $tmp . '/src/Billing/InvoiceService.php',
                <<<'PHP'
<?php
declare(strict_types=1);

namespace App\Billing;

use App\Admin\InternalBackoffice;

final readonly class InvoiceService
{
    public function note(): string
    {
        return InternalBackoffice::class;
    }
}
PHP,
            );

            $app = App::create($tmp, 'Policy Demo');
            $app->router()->get('/admin/login', static fn(): Response => Response::text('login'));
            $app->router()->get('/admin/dashboard', [PolicyAdminController::class, 'protectedDashboard']);
            $app->router()->get('/admin/users', [PolicyAdminController::class, 'exposedUsers']);
            $app->router()->post('/api/webhooks/verified', [PolicyAdminController::class, 'verifiedWebhook']);
            $app->router()->post('/api/webhooks/stripe', [PolicyAdminController::class, 'unverifiedWebhook']);
            $app->router()->post('/api/orders', static fn(Request $req): Response => Response::json(['raw' => true]));
            $app->router()->post('/api/accounts', static fn(RegisterAccountDto $dto): Response => Response::json(['ok' => true]));

            $customRules = [
                Invariant::routesMatching('/api/*', 'KGN-POLICY-API-DTO')
                    ->except('/api/webhooks/*')
                    ->methods('POST', 'PUT', 'PATCH')
                    ->mustBindInputDto(),
                Invariant::namespace('App\\Billing', 'KGN-POLICY-NO-BILLING-TO-ADMIN')
                    ->mustNotDependOn('App\\Admin'),
            ];

            $report = (new PolicyVerifier())->verify($tmp, $app, $customRules);

            self::assertFalse($report['passed']);
            self::assertTrue($report['custom_policy_loaded']);
            self::assertSame(4, $report['rules_evaluated']);

            $codes = array_column($report['violations'], 'code');
            // 1. Default rule catches `/admin/users` (NOT `/admin/login` or `#[Authenticated] /admin/dashboard`)
            self::assertContains('KGN-POLICY-ADMIN-AUTH', $codes);
            // 2. Default rule catches `/api/webhooks/stripe` (NOT `#[VerifyWebhook] /api/webhooks/verified`)
            self::assertContains('KGN-POLICY-WEBHOOK-VERIFY', $codes);
            // 3. Custom DTO rule catches raw `/api/orders` (NOT DTO-backed `/api/accounts`)
            self::assertContains('KGN-POLICY-API-DTO', $codes);
            // 4. Custom namespace boundary rule catches `App\Billing` -> `App\Admin`
            self::assertContains('KGN-POLICY-NO-BILLING-TO-ADMIN', $codes);
            self::assertCount(4, $report['violations']);

            // MCP `kongono_policy_check` works cleanly
            $mcp = KongonoMcpFactory::forApp($tmp, $app);
            $rpcRaw = $mcp->handleJson('{"jsonrpc":"2.0","id":92,"method":"tools/call","params":{"name":"kongono_policy_check"}}');
            self::assertIsString($rpcRaw);
            $rpc = json_decode($rpcRaw, true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($rpc);
            self::assertFalse($rpc['result']['isError']);
            $mcpPolicy = json_decode((string) $rpc['result']['content'][0]['text'], true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($mcpPolicy);
            self::assertFalse($mcpPolicy['passed']);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    public function testPerfProverDetectsNPlusOneGlobalMutationAndPassesCleanApps(): void
    {
        $tmp = $this->makeTempDir();

        try {
            // 1. Clean persistent-worker app passes 100/100
            $cleanApp = App::create($tmp, 'Clean Worker App');
            $cleanApp->router()->get('/health', static fn(): Response => Response::json(['ok' => true]));
            $cleanApp->router()->get('/items/{id}', static fn(int $id): Response => Response::text((string) $id)); // dynamic route skipped automatically

            $cleanReport = (new PerfProver())->prove($cleanApp, iterations: 3);
            self::assertTrue($cleanReport['passed']);
            self::assertSame(100, $cleanReport['score']);
            self::assertSame(1, $cleanReport['routes_probed']);
            self::assertSame(0, $cleanReport['n_plus_one_detected']);
            self::assertSame(0, $cleanReport['leaks_detected']);
            self::assertSame(0, $cleanReport['routes'][0]['active_ops_after']);

            // 2. Leaky + N+1 app caught deterministically
            /** @var ?Closure(string, float): void $activeListener */
            $activeListener = null;
            $emitQuery = static function (string $sql, float $ms) use (&$activeListener): void {
                if (null !== $activeListener) {
                    $activeListener($sql, $ms);
                }
            };

            $badApp = App::create($tmp, 'Leaky App');
            $badApp->router()->get('/n-plus-one', static function () use ($emitQuery): Response {
                for ($i = 1; $i <= 5; ++$i) {
                    $emitQuery(sprintf("SELECT * FROM line_items WHERE order_id = %d AND status = 'open'", $i), 0.4);
                }

                return Response::json(['count' => 5]);
            });
            $badApp->router()->get('/mutate-global', static function (): Response {
                $_GET['polluted_by_handler'] = 'leaked';

                return Response::text('oops');
            });

            $badReport = (new PerfProver())->prove(
                app: $badApp,
                iterations: 2,
                attachQueryListener: static function (Closure $listener) use (&$activeListener): Closure {
                    $activeListener = $listener;

                    return static function () use (&$activeListener): void {
                        $activeListener = null;
                    };
                },
            );

            self::assertFalse($badReport['passed']);
            self::assertSame(1, $badReport['n_plus_one_detected']);
            self::assertSame(1, $badReport['leaks_detected']);
            $codes = array_column($badReport['findings'], 'code');
            self::assertContains('KGN-PERF-N-PLUS-ONE', $codes);
            self::assertContains('KGN-PERF-GLOBAL-MUTATION', $codes);

            // 3. MCP `kongono_perf_probe` works via JSON-RPC
            $mcp = KongonoMcpFactory::forApp($tmp, $cleanApp);
            $rpcRaw = $mcp->handleJson('{"jsonrpc":"2.0","id":93,"method":"tools/call","params":{"name":"kongono_perf_probe","arguments":{"iterations":2}}}');
            self::assertIsString($rpcRaw);
            $rpc = json_decode($rpcRaw, true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($rpc);
            self::assertFalse($rpc['result']['isError']);
            $mcpPerf = json_decode((string) $rpc['result']['content'][0]['text'], true, 32, JSON_THROW_ON_ERROR);
            self::assertIsArray($mcpPerf);
            self::assertTrue($mcpPerf['passed']);
            self::assertSame(100, $mcpPerf['score']);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    private function makeTempDir(): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-diff-' . bin2hex(random_bytes(6));
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
