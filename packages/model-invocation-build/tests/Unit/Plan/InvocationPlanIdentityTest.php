<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Unit\Plan;

use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanSourceException;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use PHPUnit\Framework\TestCase;

final class InvocationPlanIdentityTest extends TestCase
{
    public function testExactGrammarBoundsAndEquality(): void
    {
        foreach (['a', 'plan.primary-v1', 'a' . str_repeat('0', 127)] as $valid) {
            self::assertSame($valid, (new InvocationPlanIdentity($valid))->value());
        }
        self::assertTrue((new InvocationPlanIdentity('plan.one'))->equals(new InvocationPlanIdentity('plan.one')));
        foreach (['', 'A', 'a..b', 'a-', 'a' . str_repeat('0', 128)] as $invalid) {
            try {
                new InvocationPlanIdentity($invalid);
                self::fail('Expected identity rejection.');
            } catch (InvalidInvocationPlanSourceException $failure) {
                self::assertSame('KGN-MODEL-BUILD-1410', $failure->diagnosticId());
            }
        }
    }
}
