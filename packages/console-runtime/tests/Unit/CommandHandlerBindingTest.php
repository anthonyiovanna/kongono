<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime\Tests\Unit;

use Kongono\Console\CommandHandler;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandOperation;
use Kongono\Console\CommandResult;
use Kongono\ConsoleRuntime\CommandHandlerBinding;
use PHPUnit\Framework\TestCase;

final class CommandHandlerBindingTest extends TestCase
{
    public function testAccessorsPreserveExactExplicitIdentities(): void
    {
        $identifier = new CommandIdentifier('graph:inspect');
        $handler = new class implements CommandHandler {
            public function execute(CommandOperation $operation): CommandResult
            {
                return CommandResult::succeeded();
            }
        };

        $binding = new CommandHandlerBinding($identifier, $handler);

        self::assertSame($identifier, $binding->identifier());
        self::assertSame($handler, $binding->handler());
    }
}
