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

        $safeName = str_replace("'", "\\'", $appName);
        $files = [
            'composer.json' => json_encode([
                'name' => 'app/' . strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', trim($appName))),
                'type' => 'project',
                'require' => [
                    'php' => '>=8.5.0',
                    'kongono/framework' => '^1.0@alpha',
                ],
                'autoload' => [
                    'psr-4' => ['App\\' => 'src/'],
                ],
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            'app/bootstrap.php' => <<<PHP
<?php

declare(strict_types=1);

use App\Http\HomeController;
use Kongono\Http\App;
use Kongono\Security\Middleware\SecurityHeadersMiddleware;

return App::create(dirname(__DIR__), name: '{$safeName}')
    ->config(['app' => ['debug' => true]])
    ->views()
    ->middleware(new SecurityHeadersMiddleware())
    ->controller(HomeController::class);

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
            'src/Http/HomeController.php' => <<<PHP
<?php

declare(strict_types=1);

namespace App\Http;

use Kongono\Http\Response;
use Kongono\Http\Routing\Get;
use Kongono\View\ViewEngine;

final readonly class HomeController
{
    public function __construct(
        private ViewEngine \$views,
    ) {}

    #[Get('/', name: 'home')]
    public function index(): Response
    {
        return Response::html(\$this->views->render('home', [
            'title' => '{$safeName}',
            'phpVersion' => PHP_VERSION,
        ]));
    }

    #[Get('/api/health', name: 'api.health')]
    public function health(): Response
    {
        return Response::json(['status' => 'ok', 'app' => '{$safeName}']);
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
