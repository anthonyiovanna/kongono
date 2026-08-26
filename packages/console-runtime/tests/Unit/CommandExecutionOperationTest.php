<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime\Tests\Unit;

use Kongono\Console\CommandCancellation;
use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandInvocation;
use Kongono\Console\CommandLifecycleState;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandSummary;
use Kongono\ConsoleRuntime\CommandExecutionOperation;
use Kongono\ConsoleRuntime\Exception\InvalidCommandRuntimeValueException;
use PHPUnit\Framework\TestCase;
use WeakReference;

final class CommandExecutionOperationTest extends TestCase
{
    public function testActiveOperationReturnsExactCollaboratorsAndRetirementDetachesThem(): void
    {
        $invocation = $this->invocation();
        $cancellation = new class implements CommandCancellation {
            public function isRequested(): bool
            {
                return false;
            }
        };
        $invocationReference = WeakReference::create($invocation);
        $cancellationReference = WeakReference::create($cancellation);
        $operation = new CommandExecutionOperation($invocation, $cancellation);

        self::assertSame(CommandLifecycleState::Active, $operation->state());
        self::assertSame($invocation, $operation->invocation());
        self::assertSame($cancellation, $operation->cancellation());

        unset($invocation, $cancellation);
        $operation->retire();
        gc_collect_cycles();

        self::assertSame(CommandLifecycleState::Retired, $operation->state());
        self::assertNull($invocationReference->get());
        self::assertNull($cancellationReference->get());
        $operation->retire();

        foreach ([
            static fn() => $operation->invocation(),
            static fn() => $operation->cancellation(),
        ] as $accessor) {
            try {
                $accessor();
                self::fail('Expected retired operation access to fail.');
            } catch (InvalidCommandRuntimeValueException $exception) {
                self::assertSame('KGN-CONSOLE-RUNTIME-1001', $exception->diagnosticId());
            }
        }
    }

    private function invocation(): CommandInvocation
    {
        return new CommandInvocation(
            new CommandDefinition(
                new CommandIdentifier('graph:inspect'),
                new CommandSummary('Inspect graph.'),
                CommandInteractionPolicy::None,
                CommandMutationPolicy::ReadOnly,
            ),
            [],
            CommandExecutionIntent::Observe,
            CommandInteractionMode::Disabled,
        );
    }
}
