<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Tests\Unit;

use Kongono\DatabaseMapping\Build\MappingArtifactBuilder;
use Kongono\DatabaseMapping\Build\MappingArtifactValidator;
use Kongono\DatabaseMapping\Exception\InvalidMappingArtifactException;
use Kongono\DatabaseMapping\Exception\InvalidMappingSourceException;
use Kongono\DatabaseMapping\Exception\StaleMappingArtifactException;
use Kongono\DatabaseMapping\Tests\Fixture\TemporaryMappingApplication;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** @phpstan-import-type MappingSource from TemporaryMappingApplication */
final class MappingArtifactTest extends TestCase
{
    public function testCanonicalModelIgnoresJsonPresentationButArtifactBindsRawSource(): void
    {
        $application = new TemporaryMappingApplication();
        try {
            $builder = new MappingArtifactBuilder();
            $first = $builder->build($application->root(), $application->sourcePath());
            $source = $application->validMapping();
            $reordered = [
                'mappings' => $source['mappings'],
                'generated_namespace' => $source['generated_namespace'],
                'version' => 1,
                'schema' => 'kongono.data-mapping',
            ];
            $application->writeRawMapping(json_encode($reordered, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $second = $builder->build($application->root(), $application->sourcePath());

            self::assertSame($first->modelSha256(), $second->modelSha256());
            self::assertNotSame($first->sourceSha256(), $second->sourceSha256());
            self::assertNotSame($first->artifactSha256(), $second->artifactSha256());
            self::assertSame($second->canonicalBytes(), (new MappingArtifactValidator())->validateCurrent(
                $application->root(),
                $application->sourcePath(),
                $second->canonicalBytes(),
            )->canonicalBytes());
        } finally {
            $application->close();
        }
    }

    public function testStrictSourceAndPathAdmissionRejectsUnsafeInputs(): void
    {
        $application = new TemporaryMappingApplication();
        try {
            $builder = new MappingArtifactBuilder();
            foreach (['../kongono.data-mapping.json', 'config\\kongono.data-mapping.json', 'config/KONGONO.data-mapping.json', 'config/kongono.data-mapping.json:stream'] as $path) {
                try {
                    $builder->build($application->root(), $path);
                    self::fail('Unsafe mapping source path was accepted.');
                } catch (InvalidMappingSourceException $exception) {
                    self::assertSame('KGN-MAPPING-1001', $exception->diagnosticId());
                }
            }

            foreach ([
                "\xEF\xBB\xBF{}",
                "{\"schema\":\"kongono.data-mapping\",\"schema\":\"duplicate\"}",
                "{\"schema\":\"kongono.data-mapping\",\"version\":1,\"generated_namespace\":\"X\\\\Y\",\"mappings\":[],\"extra\":true}",
                "{\"schema\":\"kongono.data-mapping\",\"version\":1,\"generated_namespace\":\"X\\\\Y\",\"mappings\":[]}",
            ] as $bytes) {
                $application->writeRawMapping($bytes);
                $this->expectSourceFailure($builder, $application);
            }
        } finally {
            $application->close();
        }
    }

    /** @param \Closure(string): string $factory */
    #[DataProvider('invalidRecordSources')]
    public function testRecordContractRejectsInvalidModifiersAndSignatures(\Closure $factory): void
    {
        $application = new TemporaryMappingApplication($factory);
        try {
            $this->expectException(InvalidMappingSourceException::class);
            (new MappingArtifactBuilder())->build($application->root(), $application->sourcePath());
        } finally {
            $application->close();
        }
    }

    /** @return iterable<string, array{0: \Closure(string): string}> */
    public static function invalidRecordSources(): iterable
    {
        yield 'non-final' => [static fn(string $namespace): string => self::recordSource($namespace, 'readonly class Record', 'private int $id, private ?string $name')];
        yield 'non-readonly' => [static fn(string $namespace): string => self::recordSource($namespace, 'final class Record', 'private int $id, private ?string $name')];
        yield 'default' => [static fn(string $namespace): string => self::recordSource($namespace, 'final readonly class Record', 'private int $id = 1, private ?string $name = null')];
        yield 'union' => [static fn(string $namespace): string => self::recordSource($namespace, 'final readonly class Record', 'private int|string $id, private ?string $name', 'int|string')];
        yield 'variadic' => [static fn(string $namespace): string => <<<PHP
<?php
declare(strict_types=1);
namespace {$namespace};
final readonly class Record
{
    public function __construct(int ...\$id) {}
    public function id(): int { return 1; }
    public function name(): ?string { return null; }
}
PHP];
    }

    public function testMappingVocabularyRejectsDuplicatesMismatchAndInvalidIdentity(): void
    {
        $application = new TemporaryMappingApplication();
        try {
            $builder = new MappingArtifactBuilder();
            $source = $application->validMapping();
            $mapping = $source['mappings'][0];
            $fields = $mapping['fields'];
            $fields[1] = [...$fields[1], 'column' => 'record_id'];
            $source['mappings'][0] = [...$mapping, 'fields' => $fields];
            $application->writeMapping($source);
            $this->expectSourceFailure($builder, $application);

            $source = $application->validMapping();
            $mapping = $source['mappings'][0];
            $fields = $mapping['fields'];
            $fields[1] = [...$fields[1], 'constructor_parameter' => 'missing'];
            $source['mappings'][0] = [...$mapping, 'fields' => $fields];
            $application->writeMapping($source);
            $this->expectSourceFailure($builder, $application);

            $source = $application->validMapping();
            $mapping = $source['mappings'][0];
            $fields = $mapping['fields'];
            $fields[1] = [...$fields[1], 'nullable' => false];
            $source['mappings'][0] = [...$mapping, 'fields' => $fields];
            $application->writeMapping($source);
            $this->expectSourceFailure($builder, $application);

            $source = $application->validMapping();
            $source['mappings'][0] = [...$source['mappings'][0], 'identity' => 'name'];
            $application->writeMapping($source);
            $this->expectSourceFailure($builder, $application);

            $source = $application->validMapping();
            $source['mappings'][] = $source['mappings'][0];
            $application->writeMapping($source);
            $this->expectSourceFailure($builder, $application);
        } finally {
            $application->close();
        }
    }

    public function testArtifactValidationRejectsTamperingAndCurrentValidationDetectsStaleEvidence(): void
    {
        $application = new TemporaryMappingApplication();
        try {
            $builder = new MappingArtifactBuilder();
            $artifact = $builder->build($application->root(), $application->sourcePath());
            $validator = new MappingArtifactValidator();
            self::assertSame($artifact->artifactSha256(), $validator->validateStructure($artifact->canonicalBytes())->artifactSha256());

            $tampered = str_replace('kongono.data-mapping-artifact', 'kongono.data-mapping-artifacx', $artifact->canonicalBytes());
            $this->expectException(InvalidMappingArtifactException::class);
            $validator->validateStructure($tampered);
        } finally {
            $application->close();
        }
    }

    public function testCurrentValidationFailsClosedAfterExactEvidenceChanges(): void
    {
        $application = new TemporaryMappingApplication();
        try {
            $artifact = (new MappingArtifactBuilder())->build($application->root(), $application->sourcePath());
            $application->writeRawMapping(json_encode($application->validMapping(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $this->expectException(StaleMappingArtifactException::class);
            (new MappingArtifactValidator())->validateCurrent($application->root(), $application->sourcePath(), $artifact->canonicalBytes());
        } finally {
            $application->close();
        }
    }

    private function expectSourceFailure(MappingArtifactBuilder $builder, TemporaryMappingApplication $application): void
    {
        try {
            $builder->build($application->root(), $application->sourcePath());
            self::fail('Invalid mapping source was accepted.');
        } catch (InvalidMappingSourceException $exception) {
            self::assertSame('KGN-MAPPING-1001', $exception->diagnosticId());
            self::assertStringNotContainsString($application->recordClass(), $exception->getMessage());
            self::assertStringNotContainsString($application->root(), (string) $exception);
        }
    }

    private static function recordSource(string $namespace, string $declaration, string $parameters, string $identityType = 'int'): string
    {
        return <<<PHP
<?php
declare(strict_types=1);
namespace {$namespace};
{$declaration}
{
    public function __construct({$parameters}) {}
    public function id(): {$identityType} { return \$this->id; }
    public function name(): ?string { return \$this->name; }
}
PHP;
    }
}
