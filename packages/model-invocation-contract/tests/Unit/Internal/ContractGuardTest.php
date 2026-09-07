<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Internal;

use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Input\InputMessage;
use Kongono\ModelInvocationContract\Input\InputRole;
use Kongono\ModelInvocationContract\Result\ActionArguments;
use Kongono\ModelInvocationContract\Result\ResultText;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use PHPUnit\Framework\TestCase;

final class ContractGuardTest extends TestCase
{
    public function testSequentialConstructionIsIsolatedAndRetainsNoMutableState(): void
    {
        $first = new InputMessage(InputRole::User, ModelVisibility::Private, 'first');
        try {
            new ResultText("rejected\rvalue");
            self::fail('Expected rejected result text.');
        } catch (InvalidModelInvocationContractException) {
            self::assertSame('first', $first->content());
        }
        $second = new InputMessage(InputRole::Assistant, ModelVisibility::Application, 'second');
        self::assertSame('second', $second->content());
        self::assertSame('{}', (new ActionArguments('{}'))->value());
        self::assertSame([], (new \ReflectionClass('Kongono\\ModelInvocationContract\\Internal\\ContractGuard'))->getProperties());
    }
}
