<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use Kongono\Persistence\ChangeKind;
use Kongono\Persistence\ChangeSet;
use Kongono\Persistence\EntityIdentity;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;
use Kongono\Persistence\PersistenceChange;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ChangeSetTest extends TestCase
{
    public function testChangesRemainDetachedOrderedAndCounted(): void
    {
        $first = $this->insert(new \stdClass(), 'first');
        $second = $this->insert(new \stdClass(), 'second');
        $sparse = [3 => $first, 8 => $second];
        $set = (new ReflectionClass(ChangeSet::class))->newInstanceArgs([$sparse]);
        self::assertInstanceOf(ChangeSet::class, $set);
        $sparse[3] = $second;

        self::assertSame([$first, $second], $set->changes());
        self::assertSame(2, $set->count());
        self::assertFalse($set->isEmpty());
        self::assertTrue((new ChangeSet([]))->isEmpty());
    }

    public function testDuplicateObjectInstanceIsRejected(): void
    {
        $entity = new \stdClass();

        $this->expectException(InvalidPersistenceValueException::class);
        new ChangeSet([
            $this->insert($entity, 'first'),
            $this->insert($entity, 'second'),
        ]);
    }

    public function testDuplicateExactIdentityIsRejected(): void
    {
        $this->expectException(InvalidPersistenceValueException::class);
        new ChangeSet([
            $this->insert(new \stdClass(), 'same'),
            $this->insert(new \stdClass(), 'same'),
        ]);
    }

    public function testInvalidMemberIsRejected(): void
    {
        $invalid = [new \stdClass()];

        $this->expectException(InvalidPersistenceValueException::class);
        (new ReflectionClass(ChangeSet::class))->newInstanceArgs([$invalid]);
    }

    public function testExactMaximumIsAcceptedAndOverflowIsRejected(): void
    {
        $changes = [];
        for ($index = 0; $index < 1024; ++$index) {
            $changes[] = $this->insert(new \stdClass(), sprintf('record-%d', $index));
        }

        self::assertSame(1024, (new ChangeSet($changes))->count());
        $changes[] = $this->insert(new \stdClass(), 'overflow');

        $this->expectException(InvalidPersistenceValueException::class);
        new ChangeSet($changes);
    }

    private function insert(object $entity, string $key): PersistenceChange
    {
        return new PersistenceChange(
            $entity,
            ChangeKind::Insert,
            new EntityIdentity($entity::class, $key),
            [],
            null,
        );
    }
}
