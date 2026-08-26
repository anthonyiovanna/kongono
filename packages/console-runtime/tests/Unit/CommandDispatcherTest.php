<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime\Tests\Unit;

use Kongono\Console\CommandCancellation;
use Kongono\Console\CommandCleanup;
use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
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

final class CommandDispatcherTest extends TestCase
{
    public function testMissingBindingReturnsNeutralFailureWithoutCallingCancellationOrHandler(): void
    {
        $handler = $this->handler(CommandResult::succeeded());
        $dispatcher = $this->dispatcher('other:command', $handler);
        $cancellation = $this->cancellation(false);

        $result = $dispatcher->dispatch($this->invocation(), $cancellation);

        self::assertSame(CommandOutcome::Failed, $result->outcome());
        $failure = $result->failure();
        self::assertNotNull($failure);
        self::assertSame(CommandFailureCategory::Capability, $failure->category());
        self::assertSame('KGN-CONSOLE-RUNTIME-1002', $failure->diagnosticIdentifier());
        self::assertSame(0, $cancellation->calls);
        self::assertSame(0, $handler->calls);
    }

    public function testPreExecutionCancellationIsCheckedOnceAndSkipsHandler(): void
    {
        $handler = $this->handler(CommandResult::succeeded());
        $dispatcher = $this->dispatcher('graph:inspect', $handler);
        $cancellation = $this->cancellation(true);

        $result = $dispatcher->dispatch($this->invocation(), $cancellation);

        self::assertSame(CommandOutcome::Cancelled, $result->outcome());
        $failure = $result->failure();
        self::assertNotNull($failure);
        self::assertSame('KGN-CONSOLE-RUNTIME-1003', $failure->diagnosticIdentifier());
        self::assertSame(1, $cancellation->calls);
        self::assertSame(0, $handler->calls);
    }

    public function testEveryValidHandlerResultIsPreservedWithoutLateCancellationRewrite(): void
    {
        $results = [
            CommandResult::succeeded(),
            CommandResult::rejected(new CommandFailure(CommandFailureCategory::Denied, 'KGN-TEST-1001')),
            CommandResult::failed(new CommandFailure(CommandFailureCategory::Infrastructure, 'KGN-TEST-1002')),
            CommandResult::cancelled(new CommandFailure(CommandFailureCategory::Cancelled, 'KGN-TEST-1003')),
        ];

        foreach ($results as $expected) {
            $handler = $this->handler($expected);
            $cancellation = $this->cancellation(false);
            self::assertSame($expected, $this->dispatcher('graph:inspect', $handler)->dispatch($this->invocation(), $cancellation));
            self::assertSame(1, $cancellation->calls);
            self::assertSame(1, $handler->calls);
        }
    }

    public function testCooperativeCancellationUsesTheExplicitOperationCollaborator(): void
    {
        $cancellation = $this->cancellation(false);
        $handler = new class implements CommandHandler {
            public function execute(CommandOperation $operation): CommandResult
            {
                TestCase::assertFalse($operation->cancellation()->isRequested());

                return CommandResult::cancelled(new CommandFailure(CommandFailureCategory::Cancelled, 'KGN-TEST-1003'));
            }
        };

        $result = $this->dispatcher('graph:inspect', $handler)->dispatch($this->invocation(), $cancellation);
        self::assertSame(CommandOutcome::Cancelled, $result->outcome());
        self::assertSame(2, $cancellation->calls);
    }

