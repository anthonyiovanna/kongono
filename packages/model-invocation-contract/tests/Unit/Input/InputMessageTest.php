<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Input;

use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Input\InputMessage;
use Kongono\ModelInvocationContract\Input\InputRole;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use PHPUnit\Framework\TestCase;

final class InputMessageTest extends TestCase
{
    public function testRolesVisibilityAndTextBoundariesAreExact(): void
    {
        self::assertSame(['system', 'user', 'assistant', 'tool'], array_column(InputRole::cases(), 'value'));
        $message = new InputMessage(InputRole::User, ModelVisibility::Private, str_repeat('x', 32768));
        self::assertSame(32768, strlen($message->content()));
        self::assertTrue($message->equals(new InputMessage(InputRole::User, ModelVisibility::Private, str_repeat('x', 32768))));
        self::assertSame("line one\nline\ttwo", (new InputMessage(InputRole::System, ModelVisibility::Protected, "line one\nline\ttwo"))->content());
    }

    public function testInvalidUtf8ControlsWhitespaceAndAboveLimitAreNondisclosing(): void
    {
        foreach (['', ' leading', "trailing\n", "nul\0value", "carriage\rreturn", "\xC3\x28", str_repeat('x', 32769)] as $raw) {
            try {
                new InputMessage(InputRole::User, ModelVisibility::Private, $raw);
                self::fail('Expected invalid input.');
            } catch (InvalidModelInvocationContractException $exception) {
                if ('' !== $raw) {
                    self::assertStringNotContainsString($raw, $exception->getMessage());
                    self::assertStringNotContainsString($raw, (string) $exception);
                }
            }
        }
    }
}
