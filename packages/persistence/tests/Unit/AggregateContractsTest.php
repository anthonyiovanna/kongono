<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use Kongono\Persistence\AggregateCascadePlan;
use Kongono\Persistence\AggregateLoadPlan;
use Kongono\Persistence\Exception\InvalidAggregatePersistenceException;
use Kongono\Persistence\RelationshipCardinality;
use Kongono\Persistence\RelationshipCascadePolicy;
use Kongono\Persistence\RelationshipLoad;
use PHPUnit\Framework\TestCase;

final class AggregateContractsTest extends TestCase
{
    public function testLoadAndCascadeEvidencePreservesExplicitOrderAndBounds(): void
    {
        $load = new RelationshipLoad('profiles', \stdClass::class, RelationshipCardinality::ToMany, false, [['field' => 'id', 'direction' => 'ascending']], 32);
        $plan = new AggregateLoadPlan('account', 'loadWithProfiles', \stdClass::class, [$load], 33);
        self::assertSame([$load], $plan->relationships());
        self::assertSame(33, $plan->maxTotalObjects());
        self::assertSame([['field' => 'id', 'direction' => 'ascending']], $load->order());

        $policy = new RelationshipCascadePolicy('profiles', \stdClass::class, true, false);
        $cascade = new AggregateCascadePlan('account', \stdClass::class, [$policy], 33);
        self::assertSame([$policy], $cascade->policies());
        self::assertSame(33, $cascade->maxChanges());
    }

    public function testInvalidBoundsAndDuplicateRelationshipsFailClosed(): void
    {
        foreach ([
            static fn(): RelationshipLoad => new RelationshipLoad('profiles', \stdClass::class, RelationshipCardinality::ToMany, false, [], 1),
            static fn(): AggregateLoadPlan => new AggregateLoadPlan('account', 'load', \stdClass::class, [
                new RelationshipLoad('profile', \stdClass::class, RelationshipCardinality::ToOne, true, [], 1),
                new RelationshipLoad('profile', \stdClass::class, RelationshipCardinality::ToOne, true, [], 1),
            ], 3),
            static fn(): AggregateCascadePlan => new AggregateCascadePlan('account', \stdClass::class, [], AggregateCascadePlan::MAX_CHANGES + 1),
        ] as $invalid) {
            try {
                $invalid();
                self::fail('Expected aggregate evidence rejection.');
            } catch (InvalidAggregatePersistenceException $failure) {
                self::assertSame('KGN-PERSISTENCE-1009', $failure->diagnosticId());
            }
        }
    }
}
