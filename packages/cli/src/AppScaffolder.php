<?php

declare(strict_types=1);

namespace Kongono\Cli;

use Kongono\Cli\Auth\AuthScaffolder;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Skills\SkillsInstaller;
use Kongono\Core\CoreException;

/** Scaffolds a fresh, runnable Kongono v1 application via `kongo new <dir> [--starter=minimal|api|saas]`. */
final class AppScaffolder
{
    /** @return list<string> Relative paths created */
    public function scaffold(string $targetDir, string $appName = 'My Kongono App', string $starter = 'minimal'): array
    {
        $normalizedStarter = strtolower(trim($starter));
        if (!in_array($normalizedStarter, ['minimal', 'api', 'saas'], true)) {
            throw new CoreException(sprintf('Unsupported starter preset "%s". Use minimal, api, or saas.', $starter));
        }

        if (is_dir($targetDir)) {
            $entries = scandir($targetDir);
            if (is_array($entries) && count(array_diff($entries, ['.', '..'])) > 0) {
                throw new CoreException(sprintf('Target directory "%s" already exists and is not empty.', $targetDir));
            }
        } elseif (!@mkdir($targetDir, 0o775, true) && !is_dir($targetDir)) {
            throw new CoreException(sprintf('Unable to create project directory "%s".', $targetDir));
        }

        $cleanName = trim((string) preg_replace('/[\x00-\x1f\x7f]/', '', $appName));
        if ('' === $cleanName) {
            $cleanName = 'My Kongono App';
        }
        $cleanName = substr($cleanName, 0, 120);
        $exportedName = var_export($cleanName, true);
        $slugName = trim(strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $cleanName)), '-');
        if ('' === $slugName) {
            $slugName = 'kongono-app';
        }
        $bootstrapPhp = match ($normalizedStarter) {
            'api' => self::apiBootstrap($exportedName),
            'saas' => self::saasBootstrap($exportedName),
            default => self::minimalBootstrap($exportedName),
        };
        $homeView = 'saas' === $normalizedStarter ? self::saasHomeView() : self::minimalHomeView();

        $files = [
            'composer.json' => json_encode([
                'name' => 'app/' . $slugName,
                'type' => 'project',
                'require' => [
                    'php' => '>=8.5.13',
                    'kongono/framework' => '^1.18',
                ],
                'autoload' => [
                    'psr-4' => ['App\\' => 'src/'],
                ],
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            '.env.example' => "APP_ENV=local\nAPP_DEBUG=false\n# Run `php vendor/bin/kongo key:generate` and place the generated key in your untracked .env file:\nAPP_KEY=REPLACE_WITH_kongo_key_generate\n# Set any one AI provider for Oshie, Chie & Hantei:\n# OPENAI_API_KEY=\n# ANTHROPIC_API_KEY=\n# GEMINI_API_KEY=\n# GROQ_API_KEY=\n# OLLAMA_HOST=http://127.0.0.1:11434\n",
            'app/bootstrap.php' => $bootstrapPhp,
            'public/index.php' => <<<'PHP'
<?php

declare(strict_types=1);

use Kongono\Http\App;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var App $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$app->run();

PHP,
            'public/worker.php' => <<<'PHP'
<?php

declare(strict_types=1);

use Kongono\Http\App;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var App $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';

if (function_exists('frankenphp_handle_request')) {
    $app->runWorker(static fn(Closure $handler): bool => frankenphp_handle_request($handler));
} else {
    $app->run();
}

PHP,
            'Caddyfile' => "{\n    frankenphp {\n        worker ./public/worker.php\n    }\n}\n\n:8080 {\n    root * ./public\n    encode zstd br gzip\n    php_server\n}\n",
            'Dockerfile' => "FROM dunglas/frankenphp:php8.5\nRUN useradd -r -u 10001 -s /usr/sbin/nologin kongono && mkdir -p /app/var /data /config && chown -R kongono:kongono /app /data /config && printf \"expose_php=Off\\nsession.upload_progress.enabled=Off\\nzend.exception_ignore_args=On\\nallow_url_include=Off\\ndisplay_errors=Off\\nlog_errors=On\\n\" > /usr/local/etc/php/conf.d/99-kongono-security.ini\nENV APP_ENV=production APP_DEBUG=false SESSION_SECURE=true TRUSTED_PROXIES=\"\"\nWORKDIR /app\nCOPY --chown=kongono:kongono . /app\nUSER kongono\nEXPOSE 8080\nCMD [\"frankenphp\", \"run\", \"--config\", \"/app/Caddyfile\"]\n",
            '.dockerignore' => ".git\n.env\n.env.*\n!.env.example\n/var\n/tests\n/vendor\nAGENTS.md\nCLAUDE.md\n.mcp.json\n.agents\n.claude\n",
            'src/Http/HomeController.php' => <<<PHP
<?php

declare(strict_types=1);

namespace App\Http;

use Kongono\Core\Health\HealthCheck;
use Kongono\Http\App;
use Kongono\Http\OpenApi\ApiDocsRenderer;
use Kongono\Http\OpenApi\OpenApiGenerator;
use Kongono\Http\Response;
use Kongono\Http\Routing\Get;
use Kongono\View\ViewEngine;

final readonly class HomeController
{
    public function __construct(
        private ViewEngine \$views,
        private HealthCheck \$health,
        private App \$app,
    ) {}

    #[Get('/', name: 'home')]
    public function index(): Response
    {
        return Response::html(\$this->views->render('home', [
            'title' => {$exportedName},
            'phpVersion' => PHP_VERSION,
        ]));
    }

    #[Get('/health', name: 'health')]
    public function health(): Response
    {
        \$report = \$this->health->run();

        return Response::json(\$report, status: \$report['healthy'] ? 200 : 503);
    }

    #[Get('/openapi.json', name: 'openapi')]
    public function openApi(): Response
    {
        return Response::json((new OpenApiGenerator())->generate(\$this->app));
    }

    #[Get('/docs', name: 'api.docs')]
    public function docs(): Response
    {
        return Response::html((new ApiDocsRenderer())->render(\$this->app));
    }
}

PHP,
            'views/home.kgn.html' => $homeView,
            '.gitignore' => "/vendor/\n/var/\n.env\n",
            '.mcp.json' => json_encode([
                'mcpServers' => [
                    'kongono' => [
                        'command' => 'php',
                        'args' => ['vendor/bin/kongo', 'mcp'],
                    ],
                ],
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            'AGENTS.md' => self::agentsGuide($cleanName),
            'CLAUDE.md' => "@AGENTS.md\n\n<!-- Claude Code automatically imports AGENTS.md above. Connect to the local MCP server defined in .mcp.json (`php vendor/bin/kongo mcp`) to inspect live routes, graph, OpenAPI, and audits. -->\n",
        ];

        $created = [];
        foreach ($files as $relative => $contents) {
            $full = $targetDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $dir = dirname($full);
            if (!is_dir($dir) && !@mkdir($dir, 0o775, true) && !is_dir($dir)) {
                throw new CoreException(sprintf('Unable to create directory "%s".', $dir));
            }
            if (false === file_put_contents($full, $contents, LOCK_EX)) {
                throw new CoreException(sprintf('Unable to write "%s".', $relative));
            }
            $created[] = $relative;
        }

        if ('api' === $normalizedStarter) {
            $context = new ProjectContext($targetDir);
            $authResult = (new AuthScaffolder())->scaffold($context, mode: 'api', force: true);
            $created = [...$created, ...$authResult['created']];
        } elseif ('saas' === $normalizedStarter) {
            $context = new ProjectContext($targetDir);
            $authResult = (new AuthScaffolder())->scaffold($context, mode: 'both', force: true);
            $created = [...$created, ...$authResult['created']];

            $skillsResult = (new SkillsInstaller())->install($targetDir, target: 'agents', force: true);
            $created = [...$created, ...$skillsResult['installed']];
        }

        return $created;
    }

    private static function minimalBootstrap(string $exportedName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use App\Http\HomeController;
use Kongono\Core\Config;
use Kongono\Core\EnvLoader;
use Kongono\Core\Health\HealthCheck;
use Kongono\Http\App;
use Kongono\Http\Dev\DevDashboardMiddleware;
use Kongono\Http\Dev\DevInspector;
use Kongono\Security\Middleware\SecurityHeadersMiddleware;

\$baseDir = dirname(__DIR__);
EnvLoader::load(\$baseDir . DIRECTORY_SEPARATOR . '.env');

\$debug = filter_var(Config::env('APP_DEBUG') ?? 'false', FILTER_VALIDATE_BOOL);
\$inspector = new DevInspector();
\$health = (new HealthCheck())->check('runtime', static fn(): bool => true);

\$app = App::create(\$baseDir, name: {$exportedName})
    ->config(['app' => ['debug' => \$debug]])
    ->views(autoReload: \$debug)
    ->instance(HealthCheck::class, \$health)
    ->instance(DevInspector::class, \$inspector)
    ->controller(HomeController::class);

\$app->middleware(
    new DevDashboardMiddleware(\$app, \$inspector),
    new SecurityHeadersMiddleware(),
);

return \$app;

PHP;
    }

    private static function apiBootstrap(string $exportedName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use App\Http\ApiAuthController;
use App\Http\HomeController;
use App\Security\MemoryUserStore;
use App\Security\UserStoreInterface;
use Kongono\Core\Cache\AtomicCacheInterface;
use Kongono\Core\Cache\CacheInterface;
use Kongono\Core\Cache\MemoryCache;
use Kongono\Core\Config;
use Kongono\Core\EnvLoader;
use Kongono\Core\Health\HealthCheck;
use Kongono\Core\Telemetry\Metrics;
use Kongono\Http\App;
use Kongono\Http\Dev\DevDashboardMiddleware;
use Kongono\Http\Dev\DevInspector;
use Kongono\Http\Ops\OpsDashboardMiddleware;
use Kongono\Jobs\MemoryQueueDriver;
use Kongono\Jobs\QueueDriverInterface;
use Kongono\Security\Auth\LoginThrottler;
use Kongono\Security\Auth\MemoryPersonalAccessTokenStore;
use Kongono\Security\Auth\PersonalAccessTokenStoreInterface;
use Kongono\Security\Gate;
use Kongono\Security\Middleware\BearerAuthMiddleware;
use Kongono\Security\Middleware\RateLimitMiddleware;
use Kongono\Security\Middleware\SecurityHeadersMiddleware;
use Kongono\Security\PasswordHasher;
use Kongono\Security\Principal;

\$baseDir = dirname(__DIR__);
EnvLoader::load(\$baseDir . DIRECTORY_SEPARATOR . '.env');

\$debug = filter_var(Config::env('APP_DEBUG') ?? 'false', FILTER_VALIDATE_BOOL);
\$cache = new MemoryCache();
\$queueDriver = new MemoryQueueDriver();
\$metrics = new Metrics();
\$inspector = new DevInspector();
\$health = (new HealthCheck())->check('runtime', static fn(): bool => true);
\$users = new MemoryUserStore();
\$tokens = new MemoryPersonalAccessTokenStore();
\$hasher = new PasswordHasher();
\$throttler = new LoginThrottler(\$cache);
\$gate = (new Gate())
    ->define('ops.view', static fn(Principal \$p): bool => \$p->hasCapability('ops.view'))
    ->define('ops.manage', static fn(Principal \$p): bool => \$p->hasCapability('ops.manage'));

\$app = App::create(\$baseDir, name: {$exportedName})
    ->config(['app' => ['debug' => \$debug]])
    ->views(autoReload: \$debug)
    ->instance(CacheInterface::class, \$cache)
    ->instance(AtomicCacheInterface::class, \$cache)
    ->instance(QueueDriverInterface::class, \$queueDriver)
    ->instance(Metrics::class, \$metrics)
    ->instance(HealthCheck::class, \$health)
    ->instance(DevInspector::class, \$inspector)
    ->instance(UserStoreInterface::class, \$users)
    ->instance(PersonalAccessTokenStoreInterface::class, \$tokens)
    ->instance(PasswordHasher::class, \$hasher)
    ->instance(LoginThrottler::class, \$throttler)
    ->instance(Gate::class, \$gate)
    ->controller(HomeController::class)
    ->controller(ApiAuthController::class);

\$app->middleware(
    new BearerAuthMiddleware(\$tokens->toBearerResolver(), required: false),
    new RateLimitMiddleware(\$cache, maxRequests: 120, windowSeconds: 60),
    new OpsDashboardMiddleware(
        appName: {$exportedName},
        queueDriver: \$queueDriver,
        queues: ['default'],
        healthCheck: \$health,
        metrics: \$metrics,
        inspector: \$inspector,
        gate: \$gate,
    ),
    new DevDashboardMiddleware(\$app, \$inspector),
    new SecurityHeadersMiddleware(),
);

return \$app;

PHP;
    }

    private static function saasBootstrap(string $exportedName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use App\Http\ApiAuthController;
use App\Http\HomeController;
use App\Http\WebAuthController;
use App\Security\MemoryUserStore;
use App\Security\UserStoreInterface;
use Kongono\Core\Cache\AtomicCacheInterface;
use Kongono\Core\Cache\CacheInterface;
use Kongono\Core\Cache\MemoryCache;
use Kongono\Core\Config;
use Kongono\Core\EnvLoader;
use Kongono\Core\Health\HealthCheck;
use Kongono\Core\Telemetry\Metrics;
use Kongono\Http\App;
use Kongono\Http\Dev\DevDashboardMiddleware;
use Kongono\Http\Dev\DevInspector;
use Kongono\Http\Live\LiveMiddleware;
use Kongono\Http\Ops\OpsDashboardMiddleware;
use Kongono\Http\Session\CacheSessionStore;
use Kongono\Http\Session\Session;
use Kongono\Http\Session\SessionMiddleware;
use Kongono\Jobs\MemoryQueueDriver;
use Kongono\Jobs\QueueDriverInterface;
use Kongono\Security\Auth\LoginThrottler;
use Kongono\Security\Auth\MemoryPersonalAccessTokenStore;
use Kongono\Security\Auth\PersonalAccessTokenStoreInterface;
use Kongono\Security\Auth\SessionAuth;
use Kongono\Security\Gate;
use Kongono\Security\Middleware\BearerAuthMiddleware;
use Kongono\Security\Middleware\CsrfMiddleware;
use Kongono\Security\Middleware\SecurityHeadersMiddleware;
use Kongono\Security\Middleware\SessionAuthMiddleware;
use Kongono\Security\PasswordHasher;
use Kongono\Security\Principal;
use Kongono\Security\TokenSigner;
use Psr\Http\Message\ServerRequestInterface;

\$baseDir = dirname(__DIR__);
EnvLoader::load(\$baseDir . DIRECTORY_SEPARATOR . '.env');

\$debug = filter_var(Config::env('APP_DEBUG') ?? 'false', FILTER_VALIDATE_BOOL);
\$secureCookies = filter_var(Config::env('SESSION_SECURE') ?? 'false', FILTER_VALIDATE_BOOL);
\$appKey = Config::env('APP_KEY');
\$signingKey = (is_string(\$appKey) && strlen(\$appKey) >= 32 && !str_starts_with(\$appKey, 'REPLACE_WITH_'))
    ? \$appKey
    : bin2hex(random_bytes(32));

\$cache = new MemoryCache();
\$sessionStore = new CacheSessionStore(\$cache);
\$queueDriver = new MemoryQueueDriver();
\$metrics = new Metrics();
\$inspector = new DevInspector();
\$health = (new HealthCheck())->check('runtime', static fn(): bool => true);
\$signer = new TokenSigner(\$signingKey);
\$users = new MemoryUserStore();
\$tokens = new MemoryPersonalAccessTokenStore();
\$hasher = new PasswordHasher();
\$sessionAuth = new SessionAuth();
\$throttler = new LoginThrottler(\$cache);
\$gate = (new Gate())
    ->define('ops.view', static fn(Principal \$p): bool => \$p->hasCapability('ops.view'))
    ->define('ops.manage', static fn(Principal \$p): bool => \$p->hasCapability('ops.manage'));

\$app = App::create(\$baseDir, name: {$exportedName})
    ->config(['app' => ['debug' => \$debug]])
    ->views(autoReload: \$debug)
    ->instance(CacheInterface::class, \$cache)
    ->instance(AtomicCacheInterface::class, \$cache)
    ->instance(QueueDriverInterface::class, \$queueDriver)
    ->instance(Metrics::class, \$metrics)
    ->instance(HealthCheck::class, \$health)
    ->instance(DevInspector::class, \$inspector)
    ->instance(TokenSigner::class, \$signer)
    ->instance(UserStoreInterface::class, \$users)
    ->instance(PersonalAccessTokenStoreInterface::class, \$tokens)
    ->instance(PasswordHasher::class, \$hasher)
    ->instance(SessionAuth::class, \$sessionAuth)
    ->instance(LoginThrottler::class, \$throttler)
    ->instance(Gate::class, \$gate)
    ->controller(HomeController::class)
    ->controller(WebAuthController::class)
    ->controller(ApiAuthController::class);

\$app->middleware(
    new SessionMiddleware(\$sessionStore, secure: \$secureCookies),
    new SessionAuthMiddleware(auth: \$sessionAuth),
    new BearerAuthMiddleware(\$tokens->toBearerResolver(), required: false),
    new CsrfMiddleware(\$signer, exemptPrefixes: ['/api/']),
    new LiveMiddleware(),
    new OpsDashboardMiddleware(
        appName: {$exportedName},
        queueDriver: \$queueDriver,
        queues: ['default'],
        healthCheck: \$health,
        metrics: \$metrics,
        inspector: \$inspector,
        gate: \$gate,
        csrfTokenProvider: static function (ServerRequestInterface \$req) use (\$signer): ?string {
            \$session = \$req->getAttribute(Session::class);

            return \$session instanceof Session ? \$signer->generateCsrfToken(\$session->id()) : null;
        },
    ),
    new DevDashboardMiddleware(\$app, \$inspector),
    new SecurityHeadersMiddleware(),
);

return \$app;

PHP;
    }

    private static function minimalHomeView(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $title }}</title>
</head>
<body>
  <h1>{{ $title }}</h1>
  <p>Running on Kongono Framework v1 (PHP {{ $phpVersion }}).</p>
  <p><a href="/docs">API Explorer (/docs)</a> &middot; <a href="/_kongono">Dev Studio (/_kongono)</a> &middot; <a href="/health">Health</a></p>
</body>
</html>

HTML;
    }

    private static function saasHomeView(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }}</title>
  @liveScript
