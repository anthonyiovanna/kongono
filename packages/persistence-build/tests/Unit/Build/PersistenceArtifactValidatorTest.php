<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Build;

use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Build\PersistenceArtifactValidator;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceArtifactException;
use Kongono\PersistenceBuild\Exception\StalePersistenceArtifactException;
use Kongono\PersistenceBuild\Internal\CanonicalJson;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PersistenceArtifactValidatorTest extends TestCase
{
    private const SOURCE = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';

    public function testStructuralAndCurrentValidationAreDistinctSuccessfulProofs(): void
    {
        $root = dirname(__DIR__, 5);
        $bytes = (new PersistenceArtifactBuilder())->build($root, self::SOURCE)->canonicalBytes();
        $validator = new PersistenceArtifactValidator();

        self::assertSame($bytes, $validator->validateStructure($bytes)->canonicalBytes());
        self::assertSame($bytes, $validator->validateCurrent($root, self::SOURCE, $bytes)->canonicalBytes());
    }

    public function testStructurallyValidButNoncurrentEvidenceIsStale(): void
    {
        $root = dirname(__DIR__, 5);
        $bytes = (new PersistenceArtifactBuilder())->build($root, self::SOURCE)->canonicalBytes();
        $data = CanonicalJson::decodeObject($bytes, 96);
        $provenance = $data['provenance'] ?? null;
        self::assertIsArray($provenance);
        $composer = $provenance['composer_json'] ?? null;
        self::assertIsArray($composer);
        $composer['sha256'] = str_repeat('0', 64);
        $provenance['composer_json'] = $composer;
        $data['provenance'] = $provenance;
        $staleBytes = CanonicalJson::encode($data);
        $validator = new PersistenceArtifactValidator();
        self::assertSame($staleBytes, $validator->validateStructure($staleBytes)->canonicalBytes());

        $this->expectException(StalePersistenceArtifactException::class);
        $validator->validateCurrent($root, self::SOURCE, $staleBytes);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidArtifacts(): iterable
    {
        yield 'empty' => [''];
        yield 'noncanonical' => ["{ \"schema\": \"kongono.persistence-artifact\" }\n"];
        yield 'crlf' => ["{}\r\n"];
        yield 'duplicate key' => ["{\"schema\":1,\"schema\":1}\n"];
    }

    #[DataProvider('invalidArtifacts')]
    public function testMalformedOrNoncanonicalArtifactsFailClosed(string $bytes): void
    {
        $this->expectException(InvalidPersistenceArtifactException::class);
        (new PersistenceArtifactValidator())->validateStructure($bytes);
    }

    /** @return iterable<string, array{string}> */
    public static function semanticTamperCases(): iterable
    {
        foreach ([
            'duplicate_entity',
            'duplicate_storage_record',
            'duplicate_field_binding',
            'invalid_roles',
            'missing_aggregate_membership',
            'duplicate_aggregate_name',
            'duplicate_aggregate_member',
            'aggregate_root_outside_members',
            'unknown_relationship_endpoint',
            'unknown_relationship_field',
            'incompatible_relationship_kinds',
            'cross_aggregate_ownership',
            'illegal_to_one_ordering',
            'missing_to_many_ordering',
            'duplicate_relationship_ordering',
            'noncanonical_entity_order',
        ] as $case) {
            yield $case => [$case];
        }
    }

    #[DataProvider('semanticTamperCases')]
    public function testSelfConsistentSemanticTamperingFailsStructuralValidation(string $case): void
    {
        $root = dirname(__DIR__, 5);
        $artifact = (new PersistenceArtifactBuilder())->build($root, self::SOURCE);
        $data = CanonicalJson::decodeObject($artifact->canonicalBytes(), 96);
        $this->tamper($data, $case);
        $bytes = $this->recomputeAttackerControlledModelIdentity($data);

        $this->expectException(InvalidPersistenceArtifactException::class);
        (new PersistenceArtifactValidator())->validateStructure($bytes);
    }

    public function testRepeatedValidBuildsRemainByteIdenticalAfterSemanticRevalidation(): void
    {
        $root = dirname(__DIR__, 5);
        $builder = new PersistenceArtifactBuilder();
        $first = $builder->build($root, self::SOURCE);
        $second = $builder->build($root, self::SOURCE);
        $validator = new PersistenceArtifactValidator();

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->artifactSha256(), $second->artifactSha256());
        self::assertSame($first->canonicalBytes(), $validator->validateStructure($first->canonicalBytes())->canonicalBytes());
    }

    /** @param array<string, mixed> $data */
    private function tamper(array &$data, string $case): void
    {
        $model = $this->object($data['model'] ?? null);
        $entities = $this->list($model['entities'] ?? null);
        $aggregates = $this->list($model['aggregates'] ?? null);
        $relationships = $this->list($model['relationships'] ?? null);
        $firstEntity = $this->object($entities[0] ?? null);
        $secondEntity = $this->object($entities[1] ?? null);
        $firstAggregate = $this->object($aggregates[0] ?? null);
        $firstRelationship = $this->object($relationships[0] ?? null);

        switch ($case) {
            case 'duplicate_entity':
                $entities[] = $firstEntity;
                $provenance = $this->object($data['provenance'] ?? null);
                $domainSources = $this->list($provenance['domain_sources'] ?? null);
                $domainSources[] = $domainSources[0];
                $provenance['domain_sources'] = $domainSources;
                $data['provenance'] = $provenance;
                break;
            case 'duplicate_storage_record':
                $secondEntity['storage_record'] = $firstEntity['storage_record'];
                $entities[1] = $secondEntity;
                break;
            case 'duplicate_field_binding':
                $fields = $this->list($firstEntity['fields'] ?? null);
                $fields[] = $fields[0];
                $firstEntity['fields'] = $fields;
                $entities[0] = $firstEntity;
                break;
            case 'invalid_roles':
                $fields = $this->list($firstEntity['fields'] ?? null);
                $identity = $this->object($fields[0] ?? null);
                $identity['role'] = 'value';
                $fields[0] = $identity;
                $firstEntity['fields'] = $fields;
                $entities[0] = $firstEntity;
                break;
            case 'missing_aggregate_membership':
                $members = $this->list($firstAggregate['members'] ?? null);
                array_pop($members);
                $firstAggregate['members'] = $members;
                $aggregates[0] = $firstAggregate;
                break;
            case 'duplicate_aggregate_name':
                $aggregates[] = $firstAggregate;
                break;
            case 'duplicate_aggregate_member':
                $members = $this->list($firstAggregate['members'] ?? null);
                $members[] = $members[0];
                $firstAggregate['members'] = $members;
                $aggregates[0] = $firstAggregate;
                break;
            case 'aggregate_root_outside_members':
                $firstAggregate['root'] = 'Attacker\\MissingRoot';
                $aggregates[0] = $firstAggregate;
                break;
            case 'unknown_relationship_endpoint':
                $firstRelationship['target'] = 'Attacker\\MissingEntity';
                $relationships[0] = $firstRelationship;
                break;
            case 'unknown_relationship_field':
                $firstRelationship['source_field'] = 'missing_field';
                $relationships[0] = $firstRelationship;
                break;
            case 'incompatible_relationship_kinds':
                $fields = $this->list($secondEntity['fields'] ?? null);
                $target = $this->object($fields[1] ?? null);
                $target['kind'] = 'text';
                $fields[1] = $target;
                $secondEntity['fields'] = $fields;
                $entities[1] = $secondEntity;
                break;
            case 'cross_aggregate_ownership':
                $members = $this->list($firstAggregate['members'] ?? null);
                $profile = $members[1];
                $firstAggregate['members'] = [$members[0]];
                $aggregates = [$firstAggregate, ['name' => 'profile', 'root' => $profile, 'members' => [$profile]]];
                break;
            case 'illegal_to_one_ordering':
                $firstRelationship['cardinality'] = 'to_one';
                $relationships[0] = $firstRelationship;
                break;
            case 'missing_to_many_ordering':
                $firstRelationship['order'] = [];
                $relationships[0] = $firstRelationship;
                break;
            case 'duplicate_relationship_ordering':
                $order = $this->list($firstRelationship['order'] ?? null);
                $order[] = $order[0];
                $firstRelationship['order'] = $order;
                $relationships[0] = $firstRelationship;
                break;
            case 'noncanonical_entity_order':
                $entities = array_reverse($entities);
                break;
            default:
                self::fail('Unknown semantic tamper case.');
        }

        $model['entities'] = $entities;
        $model['aggregates'] = $aggregates;
        $model['relationships'] = $relationships;
        $data['model'] = $model;
    }

    /** @param array<string, mixed> $data */
    private function recomputeAttackerControlledModelIdentity(array $data): string
    {
        $model = $this->object($data['model'] ?? null);
        $modelBytes = CanonicalJson::encode($model);
        $provenance = $this->object($data['provenance'] ?? null);
        $provenance['persistence_model'] = ['bytes' => strlen($modelBytes), 'sha256' => hash('sha256', $modelBytes)];
        $data['provenance'] = $provenance;

        return CanonicalJson::encode($data);
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        self::assertIsArray($value);
        self::assertFalse(array_is_list($value));

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        self::assertIsArray($value);
        self::assertTrue(array_is_list($value));

        return $value;
    }
}
