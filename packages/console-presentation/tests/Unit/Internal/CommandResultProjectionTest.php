<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Tests\Unit\Internal;

use Kongono\Console\CommandResult;
use Kongono\Console\CommandResultField;
use Kongono\Console\CommandResultFieldDisclosure;
use Kongono\Console\CommandResultFieldLabel;
use Kongono\Console\CommandResultFieldName;
use Kongono\Console\CommandResultRecord;
use Kongono\Console\CommandResultValue;
use Kongono\ConsolePresentation\Internal\CommandResultProjection;
use PHPUnit\Framework\TestCase;

final class CommandResultProjectionTest extends TestCase
{
    public function testProjectionReturnsOnlySafeFieldsAndProtectedCount(): void
    {
        $safe = $this->field('safe', CommandResultFieldDisclosure::Safe);
        $projection = CommandResultProjection::fromResult(CommandResult::succeeded(new CommandResultRecord([
            $this->field('protected', CommandResultFieldDisclosure::Protected),
            $safe,
        ])));
        self::assertSame([$safe], $projection->safeFields());
        self::assertSame(1, $projection->protectedFieldCount());
        self::assertSame([], CommandResultProjection::fromResult(CommandResult::succeeded())->safeFields());
    }

    private function field(string $name, CommandResultFieldDisclosure $disclosure): CommandResultField
    {
        return new CommandResultField(new CommandResultFieldName($name), new CommandResultFieldLabel(ucfirst($name)), CommandResultValue::text($name), $disclosure);
    }
}
