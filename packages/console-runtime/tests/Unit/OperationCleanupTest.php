<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime\Tests\Unit;

use Kongono\Console\CommandCancellation;
use Kongono\Console\CommandCleanup;
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
use Kongono\ConsoleRuntime\Exception\CommandExecutionException;
use Kongono\ConsoleRuntime\Exception\InvalidCommandRuntimeValueException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OperationCleanupTest extends TestCase
{
    public function testCleanupRunsOnceInReverseOrderAfterRetirementAndContinuesAfterFailures(): void
    {
        $events = new \ArrayObject();
        $operation = $this->operation();
        $firstFailure = new RuntimeException('hostile-first-secret');
        $thirdFailure = new RuntimeException('hostile-third-secret');

        $operation->defer($this->cleanup('first', $events, $operation, $firstFailure));
        $operation->defer($this->cleanup('second', $events, $operation));
        $operation->defer($this->cleanup('third', $events, $operation, $thirdFailure));

        try {
            $operation->retire();
            self::fail('Expected cleanup failure.');
        } catch (CommandExecutionException $exception) {
            self::assertNull($exception->primaryFailure());
            self::assertSame([$thirdFailure, $firstFailure], $exception->cleanupFailures());
            self::assertSame(['third:retired', 'second:retired', 'first:retired'], $events->getArrayCopy());
            self::assertStringNotContainsString('secret', (string) $exception);
        }

        $operation->retire();
        self::assertSame(['third:retired', 'second:retired', 'first:retired'], $events->getArrayCopy());
    }

    public function testCapacityIdentityAndLifecycleMisuseAreRejected(): void
    {
        $operation = $this->operation();
        $cleanup = $this->cleanup('same', new \ArrayObject(), $operation);
        $operation->defer($cleanup);
        try {
            $operation->defer($cleanup);
            self::fail('Expected duplicate cleanup rejection.');
        } catch (InvalidCommandRuntimeValueException) {
            self::assertSame(CommandLifecycleState::Active, $operation->state());
        }

        $operation = $this->operation();
        for ($index = 0; $index < 64; ++$index) {
            $operation->defer($this->cleanup((string) $index, new \ArrayObject(), $operation));
        }
        try {
            $operation->defer($this->cleanup('overflow', new \ArrayObject(), $operation));
            self::fail('Expected cleanup overflow rejection.');
        } catch (InvalidCommandRuntimeValueException) {
            self::assertSame(CommandLifecycleState::Active, $operation->state());
        }
        $operation->retire();
        $this->expectException(InvalidCommandRuntimeValueException::class);
        $operation->defer($this->cleanup('late', new \ArrayObject(), $operation));
    }

    /** @param \ArrayObject<int, string> $events */
    private function cleanup(
        string $name,
        \ArrayObject $events,
        CommandExecutionOperation $operation,
        ?RuntimeException $failure = null,
    ): CommandCleanup {
        return new class ($name, $events, $operation, $failure) implements CommandCleanup {
            /** @param \ArrayObject<int, string> $events */
            public function __construct(
                private readonly string $name,
                private readonly \ArrayObject $events,
                private readonly CommandExecutionOperation $operation,
                private readonly ?RuntimeException $failure,
            ) {}

            public function release(): void
            {
                $this->events->append($this->name . ':' . $this->operation->state()->value);
                if (null !== $this->failure) {
                    throw $this->failure;
                }
            }
        };
    }

    private function operation(): CommandExecutionOperation
    {
        $invocation = new CommandInvocation(
            new CommandDefinition(new CommandIdentifier('graph:inspect'), new CommandSummary('Inspect graph.'), CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly),
            [],
            CommandExecutionIntent::Observe,
            CommandInteractionMode::Disabled,
        );
        $cancellation = new class implements CommandCancellation {
            public function isRequested(): bool
            {
                return false;
            }
        };

        return new CommandExecutionOperation($invocation, $cancellation);
    }
}
