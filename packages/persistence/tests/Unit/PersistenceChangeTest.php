<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use Kongono\Persistence\ChangeKind;
use Kongono\Persistence\ConcurrencyToken;
use Kongono\Persistence\EntityIdentity;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;
use Kongono\Persistence\PersistenceChange;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class PersistenceChangeTest extends TestCase
{
    public function testAllValidKindsRetainOnlyExplicitDetachedData(): void
    {
        $insertEntity = new \stdClass();
        $updateEntity = new \stdClass();
        $deleteEntity = new \stdClass();
        $identity = new EntityIdentity(\stdClass::class, 'record-1');
        $token = new ConcurrencyToken('version', '8');
        $sparse = [2 => 'profile.name', 7 => 'updated_at'];

        $insert = new PersistenceChange($insertEntity, ChangeKind::Insert, null, [], null);
        $update = (new ReflectionClass(PersistenceChange::class))->newInstanceArgs([$updateEntity, ChangeKind::Update, $identity, $sparse, $token]);
        self::assertInstanceOf(PersistenceChange::class, $update);
        $delete = new PersistenceChange($deleteEntity, ChangeKind::Delete, new EntityIdentity(\stdClass::class, 'record-2'), [], null);
        $sparse[2] = 'changed_after_construction';

        self::assertSame($insertEntity, $insert->entity());
        self::assertSame(ChangeKind::Insert, $insert->kind());
        self::assertNull($insert->identity());
        self::assertSame(['profile.name', 'updated_at'], $update->changedFields());
        self::assertSame($identity, $update->identity());
        self::assertSame($token, $update->expectedConcurrencyToken());
        self::assertSame(ChangeKind::Delete, $delete->kind());

        $reflection = new ReflectionClass(PersistenceChange::class);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        $properties = array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $reflection->getProperties());
        sort($properties);
        self::assertSame(['changedFields', 'entity', 'expectedConcurrencyToken', 'identity', 'kind'], $properties);
    }

    /** @param list<string> $fields */
    #[DataProvider('invalidShapes')]
    public function testInvalidKindShapesAreRejected(ChangeKind $kind, ?EntityIdentity $identity, array $fields, ?ConcurrencyToken $token): void
    {
        $this->expectException(InvalidPersistenceValueException::class);
        new PersistenceChange(new \stdClass(), $kind, $identity, $fields, $token);
    }

    /** @return iterable<string, array{ChangeKind, ?EntityIdentity, list<string>, ?ConcurrencyToken}> */
    public static function invalidShapes(): iterable
    {
        $identity = new EntityIdentity(\stdClass::class, 'record');
        $token = new ConcurrencyToken('version', '1');
        yield 'insert token' => [ChangeKind::Insert, null, [], $token];
        yield 'update missing identity' => [ChangeKind::Update, null, ['name'], null];
        yield 'update missing fields' => [ChangeKind::Update, $identity, [], null];
        yield 'delete missing identity' => [ChangeKind::Delete, null, [], null];
        yield 'delete fields' => [ChangeKind::Delete, $identity, ['name'], null];
    }

    /** @param array<array-key, mixed> $fields */
    #[DataProvider('invalidFieldLists')]
    public function testInvalidFieldsAreRejected(array $fields): void
    {
        $this->expectException(InvalidPersistenceValueException::class);
        (new ReflectionClass(PersistenceChange::class))->newInstanceArgs([
            new \stdClass(),
            ChangeKind::Update,
            new EntityIdentity(\stdClass::class, 'record'),
            $fields,
            null,
        ]);
    }

    /** @return iterable<string, array{array<array-key, mixed>}> */
    public static function invalidFieldLists(): iterable
    {
        yield 'non-string' => [[1]];
        yield 'empty' => [['']];
        yield 'invalid first' => [['1field']];
        yield 'space' => [['field name']];
        yield 'invalid utf8' => [["field\xC3\x28"]];
        yield 'over bound' => [[str_repeat('f', 129)]];
        yield 'duplicate' => [['name', 'name']];
        yield 'over count' => [array_fill(0, 257, 'name')];
    }

    public function testIdentityMustMatchTheExactEntityType(): void
    {
        $this->expectException(InvalidPersistenceValueException::class);
        new PersistenceChange(
            new \stdClass(),
            ChangeKind::Update,
            new EntityIdentity(\ArrayObject::class, 'record'),
            ['name'],
            null,
        );
    }
}
