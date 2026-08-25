<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Tests\Fixture;

use Closure;
use RuntimeException;

/**
 * @phpstan-type MappingField array{field: string, column: string, kind: string, nullable: bool, constructor_parameter: string, accessor: string}
 * @phpstan-type MappingRecord array{record: string, mapper: string, repository: string, table: list<string>, identity: string, fields: list<MappingField>}
 * @phpstan-type MappingSource array{schema: string, version: int, generated_namespace: string, mappings: list<MappingRecord>}
 */
final class TemporaryMappingApplication
{
    private readonly string $root;
    private readonly string $recordClass;
    private readonly string $generatedNamespace;

    /** @param ?Closure(string): string $recordSourceFactory */
    public function __construct(?Closure $recordSourceFactory = null)
    {
        $suffix = bin2hex(random_bytes(8));
        $this->root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-mapping-test-' . $suffix;
        $namespace = 'KongonoMappingFixture\\N' . $suffix;
        $this->recordClass = $namespace . '\\Record';
        $this->generatedNamespace = $namespace . '\\Generated';
        if (!mkdir($this->root . '/src', 0o700, true)) {
            throw new RuntimeException('Unable to create mapping fixture.');
        }
        $source = null === $recordSourceFactory ? $this->validRecordSource($namespace) : $recordSourceFactory($namespace);
        $this->write('composer.json', "{}\n");
        $this->write('composer.lock', "{}\n");
        $this->write('src/Record.php', $source);
        require $this->root . '/src/Record.php';
        $this->writeMapping($this->validMapping());
    }

    public function root(): string
    {
        return $this->root;
    }

    public function sourcePath(): string
    {
        return 'config/kongono.data-mapping.json';
    }

    public function recordClass(): string
    {
        return $this->recordClass;
    }

    public function generatedNamespace(): string
    {
        return $this->generatedNamespace;
    }

    /** @return MappingSource */
    public function validMapping(): array
    {
        return [
            'schema' => 'kongono.data-mapping',
            'version' => 1,
            'generated_namespace' => $this->generatedNamespace,
            'mappings' => [[
                'record' => $this->recordClass,
                'mapper' => 'RecordMapper',
                'repository' => 'RecordRepository',
                'table' => ['public', 'fixture_record'],
                'identity' => 'id',
                'fields' => [
                    ['field' => 'id', 'column' => 'record_id', 'kind' => 'integer', 'nullable' => false, 'constructor_parameter' => 'id', 'accessor' => 'id'],
                    ['field' => 'name', 'column' => 'name', 'kind' => 'text', 'nullable' => true, 'constructor_parameter' => 'name', 'accessor' => 'name'],
                ],
            ]],
        ];
    }

    /** @param MappingSource $mapping */
    public function writeMapping(array $mapping): void
    {
        $bytes = json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $this->write($this->sourcePath(), $bytes);
    }

    public function writeRawMapping(string $bytes): void
    {
        $this->write($this->sourcePath(), $bytes);
    }

    public function rewriteRecordSource(string $bytes): void
    {
        $this->write('src/Record.php', $bytes);
    }

    public function close(): void
    {
        if (!is_dir($this->root)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($this->root);
    }

    private function validRecordSource(string $namespace): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

final readonly class Record
{
    public function __construct(private int \$id, private ?string \$name) {}

    public function id(): int
    {
        return \$this->id;
    }

    public function name(): ?string
    {
        return \$this->name;
    }
}
PHP;
    }

    private function write(string $relative, string $bytes): void
    {
        $path = $this->root . '/' . $relative;
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0o700, true)) {
            throw new RuntimeException('Unable to create mapping fixture directory.');
        }
        if (strlen($bytes) !== file_put_contents($path, $bytes)) {
            throw new RuntimeException('Unable to write mapping fixture.');
        }
    }
}
