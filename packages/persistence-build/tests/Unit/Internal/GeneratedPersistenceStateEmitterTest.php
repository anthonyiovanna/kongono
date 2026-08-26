<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Internal;

use Kongono\Persistence\EntityIdentity;
use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Exception\InvalidGeneratedPersistenceStateException;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateValidator;
use Kongono\PersistenceBuild\Internal\GeneratedPersistenceStateEmitter;
use Kongono\PersistenceBuild\Internal\PersistenceGenerationImplementation;
use Kongono\PersistenceBuild\Model\AggregateDefinition;
use Kongono\PersistenceBuild\Model\ConstructionDefinition;
use Kongono\PersistenceBuild\Model\ConstructionKind;
use Kongono\PersistenceBuild\Model\EntityDefinition;
use Kongono\PersistenceBuild\Model\FieldDefinition;
use Kongono\PersistenceBuild\Model\FieldRole;
use Kongono\PersistenceBuild\Model\FieldValueKind;
use Kongono\PersistenceBuild\Model\PersistenceModel;
use PHPUnit\Framework\TestCase;

final class GeneratedPersistenceStateEmitterTest extends TestCase
{
    public function testEmitterProducesOneCanonicalClassPerEntityWithoutAmbientAuthority(): void
    {
        $root = dirname(__DIR__, 5);
        $artifact = (new PersistenceArtifactBuilder())->build(
            $root,
            'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json',
        );
        $emitter = new GeneratedPersistenceStateEmitter();
        $generatorIdentity = PersistenceGenerationImplementation::generatorSha256();
        $first = $emitter->emit(
            $artifact->model(),
            $artifact->artifactSha256(),
            $artifact->modelSha256(),
            $artifact->builderImplementationSha256(),
            $generatorIdentity,
        );
        $second = $emitter->emit(
            $artifact->model(),
            $artifact->artifactSha256(),
            $artifact->modelSha256(),
            $artifact->builderImplementationSha256(),
            $generatorIdentity,
        );

        self::assertSame($first, $second);
        self::assertSame(2, substr_count($first, 'final class EntityState_'));
        self::assertSame(2, substr_count($first, 'private function __construct() {}'));
        foreach (['hydrate', 'extract', 'identity', 'snapshot', 'changes', 'insertion', 'deletion'] as $method) {
            self::assertSame(2, substr_count($first, 'public static function ' . $method . '('));
        }
        self::assertSame(0, preg_match('/\b(?:Reflection|eval|unserialize|PDO|pg_connect|mysqli_connect|getenv|putenv)\b/i', $first));
        self::assertStringEndsWith("\n", $first);
        self::assertStringNotContainsString("\r", $first);
        self::assertFalse(str_starts_with($first, "\xEF\xBB\xBF"));
    }

    public function testEveryScalarEncodingNonFiniteRejectionAndInvariantPropagation(): void
    {
        $bytes = (new GeneratedPersistenceStateEmitter())->emit(
            $this->scalarModel(),
            str_repeat('1', 64),
            str_repeat('2', 64),
            str_repeat('3', 64),
            PersistenceGenerationImplementation::generatorSha256(),
        );
        $artifact = (new GeneratedPersistenceStateValidator())->validateStructure($bytes);
        $path = sys_get_temp_dir() . '/kongono-generated-scalar-' . bin2hex(random_bytes(8)) . '.php';

        try {
            self::assertSame(strlen($bytes), file_put_contents($path, $bytes));
            require $path;
            $map = $artifact->classMap();
            foreach ([
                BinaryIdentityEntity::class => ["\x31", 'x:MQ=='],
                BooleanIdentityEntity::class => [true, 'b:1'],
                FloatIdentityEntity::class => [1.0, 'f:3ff0000000000000'],
                InvariantEntity::class => [7, 'i:7'],
                TextIdentityEntity::class => ['1', 't:MQ=='],
            ] as $entityClass => [$value, $encoded]) {
                $adapter = $map[$entityClass];
                $entity = $adapter::hydrate(['value' => $value]);
                self::assertInstanceOf($entityClass, $entity);
                $identity = $adapter::identity($entity);
                self::assertInstanceOf(EntityIdentity::class, $identity);
                self::assertSame($encoded, $identity->key());
                self::assertSame(['value' => $value], $adapter::extract($entity));
            }

            $floatAdapter = $map[FloatIdentityEntity::class];
            foreach ([NAN, INF, -INF] as $nonFinite) {
                try {
                    $floatAdapter::hydrate(['value' => $nonFinite]);
                    self::fail('Expected non-finite float rejection.');
                } catch (InvalidGeneratedPersistenceStateException $failure) {
                    self::assertSame('KGN-PERSISTENCE-BUILD-1015', $failure->diagnosticId());
                }
            }

            $invariantAdapter = $map[InvariantEntity::class];
            try {
                $invariantAdapter::hydrate(['value' => 13]);
                self::fail('Expected application invariant failure.');
            } catch (\DomainException $failure) {
                self::assertSame('application invariant', $failure->getMessage());
            }
        } finally {
            if (file_exists($path)) {
                self::assertTrue(unlink($path));
            }
        }
    }

    private function scalarModel(): PersistenceModel
    {
        $entities = [];
        foreach ([
            BinaryIdentityEntity::class => FieldValueKind::Binary,
            BooleanIdentityEntity::class => FieldValueKind::Boolean,
            FloatIdentityEntity::class => FieldValueKind::Float,
            InvariantEntity::class => FieldValueKind::Integer,
            TextIdentityEntity::class => FieldValueKind::Text,
        ] as $entity => $kind) {
            $entities[] = new EntityDefinition(
                $entity,
                $entity . 'Record',
                new ConstructionDefinition(ConstructionKind::Constructor, null),
                'value',
                null,
                [new FieldDefinition('value', 'value', 'value', 'value', FieldRole::Identity, $kind, false)],
                'fixtures/scalar.php',
                1,
                str_repeat('4', 64),
            );
        }

        return new PersistenceModel(
            'Kongono\PersistenceBuild\Tests\GeneratedScalarEvidence',
            $entities,
            [new AggregateDefinition('scalar', BinaryIdentityEntity::class, array_keys([
                BinaryIdentityEntity::class => true,
                BooleanIdentityEntity::class => true,
                FloatIdentityEntity::class => true,
                InvariantEntity::class => true,
                TextIdentityEntity::class => true,
            ]))],
            [],
        );
    }
}

final readonly class BinaryIdentityEntity
{
    public function __construct(private string $value) {}

    public function value(): string
    {
        return $this->value;
    }
}

final readonly class BooleanIdentityEntity
{
    public function __construct(private bool $value) {}

    public function value(): bool
    {
        return $this->value;
    }
}

final readonly class FloatIdentityEntity
{
    public function __construct(private float $value) {}

    public function value(): float
    {
        return $this->value;
    }
}

final readonly class InvariantEntity
{
    public function __construct(private int $value)
    {
        if (13 === $value) {
            throw new \DomainException('application invariant');
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}

final readonly class TextIdentityEntity
{
    public function __construct(private string $value) {}

    public function value(): string
    {
        return $this->value;
    }
}
