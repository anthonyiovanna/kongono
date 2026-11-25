<?php

declare(strict_types=1);

namespace Kongono\Cli\Auth;

use Kongono\Cli\Context\ProjectContext;
use Kongono\Core\CoreException;

/**
 * Zero-bloat Auth Starter Kit scaffolder (`kongo auth:init [--mode=web|api|both] [--force]`).
 *
 * Generates editable, PHPStan-L9-clean migrations, DTOs, user stores (Memory + PDO),
 * web SessionAuth + LoginThrottler + CSRF controllers/views, revocable SHA-256 Personal Access Token
 * API endpoints, and TestClient integration tests directly into the developer's application.
 */
final readonly class AuthScaffolder
{
    /**
     * @return array{created: list<string>, overwritten: list<string>, skipped: list<string>}
     */
    public function scaffold(ProjectContext $context, string $mode = 'both', bool $force = false): array
    {
        $normalized = strtolower(trim($mode));
        if (!in_array($normalized, ['web', 'api', 'both'], true)) {
            throw new CoreException(sprintf('Unsupported auth:init mode "%s". Use web, api, or both.', $mode));
        }

        $includeWeb = 'web' === $normalized || 'both' === $normalized;
        $includeApi = 'api' === $normalized || 'both' === $normalized;

        $files = [
            'migrations/20260101000001_create_auth_tables.php' => self::authMigration(),
            'src/Http/Dto/RegisterInput.php' => self::registerDto(),
            'src/Http/Dto/LoginInput.php' => self::loginDto(),
            'src/Security/AuthUser.php' => self::authUser(),
            'src/Security/UserStoreInterface.php' => self::userStoreInterface(),
            'src/Security/MemoryUserStore.php' => self::memoryUserStore(),
            'src/Security/PdoUserStore.php' => self::pdoUserStore(),
        ];

        if ($includeWeb) {
            $files['src/Http/WebAuthController.php'] = self::webAuthController();
            $files['views/auth/login.kgn.html'] = self::loginView();
            $files['views/auth/register.kgn.html'] = self::registerView();
            $files['views/auth/dashboard.kgn.html'] = self::dashboardView();
        }

        if ($includeApi) {
            $files['src/Http/Dto/CreateTokenInput.php'] = self::createTokenDto();
            $files['src/Http/ApiAuthController.php'] = self::apiAuthController();
        }

        $files['tests/AuthFlowTest.php'] = self::authTest($includeWeb, $includeApi);

        $created = [];
        $overwritten = [];
        $skipped = [];

        foreach ($files as $relative => $contents) {
            $fullPath = $context->rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $exists = is_file($fullPath);
            if ($exists && !$force) {
                $skipped[] = $relative;
                continue;
            }
            $context->writeFileContained($relative, $contents);
            if ($exists) {
                $overwritten[] = $relative;
            } else {
                $created[] = $relative;
            }
        }

        return [
            'created' => $created,
            'overwritten' => $overwritten,
            'skipped' => $skipped,
        ];
    }

    private static function authMigration(): string
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
        return '20260101000001_create_auth_tables';
    }

    public function up(ConnectionInterface $db): void
    {
        Schema::create($db, 'users', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 190)->unique();
            $table->string('password_hash', 255);
            $table->string('role', 40)->default('member');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create($db, 'personal_access_tokens', static function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('principal_id', 120);
            $table->string('principal_kind', 32);
            $table->string('name', 120);
            $table->string('token_hash', 64)->unique();
            $table->text('capabilities');
            $table->integer('last_used_at')->nullable();
            $table->integer('expires_at')->nullable();
            $table->integer('created_at');
        });
    }

    public function down(ConnectionInterface $db): void
    {
        Schema::dropIfExists($db, 'personal_access_tokens');
        Schema::dropIfExists($db, 'users');
    }
};

