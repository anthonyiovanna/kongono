<?php

declare(strict_types=1);

namespace Kongono\Cli\Tests;

use FilesystemIterator;
use Kongono\Cli\KongoCli;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class AuthInitFeaturesCliTest extends TestCase
{
    public function testComposableWithFlagGeneratesOnlySelectedModulesAndValidatesInputs(): void
    {
        $tmp = $this->makeTempDir('auth_with_select');
        $cli = new KongoCli($tmp);

        try {
            [$newCode, $newOut] = $cli->run(['new', $tmp, '--name=Auth Demo', '--starter=minimal']);
            self::assertSame(0, $newCode, $newOut);

            // 1. Only passkey + qr requested -> no otp/magic-link/totp/oauth files created
            [$code, $out] = $cli->run(['auth:init', '--mode=web', '--with=passkey,qr', '--app=' . $tmp, '--no-bootstrap']);
            self::assertSame(0, $code, $out);
            self::assertStringContainsString('with: passkey, qr', $out);

            self::assertFileExists($tmp . '/src/Http/PasskeyAuthController.php');
            self::assertFileExists($tmp . '/src/Security/MemoryPasskeyStore.php');
            self::assertFileExists($tmp . '/src/Security/PdoPasskeyStore.php');
            self::assertFileExists($tmp . '/migrations/20260101000002_create_passkeys_table.php');
            self::assertFileExists($tmp . '/views/auth/passkey.kgn.html');
            self::assertFileExists($tmp . '/src/Http/QrLoginController.php');
            self::assertFileExists($tmp . '/views/auth/qr.kgn.html');
            self::assertFileExists($tmp . '/tests/AuthFeaturesTest.php');

            self::assertFileDoesNotExist($tmp . '/src/Http/OtpAuthController.php');
            self::assertFileDoesNotExist($tmp . '/src/Http/MagicLinkController.php');
            self::assertFileDoesNotExist($tmp . '/src/Http/TotpController.php');
            self::assertFileDoesNotExist($tmp . '/src/Http/OAuthController.php');

            // 2. Reject unknown feature names clearly
            [$badCode, $badOut] = $cli->run(['auth:init', '--with=passkey,unknown-auth', '--app=' . $tmp, '--no-bootstrap']);
            self::assertSame(1, $badCode);
            self::assertStringContainsString('Unsupported auth:init feature', $badOut);

            // 3. Space-separated --with all generates all 6 modules and syncs OAuth env vars via kongo env:sync
            [$allCode, $allOut] = $cli->run(['auth:init', '--mode=both', '--with', 'all', '--force', '--app=' . $tmp, '--no-bootstrap']);
            self::assertSame(0, $allCode, $allOut);
            self::assertStringContainsString('with: passkey, qr, otp, magic-link, totp, oauth', $allOut);

            foreach ([
                'src/Http/PasskeyAuthController.php',
                'src/Http/QrLoginController.php',
                'src/Http/OtpAuthController.php',
                'src/Http/MagicLinkController.php',
                'src/Http/TotpController.php',
                'src/Http/OAuthController.php',
                'migrations/20260101000002_create_passkeys_table.php',
                'migrations/20260101000003_create_totp_factors_table.php',
                'src/Security/MemoryTotpStore.php',
            ] as $expected) {
                self::assertFileExists($tmp . '/' . $expected);
            }

            // Verify kongo env:sync automatically discovers OAUTH_* variables from OAuthController
            [$envCode, $envOut] = $cli->run(['env:sync', '--dry-run', '--app=' . $tmp, '--no-bootstrap']);
            self::assertSame(0, $envCode, $envOut);
            self::assertStringContainsString('OAUTH_GITHUB_CLIENT_ID', $envOut);
            self::assertStringContainsString('OAUTH_GOOGLE_CLIENT_ID', $envOut);

            // Verify every generated PHP file passes `php -l`
            $this->assertAllPhpFilesLintClean($tmp);
        } finally {
            $this->removeDir($tmp);
        }
    }

    public function testGeneratedAuthFeatureControllersExecuteEndToEndInIsolatedRuntime(): void
    {
        $tmp = $this->makeTempDir('auth_with_runtime');
        $cli = new KongoCli($tmp);

        try {
            [$code, $out] = $cli->run(['auth:init', '--mode=both', '--with=all', '--app=' . $tmp, '--no-bootstrap']);
            self::assertSame(0, $code, $out);

            $repoRoot = dirname(__DIR__, 3);
            $runner = $tmp . DIRECTORY_SEPARATOR . 'verify_runtime.php';
            file_put_contents($runner, self::runtimeProofScript($repoRoot, $tmp));

            $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($runner);
            $outputLines = [];
            $exitCode = 0;
            exec($cmd . ' 2>&1', $outputLines, $exitCode);
            $raw = implode("\n", $outputLines);

            self::assertSame(0, $exitCode, 'Isolated runtime proof failed: ' . $raw);
            $decoded = json_decode(trim($raw), true);
            self::assertIsArray($decoded, 'Expected JSON proof output, got: ' . $raw);
            self::assertTrue($decoded['passkey_ok'] ?? false, $raw);
            self::assertTrue($decoded['qr_ok'] ?? false, $raw);
            self::assertTrue($decoded['otp_ok'] ?? false, $raw);
            self::assertTrue($decoded['magic_ok'] ?? false, $raw);
            self::assertTrue($decoded['totp_ok'] ?? false, $raw);
            self::assertTrue($decoded['oauth_ok'] ?? false, $raw);
        } finally {
            $this->removeDir($tmp);
        }
    }

    private static function runtimeProofScript(string $repoRoot, string $appRoot): string
    {
        $autoload = var_export($repoRoot . '/vendor/autoload.php', true);
        $srcRoot = var_export($appRoot . '/src', true);
        $appDir = var_export($appRoot, true);

        return <<<PHP
<?php

declare(strict_types=1);

require {$autoload};

spl_autoload_register(static function (string \$class): void {
    if (!str_starts_with(\$class, 'App\\\\')) {
        return;
    }
    \$rel = str_replace('\\\\', '/', substr(\$class, 4)) . '.php';
    \$path = {$srcRoot} . '/' . \$rel;
    if (is_file(\$path)) {
        require \$path;
    }
});

use App\Http\MagicLinkController;
use App\Http\OAuthController;
use App\Http\OtpAuthController;
use App\Http\PasskeyAuthController;
use App\Http\QrLoginController;
use App\Http\TotpController;
use App\Security\MemoryPasskeyStore;
use App\Security\MemoryTotpStore;
use App\Security\MemoryUserStore;
use Kongono\Core\Cache\MemoryCache;
use Kongono\Http\App;
use Kongono\Http\Session\CacheSessionStore;
use Kongono\Http\Session\SessionMiddleware;
use Kongono\Http\Testing\TestClient;
use Kongono\Security\Auth\LoginThrottler;
use Kongono\Security\Auth\MagicLinkAuth;
use Kongono\Security\Auth\OneTimeCodeAuth;
use Kongono\Security\Auth\PasskeyVerifier;
use Kongono\Security\Auth\PkceOAuthClient;
use Kongono\Security\Auth\QrLoginAuth;
use Kongono\Security\Auth\SessionAuth;
use Kongono\Security\Encryptor;
use Kongono\Security\Middleware\SessionAuthMiddleware;
use Kongono\Security\PasswordHasher;
use Kongono\Security\Principal;
use Kongono\Security\Totp;

\$cache = new MemoryCache();
\$users = new MemoryUserStore();
\$hasher = new PasswordHasher();
\$users->create('Ada Lovelace', 'ada@kongono.dev', \$hasher->hash('correct-horse-battery-staple'));
\$sessionAuth = new SessionAuth();
\$throttler = new LoginThrottler(\$cache);
\$passkeys = new PasskeyVerifier(\$cache);
\$passkeyStore = new MemoryPasskeyStore();
\$qrAuth = new QrLoginAuth('qr-test-secret-key-at-least-32-bytes', \$cache);
\$otpAuth = new OneTimeCodeAuth('otp-test-secret-key-at-least-32-bytes', \$cache);
\$magicAuth = new MagicLinkAuth('magic-test-secret-key-at-least-32-bytes', \$cache);
\$totp = new Totp();
\$encryptor = new Encryptor('encryptor-test-key-32-bytes-minimum');
\$totpStore = new MemoryTotpStore();

\$deliveredOtp = '';
\$deliveredMagic = '';
\$githubOAuth = PkceOAuthClient::github(
    'gh_client_id',
    'gh_client_secret',
    transportOverride: static function (string \$method, string \$url): array {
        if (str_contains(\$url, 'access_token')) {
            return ['status' => 200, 'body' => '{"access_token":"gho_mock_token"}'];
        }
        return ['status' => 200, 'body' => '{"login":"ada","name":"Ada Lovelace","email":"ada@kongono.dev"}'];
    },
);

\$app = App::create({$appDir}, name: 'Auth Runtime Proof')
    ->instance(PasskeyAuthController::class, new PasskeyAuthController(
        \$passkeys,
        \$passkeyStore,
        \$users,
        \$sessionAuth,
        rpId: 'kongono.dev',
        rpName: 'Kongono App',
        origin: 'https://kongono.dev',
    ))
    ->instance(QrLoginController::class, new QrLoginController(\$qrAuth, \$sessionAuth))
    ->instance(OtpAuthController::class, new OtpAuthController(\$otpAuth, \$users, \$sessionAuth, \$throttler, static function (string \$email, string \$code) use (&\$deliveredOtp): void {
        \$deliveredOtp = \$code;
    }))
    ->instance(MagicLinkController::class, new MagicLinkController(\$magicAuth, \$users, \$sessionAuth, \$throttler, static function (string \$email, string \$path) use (&\$deliveredMagic): void {
        \$deliveredMagic = \$path;
    }))
    ->instance(TotpController::class, new TotpController(\$totp, \$encryptor, \$totpStore, \$cache))
    ->instance(OAuthController::class, new OAuthController(['github' => \$githubOAuth], \$users, \$hasher, \$sessionAuth, 'https://kongono.dev'))
    ->controller(PasskeyAuthController::class)
    ->controller(QrLoginController::class)
    ->controller(OtpAuthController::class)
    ->controller(MagicLinkController::class)
    ->controller(TotpController::class)
    ->controller(OAuthController::class);

\$app->middleware(
    new SessionMiddleware(new CacheSessionStore(\$cache), secure: false),
    new SessionAuthMiddleware(
        principalResolver: static fn(Principal \$p): ?Principal => \$users->findById(\$p->id)?->toPrincipal(),
        auth: \$sessionAuth,
    ),
);

// 1. OTP sign-in establishes session
\$client = (new TestClient(\$app))->withStatefulCookies();
\$client->postJson('/auth/otp/request', ['email' => 'ada@kongono.dev'])->assertStatus(202);
if ('' === \$deliveredOtp) {
    throw new RuntimeException('OTP was not delivered.');
}
\$otpRes = \$client->postJson('/auth/otp/verify', ['email' => 'ada@kongono.dev', 'code' => \$deliveredOtp])->assertOk();
\$otpOk = true === (\$otpRes->json()['authenticated'] ?? false);

// 2. Authenticated user registers an Ed25519 passkey, lists it, signs in on a fresh client via passkey, then deletes it
\$regOptsRes = \$client->postJson('/auth/passkeys/register/options', [])->assertOk();
\$regChallenge = (string) \$regOptsRes->json()['challenge'];
\$kp = sodium_crypto_sign_keypair();
\$pk = sodium_crypto_sign_publickey(\$kp);
\$sk = sodium_crypto_sign_secretkey(\$kp);
\$cborHead = static fn(int \$major, int \$val): string => \$val < 24 ? chr((\$major << 5) | \$val) : chr((\$major << 5) | 24) . chr(\$val);
\$cose = \$cborHead(5, 4)
    . \$cborHead(0, 1) . \$cborHead(0, 1)
    . \$cborHead(0, 3) . \$cborHead(1, 7)
    . \$cborHead(1, 0) . \$cborHead(0, 6)
    . \$cborHead(1, 1) . \$cborHead(2, 32) . \$pk;
\$credId = random_bytes(16);
\$authData = hash('sha256', 'kongono.dev', true) . chr(0x45) . pack('N', 0) . str_repeat("\\x00", 16) . pack('n', 16) . \$credId . \$cose;
\$att = \$cborHead(5, 3)
    . \$cborHead(3, 3) . 'fmt' . \$cborHead(3, 4) . 'none'
    . \$cborHead(3, 7) . 'attStmt' . \$cborHead(5, 0)
    . \$cborHead(3, 8) . 'authData' . chr((2 << 5) | 24) . chr(strlen(\$authData)) . \$authData;
\$regCd = json_encode(['type' => 'webauthn.create', 'challenge' => \$regChallenge, 'origin' => 'https://kongono.dev'], JSON_THROW_ON_ERROR);

\$regDone = \$client->postJson('/auth/passkeys/register/verify', [
    'clientDataJSON' => PasskeyVerifier::base64UrlEncode(\$regCd),
    'attestationObject' => PasskeyVerifier::base64UrlEncode(\$att),
    'label' => 'Test YubiKey',
    'transports' => ['usb'],
])->assertCreated();
\$credIdB64 = (string) \$regDone->json()['credential_id'];
\$listRes = \$client->get('/auth/passkeys')->assertOk();

\$pkClient = (new TestClient(\$app))->withStatefulCookies();
\$loginOpts = \$pkClient->postJson('/auth/passkeys/login/options', ['email' => 'ada@kongono.dev'])->assertOk();
\$loginChallenge = (string) \$loginOpts->json()['challenge'];
\$assertCd = json_encode(['type' => 'webauthn.get', 'challenge' => \$loginChallenge, 'origin' => 'https://kongono.dev'], JSON_THROW_ON_ERROR);
\$assertAd = hash('sha256', 'kongono.dev', true) . chr(0x05) . pack('N', 1);
\$assertSig = sodium_crypto_sign_detached(\$assertAd . hash('sha256', \$assertCd, true), \$sk);
\$pkLogin = \$pkClient->postJson('/auth/passkeys/login/verify', [
    'credentialId' => \$credIdB64,
    'clientDataJSON' => PasskeyVerifier::base64UrlEncode(\$assertCd),
    'authenticatorData' => PasskeyVerifier::base64UrlEncode(\$assertAd),
    'signature' => PasskeyVerifier::base64UrlEncode(\$assertSig),
])->assertOk();
\$client->delete('/auth/passkeys/' . \$credIdB64)->assertOk();
\$passkeyOk = true === (\$pkLogin->json()['authenticated'] ?? false) && 1 === count(\$listRes->json()['passkeys'] ?? []);

// 3. Cross-device QR login (desktop begins -> signed-in mobile inspects & approves)
\$desktop = (new TestClient(\$app))->withStatefulCookies();
\$qrBegin = \$desktop->postJson('/auth/qr/begin', [])->assertCreated();
\$qrData = \$qrBegin->json();
\$cid = (string) \$qrData['challenge_id'];
\$desktop->get('/auth/qr/poll/' . \$cid)->assertOk();
\$directQr = \$qrAuth->begin('/auth/qr/approve', requesterIp: '127.0.0.1');
\$inspectRes = \$client->postJson('/auth/qr/inspect', ['qr_payload' => \$directQr['qr_payload']])->assertOk();
\$approveRes = \$client->postJson('/auth/qr/approve', ['qr_payload' => \$directQr['qr_payload'], 'verification_code' => \$directQr['verification_code']])->assertOk();
\$qrOk = str_contains((string) (\$qrData['qr_svg'] ?? ''), '<svg')
    && \$directQr['verification_code'] === (\$inspectRes->json()['verification_code'] ?? '')
    && true === (\$approveRes->json()['approved'] ?? false);

// 4. Magic link sign-in
\$magicClient = (new TestClient(\$app))->withStatefulCookies();
\$magicClient->postJson('/auth/magic/request', ['email' => 'ada@kongono.dev'])->assertStatus(202);
\$magicVerify = \$magicClient->get(\$deliveredMagic)->assertOk();
\$magicOk = true === (\$magicVerify->json()['authenticated'] ?? false);

// 5. TOTP 2FA setup (SVG QR + 80-bit recovery codes) -> confirm -> verify recovery code -> disable
\$totpSetup = \$client->postJson('/auth/totp/setup', [])->assertCreated()->json();
\$secret = (string) \$totpSetup['secret'];
\$recoveryCodes = \$totpSetup['recovery_codes'];
\$client->postJson('/auth/totp/confirm', ['code' => \$totp->code(\$secret)])->assertOk();
\$verifyRecovery = \$client->postJson('/auth/totp/verify', ['code' => \$recoveryCodes[0]])->assertOk();
\$disableTotp = \$client->request(
    'DELETE',
    '/auth/totp',
    body: json_encode(['code' => \$recoveryCodes[1]], JSON_THROW_ON_ERROR),
    headers: ['Content-Type' => 'application/json'],
)->assertOk();
\$totpOk = str_contains((string) (\$totpSetup['qr_svg'] ?? ''), '<svg')
    && true === (\$verifyRecovery->json()['verified'] ?? false)
    && true === (\$disableTotp->json()['disabled'] ?? false);

// 6. OAuth 2.0 + PKCE S256 redirect -> callback
\$oauthClient = (new TestClient(\$app))->withStatefulCookies();
\$redir = \$oauthClient->get('/auth/oauth/github/redirect')->assertStatus(302);
\$loc = \$redir->header('Location');
parse_str((string) parse_url(\$loc, PHP_URL_QUERY), \$q);
\$state = is_string(\$q['state'] ?? null) ? \$q['state'] : '';
\$cb = \$oauthClient->get('/auth/oauth/github/callback?code=auth_code_1&state=' . rawurlencode(\$state))->assertOk();
\$oauthOk = true === (\$cb->json()['authenticated'] ?? false) && 'github' === (\$cb->json()['provider'] ?? '');

echo json_encode([
    'passkey_ok' => \$passkeyOk,
    'qr_ok' => \$qrOk,
    'otp_ok' => \$otpOk,
    'magic_ok' => \$magicOk,
    'totp_ok' => \$totpOk,
    'oauth_ok' => \$oauthOk,
], JSON_THROW_ON_ERROR);
PHP;
    }

    private function assertAllPhpFilesLintClean(string $dir): void
    {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || 'php' !== strtolower($file->getExtension())) {
                continue;
            }
            $out = [];
            $code = 0;
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $out, $code);
            self::assertSame(0, $code, 'PHP lint failed for ' . $file->getPathname() . ': ' . implode("\n", $out));
        }
    }

    private function makeTempDir(string $prefix): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono_' . $prefix . '_' . bin2hex(random_bytes(5));
        mkdir($dir, 0777, true);

        return $dir;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $item) {
            if ($item instanceof SplFileInfo) {
                $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}
