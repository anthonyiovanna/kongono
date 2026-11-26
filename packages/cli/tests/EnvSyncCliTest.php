<?php

declare(strict_types=1);

namespace Kongono\Cli\Tests;

use Kongono\Cli\AppScaffolder;
use Kongono\Cli\Diagnostics\KongonoAppInspector;
use Kongono\Cli\KongoCli;
use Kongono\Http\App;
use Kongono\Http\Dev\DevDashboardMiddleware;
use Kongono\Http\Dev\DevInspector;
use Kongono\Http\Testing\TestClient;
use PHPUnit\Framework\TestCase;

final class EnvSyncCliTest extends TestCase
{
    public function testKongoNewEnvSyncDiagnoseFixAndDevStudioRuntimeDrift(): void
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-env-cli-' . bin2hex(random_bytes(5));
        mkdir($tmp, 0o775, true);
        $prevStripe = $_ENV['STRIPE_SECRET_KEY'] ?? null;

        try {
            // 1. `kongo new --starter=saas` generates a preset-aware `.env.example` including SESSION_SECURE
            $appDir = $tmp . DIRECTORY_SEPARATOR . 'saas-app';
            (new AppScaffolder())->scaffold($appDir, 'SaaS Demo', 'saas');
            $initialEnv = (string) file_get_contents($appDir . DIRECTORY_SEPARATOR . '.env.example');
            self::assertStringContainsString('APP_DEBUG=false', $initialEnv);
            self::assertStringContainsString('APP_KEY=REPLACE_WITH_kongo_key_generate', $initialEnv);
            self::assertStringContainsString('SESSION_SECURE=false', $initialEnv);

            $cli = new KongoCli($appDir);
            [$checkOkCode, $checkOkOut] = $cli->run(['env:sync', '--check', '--app=' . $appDir]);
            self::assertSame(0, $checkOkCode, $checkOkOut);
            self::assertStringContainsString('.env.example is in sync', $checkOkOut);

            // 2. Developer later adds Stripe to composer.json and reads a custom key in a controller
            $composer = json_decode((string) file_get_contents($appDir . '/composer.json'), true, 16, JSON_THROW_ON_ERROR);
            self::assertIsArray($composer);
            $composer['require']['stripe/stripe-php'] = '^16.0';
            file_put_contents($appDir . '/composer.json', json_encode($composer, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
            file_put_contents($appDir . '/src/Http/BillingController.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Http;
use Kongono\Core\Config;
final readonly class BillingController {
    public function mode(): string {
        return Config::env('BILLING_MODE', 'sandbox') ?? 'sandbox';
    }
}
PHP);

            // `env:sync --check` now catches the drift with exit code 1
            [$driftCode, $driftOut] = $cli->run(['env:sync', '--check', '--app=' . $appDir]);
            self::assertSame(1, $driftCode);
            self::assertStringContainsString('STRIPE_SECRET_KEY', $driftOut);
            self::assertStringContainsString('BILLING_MODE', $driftOut);

            // `KongonoAppInspector` (`kongo diagnose` / `kongo prove`) emits fixable `KGN-DIAG-ENV-DRIFT`
            $diagBefore = (new KongonoAppInspector())->inspect($appDir);
            $codesBefore = array_column($diagBefore['findings'], 'code');
            self::assertContains('KGN-DIAG-ENV-DRIFT', $codesBefore);

            // Dev Studio `/_kongono/api/runtime` exposes drift preview and NEVER leaks live secret values
            $fakeStripeKey = 'sk_' . 'test_' . '51NeverLeakIntoStudioRuntime999999';
            $_ENV['STRIPE_SECRET_KEY'] = $fakeStripeKey;
            $app = App::create($appDir, name: 'SaaS Demo')->debug(true);
            $app->middleware(new DevDashboardMiddleware($app, new DevInspector()));
            $runtimeRes = (new TestClient($app))->get('/_kongono/api/runtime')->assertOk();
            $runtimeBody = $runtimeRes->body();
            self::assertStringNotContainsString($fakeStripeKey, $runtimeBody);
            /** @var array{env_example: array{exists: bool, in_sync: bool, missing_keys: list<string>, detected_integrations: list<string>, diff_preview: string}, secret_env: list<array{key: string, configured: bool}>} $runtimeJson */
            $runtimeJson = json_decode($runtimeBody, true, 32, JSON_THROW_ON_ERROR);
            self::assertTrue($runtimeJson['env_example']['exists']);
            self::assertFalse($runtimeJson['env_example']['in_sync']);
            self::assertContains('Stripe', $runtimeJson['env_example']['detected_integrations']);
            self::assertContains('STRIPE_SECRET_KEY', $runtimeJson['env_example']['missing_keys']);
            self::assertStringContainsString('+ STRIPE_SECRET_KEY=', $runtimeJson['env_example']['diff_preview']);
            $stripeStatus = array_values(array_filter($runtimeJson['secret_env'], static fn(array $s): bool => 'STRIPE_SECRET_KEY' === $s['key']));
            self::assertCount(1, $stripeStatus);
            self::assertTrue($stripeStatus[0]['configured']);

            // 3. `kongo diagnose --fix` automatically fixes `.env.example` drift without leaking secrets
            [$fixCode, $fixOut] = $cli->run(['diagnose', '--fix', '--no-bootstrap', '--app=' . $appDir]);
            self::assertSame(0, $fixCode, $fixOut);
            self::assertStringContainsString('.env.example', $fixOut);

            $updatedEnv = (string) file_get_contents($appDir . '/.env.example');
            self::assertStringContainsString("STRIPE_SECRET_KEY=\n", $updatedEnv);
            self::assertStringContainsString("STRIPE_PUBLISHABLE_KEY=\n", $updatedEnv);
            self::assertStringContainsString("STRIPE_WEBHOOK_SECRET=\n", $updatedEnv);
            self::assertStringContainsString("BILLING_MODE=sandbox\n", $updatedEnv);
            self::assertStringNotContainsString('sk_' . 'test_', $updatedEnv);

            // Both `env:sync --check` and `KongonoAppInspector` are now 100% clean!
            [$afterCode, $afterOut] = $cli->run(['env:sync', '--check', '--no-bootstrap', '--app=' . $appDir]);
            self::assertSame(0, $afterCode, $afterOut);
            $diagAfter = (new KongonoAppInspector())->inspect($appDir);
            self::assertNotContains('KGN-DIAG-ENV-DRIFT', array_column($diagAfter['findings'], 'code'));

            // 4. Adding Supabase later and running `kongo env:sync` directly merges cleanly
            file_put_contents($appDir . '/src/Http/SupabaseController.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Http;
use Kongono\Core\Config;
final readonly class SupabaseController {
    public function endpoint(): string {
        return Config::env('SUPABASE_URL') ?? '';
    }
}
PHP);
            [$syncCode, $syncOut] = $cli->run(['env:sync', '--no-bootstrap', '--app=' . $appDir]);
            self::assertSame(0, $syncCode, $syncOut);
            self::assertStringContainsString('Supabase', $syncOut);
            self::assertStringContainsString('+ SUPABASE_URL=https://your-project.supabase.co', $syncOut);
            $afterSupabase = (string) file_get_contents($appDir . '/.env.example');
            self::assertStringContainsString("SUPABASE_ANON_KEY=\n", $afterSupabase);
            self::assertStringContainsString("SUPABASE_SERVICE_ROLE_KEY=\n", $afterSupabase);
            self::assertStringContainsString("STRIPE_SECRET_KEY=\n", $afterSupabase);
        } finally {
            if (null === $prevStripe) {
                unset($_ENV['STRIPE_SECRET_KEY']);
            } else {
                $_ENV['STRIPE_SECRET_KEY'] = $prevStripe;
            }
            $this->removeDir($tmp);
        }
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if (false !== $items) {
            foreach ($items as $item) {
                if ('.' === $item || '..' === $item) {
                    continue;
                }
                $path = $dir . DIRECTORY_SEPARATOR . $item;
                if (is_dir($path) && !is_link($path)) {
                    $this->removeDir($path);
                } else {
                    @unlink($path);
                }
            }
        }
        @rmdir($dir);
    }
}
