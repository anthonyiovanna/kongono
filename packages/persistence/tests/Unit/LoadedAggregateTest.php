<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use Kongono\Persistence\Exception\InvalidAggregatePersistenceException;
use Kongono\Persistence\LoadedAggregate;
use PHPUnit\Framework\TestCase;
use WeakReference;

final class LoadedAggregateTest extends TestCase
{
    public function testExplicitEvidencePreservesRelationshipOrderAndReleasesWithCaller(): void
    {
        $root = new \stdClass();
        $first = new \stdClass();
        $second = new \stdClass();
        $weakRoot = WeakReference::create($root);
        $weakFirst = WeakReference::create($first);
        $aggregate = new LoadedAggregate($root, ['profiles' => [$first, $second], 'owner' => null]);
        self::assertSame(['profiles', 'owner'], array_keys($aggregate->relationships()));
        self::assertSame([$first, $second], $aggregate->relationship('profiles'));
        unset($aggregate, $root, $first, $second);
        gc_collect_cycles();
        self::assertNull($weakRoot->get());
        self::assertNull($weakFirst->get());
    }

    public function testInvalidNeutralShapeFailsWithoutDisclosingValues(): void
    {
        try {
            new LoadedAggregate(new \stdClass(), ['secret' => ['not-an-object']]);
            self::fail('Expected neutral shape rejection.');
        } catch (InvalidAggregatePersistenceException $failure) {
            self::assertSame('KGN-PERSISTENCE-1009: Invalid aggregate persistence evidence', (string) $failure);
            self::assertStringNotContainsString('secret', (string) $failure);
        }
    }
}
