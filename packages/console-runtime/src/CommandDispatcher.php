<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandCancellation;
use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandInvocation;
use Kongono\Console\CommandLifecycleState;
use Kongono\Console\CommandResult;
use Kongono\ConsoleRuntime\Diagnostic\ConsoleRuntimeDiagnostic;
use Kongono\ConsoleRuntime\Exception\CommandExecutionException;
use Kongono\ConsoleRuntime\Exception\InvalidCommandRuntimeValueException;
use Throwable;

/** Dispatches one invocation through one fresh operation without presentation or host I/O. */
#[Api(ApiStatus::Experimental)]
final class CommandDispatcher
{
    private bool $dispatchActive = false;

    public function __construct(private readonly CommandHandlerSet $handlers) {}

    public function dispatch(CommandInvocation $invocation, CommandCancellation $cancellation): CommandResult
    {
        if ($this->dispatchActive) {
            throw InvalidCommandRuntimeValueException::reentrantDispatch();
        }

        $this->dispatchActive = true;
        try {
            $binding = $this->handlers->binding($invocation->definition()->identifier());
            if (null === $binding) {
                return CommandResult::failed(new CommandFailure(
                    CommandFailureCategory::Capability,
                    ConsoleRuntimeDiagnostic::HandlerUnavailable->value,
                ));
            }

            $operation = new CommandExecutionOperation($invocation, $cancellation);
            $result = null;
            $primaryFailure = null;
            $cleanupFailures = [];

            try {
                if ($cancellation->isRequested()) {
                    $result = CommandResult::cancelled(new CommandFailure(
                        CommandFailureCategory::Cancelled,
                        ConsoleRuntimeDiagnostic::CancelledBeforeExecution->value,
                    ));
                } else {
                    $result = $binding->handler()->execute($operation);
                }
            } catch (Throwable $failure) {
                $primaryFailure = $failure;
            }

            if (CommandLifecycleState::Retired === $operation->state()) {
                if ($primaryFailure instanceof CommandExecutionException && null === $primaryFailure->primaryFailure()) {
                    $cleanupFailures = $primaryFailure->cleanupFailures();
                }
                $primaryFailure = InvalidCommandRuntimeValueException::operationLifecycle();
            }

            try {
                $operation->retire();
            } catch (CommandExecutionException $cleanupFailure) {
                array_push($cleanupFailures, ...$cleanupFailure->cleanupFailures());
            }

            if (null !== $primaryFailure || [] !== $cleanupFailures) {
                throw CommandExecutionException::forFailures($primaryFailure, $cleanupFailures);
            }

            return $result;
        } finally {
            $this->dispatchActive = false;
        }
    }
}
