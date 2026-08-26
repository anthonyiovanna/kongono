<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandInvocation;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandParseResult;
use Kongono\Console\CommandSummary;
use PHPUnit\Framework\TestCase;

final class CommandParseResultTest extends TestCase
{
    public function testAcceptedAndRejectedBranchesAreExclusive(): void
    {
        $definition = new CommandDefinition(new CommandIdentifier('status'), new CommandSummary('Show status.'), CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly);
        $invocation = new CommandInvocation($definition, [], CommandExecutionIntent::Observe, CommandInteractionMode::Disabled);
        $accepted = CommandParseResult::accepted($invocation);
        self::assertTrue($accepted->isAccepted());
        self::assertSame($invocation, $accepted->invocation());
        self::assertNull($accepted->failure());

        $failure = new CommandFailure(CommandFailureCategory::InvalidInput, 'KGN-CONSOLE-1006');
        $rejected = CommandParseResult::rejected($failure);
        self::assertFalse($rejected->isAccepted());
        self::assertNull($rejected->invocation());
        self::assertSame($failure, $rejected->failure());
    }
}
