<?php

declare(strict_types=1);

namespace Kongono\Cli;

use Kongono\Core\CoreException;

/** Scaffolds a fresh, runnable Kongono v1 application via `kongo new <dir>`. */
final class AppScaffolder
{
    /** @return list<string> Relative paths created */
    public function scaffold(string $targetDir, string $appName = 'My Kongono App'): array
    {
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
        $files = [
            'composer.json' => json_encode([
                'name' => 'app/' . $slugName,
                'type' => 'project',
                'require' => [
                    'php' => '>=8.5.13',
                    'kongono/framework' => '^1.13',
                ],
                'autoload' => [
                    'psr-4' => ['App\\' => 'src/'],
                ],
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            '.env.example' => "APP_ENV=local\nAPP_DEBUG=false\n# Run `php vendor/bin/kongo key:generate` and place the generated key in your untracked .env file:\nAPP_KEY=REPLACE_WITH_kongo_key_generate\n# Set any one AI provider for Oshie, Chie & Hantei:\n# OPENAI_API_KEY=\n# ANTHROPIC_API_KEY=\n# GEMINI_API_KEY=\n# OLLAMA_HOST=http://127.0.0.1:11434\n",
            'app/bootstrap.php' => <<<PHP
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

PHP,
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
            '.dockerignore' => ".git\n.env\n.env.*\n!.env.example\n/var\n/tests\n/vendor\n",
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
            'views/home.kgn.html' => <<<'HTML'
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

HTML,
            '.gitignore' => "/vendor/\n/var/\n.env\n",
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

        return $created;
    }
}
