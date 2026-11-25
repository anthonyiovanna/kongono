<?php

declare(strict_types=1);

namespace Kongono\Cli;

use Kongono\Cli\Context\ProjectContext;
use Kongono\Core\CoreException;

/** Zero-AI instant deterministic code generators (`kongo make:*`, including `make:resource`). */
final class CodeGenerator
{
    public function makeController(ProjectContext $context, string $name, string $namespace = 'App\\Http'): string
    {
        $validNs = $this->validateNamespace($namespace);
        $className = $this->studly($name);
        if (!str_ends_with($className, 'Controller')) {
            $className .= 'Controller';
        }
        $slug = strtolower((string) preg_replace('/Controller\z/', '', $className));
        $path = 'src/Http/' . $className . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$validNs};

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
        $validNs = $this->validateNamespace($namespace);
        $className = $this->studly($name);
        $path = 'src/Http/' . $className . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$validNs};

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

    public function makeMigration(ProjectContext $context, string $name, ?string $alterTable = null): string
    {
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', trim($name)));
        $slug = trim($slug, '_');
        if ('' === $slug) {
            throw new CoreException('Migration name cannot be empty.');
        }

        $timestamp = gmdate('YmdHis');
        $id = $timestamp . '_' . $slug;
        $path = 'migrations/' . $id . '.php';

        if (null !== $alterTable && '' !== trim($alterTable)) {
            $tableSlug = strtolower((string) preg_replace('/[^a-zA-Z0-9_]+/', '_', trim($alterTable)));
            $tableSlug = trim($tableSlug, '_');
            if ('' === $tableSlug) {
                throw new CoreException('Target table name cannot be empty.');
            }

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
        Schema::table(\$db, '{$tableSlug}', static function (Blueprint \$table): void {
            // \$table->string('example_column')->nullable();
        });
    }

    public function down(ConnectionInterface \$db): void
    {
        Schema::table(\$db, '{$tableSlug}', static function (Blueprint \$table): void {
            // \$table->dropColumn('example_column');
        });
    }
};

PHP;
        } else {
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
        }

        $context->writeFileContained($path, $code);

        return $path;
    }

    public function makeTest(ProjectContext $context, string $name, string $namespace = 'App\\Tests'): string
    {
        $validNs = $this->validateNamespace($namespace);
        $className = $this->studly($name);
        if (!str_ends_with($className, 'Test')) {
            $className .= 'Test';
        }
        $path = 'tests/' . $className . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$validNs};

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

    public function makeJob(ProjectContext $context, string $name, string $namespace = 'App\\Jobs'): string
    {
        $validNs = $this->validateNamespace($namespace);
        $className = $this->studly($name);
        if (!str_ends_with($className, 'Job')) {
            $className .= 'Job';
        }
        $path = 'src/Jobs/' . $className . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$validNs};

use Kongono\Jobs\Job;
use Kongono\Jobs\JobHandlerInterface;
use Kongono\Jobs\JobOutcome;

final readonly class {$className} implements JobHandlerInterface
{
    public function handle(Job \$job): JobOutcome
    {
        // \$payload = \$job->payload;

        return JobOutcome::Completed;
    }
}

PHP;
        $context->writeFileContained($path, $code);

        return $path;
    }

    public function makePolicy(ProjectContext $context, string $name, string $namespace = 'App\\Security'): string
    {
        $validNs = $this->validateNamespace($namespace);
        $className = $this->studly($name);
        if (!str_ends_with($className, 'Policy')) {
            $className .= 'Policy';
        }
        $path = 'src/Security/' . $className . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$validNs};

use Kongono\Security\Principal;

final readonly class {$className}
{
    public function view(Principal \$principal, mixed \$resource = null): bool
    {
        return \$principal->hasCapability('admin') || (is_array(\$resource) && (\$resource['user_id'] ?? null) === \$principal->id);
    }

    public function create(Principal \$principal): bool
    {
        return '' !== \$principal->id;
    }

    public function update(Principal \$principal, mixed \$resource): bool
    {
        return \$principal->hasCapability('admin') || (is_array(\$resource) && (\$resource['user_id'] ?? null) === \$principal->id);
    }

    public function delete(Principal \$principal, mixed \$resource): bool
    {
        return \$this->update(\$principal, \$resource);
    }
}

PHP;
        $context->writeFileContained($path, $code);

        return $path;
    }

    public function makeMiddleware(ProjectContext $context, string $name, string $namespace = 'App\\Http\\Middleware'): string
    {
        $validNs = $this->validateNamespace($namespace);
        $className = $this->studly($name);
        if (!str_ends_with($className, 'Middleware')) {
            $className .= 'Middleware';
        }
        $path = 'src/Http/Middleware/' . $className . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$validNs};

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class {$className} implements MiddlewareInterface
{
    public function process(ServerRequestInterface \$request, RequestHandlerInterface \$handler): ResponseInterface
    {
        \$response = \$handler->handle(\$request);

        return \$response;
    }
}

