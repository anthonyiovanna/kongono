<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime\Tests\Unit;

use Kongono\Console\CommandCancellation;
use Kongono\Console\CommandCleanup;
use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandHandler;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandInvocation;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandOperation;
use Kongono\Console\CommandOutcome;
use Kongono\Console\CommandResult;
use Kongono\Console\CommandSummary;
use Kongono\ConsoleRuntime\CommandDispatcher;
use Kongono\ConsoleRuntime\CommandHandlerBinding;
use Kongono\ConsoleRuntime\CommandHandlerSet;
use Kongono\ConsoleRuntime\Exception\CommandExecutionException;
use Kongono\ConsoleRuntime\Exception\InvalidCommandRuntimeValueException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SequentialIsolationTest extends TestCase
{
    public function testFailedFirstDispatchDoesNotPoisonSecondAndOperationsDiffer(): void
    {
        $handler = new class implements CommandHandler {
            /** @var list<int> */
            public array $operationIds = [];
            public int $calls = 0;
            public function execute(CommandOperation $operation): CommandResult
            {
                $this->operationIds[] = spl_object_id($operation);
                if (1 === ++$this->calls) {
                    throw new RuntimeException('first-secret');
                }

                return CommandResult::succeeded();
            }
        };
        $dispatcher = $this->dispatcher($handler);

        try {
            $dispatcher->dispatch($this->invocation(), $this->cancellation());
            self::fail('Expected first call failure.');
        } catch (CommandExecutionException $exception) {
            self::assertSame('KGN-CONSOLE-RUNTIME-1004', $exception->diagnosticId());
        }

        $result = $dispatcher->dispatch($this->invocation(), $this->cancellation());
        self::assertSame(CommandOutcome::Succeeded, $result->outcome());
        self::assertCount(2, array_unique($handler->operationIds));
    }

    public function testSameDispatcherReentrancyFailsAndLaterSequentialUseRecovers(): void
    {
        $handler = new class implements CommandHandler {
            public ?CommandDispatcher $dispatcher = null;
            public bool $reenter = true;
            public CommandCancellation $cancellation;
            public function __construct()
            {
                $this->cancellation = new class implements CommandCancellation {
                    public function isRequested(): bool
                    {
                        return false;
                    }
                };
            }
            public function execute(CommandOperation $operation): CommandResult
            {
                if ($this->reenter) {
                    $this->reenter = false;
                    $this->dispatcher?->dispatch($operation->invocation(), $this->cancellation);
                }

                return CommandResult::succeeded();
            }
        };
        $dispatcher = $this->dispatcher($handler);
        $handler->dispatcher = $dispatcher;

        try {
            $dispatcher->dispatch($this->invocation(), $this->cancellation());
            self::fail('Expected reentrant dispatch rejection.');
        } catch (CommandExecutionException $exception) {
            self::assertInstanceOf(InvalidCommandRuntimeValueException::class, $exception->primaryFailure());
        }

        self::assertSame($handler, (new CommandHandlerSet([
            new CommandHandlerBinding(new CommandIdentifier('graph:inspect'), $handler),
        ]))->binding(new CommandIdentifier('graph:inspect'))?->handler());
        self::assertSame(CommandOutcome::Succeeded, $dispatcher->dispatch($this->invocation(), $this->cancellation())->outcome());
    }

    public function testDifferentDispatcherInstancesCanDispatchIndependently(): void
    {
        $innerHandler = new class implements CommandHandler {
            public int $calls = 0;
            public function execute(CommandOperation $operation): CommandResult
            {
                ++$this->calls;

                return CommandResult::succeeded();
            }
        };
        $inner = $this->dispatcher($innerHandler);
        $outerHandler = new class ($inner, $this->cancellation()) implements CommandHandler {
            public function __construct(
                private readonly CommandDispatcher $inner,
                private readonly CommandCancellation $cancellation,
            ) {}
            public function execute(CommandOperation $operation): CommandResult
            {
                return $this->inner->dispatch($operation->invocation(), $this->cancellation);
            }
        };

        self::assertSame(CommandOutcome::Succeeded, $this->dispatcher($outerHandler)->dispatch($this->invocation(), $this->cancellation())->outcome());
        self::assertSame(1, $innerHandler->calls);
    }

    public function testSequentialOutcomeMatrixAndRecoveryRemainIsolated(): void
    {
        $handler = new SequencedOutcomeHandler();
        $dispatcher = $this->dispatcher($handler);
        $observed = [];

        for ($call = 0; $call < 6; ++$call) {
            try {
                $observed[] = $dispatcher->dispatch($this->invocation(), $this->cancellation())->outcome()->value;
            } catch (CommandExecutionException $exception) {
                $observed[] = $exception->diagnosticId();
            }
        }

        self::assertSame([
            'succeeded',
            'rejected',
            'cancelled',
            'KGN-CONSOLE-RUNTIME-1004',
            'KGN-CONSOLE-RUNTIME-1005',
            'succeeded',
        ], $observed);
    }

    public function testFirstCallCleanupAndCancellationFailuresDoNotPoisonSecondCall(): void
    {
        $cleanupDispatcher = $this->dispatcher(new CleanupThenSuccessHandler());
        try {
            $cleanupDispatcher->dispatch($this->invocation(), $this->cancellation());
            self::fail('Expected first cleanup failure.');
        } catch (CommandExecutionException $exception) {
            self::assertSame('KGN-CONSOLE-RUNTIME-1005', $exception->diagnosticId());
        }
        self::assertSame(CommandOutcome::Succeeded, $cleanupDispatcher->dispatch($this->invocation(), $this->cancellation())->outcome());

        $cancellationDispatcher = $this->dispatcher(new AlwaysSuccessfulHandler());
        $cancellation = new ThrowOnceCancellation();
        try {
            $cancellationDispatcher->dispatch($this->invocation(), $cancellation);
            self::fail('Expected first cancellation failure.');
        } catch (CommandExecutionException $exception) {
            self::assertSame('KGN-CONSOLE-RUNTIME-1004', $exception->diagnosticId());
        }
        self::assertSame(CommandOutcome::Succeeded, $cancellationDispatcher->dispatch($this->invocation(), $cancellation)->outcome());
    }

    private function dispatcher(CommandHandler $handler): CommandDispatcher
    {
        return new CommandDispatcher(new CommandHandlerSet([
            new CommandHandlerBinding(new CommandIdentifier('graph:inspect'), $handler),
        ]));
    }

    private function invocation(): CommandInvocation
    {
        return new CommandInvocation(
            new CommandDefinition(new CommandIdentifier('graph:inspect'), new CommandSummary('Inspect graph.'), CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly),
            [],
            CommandExecutionIntent::Observe,
            CommandInteractionMode::Disabled,
        );
    }

    private function cancellation(): CommandCancellation
    {
        return new class implements CommandCancellation {
            public function isRequested(): bool
            {
                return false;
            }
        };
    }
}

