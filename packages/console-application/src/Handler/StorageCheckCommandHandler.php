<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Handler;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandHandler;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandOperation;
use Kongono\Console\CommandResult;
use Kongono\ConsoleApplication\Diagnostic\ConsoleApplicationDiagnostic;
use Kongono\ConsoleApplication\KongoCommand;
use Kongono\StorageApplication\Exception\ExceptionInterface as StorageApplicationException;
use Kongono\StorageApplication\Summary\StorageApplicationSummary;
use Kongono\StorageApplication\Workflow\StorageApplicationWorkflow;
use Throwable;

/** Checks one exact-current storage-profile artifact without mutation. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageCheckCommandHandler implements CommandHandler
{
    public function __construct(private StorageApplicationWorkflow $workflow) {}
    public function execute(CommandOperation $operation): CommandResult
    {
        $invocation = $operation->invocation();
        if (KongoCommand::StorageCheck->value !== $invocation->definition()->identifier()->value() || CommandMutationPolicy::ReadOnly !== $invocation->definition()->mutationPolicy() || CommandInteractionMode::Disabled !== $invocation->interactionMode() || CommandExecutionIntent::Observe !== $invocation->executionIntent() || 3 !== count($invocation->inputs())) {
            return $this->invalid();
        }
        $definition = StorageBuildCommandHandler::definition($operation);
        if (null === $definition) {
            return $this->invalid();
        }
        try {
            return CommandResult::succeeded(StorageBuildCommandHandler::record(StorageApplicationSummary::fromCurrent($this->workflow->check($definition))));
        } catch (StorageApplicationException) {
            return $this->precondition();
        } catch (Throwable) {
            return $this->infrastructure();
        }
    }
    private function invalid(): CommandResult
    {
        return CommandResult::rejected(new CommandFailure(CommandFailureCategory::InvalidInput, ConsoleApplicationDiagnostic::InvalidProcessRequest->value));
    }
    private function precondition(): CommandResult
    {
        return CommandResult::rejected(new CommandFailure(CommandFailureCategory::Precondition, ConsoleApplicationDiagnostic::BuildPrecondition->value));
    }
    private function infrastructure(): CommandResult
    {
        return CommandResult::failed(new CommandFailure(CommandFailureCategory::Infrastructure, ConsoleApplicationDiagnostic::ApplicationFailure->value));
    }
}
