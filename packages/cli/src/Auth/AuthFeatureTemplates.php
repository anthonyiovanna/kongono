<?php

declare(strict_types=1);

namespace Kongono\Cli\Auth;

/**
 * Editable, PHPStan-L9-clean, decoupled auth add-on templates for `kongo auth:init --with=...`.
 *
 * Each feature module (`passkey`, `qr`, `otp`, `magic-link`, `totp`, `oauth`) is 100% standalone
 * and can be mixed & matched freely without pulling in unused auth flows.
 */
final readonly class AuthFeatureTemplates
{
    /** @var list<string> */
    public const array SUPPORTED_FEATURES = [
        'passkey',
        'qr',
        'otp',
        'magic-link',
        'totp',
        'oauth',
    ];

    /**
     * Collect all files for the requested normalized feature slugs.
     *
     * @param list<string> $features
     * @return array<string, string>
     */
    public static function filesFor(array $features, bool $includeWeb): array
    {
        $files = [];

        if (in_array('passkey', $features, true)) {
            $files['migrations/20260101000002_create_passkeys_table.php'] = self::passkeyMigration();
            $files['src/Security/PasskeyRecord.php'] = self::passkeyRecord();
            $files['src/Security/PasskeyStoreInterface.php'] = self::passkeyStoreInterface();
            $files['src/Security/MemoryPasskeyStore.php'] = self::memoryPasskeyStore();
            $files['src/Security/PdoPasskeyStore.php'] = self::pdoPasskeyStore();
            $files['src/Http/PasskeyAuthController.php'] = self::passkeyAuthController();
            if ($includeWeb) {
                $files['views/auth/passkey.kgn.html'] = self::passkeyView();
            }
        }

        if (in_array('qr', $features, true)) {
            $files['src/Http/QrLoginController.php'] = self::qrLoginController();
            if ($includeWeb) {
                $files['views/auth/qr.kgn.html'] = self::qrView();
            }
        }

        if (in_array('otp', $features, true)) {
            $files['src/Http/OtpAuthController.php'] = self::otpAuthController();
        }

        if (in_array('magic-link', $features, true)) {
            $files['src/Http/MagicLinkController.php'] = self::magicLinkController();
        }

        if (in_array('totp', $features, true)) {
            $files['migrations/20260101000003_create_totp_factors_table.php'] = self::totpMigration();
            $files['src/Security/TotpFactor.php'] = self::totpFactor();
            $files['src/Security/TotpStoreInterface.php'] = self::totpStoreInterface();
            $files['src/Security/MemoryTotpStore.php'] = self::memoryTotpStore();
            $files['src/Http/TotpController.php'] = self::totpController();
        }

        if (in_array('oauth', $features, true)) {
            $files['src/Http/OAuthController.php'] = self::oauthController();
        }

        if ([] !== $features) {
            $files['tests/AuthFeaturesTest.php'] = self::featuresTest($features);
        }

        return $files;
    }

    private static function passkeyMigration(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use Kongono\Database\ConnectionInterface;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Schema\Blueprint;
use Kongono\Database\Schema\Schema;

return new class () implements MigrationInterface {
    public function id(): string
    {
        return '20260101000002_create_passkeys_table';
    }

    public function up(ConnectionInterface $db): void
    {
        Schema::create($db, 'user_passkeys', static function (Blueprint $table): void {
            $table->string('credential_id', 255)->primary();
            $table->string('user_id', 120);
            $table->string('label', 120)->default('Passkey');
            $table->text('public_key');
            $table->string('algorithm', 16);
            $table->integer('sign_count')->default(0);
            $table->string('aaguid', 64)->default('00000000-0000-0000-0000-000000000000');
            $table->text('transports');
            $table->integer('last_used_at')->nullable();
            $table->integer('created_at');
        });
    }

    public function down(ConnectionInterface $db): void
    {
        Schema::dropIfExists($db, 'user_passkeys');
    }
};

PHP;
    }

    private static function passkeyRecord(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Security;

final readonly class PasskeyRecord
{
    /** @param list<string> $transports */
    public function __construct(
        public string $credentialId,
        public string $userId,
        public string $publicKey,
        public string $algorithm,
        public int $signCount,
        public string $label = 'Passkey',
        public string $aaguid = '00000000-0000-0000-0000-000000000000',
        public array $transports = [],
        public int $createdAt = 0,
        public ?int $lastUsedAt = null,
    ) {}
}

PHP;
    }

    private static function passkeyStoreInterface(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Security;

interface PasskeyStoreInterface
{
    public function save(PasskeyRecord $record): void;

    public function findByCredentialId(string $credentialId): ?PasskeyRecord;

    /** @return list<PasskeyRecord> */
    public function listForUser(string $userId): array;

    public function updateSignCount(string $credentialId, int $signCount, int $lastUsedAt): void;

    public function deleteForUser(string $credentialId, string $userId): bool;
}

PHP;
    }

    private static function memoryPasskeyStore(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Security;

/** Zero-config persistent-runtime-safe in-memory WebAuthn passkey store for local development & tests. */
final class MemoryPasskeyStore implements PasskeyStoreInterface
{
    /** @var array<string, PasskeyRecord> */
    private array $byCredentialId = [];

    public function save(PasskeyRecord $record): void
    {
        $this->byCredentialId[$record->credentialId] = $record;
    }

    public function findByCredentialId(string $credentialId): ?PasskeyRecord
    {
        return $this->byCredentialId[$credentialId] ?? null;
    }

    public function listForUser(string $userId): array
    {
        $items = [];
        foreach ($this->byCredentialId as $record) {
            if ($record->userId === $userId) {
                $items[] = $record;
            }
        }

        return $items;
    }

    public function updateSignCount(string $credentialId, int $signCount, int $lastUsedAt): void
    {
        $existing = $this->byCredentialId[$credentialId] ?? null;
        if (null === $existing) {
            return;
        }

        $this->byCredentialId[$credentialId] = new PasskeyRecord(
            credentialId: $existing->credentialId,
            userId: $existing->userId,
            publicKey: $existing->publicKey,
            algorithm: $existing->algorithm,
            signCount: $signCount,
            label: $existing->label,
            aaguid: $existing->aaguid,
            transports: $existing->transports,
            createdAt: $existing->createdAt,
            lastUsedAt: $lastUsedAt,
        );
    }

    public function deleteForUser(string $credentialId, string $userId): bool
    {
        $existing = $this->byCredentialId[$credentialId] ?? null;
        if (null === $existing || $existing->userId !== $userId) {
            return false;
        }

        unset($this->byCredentialId[$credentialId]);

        return true;
    }
}

PHP;
    }

    private static function pdoPasskeyStore(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Security;

use Kongono\Database\ConnectionInterface;

/** Production SQL WebAuthn passkey store backed by Kongono's parameterized QueryBuilder. */
final readonly class PdoPasskeyStore implements PasskeyStoreInterface
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function save(PasskeyRecord $record): void
    {
        $this->db->table('user_passkeys')->insert([
            'credential_id' => $record->credentialId,
            'user_id' => $record->userId,
            'label' => $record->label,
            'public_key' => $record->publicKey,
            'algorithm' => $record->algorithm,
            'sign_count' => $record->signCount,
            'aaguid' => $record->aaguid,
            'transports' => json_encode($record->transports, JSON_THROW_ON_ERROR),
            'last_used_at' => $record->lastUsedAt,
            'created_at' => $record->createdAt > 0 ? $record->createdAt : time(),
        ]);
    }

    public function findByCredentialId(string $credentialId): ?PasskeyRecord
    {
        $row = $this->db->table('user_passkeys')
            ->where('credential_id', '=', $credentialId)
            ->first();

        return null !== $row ? self::hydrate($row) : null;
    }

    public function listForUser(string $userId): array
    {
        $rows = $this->db->table('user_passkeys')
            ->where('user_id', '=', $userId)
            ->orderBy('created_at', 'DESC')
            ->get();

        return array_map(self::hydrate(...), $rows);
    }

    public function updateSignCount(string $credentialId, int $signCount, int $lastUsedAt): void
    {
        $this->db->table('user_passkeys')
            ->where('credential_id', '=', $credentialId)
            ->update([
                'sign_count' => $signCount,
                'last_used_at' => $lastUsedAt,
            ]);
    }

    public function deleteForUser(string $credentialId, string $userId): bool
    {
        $affected = $this->db->table('user_passkeys')
            ->where('credential_id', '=', $credentialId)
            ->where('user_id', '=', $userId)
            ->delete();

        return $affected > 0;
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): PasskeyRecord
    {
        $rawTransports = is_string($row['transports'] ?? null) ? json_decode($row['transports'], true) : [];
        $transports = [];
        if (is_array($rawTransports)) {
            foreach ($rawTransports as $item) {
                if (is_string($item) && '' !== $item) {
                    $transports[] = $item;
                }
            }
        }

        return new PasskeyRecord(
            credentialId: is_string($row['credential_id'] ?? null) ? $row['credential_id'] : '',
            userId: is_string($row['user_id'] ?? null) ? $row['user_id'] : '',
            publicKey: is_string($row['public_key'] ?? null) ? $row['public_key'] : '',
            algorithm: is_string($row['algorithm'] ?? null) ? $row['algorithm'] : 'ES256',
            signCount: is_numeric($row['sign_count'] ?? null) ? (int) $row['sign_count'] : 0,
            label: is_string($row['label'] ?? null) ? $row['label'] : 'Passkey',
            aaguid: is_string($row['aaguid'] ?? null) ? $row['aaguid'] : '00000000-0000-0000-0000-000000000000',
            transports: $transports,
            createdAt: is_numeric($row['created_at'] ?? null) ? (int) $row['created_at'] : 0,
            lastUsedAt: is_numeric($row['last_used_at'] ?? null) ? (int) $row['last_used_at'] : null,
        );
    }
}

PHP;
    }

    private static function passkeyAuthController(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http;

use App\Security\PasskeyRecord;
use App\Security\PasskeyStoreInterface;
use App\Security\UserStoreInterface;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Delete;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Post;
use Kongono\Http\Routing\Prefix;
use Kongono\Http\Session\Session;
use Kongono\Security\Attributes\Authenticated;
use Kongono\Security\Auth\PasskeyVerifier;
use Kongono\Security\Auth\SessionAuth;
use Kongono\Security\Principal;
use Kongono\Security\SecurityException;

#[Prefix('/auth/passkeys')]
final readonly class PasskeyAuthController
{
    public function __construct(
        private PasskeyVerifier $passkeys,
        private PasskeyStoreInterface $store,
        private UserStoreInterface $users,
        private SessionAuth $auth,
        private string $rpId = 'localhost',
        private string $rpName = 'Kongono App',
        private string $origin = 'http://localhost:8000',
        private bool $requireUserVerification = false,
    ) {}

    #[Get('', name: 'auth.passkeys.index')]
    #[Authenticated]
    public function list(Principal $principal): Response
    {
        $items = array_map(
            static fn(PasskeyRecord $record): array => [
                'credential_id' => $record->credentialId,
                'label' => $record->label,
                'algorithm' => $record->algorithm,
                'transports' => $record->transports,
                'created_at' => $record->createdAt,
                'last_used_at' => $record->lastUsedAt,
            ],
            $this->store->listForUser($principal->id),
        );

        return Response::json(['passkeys' => $items]);
    }

    #[Post('/register/options', name: 'auth.passkeys.register.options')]
    #[Authenticated]
    public function registerOptions(Principal $principal, Session $session): Response
    {
        $existingIds = array_map(
            static fn(PasskeyRecord $record): string => $record->credentialId,
            $this->store->listForUser($principal->id),
        );

        $email = is_string($principal->attributes['email'] ?? null) ? $principal->attributes['email'] : $principal->id;
        $name = is_string($principal->attributes['name'] ?? null) ? $principal->attributes['name'] : $email;

        $options = $this->passkeys->createRegistrationOptions(
            rpId: $this->rpId,
            rpName: $this->rpName,
            origin: $this->origin,
            userId: $principal->id,
            userName: $email,
            userDisplayName: $name,
            excludeCredentialIds: $existingIds,
            userVerification: $this->requireUserVerification ? 'required' : 'preferred',
        );

        $session->set('_passkey_reg_challenge', $options['challenge']);

        return Response::json($options);
    }

    #[Post('/register/verify', name: 'auth.passkeys.register.verify')]
    #[Authenticated]
    public function registerVerify(Request $request, Principal $principal, Session $session): Response
    {
        $expectedChallenge = $session->get('_passkey_reg_challenge');
        $session->remove('_passkey_reg_challenge');
        if (!is_string($expectedChallenge) || '' === $expectedChallenge) {
            return Response::json(['error' => 'Passkey registration challenge is missing or expired.'], status: 400);
        }

        $body = $request->jsonBody();
        $clientDataJson = is_string($body['clientDataJSON'] ?? null) ? $body['clientDataJSON'] : '';
        $attestationObject = is_string($body['attestationObject'] ?? null) ? $body['attestationObject'] : '';
        $label = is_string($body['label'] ?? null) && '' !== trim($body['label']) ? substr(trim($body['label']), 0, 120) : 'Passkey';
        $transports = [];
        if (is_array($body['transports'] ?? null)) {
            foreach ($body['transports'] as $t) {
                if (is_string($t) && '' !== $t) {
                    $transports[] = $t;
                }
            }
        }

        try {
            $registered = $this->passkeys->verifyRegistration(
                expectedChallenge: $expectedChallenge,
                clientDataJsonBase64Url: $clientDataJson,
                attestationObjectBase64Url: $attestationObject,
                requireUserVerification: $this->requireUserVerification,
                transports: $transports,
            );
        } catch (SecurityException $e) {
            return Response::json(['error' => $e->getMessage()], status: 422);
        }

        if ($registered->userId !== $principal->id) {
            return Response::json(['error' => 'Passkey registration user mismatch.'], status: 403);
        }

        if (null !== $this->store->findByCredentialId($registered->credentialId)) {
            return Response::json(['error' => 'This passkey is already registered.'], status: 409);
        }

        $this->store->save(new PasskeyRecord(
            credentialId: $registered->credentialId,
            userId: $principal->id,
            publicKey: $registered->publicKey,
            algorithm: $registered->algorithm,
            signCount: $registered->signCount,
            label: $label,
            aaguid: $registered->aaguid,
            transports: $registered->transports,
            createdAt: time(),
        ));

        return Response::json([
            'registered' => true,
            'credential_id' => $registered->credentialId,
            'algorithm' => $registered->algorithm,
            'label' => $label,
        ], status: 201);
    }

    #[Post('/login/options', name: 'auth.passkeys.login.options')]
    public function loginOptions(Request $request, Session $session): Response
    {
        $body = $request->jsonBody();
        $email = is_string($body['email'] ?? null) ? trim($body['email']) : '';
        $allowIds = [];
        if ('' !== $email) {
            $user = $this->users->findByEmail($email);
            if (null !== $user) {
                $allowIds = array_map(
                    static fn(PasskeyRecord $record): string => $record->credentialId,
                    $this->store->listForUser($user->id),
                );
            }
        }

        $options = $this->passkeys->createAuthenticationOptions(
            rpId: $this->rpId,
            origin: $this->origin,
            allowCredentialIds: $allowIds,
            userVerification: $this->requireUserVerification ? 'required' : 'preferred',
        );
        $session->set('_passkey_auth_challenge', $options['challenge']);

        return Response::json($options);
    }

    #[Post('/login/verify', name: 'auth.passkeys.login.verify')]
    public function loginVerify(Request $request, Session $session): Response
    {
        $expectedChallenge = $session->get('_passkey_auth_challenge');
        $session->remove('_passkey_auth_challenge');
        if (!is_string($expectedChallenge) || '' === $expectedChallenge) {
            return Response::json(['error' => 'Passkey authentication challenge is missing or expired.'], status: 400);
        }

        $body = $request->jsonBody();
        $credentialId = is_string($body['credentialId'] ?? null) ? $body['credentialId'] : '';
        $clientDataJson = is_string($body['clientDataJSON'] ?? null) ? $body['clientDataJSON'] : '';
        $authenticatorData = is_string($body['authenticatorData'] ?? null) ? $body['authenticatorData'] : '';
        $signature = is_string($body['signature'] ?? null) ? $body['signature'] : '';

        $record = '' !== $credentialId ? $this->store->findByCredentialId($credentialId) : null;
        if (null === $record) {
            return Response::json(['error' => 'Passkey authentication failed.'], status: 401);
        }

        try {
            $verified = $this->passkeys->verifyAssertion(
                expectedChallenge: $expectedChallenge,
                clientDataJsonBase64Url: $clientDataJson,
                authenticatorDataBase64Url: $authenticatorData,
                signatureBase64Url: $signature,
                publicKey: $record->publicKey,
                algorithm: $record->algorithm,
                previousSignCount: $record->signCount,
                requireUserVerification: $this->requireUserVerification,
            );
        } catch (SecurityException) {
            return Response::json(['error' => 'Passkey authentication failed.'], status: 401);
        }

        $user = $this->users->findById($record->userId);
        if (null === $user) {
            return Response::json(['error' => 'Passkey authentication failed.'], status: 401);
        }

        $this->store->updateSignCount($record->credentialId, $verified['signCount'], time());
        $this->auth->login($session, $user->toPrincipal());

        return Response::json([
            'authenticated' => true,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }

    #[Delete('/{credentialId}', name: 'auth.passkeys.delete')]
    #[Authenticated]
    public function delete(string $credentialId, Principal $principal): Response
    {
        if (!$this->store->deleteForUser($credentialId, $principal->id)) {
            return Response::json(['error' => 'Passkey not found.'], status: 404);
        }

        return Response::json(['deleted' => true, 'credential_id' => $credentialId]);
    }
}

PHP;
    }

    private static function passkeyView(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Passkeys (WebAuthn)</title>
</head>
<body>
  <main style="max-width:460px;margin:4rem auto;font-family:system-ui,sans-serif;">
    <h1>Passkey Sign-In</h1>
    <p>Sign in with Face ID, Touch ID, Windows Hello, or a hardware security key.</p>
    <p>
      <label for="passkey-email">Email (optional for discoverable passkeys)</label><br>
      <input id="passkey-email" type="email" autocomplete="username webauthn">
    </p>
    <button id="passkey-login-btn" type="button">Sign in with Passkey</button>
    <p id="passkey-status" role="status"></p>
  </main>
</body>
</html>

HTML;
    }

    private static function qrLoginController(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http;

use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Post;
use Kongono\Http\Routing\Prefix;
use Kongono\Http\Session\Session;
use Kongono\Security\Attributes\Authenticated;
use Kongono\Security\Auth\QrLoginAuth;
use Kongono\Security\Auth\SessionAuth;
use Kongono\Security\Principal;
use Kongono\Security\SecurityException;

#[Prefix('/auth/qr')]
final readonly class QrLoginController
{
    public function __construct(
        private QrLoginAuth $qrAuth,
        private SessionAuth $auth,
        private string $approveUrlPrefix = '/auth/qr/approve',
    ) {}

    #[Post('/begin', name: 'auth.qr.begin')]
    public function begin(Request $request, Session $session): Response
    {
        $remote = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        $ip = is_string($remote) && '' !== $remote ? $remote : '127.0.0.1';
        $ua = $request->header('User-Agent') ?? '';

        $challenge = $this->qrAuth->begin(
            approvePath: $this->approveUrlPrefix,
            requesterIp: $ip,
            requesterUserAgent: $ua,
        );

        // Keep desktop poll_secret strictly inside the requesting session (never in the QR code)
        $session->set('_qr_poll_' . $challenge['challenge_id'], $challenge['poll_secret']);

        return Response::json([
            'challenge_id' => $challenge['challenge_id'],
            'verification_code' => $challenge['verification_code'],
            'qr_svg' => $challenge['qr_svg'],
            'expires_at' => $challenge['expires_at'],
        ], status: 201);
    }

    #[Get('/poll/{challengeId}', name: 'auth.qr.poll')]
    public function poll(string $challengeId, Session $session): Response
    {
        $pollSecret = $session->get('_qr_poll_' . $challengeId);
        if (!is_string($pollSecret) || '' === $pollSecret) {
            return Response::json(['status' => 'expired']);
        }

        $status = $this->qrAuth->pollStatus($challengeId, $pollSecret);
        if ('approved' === $status) {
            $principal = $this->qrAuth->claim($challengeId, $pollSecret);
            $session->remove('_qr_poll_' . $challengeId);
            if (null === $principal) {
                return Response::json(['status' => 'expired']);
            }
            $this->auth->login($session, $principal);

            return Response::json(['status' => 'authenticated', 'user_id' => $principal->id]);
        }

        if (in_array($status, ['rejected', 'expired'], true)) {
            $session->remove('_qr_poll_' . $challengeId);
        }

        return Response::json(['status' => $status]);
    }

    #[Post('/inspect', name: 'auth.qr.inspect')]
    #[Authenticated]
    public function inspect(Request $request): Response
    {
        $body = $request->jsonBody();
        $payload = is_string($body['qr_payload'] ?? null) ? $body['qr_payload'] : '';

        try {
            return Response::json($this->qrAuth->inspect($payload));
        } catch (SecurityException $e) {
            return Response::json(['error' => $e->getMessage()], status: 422);
        }
    }

    #[Post('/approve', name: 'auth.qr.approve')]
    #[Authenticated]
    public function approve(Request $request, Principal $principal): Response
    {
        $body = $request->jsonBody();
        $payload = is_string($body['qr_payload'] ?? null) ? $body['qr_payload'] : '';
        $code = is_string($body['verification_code'] ?? null) ? $body['verification_code'] : null;

        try {
            $this->qrAuth->approve($payload, $principal, $code);
        } catch (SecurityException $e) {
            return Response::json(['error' => $e->getMessage()], status: 422);
        }

        return Response::json(['approved' => true]);
    }

    #[Post('/reject', name: 'auth.qr.reject')]
    #[Authenticated]
    public function reject(Request $request): Response
    {
        $body = $request->jsonBody();
        $payload = is_string($body['qr_payload'] ?? null) ? $body['qr_payload'] : '';

        try {
            $this->qrAuth->reject($payload);
        } catch (SecurityException $e) {
            return Response::json(['error' => $e->getMessage()], status: 422);
        }

        return Response::json(['rejected' => true]);
    }
}

PHP;
    }

    private static function qrView(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign In with QR Code</title>
</head>
<body>
  <main style="max-width:420px;margin:4rem auto;text-align:center;font-family:system-ui,sans-serif;">
    <h1>Scan to Sign In</h1>
    <p>Scan this cryptographic QR code from your signed-in mobile device and confirm the code below.</p>
    <div id="qr-container" style="margin:1.5rem auto;">{!! $qrSvg ?? '' !!}</div>
    <p>Verification code: <strong id="qr-code">{{ $verificationCode ?? '---' }}</strong></p>
  </main>
</body>
</html>

HTML;
    }

    private static function otpAuthController(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http;

use App\Security\UserStoreInterface;
use Closure;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Post;
use Kongono\Http\Routing\Prefix;
use Kongono\Http\Session\Session;
use Kongono\Security\Auth\LoginThrottler;
use Kongono\Security\Auth\OneTimeCodeAuth;
use Kongono\Security\Auth\SessionAuth;

#[Prefix('/auth/otp')]
final readonly class OtpAuthController
{
    /**
     * @param (Closure(string, string, int): void)|null $deliverCode Delivery hook (email/SMS provider) receiving ($email, $code, $expiresAt)
     */
    public function __construct(
        private OneTimeCodeAuth $otp,
        private UserStoreInterface $users,
        private SessionAuth $auth,
        private LoginThrottler $throttler,
        private ?Closure $deliverCode = null,
    ) {}

    #[Post('/request', name: 'auth.otp.request')]
    public function requestCode(Request $request): Response
    {
        $remote = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        $ip = is_string($remote) && '' !== $remote ? $remote : '127.0.0.1';
        $body = $request->jsonBody();
        $email = is_string($body['email'] ?? null) ? strtolower(trim($body['email'])) : '';

        if ('' === $email || false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::json(['error' => 'A valid email address is required.'], status: 422);
        }

        $throttleKey = 'otp:request:' . $email;
        if ($this->throttler->isLockedOut($throttleKey, $ip)) {
            return Response::json(['error' => 'Too many code requests. Please wait a moment.'], status: 429);
        }
        $this->throttler->recordFailure($throttleKey, $ip);

        // Non-enumerating: only issue & deliver if account exists, always returning identical 202 payload
        $user = $this->users->findByEmail($email);
        if (null !== $user) {
            $issued = $this->otp->issue($email, digits: 6, ttlSeconds: 600, maxAttempts: 5, claims: ['user_id' => $user->id]);
            if (null !== $this->deliverCode) {
                ($this->deliverCode)($email, $issued['code'], $issued['expires_at']);
            }
        }

        return Response::json([
            'message' => 'If an account exists for that email, a one-time code has been sent.',
        ], status: 202);
    }

    #[Post('/verify', name: 'auth.otp.verify')]
    public function verifyCode(Request $request, Session $session): Response
    {
        $remote = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        $ip = is_string($remote) && '' !== $remote ? $remote : '127.0.0.1';
        $body = $request->jsonBody();
        $email = is_string($body['email'] ?? null) ? strtolower(trim($body['email'])) : '';
        $code = is_string($body['code'] ?? null) ? $body['code'] : '';

        $throttleKey = 'otp:verify:' . $email;
        if ($this->throttler->isLockedOut($throttleKey, $ip)) {
            return Response::json(['error' => 'Too many verification attempts.'], status: 429);
        }

        $verified = $this->otp->verify($email, $code);
        if (null === $verified) {
            $this->throttler->recordFailure($throttleKey, $ip);

            return Response::json(['error' => 'Invalid or expired one-time code.'], status: 401);
        }

        $user = $this->users->findByEmail($verified['identifier']);
        if (null === $user) {
            return Response::json(['error' => 'Invalid or expired one-time code.'], status: 401);
        }

        $this->throttler->clear($throttleKey, $ip);
        $this->auth->login($session, $user->toPrincipal());

        return Response::json([
            'authenticated' => true,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }
}

PHP;
    }

    private static function magicLinkController(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http;

use App\Security\UserStoreInterface;
use Closure;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Post;
use Kongono\Http\Routing\Prefix;
use Kongono\Http\Session\Session;
use Kongono\Security\Auth\LoginThrottler;
use Kongono\Security\Auth\MagicLinkAuth;
use Kongono\Security\Auth\SessionAuth;
use Kongono\Security\SecurityException;

#[Prefix('/auth/magic')]
final readonly class MagicLinkController
{
    /**
     * @param (Closure(string, string): void)|null $deliverLink Delivery hook receiving ($email, $signedPathWithQuery)
     */
    public function __construct(
        private MagicLinkAuth $magicLinks,
        private UserStoreInterface $users,
        private SessionAuth $auth,
        private LoginThrottler $throttler,
        private ?Closure $deliverLink = null,
    ) {}

    #[Post('/request', name: 'auth.magic.request')]
    public function requestLink(Request $request): Response
    {
        $remote = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        $ip = is_string($remote) && '' !== $remote ? $remote : '127.0.0.1';
        $body = $request->jsonBody();
        $email = is_string($body['email'] ?? null) ? strtolower(trim($body['email'])) : '';

        if ('' === $email || false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::json(['error' => 'A valid email address is required.'], status: 422);
        }

        $throttleKey = 'magic:request:' . $email;
        if ($this->throttler->isLockedOut($throttleKey, $ip)) {
            return Response::json(['error' => 'Too many sign-in link requests.'], status: 429);
        }
        $this->throttler->recordFailure($throttleKey, $ip);

        $user = $this->users->findByEmail($email);
        if (null !== $user) {
            $signedPath = $this->magicLinks->createLink('/auth/magic/verify', $email, ttlSeconds: 900, claims: ['user_id' => $user->id]);
            if (null !== $this->deliverLink) {
                ($this->deliverLink)($email, $signedPath);
            }
        }

        return Response::json([
            'message' => 'If an account exists for that email, a sign-in link has been sent.',
        ], status: 202);
    }

    #[Get('/verify', name: 'auth.magic.verify')]
    public function verifyLink(Request $request, Session $session): Response
    {
        $query = $request->getUri()->getQuery();
        $signedPath = $request->path() . ('' !== $query ? '?' . $query : '');

        try {
            $consumed = $this->magicLinks->consume($signedPath);
        } catch (SecurityException) {
            return Response::json(['error' => 'Sign-in link is invalid or expired.'], status: 401);
        }

        $user = $this->users->findByEmail($consumed['email']);
        if (null === $user) {
            return Response::json(['error' => 'Sign-in link is invalid or expired.'], status: 401);
        }

        $this->auth->login($session, $user->toPrincipal());

        return Response::json([
            'authenticated' => true,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }
}

PHP;
    }

    private static function totpMigration(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use Kongono\Database\ConnectionInterface;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Schema\Blueprint;
use Kongono\Database\Schema\Schema;

return new class () implements MigrationInterface {
    public function id(): string
    {
        return '20260101000003_create_totp_factors_table';
    }

    public function up(ConnectionInterface $db): void
    {
        Schema::create($db, 'user_totp_factors', static function (Blueprint $table): void {
            $table->string('user_id', 120)->primary();
            $table->text('encrypted_secret');
            $table->text('recovery_code_hashes');
            $table->integer('confirmed_at')->nullable();
            $table->integer('last_verified_step')->nullable();
            $table->integer('created_at');
        });
    }

    public function down(ConnectionInterface $db): void
    {
        Schema::dropIfExists($db, 'user_totp_factors');
    }
};

PHP;
    }

    private static function totpFactor(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Security;

final readonly class TotpFactor
{
    /** @param list<string> $recoveryCodeHashes SHA-256 hashes of unused single-use recovery codes */
    public function __construct(
        public string $userId,
        public string $encryptedSecret,
        public array $recoveryCodeHashes,
        public ?int $confirmedAt = null,
        public ?int $lastVerifiedStep = null,
        public int $createdAt = 0,
    ) {}
}

PHP;
    }

    private static function totpStoreInterface(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Security;

interface TotpStoreInterface
{
    public function save(TotpFactor $factor): void;

    public function findForUser(string $userId): ?TotpFactor;

    public function deleteForUser(string $userId): void;
}

PHP;
    }

    private static function memoryTotpStore(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Security;

/** Zero-config persistent-runtime-safe in-memory 2FA TOTP store for local development & tests. */
final class MemoryTotpStore implements TotpStoreInterface
{
    /** @var array<string, TotpFactor> */
    private array $factors = [];

    public function save(TotpFactor $factor): void
    {
        $this->factors[$factor->userId] = $factor;
    }

    public function findForUser(string $userId): ?TotpFactor
    {
        return $this->factors[$userId] ?? null;
    }

    public function deleteForUser(string $userId): void
    {
        unset($this->factors[$userId]);
    }
}

PHP;
    }

    private static function totpController(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http;

use App\Security\TotpFactor;
use App\Security\TotpStoreInterface;
use Kongono\Core\Cache\AtomicCacheInterface;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Delete;
use Kongono\Http\Routing\Post;
use Kongono\Http\Routing\Prefix;
use Kongono\Http\Session\Session;
use Kongono\Security\Attributes\Authenticated;
use Kongono\Security\Encryptor;
use Kongono\Security\Principal;
use Kongono\Security\Totp;

#[Prefix('/auth/totp')]
final readonly class TotpController
{
    public function __construct(
        private Totp $totp,
        private Encryptor $encryptor,
        private TotpStoreInterface $store,
        private AtomicCacheInterface $replayCache,
        private string $issuer = 'Kongono App',
    ) {}

    #[Post('/setup', name: 'auth.totp.setup')]
    #[Authenticated]
    public function setup(Principal $principal): Response
    {
        $secret = Totp::generateSecret();
        $account = is_string($principal->attributes['email'] ?? null) ? $principal->attributes['email'] : $principal->id;
        $recoveryCodes = Totp::generateRecoveryCodes(8);
        $hashedRecovery = array_map(
            static fn(string $code): string => hash('sha256', strtoupper(str_replace([' ', '-'], '', $code))),
            $recoveryCodes,
        );

        $this->store->save(new TotpFactor(
            userId: $principal->id,
            encryptedSecret: $this->encryptor->encrypt($secret, 'totp:' . $principal->id),
            recoveryCodeHashes: $hashedRecovery,
            confirmedAt: null,
            lastVerifiedStep: null,
            createdAt: time(),
        ));

        return Response::json([
            'secret' => $secret,
            'otpauth_uri' => $this->totp->provisioningUri($secret, $account, $this->issuer),
            'qr_svg' => $this->totp->provisioningQrSvg($secret, $account, $this->issuer),
            'recovery_codes' => $recoveryCodes,
        ], status: 201);
    }

    #[Post('/confirm', name: 'auth.totp.confirm')]
    #[Authenticated]
    public function confirm(Request $request, Principal $principal, Session $session): Response
    {
        $factor = $this->store->findForUser($principal->id);
        if (null === $factor) {
            return Response::json(['error' => '2FA setup has not been started.'], status: 404);
        }

        $body = $request->jsonBody();
        $code = is_string($body['code'] ?? null) ? $body['code'] : '';
        $secret = $this->encryptor->decrypt($factor->encryptedSecret, 'totp:' . $principal->id);

        $step = $this->totp->verifyOnce($secret, $code, $this->replayCache, lastVerifiedStep: $factor->lastVerifiedStep);
        if (null === $step) {
            return Response::json(['error' => 'Invalid authenticator code.'], status: 422);
        }

        $now = time();
        $this->store->save(new TotpFactor(
            userId: $factor->userId,
            encryptedSecret: $factor->encryptedSecret,
            recoveryCodeHashes: $factor->recoveryCodeHashes,
            confirmedAt: $now,
            lastVerifiedStep: $step,
            createdAt: $factor->createdAt,
        ));
        $session->set('_totp_verified_at', $now);

        return Response::json(['enabled' => true]);
    }

    #[Post('/verify', name: 'auth.totp.verify')]
    #[Authenticated]
    public function verify(Request $request, Principal $principal, Session $session): Response
    {
        $factor = $this->store->findForUser($principal->id);
        if (null === $factor || null === $factor->confirmedAt) {
            return Response::json(['error' => '2FA is not enabled for this account.'], status: 400);
        }

        $body = $request->jsonBody();
        $code = is_string($body['code'] ?? null) ? $body['code'] : '';

        if (!$this->verifyFactorOrRecoveryCode($factor, $code)) {
            return Response::json(['error' => 'Invalid 2FA or recovery code.'], status: 401);
        }

        $session->set('_totp_verified_at', time());

        return Response::json(['verified' => true]);
    }

    #[Delete('', name: 'auth.totp.disable')]
    #[Authenticated]
    public function disable(Request $request, Principal $principal, Session $session): Response
    {
        $factor = $this->store->findForUser($principal->id);
        if (null === $factor) {
            return Response::json(['error' => '2FA is not enabled for this account.'], status: 404);
        }

        $body = $request->jsonBody();
        $code = is_string($body['code'] ?? null) ? $body['code'] : '';
        if (!$this->verifyFactorOrRecoveryCode($factor, $code)) {
            return Response::json(['error' => 'Current 2FA or recovery code is required to disable 2FA.'], status: 401);
        }

        $this->store->deleteForUser($principal->id);
        $session->remove('_totp_verified_at');

        return Response::json(['disabled' => true]);
    }

    private function verifyFactorOrRecoveryCode(TotpFactor $factor, string $code): bool
    {
        $secret = $this->encryptor->decrypt($factor->encryptedSecret, 'totp:' . $factor->userId);
        $step = $this->totp->verifyOnce($secret, $code, $this->replayCache, lastVerifiedStep: $factor->lastVerifiedStep);
        if (null !== $step) {
            $this->store->save(new TotpFactor(
                userId: $factor->userId,
                encryptedSecret: $factor->encryptedSecret,
                recoveryCodeHashes: $factor->recoveryCodeHashes,
                confirmedAt: $factor->confirmedAt,
                lastVerifiedStep: $step,
                createdAt: $factor->createdAt,
            ));

            return true;
        }

        $normalized = strtoupper(str_replace([' ', '-'], '', trim($code)));
        if ('' === $normalized) {
            return false;
        }
        $candidateHash = hash('sha256', $normalized);
        $remaining = [];
        $matched = false;
        foreach ($factor->recoveryCodeHashes as $storedHash) {
            if (!$matched && hash_equals($storedHash, $candidateHash)) {
                $matched = true;
                continue;
            }
            $remaining[] = $storedHash;
        }
        if ($matched) {
            $this->store->save(new TotpFactor(
                userId: $factor->userId,
                encryptedSecret: $factor->encryptedSecret,
                recoveryCodeHashes: $remaining,
                confirmedAt: $factor->confirmedAt,
                lastVerifiedStep: $factor->lastVerifiedStep,
                createdAt: $factor->createdAt,
            ));
        }

        return $matched;
    }
}

PHP;
    }

    private static function oauthController(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http;

use App\Security\UserStoreInterface;
use Kongono\Core\Config;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Prefix;
use Kongono\Http\Session\Session;
use Kongono\Security\Auth\PkceOAuthClient;
use Kongono\Security\Auth\SessionAuth;
use Kongono\Security\PasswordHasher;
use Kongono\Security\SecurityException;

#[Prefix('/auth/oauth')]
final readonly class OAuthController
{
    /**
     * @param array<string, PkceOAuthClient> $providers Map of provider slug (e.g. `github`, `google`) to configured PKCE client
     */
    public function __construct(
        private array $providers,
        private UserStoreInterface $users,
        private PasswordHasher $hasher,
        private SessionAuth $auth,
        private string $appUrl = 'http://localhost:8000',
    ) {}

    /** Build configured GitHub/Google PKCE providers from environment variables. */
    public static function fromEnv(UserStoreInterface $users, PasswordHasher $hasher, SessionAuth $auth): self
    {
        $providers = [];
        $ghId = Config::env('OAUTH_GITHUB_CLIENT_ID') ?? '';
        $ghSecret = Config::env('OAUTH_GITHUB_CLIENT_SECRET') ?? '';
        if ('' !== $ghId && '' !== $ghSecret) {
            $providers['github'] = PkceOAuthClient::github($ghId, $ghSecret);
        }

        $googleId = Config::env('OAUTH_GOOGLE_CLIENT_ID') ?? '';
        $googleSecret = Config::env('OAUTH_GOOGLE_CLIENT_SECRET') ?? '';
        if ('' !== $googleId && '' !== $googleSecret) {
            $providers['google'] = PkceOAuthClient::google($googleId, $googleSecret);
        }

        $appUrl = rtrim(Config::env('APP_URL') ?? 'http://localhost:8000', '/');

        return new self($providers, $users, $hasher, $auth, $appUrl);
    }

    #[Get('/{provider}/redirect', name: 'auth.oauth.redirect')]
    public function redirect(string $provider, Session $session): Response
    {
        $client = $this->providers[strtolower($provider)] ?? null;
        if (null === $client) {
            return Response::json(['error' => 'Unsupported OAuth provider.'], status: 404);
        }

        $callbackUrl = rtrim($this->appUrl, '/') . '/auth/oauth/' . rawurlencode(strtolower($provider)) . '/callback';
        $started = $client->beginAuthorization($callbackUrl);

        $session->set('_oauth_state_' . strtolower($provider), $started['state']);
        $session->set('_oauth_verifier_' . strtolower($provider), $started['code_verifier']);

        return Response::redirect($started['url']);
    }

    #[Get('/{provider}/callback', name: 'auth.oauth.callback')]
    public function callback(string $provider, Request $request, Session $session): Response
    {
        $slug = strtolower($provider);
        $client = $this->providers[$slug] ?? null;
        if (null === $client) {
            return Response::json(['error' => 'Unsupported OAuth provider.'], status: 404);
        }

        $expectedState = $session->get('_oauth_state_' . $slug);
        $codeVerifier = $session->get('_oauth_verifier_' . $slug);
        $session->remove('_oauth_state_' . $slug);
        $session->remove('_oauth_verifier_' . $slug);

        $receivedState = $request->query('state');
        $code = $request->query('code');
        if (!is_string($expectedState) || !is_string($codeVerifier) || !is_string($receivedState) || !is_string($code)) {
            return Response::json(['error' => 'OAuth callback is missing required state or code.'], status: 400);
        }

        $callbackUrl = rtrim($this->appUrl, '/') . '/auth/oauth/' . rawurlencode($slug) . '/callback';

        try {
            $client->verifyState($expectedState, $receivedState);
            $tokens = $client->exchangeCode($code, $callbackUrl, $codeVerifier);
            $accessToken = is_string($tokens['access_token'] ?? null) ? $tokens['access_token'] : '';
            $profile = $client->fetchUserInfo($accessToken);
        } catch (SecurityException $e) {
            return Response::json(['error' => $e->getMessage()], status: 401);
        }

        $email = is_string($profile['email'] ?? null) ? strtolower(trim($profile['email'])) : '';
        if ('' === $email || false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::json(['error' => 'OAuth provider did not return a verified email address.'], status: 422);
        }

        $name = is_string($profile['name'] ?? null) && '' !== trim($profile['name'])
            ? trim($profile['name'])
            : (is_string($profile['login'] ?? null) ? trim($profile['login']) : $email);

        $user = $this->users->findByEmail($email) ?? $this->users->create(
            name: $name,
            email: $email,
            passwordHash: $this->hasher->hash(bin2hex(random_bytes(32))),
        );
        $this->auth->login($session, $user->toPrincipal());

        return Response::json([
            'authenticated' => true,
            'provider' => $slug,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }
}

PHP;
    }

    /** @param list<string> $features */
    private static function featuresTest(array $features): string
    {
        $methods = [];

        if (in_array('passkey', $features, true)) {
            $methods[] = <<<'PHP'
    public function testPasskeyStoreLifecycle(): void
    {
        $store = new \App\Security\MemoryPasskeyStore();
        $store->save(new \App\Security\PasskeyRecord(
            credentialId: 'cred_demo_1',
            userId: 'user_1',
            publicKey: str_repeat('a', 64),
            algorithm: 'EdDSA',
            signCount: 0,
            label: 'MacBook Touch ID',
        ));

        self::assertCount(1, $store->listForUser('user_1'));
        $store->updateSignCount('cred_demo_1', 3, 1_700_000_000);
        self::assertSame(3, $store->findByCredentialId('cred_demo_1')?->signCount);
        self::assertFalse($store->deleteForUser('cred_demo_1', 'other_user'));
        self::assertTrue($store->deleteForUser('cred_demo_1', 'user_1'));
    }
PHP;
        }

        if (in_array('qr', $features, true)) {
            $methods[] = <<<'PHP'
    public function testQrLoginRoundTrip(): void
    {
        $qr = new \Kongono\Security\Auth\QrLoginAuth('test-qr-secret-key-32-bytes-minimum', new \Kongono\Core\Cache\MemoryCache());
        $started = $qr->begin('/auth/qr/approve', requesterIp: '127.0.0.1');
        $principal = new \Kongono\Security\Principal('user_1', \Kongono\Security\PrincipalKind::Human);
        $qr->approve($started['qr_payload'], $principal, $started['verification_code']);

        $claimed = $qr->claim($started['challenge_id'], $started['poll_secret']);
        self::assertSame('user_1', $claimed?->id);
        self::assertNull($qr->claim($started['challenge_id'], $started['poll_secret']));
    }
PHP;
        }

        if (in_array('otp', $features, true)) {
            $methods[] = <<<'PHP'
    public function testOneTimeCodeRoundTrip(): void
    {
        $otp = new \Kongono\Security\Auth\OneTimeCodeAuth('test-otp-secret-key-32-bytes-minimum', new \Kongono\Core\Cache\MemoryCache());
        $issued = $otp->issue('ada@example.test');
        $verified = $otp->verify('ada@example.test', $issued['code']);
        self::assertSame('ada@example.test', $verified['identifier'] ?? null);
        self::assertNull($otp->verify('ada@example.test', $issued['code']));
    }
PHP;
        }

        if (in_array('magic-link', $features, true)) {
            $methods[] = <<<'PHP'
    public function testMagicLinkRoundTrip(): void
    {
        $magic = new \Kongono\Security\Auth\MagicLinkAuth('test-magic-secret-key-32-bytes-min', new \Kongono\Core\Cache\MemoryCache());
        $link = $magic->createLink('/auth/magic/verify', 'ada@example.test');
        $consumed = $magic->consume($link);
        self::assertSame('ada@example.test', $consumed['email']);
    }
PHP;
        }

        if (in_array('totp', $features, true)) {
            $methods[] = <<<'PHP'
    public function testTotpFactorSetupAndQrSvg(): void
    {
        $totp = new \Kongono\Security\Totp();
        $secret = \Kongono\Security\Totp::generateSecret();
        $svg = $totp->provisioningQrSvg($secret, 'ada@example.test', 'Kongono App');
        self::assertStringContainsString('<svg', $svg);
        self::assertNotNull($totp->verifyOnce($secret, $totp->code($secret), new \Kongono\Core\Cache\MemoryCache()));
    }
PHP;
        }

        if (in_array('oauth', $features, true)) {
            $methods[] = <<<'PHP'
    public function testOAuthPkceAuthorizationStartsWithS256(): void
    {
        $client = \Kongono\Security\Auth\PkceOAuthClient::github('cid', 'csecret');
        $started = $client->beginAuthorization('https://app.example.test/auth/oauth/github/callback');
        self::assertStringContainsString('code_challenge_method=S256', $started['url']);
    }
PHP;
        }

        $body = implode("\n\n", $methods);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

final class AuthFeaturesTest extends TestCase
{
{$body}
}

PHP;
    }
}
