<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Unit\Plan;

use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanSourceException;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use PHPUnit\Framework\TestCase;

final class AdapterIdentityTest extends TestCase
{
    public function testExactGrammarBoundsAndEquality(): void
    {
        $identity = new AdapterIdentity('adapter.local-v1');
        self::assertSame('adapter.local-v1', $identity->value());
        self::assertTrue($identity->equals(new AdapterIdentity('adapter.local-v1')));
        foreach (['', 'Adapter', 'adapter/', 'a' . str_repeat('0', 128)] as $invalid) {
            try {
                new AdapterIdentity($invalid);
                self::fail('Expected adapter identity rejection.');
            } catch (InvalidInvocationPlanSourceException $failure) {
                self::assertSame('KGN-MODEL-BUILD-1411', $failure->diagnosticId());
            }
        }
    }
}