final class SequencedOutcomeHandler implements CommandHandler
{
    private int $calls = 0;

    public function execute(CommandOperation $operation): CommandResult
    {
        return match (++$this->calls) {
            1 => CommandResult::succeeded(),
            2 => CommandResult::rejected(new \Kongono\Console\CommandFailure(\Kongono\Console\CommandFailureCategory::Denied, 'KGN-TEST-1001')),
            3 => CommandResult::cancelled(new \Kongono\Console\CommandFailure(\Kongono\Console\CommandFailureCategory::Cancelled, 'KGN-TEST-1002')),
            4 => throw new RuntimeException('primary-secret'),
            5 => $this->cleanupFailure($operation),
            default => CommandResult::succeeded(),
        };
    }

    private function cleanupFailure(CommandOperation $operation): CommandResult
    {
        $operation->defer(new FailingCleanup());

        return CommandResult::succeeded();
    }
}

final class CleanupThenSuccessHandler implements CommandHandler
{
    private int $calls = 0;

    public function execute(CommandOperation $operation): CommandResult
    {
        if (1 === ++$this->calls) {
            $operation->defer(new FailingCleanup());
        }

        return CommandResult::succeeded();
    }
}

final class AlwaysSuccessfulHandler implements CommandHandler
{
    public function execute(CommandOperation $operation): CommandResult
    {
        return CommandResult::succeeded();
    }
}

final class ThrowOnceCancellation implements CommandCancellation
{
    private int $calls = 0;

    public function isRequested(): bool
    {
        if (1 === ++$this->calls) {
            throw new RuntimeException('cancellation-secret');
        }

        return false;
    }
}

final class FailingCleanup implements CommandCleanup
{
    public function release(): void
    {
        throw new RuntimeException('cleanup-secret');
    }
}
