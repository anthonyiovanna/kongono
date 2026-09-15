<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Handler;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetApplication\Definition\AssetApplicationDefinition;
use Kongono\AssetApplication\Exception\ExceptionInterface as AssetApplicationException;
use Kongono\AssetApplication\Summary\AssetApplicationSummary;
use Kongono\AssetApplication\Workflow\AssetApplicationWorkflow;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandHandler;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandOperation;
use Kongono\Console\CommandResult;
use Kongono\ConsoleApplication\Diagnostic\ConsoleApplicationDiagnostic;
use Kongono\ConsoleApplication\KongoCommand;
use Throwable;

/** Checks one exact-current native asset artifact without mutation. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetCheckCommandHandler implements CommandHandler
{
    public function __construct(private AssetApplicationWorkflow $workflow) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        $invocation = $operation->invocation();
        if (KongoCommand::AssetCheck->value !== $invocation->definition()->identifier()->value()
            || CommandMutationPolicy::ReadOnly !== $invocation->definition()->mutationPolicy()
            || CommandInteractionMode::Disabled !== $invocation->interactionMode()
            || CommandExecutionIntent::Observe !== $invocation->executionIntent()
            || 3 !== count($invocation->inputs())) {
            return $this->invalid();
        }
        $root = $invocation->input(new CommandInputName('application-root'));
        $source = $invocation->input(new CommandInputName('source-file'));
        $output = $invocation->input(new CommandInputName('output-directory'));
        if (null === $root || null === $source || null === $output) {
            return $this->invalid();
        }
        try {
            $definition = new AssetApplicationDefinition($root->value()->reveal(), $source->value()->reveal(), $output->value()->reveal());
            $summary = AssetApplicationSummary::fromCurrent($this->workflow->check($definition));
        } catch (AssetApplicationException) {
            return $this->precondition();
        } catch (Throwable) {
            return $this->infrastructure();
        }

        return CommandResult::succeeded(AssetBuildCommandHandler::record($summary));
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