PHP;
        $context->writeFileContained($path, $code);

        return $path;
    }

    public function makeFactory(ProjectContext $context, string $name, string $namespace = 'App\\Testing'): string
    {
        $validNs = $this->validateNamespace($namespace);
        $base = $this->studly($name);
        $className = str_ends_with($base, 'Factory') ? $base : $base . 'Factory';
        $entity = preg_replace('/Factory\z/', '', $className);
        $entityName = is_string($entity) && '' !== $entity ? $entity : 'Item';
        $slug = $this->snake($entityName);
        $path = 'src/Testing/' . $className . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$validNs};

use Kongono\Database\Testing\Factory;

final readonly class {$className}
{
    /**
     * @template T of object
     * @param class-string<T> \$dtoClass
     * @return Factory<T>
     */
    public static function forDto(string \$dtoClass): Factory
    {
        return Factory::for(\$dtoClass, static fn(int \$seq): array => [
            'id' => \$seq,
            'name' => '{$entityName} #' . \$seq,
            'slug' => '{$slug}-' . \$seq,
        ]);
    }
}

PHP;
        $context->writeFileContained($path, $code);

        return $path;
    }

    public function makeRule(ProjectContext $context, string $name, string $namespace = 'App\\Validation'): string
    {
        $validNs = $this->validateNamespace($namespace);
        $className = $this->studly($name);
        $path = 'src/Validation/' . $className . '.php';

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace {$validNs};

use Attribute;
use Kongono\Validation\RuleInterface;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class {$className} implements RuleInterface
{
    public function __construct(
        private string \$message = 'The field failed {$className} validation.',
    ) {}

    public function validate(mixed \$value, string \$field, array \$allInput): ?string
    {
        if (null === \$value || '' === \$value) {
            return null;
        }

        return is_string(\$value) && '' !== trim(\$value) ? null : \$this->message;
    }
}

PHP;
        $context->writeFileContained($path, $code);

        return $path;
    }

    /** @return list<string> Relative paths created */
    public function makeMail(ProjectContext $context, string $name, string $namespace = 'App\\Mail'): array
    {
        $validNs = $this->validateNamespace($namespace);
        $base = $this->studly($name);
        $className = str_ends_with($base, 'Mail') ? $base : $base . 'Mail';
        $subjectBase = preg_replace('/Mail\z/', '', $className);
        $label = is_string($subjectBase) && '' !== $subjectBase ? $subjectBase : 'Notification';
        $slug = str_replace('_', '-', $this->snake($label));

        $phpPath = 'src/Mail/' . $className . '.php';
        $viewPath = 'views/mail/' . $slug . '.kgn.html';

        $phpCode = <<<PHP
<?php

declare(strict_types=1);

namespace {$validNs};

use Kongono\Mail\Email;
use Kongono\View\ViewEngine;

final readonly class {$className}
{
    public function __construct(
        private ViewEngine \$views,
    ) {}

    public function build(string \$recipientEmail, string \$recipientName): Email
    {
        \$html = \$this->views->render('mail/{$slug}', [
            'name' => \$recipientName,
        ]);

        return Email::create()
            ->to(\$recipientEmail, \$recipientName)
            ->subject('{$label}')
            ->html(\$html)
            ->text(sprintf('Hello %s,', \$recipientName));
    }
}

PHP;

        $viewCode = <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Notification</title>
</head>
<body>
  <p>Hello {{ $name }},</p>
  <p>This message was sent from your Kongono application.</p>
</body>
</html>

HTML;

        $context->writeFileContained($phpPath, $phpCode);
        $context->writeFileContained($viewPath, $viewCode);

        return [$phpPath, $viewPath];
    }

    /**
     * Scaffold a complete vertical CRUD resource slice:
     * migration, DTOs, QueryBuilder repository, attribute controller, and TestClient test.
     *
     * @return list<string> Relative paths created
     */
    public function makeResource(ProjectContext $context, string $name, string $baseNamespace = 'App'): array
    {
        $validBase = $this->validateNamespace($baseNamespace);
        $singular = $this->studly($name);
        $snake = $this->snake($singular);
        $table = $this->pluralize($snake);
        $routeSlug = str_replace('_', '-', $table);

        $timestamp = gmdate('YmdHis');
        $migrationId = $timestamp . '_create_' . $table;
        $migrationPath = 'migrations/' . $migrationId . '.php';
        $dtoPath = 'src/Http/Dto/' . $singular . 'Dto.php';
        $createDtoPath = 'src/Http/Dto/Create' . $singular . 'Input.php';
        $updateDtoPath = 'src/Http/Dto/Update' . $singular . 'Input.php';
        $repoPath = 'src/Repository/' . $singular . 'Repository.php';
        $controllerPath = 'src/Http/' . $singular . 'Controller.php';
        $testPath = 'tests/' . $singular . 'ResourceTest.php';

        $migrationCode = <<<PHP
<?php

declare(strict_types=1);

use Kongono\Database\ConnectionInterface;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Schema\Blueprint;
use Kongono\Database\Schema\Schema;

return new class () implements MigrationInterface {
    public function id(): string
    {
        return '{$migrationId}';
    }

    public function up(ConnectionInterface \$db): void
    {
        Schema::create(\$db, '{$table}', static function (Blueprint \$table): void {
            \$table->id();
            \$table->string('name', 160);
            \$table->text('description')->nullable();
            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    public function down(ConnectionInterface \$db): void
    {
        Schema::dropIfExists(\$db, '{$table}');
    }
};

PHP;

        $dtoCode = <<<PHP
<?php

declare(strict_types=1);

namespace {$validBase}\\Http\\Dto;

final readonly class {$singular}Dto
{
    public function __construct(
        public int \$id,
        public string \$name,
        public ?string \$description = null,
        public ?string \$createdAt = null,
        public ?string \$updatedAt = null,
    ) {}
}

PHP;

        $createDtoCode = <<<PHP
<?php

declare(strict_types=1);

namespace {$validBase}\\Http\\Dto;

use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Required;

final readonly class Create{$singular}Input
{
    public function __construct(
        #[Required, Min(1), Max(160)]
        public string \$name,
        #[Max(4000)]
        public ?string \$description = null,
    ) {}
}

PHP;

        $updateDtoCode = <<<PHP
<?php

declare(strict_types=1);

namespace {$validBase}\\Http\\Dto;

use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;

final readonly class Update{$singular}Input
{
    public function __construct(
        #[Min(1), Max(160)]
        public ?string \$name = null,
        #[Max(4000)]
        public ?string \$description = null,
    ) {}
}

PHP;

        $repoCode = <<<PHP
<?php

declare(strict_types=1);

namespace {$validBase}\\Repository;

use {$validBase}\\Http\\Dto\\Create{$singular}Input;
use {$validBase}\\Http\\Dto\\{$singular}Dto;
use {$validBase}\\Http\\Dto\\Update{$singular}Input;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\Page;

final readonly class {$singular}Repository
{
    private const string TABLE = '{$table}';

    public function __construct(
        private ConnectionInterface \$db,
    ) {}

    /** @return Page<array<string, mixed>> */
    public function paginate(int \$page = 1, int \$perPage = 20): Page
    {
        return \$this->db->table(self::TABLE)
            ->withoutTrashed()
            ->orderBy('id', 'DESC')
            ->paginate(\$page, \$perPage);
    }

    public function findById(int \$id): ?{$singular}Dto
    {
        return \$this->db->table(self::TABLE)
            ->withoutTrashed()
            ->where('id', '=', \$id)
            ->firstAs({$singular}Dto::class);
    }

    /** @return array<string, mixed>|null */
    public function create(Create{$singular}Input \$input): ?array
    {
        \$now = gmdate('Y-m-d H:i:s');

        return \$this->db->table(self::TABLE)->insertReturning([
            'name' => \$input->name,
            'description' => \$input->description,
            'created_at' => \$now,
            'updated_at' => \$now,
        ]);
    }

    public function update(int \$id, Update{$singular}Input \$input): int
    {
        \$values = ['updated_at' => gmdate('Y-m-d H:i:s')];
        if (null !== \$input->name) {
            \$values['name'] = \$input->name;
        }
        if (null !== \$input->description) {
            \$values['description'] = \$input->description;
        }

        return \$this->db->table(self::TABLE)
            ->withoutTrashed()
            ->where('id', '=', \$id)
            ->update(\$values);
    }

    public function delete(int \$id): int
    {
        return \$this->db->table(self::TABLE)
            ->withoutTrashed()
            ->where('id', '=', \$id)
            ->softDelete();
    }
}

PHP;

        $controllerCode = <<<PHP
<?php

declare(strict_types=1);

namespace {$validBase}\\Http;

use {$validBase}\\Http\\Dto\\Create{$singular}Input;
use {$validBase}\\Http\\Dto\\Update{$singular}Input;
use {$validBase}\\Repository\\{$singular}Repository;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\Routing\CacheControl;
use Kongono\Http\Routing\Delete;
use Kongono\Http\Routing\Get;
use Kongono\Http\Routing\Idempotent;
use Kongono\Http\Routing\Patch;
use Kongono\Http\Routing\Post;
use Kongono\Http\Routing\Prefix;

#[Prefix('/api/{$routeSlug}')]
final readonly class {$singular}Controller
{
    public function __construct(
        private {$singular}Repository \$repository,
    ) {}

    #[Get('/', name: '{$routeSlug}.index')]
    #[CacheControl(maxAge: 30, public: false)]
    public function index(Request \$request): Response
    {
        \$pageParam = \$request->query('page', '1');
        \$page = is_scalar(\$pageParam) ? max(1, (int) \$pageParam) : 1;

        return Response::json(\$this->repository->paginate(\$page)->toArray());
    }

    #[Get('/{id:\\d+}', name: '{$routeSlug}.show')]
    public function show(int \$id): Response
    {
        \$item = \$this->repository->findById(\$id);
        if (null === \$item) {
            return Response::json(['error' => '{$singular} not found.'], status: 404);
        }

        return Response::json(['data' => \$item]);
    }

    #[Post('/', name: '{$routeSlug}.store')]
    #[Idempotent(ttlSeconds: 3600)]
    public function store(Create{$singular}Input \$input): Response
    {
        \$created = \$this->repository->create(\$input);

        return Response::json(['data' => \$created], status: 201);
    }

    #[Patch('/{id:\\d+}', name: '{$routeSlug}.update')]
    public function update(int \$id, Update{$singular}Input \$input): Response
    {
        \$affected = \$this->repository->update(\$id, \$input);
        if (0 === \$affected) {
            return Response::json(['error' => '{$singular} not found.'], status: 404);
        }

        return Response::json(['data' => \$this->repository->findById(\$id)]);
    }

    #[Delete('/{id:\\d+}', name: '{$routeSlug}.destroy')]
    public function destroy(int \$id): Response
    {
        \$affected = \$this->repository->delete(\$id);
        if (0 === \$affected) {
            return Response::json(['error' => '{$singular} not found.'], status: 404);
        }

        return Response::json(['deleted' => true, 'id' => \$id]);
    }
}

PHP;

        $testCode = <<<PHP
<?php

declare(strict_types=1);

namespace {$validBase}\\Tests;

use Kongono\Http\App;
use Kongono\Http\Testing\TestClient;
use PHPUnit\Framework\TestCase;

final class {$singular}ResourceTest extends TestCase
{
    public function testCreateValidationRejectsEmptyPayload(): void
    {
        /** @var App \$app */
        \$app = require dirname(__DIR__) . '/app/bootstrap.php';
        \$client = new TestClient(\$app);

        // Register {$singular}Controller in app/bootstrap.php and bind ConnectionInterface to run full CRUD tests.
        \$response = \$client->postJson('/api/{$routeSlug}', ['name' => '']);
        self::assertContains(\$response->status(), [404, 422]);
    }
}

PHP;

        $files = [
            $migrationPath => $migrationCode,
            $dtoPath => $dtoCode,
            $createDtoPath => $createDtoCode,
            $updateDtoPath => $updateDtoCode,
            $repoPath => $repoCode,
            $controllerPath => $controllerCode,
            $testPath => $testCode,
        ];

        foreach ($files as $relative => $contents) {
            $context->writeFileContained($relative, $contents);
        }

        return array_keys($files);
    }

    private function snake(string $value): string
    {
        $converted = preg_replace('/(?<!^)[A-Z]/', '_$0', $value);
        $slug = strtolower(is_string($converted) ? $converted : $value);
        $clean = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $trimmed = trim(is_string($clean) ? $clean : $slug, '_');

        return '' !== $trimmed ? $trimmed : 'item';
    }

    private function pluralize(string $snake): string
    {
        if (str_ends_with($snake, 's')) {
            return $snake;
        }
        if (str_ends_with($snake, 'y') && strlen($snake) > 1 && !in_array($snake[-2], ['a', 'e', 'i', 'o', 'u'], true)) {
            return substr($snake, 0, -1) . 'ies';
        }

        return $snake . 's';
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

    private function validateNamespace(string $namespace): string
    {
        $trimmed = trim($namespace);
        if (1 !== preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*(?:\\\\[a-zA-Z_][a-zA-Z0-9_]*)*\z/', $trimmed)) {
            throw new CoreException(sprintf('Unsafe PHP namespace "%s" rejected.', $namespace));
        }

        return $trimmed;
    }
}