    public function testHandlerAndThreeCleanupFailuresPreservePrimaryIdentityAndReverseOrder(): void
    {
        /** @var \ArrayObject<int, int> $events */
        $events = new \ArrayObject();
        $primary = new RuntimeException('primary-hostile-secret');
        $cleanupFailures = [
            new RuntimeException('cleanup-one-secret'),
            new RuntimeException('cleanup-two-secret'),
            new RuntimeException('cleanup-three-secret'),
        ];
        $handler = new class ($events, $primary, $cleanupFailures) implements CommandHandler {
            /**
             * @param \ArrayObject<int, int> $events
             * @param list<RuntimeException> $failures
             */
            public function __construct(
                private readonly \ArrayObject $events,
                private readonly RuntimeException $primary,
                private readonly array $failures,
            ) {}

            public function execute(CommandOperation $operation): CommandResult
            {
                foreach ($this->failures as $index => $failure) {
                    $operation->defer(new class ($this->events, $index, $failure) implements CommandCleanup {
                        /** @param \ArrayObject<int, int> $events */
                        public function __construct(
                            private readonly \ArrayObject $events,
                            private readonly int $index,
                            private readonly RuntimeException $failure,
                        ) {}

                        public function release(): void
                        {
                            $this->events->append($this->index);
                            throw $this->failure;
                        }
                    });
                }
                throw $this->primary;
            }
        };

        try {
            $this->dispatcher('graph:inspect', $handler)->dispatch($this->invocation(), $this->cancellation(false));
            self::fail('Expected combined failure.');
        } catch (CommandExecutionException $exception) {
            self::assertSame($primary, $exception->primaryFailure());
            self::assertSame(array_reverse($cleanupFailures), $exception->cleanupFailures());
            self::assertSame([2, 1, 0], $events->getArrayCopy());
            self::assertSame('KGN-CONSOLE-RUNTIME-1006', $exception->diagnosticId());
            self::assertStringNotContainsString('secret', (string) $exception);
        }
    }

    public function testCancellationThrowAndHandlerEarlyRetirementBecomePrimaryFailures(): void
    {
        $cancellationFailure = new RuntimeException('cancellation-secret');
        $throwingCancellation = new class ($cancellationFailure) implements CommandCancellation {
            public function __construct(private readonly RuntimeException $failure) {}
            public function isRequested(): bool
            {
                throw $this->failure;
            }
        };
        try {
            $this->dispatcher('graph:inspect', $this->handler(CommandResult::succeeded()))->dispatch($this->invocation(), $throwingCancellation);
            self::fail('Expected cancellation failure.');
        } catch (CommandExecutionException $exception) {
            self::assertSame($cancellationFailure, $exception->primaryFailure());
        }

        $earlyCleanupFailure = new RuntimeException('early-cleanup-secret');
        $retiringHandler = new class ($earlyCleanupFailure) implements CommandHandler {
            public function __construct(private readonly RuntimeException $cleanupFailure) {}

            public function execute(CommandOperation $operation): CommandResult
            {
                $operation->defer(new class ($this->cleanupFailure) implements CommandCleanup {
                    public function __construct(private readonly RuntimeException $failure) {}

                    public function release(): void
                    {
                        throw $this->failure;
                    }
                });
                $operation->retire();

                return CommandResult::succeeded();
            }
        };
        try {
            $this->dispatcher('graph:inspect', $retiringHandler)->dispatch($this->invocation(), $this->cancellation(false));
            self::fail('Expected early-retirement misuse.');
        } catch (CommandExecutionException $exception) {
            self::assertInstanceOf(InvalidCommandRuntimeValueException::class, $exception->primaryFailure());
            self::assertSame([$earlyCleanupFailure], $exception->cleanupFailures());
        }
    }

    private function dispatcher(string $identifier, CommandHandler $handler): CommandDispatcher
    {
        return new CommandDispatcher(new CommandHandlerSet([
            new CommandHandlerBinding(new CommandIdentifier($identifier), $handler),
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

    private function cancellation(bool $requested): RecordingCancellation
    {
        return new RecordingCancellation($requested);
    }

    private function handler(CommandResult $result): RecordingHandler
    {
        return new RecordingHandler($result);
    }
}

final class RecordingCancellation implements CommandCancellation
{
    public int $calls = 0;

    public function __construct(private readonly bool $requested) {}

    public function isRequested(): bool
    {
        ++$this->calls;

        return $this->requested;
    }
}

final class RecordingHandler implements CommandHandler
{
    public int $calls = 0;

    public function __construct(private readonly CommandResult $result) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        ++$this->calls;

        return $this->result;
    }
}
