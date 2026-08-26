<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Handler;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Build\CompositionArtifact;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandHandler;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandOperation;
use Kongono\Console\CommandResult;
use Kongono\Console\CommandResultField;
use Kongono\Console\CommandResultFieldDisclosure;
use Kongono\Console\CommandResultFieldLabel;
use Kongono\Console\CommandResultFieldName;
use Kongono\Console\CommandResultRecord;
use Kongono\Console\CommandResultValue;
use Kongono\ConsoleCommands\Diagnostic\ConsoleCommandsDiagnostic;
use Kongono\ConsoleCommands\ReadOnlyFrameworkCommand;

/** Projects only exact identity hashes from one injected composition artifact. */
#[Api(ApiStatus::Experimental)]
final readonly class CompositionInspectCommandHandler implements CommandHandler
{
    public function __construct(private CompositionArtifact $artifact) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        if (!$this->accepts($operation)) {
            return CommandResult::rejected(new CommandFailure(
                CommandFailureCategory::InvalidInput,
                ConsoleCommandsDiagnostic::InvalidInvocation->value,
            ));
        }

        return CommandResult::succeeded(new CommandResultRecord([
            $this->textField('artifact_sha256', 'Artifact SHA-256', $this->artifact->artifactSha256()),
            $this->textField('builder_sha256', 'Builder SHA-256', $this->artifact->builderImplementationSha256()),
            $this->textField('model_sha256', 'Model SHA-256', $this->artifact->modelSha256()),
            $this->textField('source_set_sha256', 'Source set SHA-256', $this->artifact->sourceSetSha256()),
            $this->textField('source_sha256', 'Source SHA-256', $this->artifact->sourceSha256()),
        ]));
    }

    private function accepts(CommandOperation $operation): bool
    {
        $invocation = $operation->invocation();

        return ReadOnlyFrameworkCommand::Composition->value === $invocation->definition()->identifier()->value()
            && CommandExecutionIntent::Observe === $invocation->executionIntent()
            && CommandInteractionMode::Disabled === $invocation->interactionMode()
            && CommandMutationPolicy::ReadOnly === $invocation->definition()->mutationPolicy()
            && [] === $invocation->inputs();
    }

    private function textField(string $name, string $label, string $value): CommandResultField
    {
        return new CommandResultField(
            new CommandResultFieldName($name),
            new CommandResultFieldLabel($label),
            CommandResultValue::text($value),
            CommandResultFieldDisclosure::Safe,
        );
    }
}