</head>
<body>
  <main style="max-width:720px;margin:3rem auto;font-family:system-ui,sans-serif;">
    <h1>{{ $title }}</h1>
    <p>Running on Kongono Framework v1 SaaS Starter (Live 2.5 + SessionAuth + PATs + /_ops, PHP {{ $phpVersion }}).</p>
    <p>
      <a href="/register">Create Account</a> &middot;
      <a href="/login">Sign In</a> &middot;
      <a href="/dashboard">Dashboard</a> &middot;
      <a href="/_ops">Operations (/_ops)</a> &middot;
      <a href="/docs">API Explorer (/docs)</a> &middot;
      <a href="/_kongono">Dev Studio (/_kongono)</a> &middot;
      <a href="/health">Health</a>
    </p>
  </main>
</body>
</html>

HTML;
    }

    private static function agentsGuide(string $appName): string
    {
        return <<<MD
# {$appName} — Kongono v1 AI Agent Guide

This application is built with **Kongono Framework v1** (pure PHP 8.5.13+, zero Symfony packages, persistent-runtime first).
Full LLM reference: `https://anthonyiovanna.github.io/kongono/llms.txt`

## Non-Negotiable Rules

1. **Pure PHP 8.5+:** Every PHP file starts with `declare(strict_types=1);`. Prefer `final readonly class`, enums, and native attributes.
2. **No Laravel / Symfony Patterns:** Never use global static facades (`Route::get()`, `Auth::user()`, `DB::table()`, `Http::fake()`, `Carbon::setTestNow()`), ActiveRecord magic (`__get`/`__set`), or Symfony packages.
3. **Persistent-Runtime Safe:** Never store per-request state in static properties or read `\$_GET`, `\$_POST`, `\$_COOKIE`, `\$_FILES`, or `\$_SESSION` directly. Always inject `Request`, `Session`, `Principal`, `ClockInterface`, or container services.
4. **Fail-Closed Security:** Keep `QueryBuilder` parameterized, scope tenant queries with `->forTenant(\$tenant)`, sanitize rich HTML with `@safeHtml(...)`, use `SafeHttpClient` for outbound HTTP, and never log secrets (`SecretScrubber`).

## Exact Kongono Idioms

- **Routing & Auth:** Register controllers via `\$app->controller(MyController::class)` using `#[Prefix]`, `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`, `#[Delete]`, `#[Middleware(...)]`, `#[Authenticated]`, `#[Can('ability')]`, `#[CacheControl]`, `#[Idempotent]`, `#[Feature]`.
- **DTO Validation:** Type-hint a `final readonly class` in a controller method with `#[Required]`, `#[Min]`, `#[Max]`, `#[Email]`, `#[Uuid]`, `#[Date]`, `#[Ip]`, `#[In]`, `#[NotIn]`, `#[RequiredIf]`, `#[Confirmed]`, `#[ArrayOf]`, `RuleInterface`, or `ValidatableDtoInterface`.
- **Database & Relations:** Use `PdoConnection` + `QueryBuilder` (`withoutTrashed()`, `onlyTrashed()`, `softDelete()`, `restore()`, `paginate()`, `getAs(Dto::class)`), `BatchLoader::hasMany()` / `BatchLoader::belongsTo()` (zero N+1), `Factory::for()`, and `Schema::create()` / `Schema::table()`.
- **Views, Live 2.5 & Bridge:** Templates live in `views/*.kgn.html` (`{{ \$escaped }}`, `@safeHtml(\$html)`, `@old('field')`, `@error('field')`, `@liveScript`, `@island('Widget', \$props)`). Return `Response::html()`, `Response::json()`, `Response::fragment()`, `Response::page()`, or `Response::adaptive()`.
- **Auth & Tokens:** Use `SessionAuth` + `SessionAuthMiddleware` for cookie sessions, `PersonalAccessToken` (`Memory`/`Cache`/`Pdo` stores) + `BearerAuthMiddleware` for revocable API tokens, and `Gate` for authorization.
- **Queues, Storage & AI:** Use `Queue` / `Worker` (`uniqueKey`, `JobBatch`, `Workflow`), `LocalStorage` / `S3Storage` (`putStream`/`getStream`), and `AiClient::fromEnv()` (OpenAI, Anthropic, Gemini, Groq, Ollama).
- **Testing (Zero Global State):** Use `(new TestClient(\$app))->actingAs(\$principal)->withStatefulCookies()`, `TestUpload::fakePng()`, `DatabaseTestHelper::inRollbackTransaction()`, `SafeHttpClient::fake()`, and `FrozenClock::at(...)`.

## Inspect & Verify with CLI / MCP

Connect to `.mcp.json` (`php vendor/bin/kongo mcp`) for live read-only inspection (`kongono_graph`, `kongono_routes`, `kongono_openapi`, `kongono_audit`, `kongono_eval`, `kongono_migrate_lint`, `kongono_test_route`), install bundled workflow skills with `php vendor/bin/kongo skills:install --target=both`, or run:

```bash
php vendor/bin/kongo routes --json
php vendor/bin/kongo migrate --pretend
php vendor/bin/kongo migrate:lint
php vendor/bin/kongo audit --attack --strict
php vendor/bin/kongo test
```

MD;
    }
}
