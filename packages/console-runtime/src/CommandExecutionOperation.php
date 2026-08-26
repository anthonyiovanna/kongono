<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandCancellation;
use Kongono\Console\CommandCleanup;
use Kongono\Console\CommandInvocation;
use Kongono\Console\CommandLifecycleState;
use Kongono\Console\CommandOperation;
use Kongono\ConsoleRuntime\Exception\CommandExecutionException;
use Kongono\ConsoleRuntime\Exception\InvalidCommandRuntimeValueException;
use Throwable;

/** Owns the bounded lifecycle and deferred cleanup for one command dispatch. */
#[Api(ApiStatus::Experimental)]
final class CommandExecutionOperation implements CommandOperation
{
    private CommandLifecycleState $state = CommandLifecycleState::Active;

    private ?CommandInvocation $invocation;

    private ?CommandCancellation $cancellation;

    /** @var list<CommandCleanup> */
    private array $cleanups = [];

    /** @var array<int, true> */
    private array $cleanupIdentities = [];

    public function __construct(CommandInvocation $invocation, CommandCancellation $cancellation)
    {
        $this->invocation = $invocation;
        $this->cancellation = $cancellation;
    }

    public function invocation(): CommandInvocation
    {
        return $this->invocation ?? throw InvalidCommandRuntimeValueException::operationLifecycle();
    }

    public function cancellation(): CommandCancellation
    {
        return $this->cancellation ?? throw InvalidCommandRuntimeValueException::operationLifecycle();
    }

    public function state(): CommandLifecycleState
    {
        return $this->state;
    }

    public function defer(CommandCleanup $cleanup): void
    {
        if (CommandLifecycleState::Active !== $this->state || count($this->cleanups) >= 64) {
            throw InvalidCommandRuntimeValueException::operationLifecycle();
        }

        $identity = spl_object_id($cleanup);
        if (array_key_exists($identity, $this->cleanupIdentities)) {
            throw InvalidCommandRuntimeValueException::operationLifecycle();
        }

        $this->cleanups[] = $cleanup;
        $this->cleanupIdentities[$identity] = true;
    }

    public function retire(): void
    {
        if (CommandLifecycleState::Retired === $this->state) {
            return;
        }

        $this->state = CommandLifecycleState::Retired;
        $cleanups = $this->cleanups;
        $this->invocation = null;
        $this->cancellation = null;
        $this->cleanups = [];
        $this->cleanupIdentities = [];

        $failures = [];
        for ($index = count($cleanups) - 1; $index >= 0; --$index) {
            try {
                $cleanups[$index]->release();
            } catch (Throwable $failure) {
                $failures[] = $failure;
            }
        }
        $cleanups = [];

        if ([] !== $failures) {
            throw CommandExecutionException::forFailures(null, $failures);
        }
    }
}