PHP;
    }

    private static function registerDto(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Dto;

use Kongono\Validation\Attributes\Confirmed;
use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Required;

final readonly class RegisterInput
{
    public function __construct(
        #[Required, Min(2), Max(120)]
        public string $name,
        #[Required, Email, Max(190)]
        public string $email,
        #[Required, Min(12), Max(256)]
        public string $password,
        #[Required, Confirmed('password')]
        public string $passwordConfirmation,
    ) {}
}

PHP;
    }

    private static function loginDto(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Dto;

use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\Required;

final readonly class LoginInput
{
    public function __construct(
        #[Required, Email]
        public string $email,
        #[Required]
        public string $password,
    ) {}
}

PHP;
    }

    private static function createTokenDto(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Dto;

use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Required;

final readonly class CreateTokenInput
{
    public function __construct(
        #[Required, Email]
        public string $email,
        #[Required]
        public string $password,
        #[Required, Min(2), Max(80)]
        public string $tokenName = 'api-client',
    ) {}
}

PHP;
    }

    private static function authUser(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Security;

use Kongono\Security\Principal;
use Kongono\Security\PrincipalKind;

final readonly class AuthUser
{
    /** @param list<string> $capabilities */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public string $role = 'member',
        public array $capabilities = ['account.read', 'account.write'],
    ) {}

    public function toPrincipal(): Principal
    {
        return new Principal(
            id: $this->id,
            kind: PrincipalKind::Human,
            capabilities: $this->capabilities,
            attributes: [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
            ],
        );
    }
}

