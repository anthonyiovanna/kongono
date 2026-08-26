<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Model;

use Closure;
use Kongono\PersistenceBuild\Build\PersistenceArtifact;
use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceArtifactException;
use Kongono\PersistenceBuild\Model\AggregateDefinition;
use Kongono\PersistenceBuild\Model\ConstructionDefinition;
use Kongono\PersistenceBuild\Model\ConstructionKind;
use Kongono\PersistenceBuild\Model\EntityDefinition;
use Kongono\PersistenceBuild\Model\FieldDefinition;
use Kongono\PersistenceBuild\Model\FieldRole;
use Kongono\PersistenceBuild\Model\PersistenceModel;
use Kongono\PersistenceBuild\Model\RelationshipCardinality;
use Kongono\PersistenceBuild\Model\RelationshipDefinition;
use Kongono\PersistenceBuild\Model\RelationshipOrder;
use Kongono\PersistenceBuild\Model\RelationshipOwnership;
use Kongono\PersistenceBuild\Model\SortDirection;
use PHPUnit\Framework\TestCase;

final class PersistenceModelTest extends TestCase
{
    public function testModelIsDetachedReadonlyAndHasExplicitLookups(): void
    {
        $root = dirname(__DIR__, 5);
        $model = (new PersistenceArtifactBuilder())->build($root, 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json')->model();
        $entity = $model->entity('Kongono\PersistenceBuild\Tests\Fixture\Domain\Account');

        self::assertNotNull($entity);
        self::assertSame(FieldRole::Identity, $entity->field('id')?->role());
        self::assertNull($model->entity('Missing\Entity'));
        self::assertNotNull($model->aggregate('account'));
        self::assertNotNull($model->relationship('account_profiles'));
        self::assertTrue((new \ReflectionClass($model))->isReadOnly());
        self::assertFalse((new \ReflectionClass($entity))->hasProperty('reflection'));
    }

    public function testPublicValueObjectsRejectMalformedLocalAndCrossObjectState(): void
    {
        $root = dirname(__DIR__, 5);
        $model = (new PersistenceArtifactBuilder())->build($root, 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json')->model();
        $entity = $model->entities()[0];
        $field = $entity->fields()[0];
        $aggregate = $model->aggregates()[0];
        $relationship = $model->relationships()[0];

        /** @var list<Closure(): object> $invalidConstructions */
        $invalidConstructions = [
            static fn(): FieldDefinition => new FieldDefinition(str_repeat('a', 192), 'id', 'id', 'id', FieldRole::Identity, $field->valueKind(), false),
            static fn(): ConstructionDefinition => new ConstructionDefinition(ConstructionKind::StaticFactory, 'bad-method'),
            static fn(): EntityDefinition => self::entityWith($entity, entityName: 'Invalid-Entity'),
            static fn(): EntityDefinition => self::entityWith($entity, fields: [$field, $field]),
            static fn(): EntityDefinition => self::entityWith($entity, sourcePath: '../private.php'),
            static fn(): EntityDefinition => self::entityWith($entity, sourceBytes: 1_048_577),
            static fn(): EntityDefinition => self::entityWith($entity, sourceSha256: 'private-digest'),
            static fn(): AggregateDefinition => new AggregateDefinition($aggregate->name(), $aggregate->root(), [$aggregate->root(), $aggregate->root()]),
            static fn(): RelationshipDefinition => new RelationshipDefinition(
                $relationship->name(),
                $relationship->source(),
                $relationship->target(),
                $relationship->sourceField(),
                $relationship->targetField(),
                RelationshipCardinality::ToMany,
                $relationship->nullable(),
                RelationshipOwnership::Aggregate,
                [new RelationshipOrder('id', SortDirection::Ascending), new RelationshipOrder('id', SortDirection::Descending)],
            ),
            static fn(): PersistenceModel => new PersistenceModel($model->generatedNamespace(), [$entity, $entity], [$aggregate], [$relationship]),
            static fn(): PersistenceModel => new PersistenceModel('Invalid-Namespace', $model->entities(), $model->aggregates(), $model->relationships()),
        ];

        foreach ($invalidConstructions as $construct) {
            try {
                $construct();
                self::fail('Expected direct value-object rejection.');
            } catch (InvalidPersistenceArtifactException $failure) {
                self::assertSame('KGN-PERSISTENCE-BUILD-1007: Invalid persistence artifact', (string) $failure);
            }
        }
    }

    public function testArtifactDirectConstructionRejectsInconsistentIdentity(): void
    {
        $root = dirname(__DIR__, 5);
        $artifact = (new PersistenceArtifactBuilder())->build($root, 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json');
        /** @var list<Closure(): PersistenceArtifact> $invalidConstructions */
        $invalidConstructions = [
            static fn(): PersistenceArtifact => new PersistenceArtifact(
                $artifact->canonicalBytes(),
                'malformed-private-digest',
                $artifact->sourceSha256(),
                $artifact->modelSha256(),
                $artifact->mappingArtifactSha256(),
                $artifact->builderImplementationSha256(),
                $artifact->model(),
            ),
            static fn(): PersistenceArtifact => new PersistenceArtifact(
                $artifact->canonicalBytes() . 'tampered',
                $artifact->artifactSha256(),
                $artifact->sourceSha256(),
                $artifact->modelSha256(),
                $artifact->mappingArtifactSha256(),
                $artifact->builderImplementationSha256(),
                $artifact->model(),
            ),
            static fn(): PersistenceArtifact => new PersistenceArtifact(
                $artifact->canonicalBytes(),
                $artifact->artifactSha256(),
                $artifact->sourceSha256(),
                str_repeat('0', 64),
                $artifact->mappingArtifactSha256(),
                $artifact->builderImplementationSha256(),
                $artifact->model(),
            ),
        ];

        foreach ($invalidConstructions as $construct) {
            try {
                $construct();
                self::fail('Expected direct artifact rejection.');
            } catch (InvalidPersistenceArtifactException $failure) {
                self::assertSame('KGN-PERSISTENCE-BUILD-1007: Invalid persistence artifact', (string) $failure);
            }
        }
    }

    /** @param list<FieldDefinition>|null $fields */
    private static function entityWith(
        EntityDefinition $original,
        ?string $sourcePath = null,
        ?int $sourceBytes = null,
        ?string $sourceSha256 = null,
        ?array $fields = null,
        ?string $entityName = null,
    ): EntityDefinition {
        return new EntityDefinition(
            $entityName ?? $original->entity(),
            $original->storageRecord(),
            $original->construction(),
            $original->identity(),
            $original->concurrency(),
            $fields ?? $original->fields(),
            $sourcePath ?? $original->sourcePath(),
            $sourceBytes ?? $original->sourceBytes(),
            $sourceSha256 ?? $original->sourceSha256(),
        );
    }
}
