<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Unit\Plan;

use Kongono\ModelInvocationBuild\Tests\Support\InvocationPlanSourceFactory;
use PHPUnit\Framework\TestCase;

final class InvocationPlanSourceTest extends TestCase
{
    public function testOneCompleteTypedSourceExposesExactValuesAndEquality(): void
    {
        $source = InvocationPlanSourceFactory::create();
        self::assertSame('plan.primary', $source->identity()->value());
        self::assertSame('adapter.primary', $source->adapterIdentity()->value());
        self::assertSame('invocation.primary', $source->request()->identity()->value());
        self::assertSame(30_000, $source->budget()->maxDurationMilliseconds());
        self::assertTrue($source->equals(InvocationPlanSourceFactory::create()));
        self::assertFalse($source->equals(InvocationPlanSourceFactory::create(planId: 'plan.other')));
    }
}