PHP;
    }

    private static function userStoreInterface(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Security;

interface UserStoreInterface
{
    public function findByEmail(string $email): ?AuthUser;

    public function findById(string $id): ?AuthUser;

    /** @param list<string> $capabilities */
    public function create(string $name, string $email, string $passwordHash, string $role = 'member', array $capabilities = ['account.read', 'account.write']): AuthUser;
}

PHP;
    }

    private static function memoryUserStore(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Security;

use RuntimeException;

/** Zero-config persistent-runtime-safe in-memory user store for local development & tests. */
final class MemoryUserStore implements UserStoreInterface
{
    /** @var array<string, AuthUser> */
    private array $byId = [];

    /** @var array<string, string> */
    private array $idByEmail = [];

    private int $nextId = 1;

    public function findByEmail(string $email): ?AuthUser
    {
        $key = strtolower(trim($email));
        $id = $this->idByEmail[$key] ?? null;

        return null !== $id ? ($this->byId[$id] ?? null) : null;
    }

    public function findById(string $id): ?AuthUser
    {
        return $this->byId[$id] ?? null;
    }

    public function create(string $name, string $email, string $passwordHash, string $role = 'member', array $capabilities = ['account.read', 'account.write']): AuthUser
    {
        $normalized = strtolower(trim($email));
        if (isset($this->idByEmail[$normalized])) {
            throw new RuntimeException('An account with this email already exists.');
        }

        $id = 'user_' . $this->nextId++;
        $user = new AuthUser($id, $name, $normalized, $passwordHash, $role, $capabilities);
        $this->byId[$id] = $user;
        $this->idByEmail[$normalized] = $id;

        return $user;
    }
}

PHP;
    }

    private static function pdoUserStore(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Security;

use Kongono\Database\ConnectionInterface;

/** Production SQL user store backed by Kongono's parameterized QueryBuilder. */
final readonly class PdoUserStore implements UserStoreInterface
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function findByEmail(string $email): ?AuthUser
    {
        $row = $this->db->table('users')
            ->where('email', '=', strtolower(trim($email)))
            ->first();

        return null !== $row ? self::hydrate($row) : null;
    }

    public function findById(string $id): ?AuthUser
    {
        $row = $this->db->table('users')
            ->where('id', '=', $id)
            ->first();

        return null !== $row ? self::hydrate($row) : null;
    }

    public function create(string $name, string $email, string $passwordHash, string $role = 'member', array $capabilities = ['account.read', 'account.write']): AuthUser
    {
        $normalized = strtolower(trim($email));
        $now = gmdate('Y-m-d H:i:s');
        $row = $this->db->table('users')->insertReturning([
            'name' => $name,
            'email' => $normalized,
            'password_hash' => $passwordHash,
            'role' => $role,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = is_scalar($row['id'] ?? null) ? (string) $row['id'] : bin2hex(random_bytes(8));

        return new AuthUser($id, $name, $normalized, $passwordHash, $role, $capabilities);
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): AuthUser
    {
        $role = is_string($row['role'] ?? null) ? $row['role'] : 'member';
        $caps = 'admin' === $role
            ? ['account.read', 'account.write', 'ops.view', 'ops.manage']
            : ['account.read', 'account.write'];

        return new AuthUser(
            id: is_scalar($row['id'] ?? null) ? (string) $row['id'] : '',
            name: is_string($row['name'] ?? null) ? $row['name'] : '',
            email: is_string($row['email'] ?? null) ? $row['email'] : '',
            passwordHash: is_string($row['password_hash'] ?? null) ? $row['password_hash'] : '',
            role: $role,
            capabilities: $caps,
        );
    }
}

PHP;
    }

    private static function webAuthController(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Dto\LoginInput;
use App\Http\Dto\RegisterInput;
use App\Security\UserStoreInterface;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Post;
use Kongono\Http\Session\Session;
use Kongono\Security\Attributes\Authenticated;
use Kongono\Security\Auth\LoginThrottler;
use Kongono\Security\Auth\RememberMeManager;
use Kongono\Security\Auth\SessionAuth;
use Kongono\Security\PasswordHasher;
use Kongono\Security\Principal;
use Kongono\Security\TokenSigner;
use Kongono\View\ViewEngine;

final class WebAuthController
{
    private ?string $dummyHash = null;

    public function __construct(
        private readonly ViewEngine $views,
        private readonly UserStoreInterface $users,
        private readonly PasswordHasher $hasher,
        private readonly SessionAuth $auth,
        private readonly LoginThrottler $throttler,
        private readonly TokenSigner $signer,
        private readonly ?RememberMeManager $rememberMe = null,
    ) {}

    #[Get('/login', name: 'auth.login.form')]
    public function showLogin(Session $session): Response
    {
        $session->set('_csrf_ready', true);

        return Response::html($this->views->render('auth/login', [
            'csrf' => $this->signer->generateCsrfToken($session->id()),
            'error' => $session->getFlash('auth_error'),
        ]));
    }

    #[Post('/login', name: 'auth.login')]
    public function login(LoginInput $input, Request $request, Session $session): Response
    {
        $remote = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        $ip = is_string($remote) && '' !== $remote ? $remote : '127.0.0.1';
        if ($this->throttler->isLockedOut($input->email, $ip)) {
            $session->flash('auth_error', 'Too many sign-in attempts. Please wait a moment and try again.');

            return Response::redirect('/login');
        }

        $user = $this->users->findByEmail($input->email);
        if (null === $user) {
            // Constant-time Argon2id dummy verification prevents email enumeration via response timing
            $this->dummyHash ??= $this->hasher->hash('kongono-auth-dummy-timing-guard');
            $this->hasher->verify($input->password, $this->dummyHash);
            $this->throttler->recordFailure($input->email, $ip);
            $session->flash('auth_error', 'Invalid email or password.');

            return Response::redirect('/login');
        }
        if (!$this->hasher->verify($input->password, $user->passwordHash)) {
            $this->throttler->recordFailure($input->email, $ip);
            $session->flash('auth_error', 'Invalid email or password.');

            return Response::redirect('/login');
        }

        $this->throttler->clear($input->email, $ip);
        $this->auth->login($session, $user->toPrincipal());

        return Response::redirect('/dashboard');
    }

    #[Get('/register', name: 'auth.register.form')]
    public function showRegister(Session $session): Response
    {
        $session->set('_csrf_ready', true);

        return Response::html($this->views->render('auth/register', [
            'csrf' => $this->signer->generateCsrfToken($session->id()),
            'error' => $session->getFlash('auth_error'),
        ]));
    }

    #[Post('/register', name: 'auth.register')]
    public function register(RegisterInput $input, Request $request, Session $session): Response
    {
        $remote = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        $ip = is_string($remote) && '' !== $remote ? $remote : '127.0.0.1';
        if ($this->throttler->isLockedOut('register:' . $ip, $ip)) {
            $session->flash('auth_error', 'Too many registration attempts. Please wait a moment and try again.');

            return Response::redirect('/register');
        }
        $this->throttler->recordFailure('register:' . $ip, $ip);

        if (null !== $this->users->findByEmail($input->email)) {
            // Match Argon2id timing and avoid explicitly confirming whether an email address is registered
            $this->dummyHash ??= $this->hasher->hash('kongono-auth-dummy-timing-guard');
            $this->hasher->verify($input->password, $this->dummyHash);
            $session->flash('auth_error', 'Unable to complete registration. If you already have an account, try signing in.');

            return Response::redirect('/register');
        }

        $user = $this->users->create(
            name: $input->name,
            email: $input->email,
            passwordHash: $this->hasher->hash($input->password),
        );
        $this->auth->login($session, $user->toPrincipal());

        return Response::redirect('/dashboard');
    }

    #[Post('/logout', name: 'auth.logout')]
    public function logout(Request $request, Session $session): Response
    {
        $this->auth->logout($session);
        $response = Response::redirect('/login');
        if (null !== $this->rememberMe) {
            $rawRemember = $request->getCookieParams()[$this->rememberMe->cookieName()] ?? null;
            if (is_string($rawRemember) && '' !== $rawRemember) {
                $this->rememberMe->revokeCookie($rawRemember);
            }
            $response = $response->withAddedHeader('Set-Cookie', $this->rememberMe->expiredCookie()->toHeaderValue());
        }

        return $response;
    }

    #[Get('/dashboard', name: 'auth.dashboard')]
    #[Authenticated]
    public function dashboard(Principal $principal, Session $session): Response
    {
        return Response::html($this->views->render('auth/dashboard', [
            'principal' => $principal,
            'csrf' => $this->signer->generateCsrfToken($session->id()),
        ]));
    }
}

PHP;
    }

    private static function apiAuthController(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Dto\CreateTokenInput;
use App\Http\Dto\RegisterInput;
use App\Security\UserStoreInterface;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Delete;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Post;
use Kongono\Http\Routing\Prefix;
use Kongono\Security\Attributes\Authenticated;
use Kongono\Security\Auth\LoginThrottler;
use Kongono\Security\Auth\PersonalAccessTokenStoreInterface;
use Kongono\Security\PasswordHasher;
use Kongono\Security\Principal;

#[Prefix('/api/auth')]
final class ApiAuthController
{
    private ?string $dummyHash = null;

    public function __construct(
        private readonly UserStoreInterface $users,
        private readonly PasswordHasher $hasher,
        private readonly LoginThrottler $throttler,
        private readonly PersonalAccessTokenStoreInterface $tokens,
    ) {}

    #[Post('/register', name: 'api.auth.register')]
    public function register(RegisterInput $input, Request $request): Response
    {
        $remote = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        $ip = is_string($remote) && '' !== $remote ? $remote : '127.0.0.1';
        if ($this->throttler->isLockedOut('register:' . $ip, $ip)) {
            return Response::json(['error' => 'Too many registration attempts.'], status: 429);
        }
        $this->throttler->recordFailure('register:' . $ip, $ip);

        if (null !== $this->users->findByEmail($input->email)) {
            // Match Argon2id timing and return a generic non-enumerating error payload
            $this->dummyHash ??= $this->hasher->hash('kongono-auth-dummy-timing-guard');
            $this->hasher->verify($input->password, $this->dummyHash);

            return Response::json(['error' => 'Unable to complete registration.'], status: 409);
        }

        $user = $this->users->create(
            name: $input->name,
            email: $input->email,
            passwordHash: $this->hasher->hash($input->password),
        );
        $issued = $this->tokens->issue($user->toPrincipal(), 'initial-token');

        return Response::json([
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'token' => $issued->plainTextToken,
            'token_id' => $issued->record->id,
        ], status: 201);
    }

    #[Post('/tokens', name: 'api.auth.tokens.issue')]
    public function issueToken(CreateTokenInput $input, Request $request): Response
    {
        $remote = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        $ip = is_string($remote) && '' !== $remote ? $remote : '127.0.0.1';
        if ($this->throttler->isLockedOut($input->email, $ip)) {
            return Response::json(['error' => 'Too many login attempts.'], status: 429);
        }

        $user = $this->users->findByEmail($input->email);
        if (null === $user) {
            // Constant-time Argon2id dummy verification prevents email enumeration via response timing
            $this->dummyHash ??= $this->hasher->hash('kongono-auth-dummy-timing-guard');
            $this->hasher->verify($input->password, $this->dummyHash);
            $this->throttler->recordFailure($input->email, $ip);

            return Response::json(['error' => 'Invalid credentials.'], status: 401);
        }
        if (!$this->hasher->verify($input->password, $user->passwordHash)) {
            $this->throttler->recordFailure($input->email, $ip);

            return Response::json(['error' => 'Invalid credentials.'], status: 401);
        }

        $this->throttler->clear($input->email, $ip);
        $issued = $this->tokens->issue($user->toPrincipal(), $input->tokenName);

        return Response::json([
            'token' => $issued->plainTextToken,
            'token_id' => $issued->record->id,
            'name' => $issued->record->name,
        ], status: 201);
    }

    #[Get('/me', name: 'api.auth.me')]
    #[Authenticated]
    public function me(Principal $principal): Response
    {
        return Response::json([
            'id' => $principal->id,
            'kind' => $principal->kind->value,
            'capabilities' => $principal->capabilities,
            'attributes' => $principal->attributes,
        ]);
    }

    #[Get('/tokens', name: 'api.auth.tokens.index')]
    #[Authenticated]
    public function listTokens(Principal $principal): Response
    {
        $items = array_map(
            static fn($t): array => [
                'id' => $t->id,
                'name' => $t->name,
                'capabilities' => $t->capabilities,
                'last_used_at' => $t->lastUsedAt,
                'expires_at' => $t->expiresAt,
            ],
            $this->tokens->listForPrincipal($principal->id),
        );

        return Response::json(['tokens' => $items]);
    }

    #[Delete('/tokens/{id}', name: 'api.auth.tokens.revoke')]
    #[Authenticated]
    public function revokeToken(string $id, Principal $principal): Response
    {
        foreach ($this->tokens->listForPrincipal($principal->id) as $token) {
            if ($token->id === $id) {
                $this->tokens->revoke($id);

                return Response::json(['revoked' => true, 'id' => $id]);
            }
        }

        return Response::json(['error' => 'Token not found.'], status: 404);
    }
}

PHP;
    }

    private static function loginView(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign In</title>
</head>
<body>
  <main style="max-width:420px;margin:4rem auto;font-family:system-ui,sans-serif;">
    <h1>Sign In</h1>
    @if ($error)
      <p role="alert" style="color:#b91c1c;">{{ $error }}</p>
    @endif
    <form method="post" action="/login">
      <input type="hidden" name="_csrf" value="{{ $csrf }}">
      <p>
        <label for="email">Email</label><br>
        <input id="email" name="email" type="email" value="@old('email')" required>
      </p>
      <p>
        <label for="password">Password</label><br>
        <input id="password" name="password" type="password" required>
      </p>
      <button type="submit">Sign In</button>
    </form>
    <p><a href="/register">Create an account</a></p>
  </main>
</body>
</html>

HTML;
    }

    private static function registerView(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Account</title>
</head>
<body>
  <main style="max-width:420px;margin:4rem auto;font-family:system-ui,sans-serif;">
    <h1>Create Account</h1>
    @if ($error)
      <p role="alert" style="color:#b91c1c;">{{ $error }}</p>
    @endif
    <form method="post" action="/register">
      <input type="hidden" name="_csrf" value="{{ $csrf }}">
      <p>
        <label for="name">Name</label><br>
        <input id="name" name="name" type="text" value="@old('name')" required>
      </p>
      <p>
        <label for="email">Email</label><br>
        <input id="email" name="email" type="email" value="@old('email')" required>
      </p>
      <p>
        <label for="password">Password (12+ chars)</label><br>
        <input id="password" name="password" type="password" minlength="12" required>
      </p>
      <p>
        <label for="passwordConfirmation">Confirm Password</label><br>
        <input id="passwordConfirmation" name="passwordConfirmation" type="password" minlength="12" required>
      </p>
      <button type="submit">Create Account</button>
    </form>
    <p><a href="/login">Already have an account? Sign in</a></p>
  </main>
</body>
</html>

HTML;
    }

    private static function dashboardView(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard</title>
</head>
<body>
  <main style="max-width:640px;margin:3rem auto;font-family:system-ui,sans-serif;">
    <h1>Welcome, {{ $principal->attributes['name'] ?? $principal->id }}</h1>
    <p>Signed in as <code>{{ $principal->attributes['email'] ?? $principal->id }}</code>.</p>
    <form method="post" action="/logout">
      <input type="hidden" name="_csrf" value="{{ $csrf }}">
      <button type="submit">Sign Out</button>
    </form>
  </main>
</body>
</html>

HTML;
    }

    private static function authTest(bool $includeWeb, bool $includeApi): string
    {
        $methods = [];
        if ($includeApi) {
            $methods[] = <<<'PHP'
    public function testApiAuthRegisterMeAndRevokeToken(): void
    {
        /** @var App $app */
        $app = require dirname(__DIR__) . '/app/bootstrap.php';
        $client = new TestClient($app);

        $client->get('/api/auth/me')->assertUnauthorized();

        $registered = $client->postJson('/api/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'password' => 'correct-horse-battery-staple',
            'passwordConfirmation' => 'correct-horse-battery-staple',
        ])->assertCreated();

        $data = $registered->json();
        $token = is_string($data['token'] ?? null) ? $data['token'] : '';
        $tokenId = is_string($data['token_id'] ?? null) ? $data['token_id'] : '';
        self::assertStringStartsWith('kgn_pat_', $token);

        $client->withBearerToken($token)
            ->get('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('attributes.email', 'ada@example.test');

        $client->withBearerToken($token)
            ->delete('/api/auth/tokens/' . $tokenId)
            ->assertOk();

        $client->withBearerToken($token)
            ->get('/api/auth/me')
            ->assertUnauthorized();
    }
PHP;
        }
        if ($includeWeb) {
            $methods[] = <<<'PHP'
    public function testWebLoginFormRendersAndDashboardRequiresAuth(): void
    {
        /** @var App $app */
        $app = require dirname(__DIR__) . '/app/bootstrap.php';
        $client = new TestClient($app);

        $client->get('/login')->assertOk()->assertSee('Sign In');
        $client->get('/register')->assertOk()->assertSee('Create Account');
        self::assertContains($client->get('/dashboard')->status(), [302, 401]);
    }
PHP;
        }

        $body = implode("\n\n", $methods);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Tests;

use Kongono\Http\App;
use Kongono\Http\Testing\TestClient;
use PHPUnit\Framework\TestCase;

final class AuthFlowTest extends TestCase
{
{$body}
}

PHP;
    }
}
