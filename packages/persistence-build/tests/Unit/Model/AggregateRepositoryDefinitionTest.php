<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Model;

use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Model\AggregateRepositoryDefinition;
use Kongono\PersistenceBuild\Model\LoadPlanDefinition;
use Kongono\PersistenceBuild\Model\RelationshipCardinality;
use Kongono\PersistenceBuild\Model\RelationshipCascadeDefinition;
use Kongono\PersistenceBuild\Model\RelationshipLoadDefinition;
use PHPUnit\Framework\TestCase;

final class AggregateRepositoryDefinitionTest extends TestCase
{
    public function testDetachedDefinitionsPreserveDeclaredOrder(): void
    {
        $load = new RelationshipLoadDefinition('profiles', \stdClass::class, RelationshipCardinality::ToMany, false, [['field' => 'id', 'direction' => 'ascending']], 8);
        $plan = new LoadPlanDefinition('loadWithProfiles', [$load], 9);
        $cascade = new RelationshipCascadeDefinition('profiles', \stdClass::class, true, false, 8);
        $repository = new AggregateRepositoryDefinition('account', 'AccountRepository', \stdClass::class, [$plan], [$cascade], 9);
        self::assertSame([$plan], $repository->loadPlans());
        self::assertSame([$cascade], $repository->cascades());
        self::assertSame(9, $repository->maxChanges());
    }

    public function testDuplicateMethodsFailClosed(): void
    {
        $plan = new LoadPlanDefinition('loadOne', [], 1);
        $this->expectException(InvalidPersistenceSourceException::class);
        new AggregateRepositoryDefinition('account', 'AccountRepository', \stdClass::class, [$plan, $plan], [], 1);
    }
}
