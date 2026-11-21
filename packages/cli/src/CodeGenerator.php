<?php

declare(strict_types=1);

namespace Kongono\Cli;

use Kongono\Cli\Context\ProjectContext;
use Kongono\Core\CoreException;

/** Zero-AI instant deterministic code generators (`kongo make:controller`, `make:dto`, `make:migration`, `make:test`). */
final class CodeGenerator
{
    public function makeController(ProjectContext $context, string $name, string $namespace = 'App\\Http'): string
    {
        $className = $this->studly($name);
        if (!str_ends_with($className, 'Controller')) {
            $className .= 'Controller';
        }
        $slug = strtolower((string) preg_replace('/Controller\z/', '', $className));
        $path = 'src/Http/' . $className . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\Get;

final readonly class {$className}
{
    #[Get('/{$slug}', name: '{$slug}.index')]
    public function index(Request \$request): Response
    {
        return Response::json(['resource' => '{$slug}', 'status' => 'ok']);
    }
}

PHP;
        $context->writeFileContained($path, $code);

        return $path;
    }

    public function makeDto(ProjectContext $context, string $name, string $namespace = 'App\\Http'): string
    {
        $className = $this->studly($name);
        $path = 'src/Http/' . $className . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Required;

final readonly class {$className}
{
    public function __construct(
        #[Required, Min(1), Max(120)]
        public string \$name,
    ) {}
}

PHP;
        $context->writeFileContained($path, $code);

        return $path;
    }

    public function makeMigration(ProjectContext $context, string $name): string
    {
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', trim($name)));
        $slug = trim($slug, '_');
        if ('' === $slug) {
            throw new CoreException('Migration name cannot be empty.');
        }

        $timestamp = gmdate('YmdHis');
        $id = $timestamp . '_' . $slug;
        $path = 'migrations/' . $id . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

use Kongono\Database\ConnectionInterface;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Schema\Blueprint;
use Kongono\Database\Schema\Schema;

return new class () implements MigrationInterface {
    public function id(): string
    {
        return '{$id}';
    }

    public function up(ConnectionInterface \$db): void
    {
        Schema::create(\$db, '{$slug}', static function (Blueprint \$table): void {
            \$table->id();
            \$table->string('name');
            \$table->timestamps();
        });
    }

    public function down(ConnectionInterface \$db): void
    {
        Schema::dropIfExists(\$db, '{$slug}');
    }
};

PHP;
        $context->writeFileContained($path, $code);

        return $path;
    }

    public function makeTest(ProjectContext $context, string $name, string $namespace = 'App\\Tests'): string
    {
        $className = $this->studly($name);
        if (!str_ends_with($className, 'Test')) {
            $className .= 'Test';
        }
        $path = 'tests/' . $className . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Kongono\Http\App;
use Kongono\Http\Testing\TestClient;
use PHPUnit\Framework\TestCase;

final class {$className} extends TestCase
{
    public function testHomeRouteReturnsOk(): void
    {
        /** @var App \$app */
        \$app = require dirname(__DIR__) . '/app/bootstrap.php';
        \$client = new TestClient(\$app);

        \$client->get('/')->assertOk();
    }
}

PHP;
        $context->writeFileContained($path, $code);

        return $path;
    }

    public function makeSeeder(ProjectContext $context, string $name): string
    {
        $className = $this->studly($name);
        if (!str_ends_with($className, 'Seeder')) {
            $className .= 'Seeder';
        }
        $path = 'seeders/' . $className . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

use Kongono\Database\ConnectionInterface;
use Kongono\Database\SeederInterface;

return new class () implements SeederInterface {
    public function run(ConnectionInterface \$db): void
    {
        // \$db->table('items')->insert(['name' => 'Example']);
    }
};

PHP;
        $context->writeFileContained($path, $code);

        return $path;
    }

    private function studly(string $name): string
    {
        $cleaned = preg_replace('/[^a-zA-Z0-9]+/', ' ', trim($name));
        $studly = str_replace(' ', '', ucwords(is_string($cleaned) ? $cleaned : $name));
        if ('' === $studly) {
            throw new CoreException(sprintf('Invalid class name "%s".', $name));
        }

        return $studly;
    }
}
