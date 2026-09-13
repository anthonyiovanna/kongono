<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Handler;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandHandler;
use Kongono\Console\CommandInputName;
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
use Kongono\ConsoleApplication\Diagnostic\ConsoleApplicationDiagnostic;
use Kongono\ConsoleApplication\KongoCommand;
use Kongono\ViewApplication\Definition\ViewApplicationDefinition;
use Kongono\ViewApplication\Exception\ExceptionInterface as ViewApplicationException;
use Kongono\ViewApplication\Summary\ViewApplicationSummary;
use Kongono\ViewApplication\Workflow\ViewApplicationBuildWorkflow;
use Throwable;

/** Checks one exact-current compiled-view application artifact without mutation. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewCheckCommandHandler implements CommandHandler
{
    public function __construct(private ViewApplicationBuildWorkflow $workflow) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        $invocation = $operation->invocation();
        if (KongoCommand::ViewCheck->value !== $invocation->definition()->identifier()->value()
            || CommandMutationPolicy::ReadOnly !== $invocation->definition()->mutationPolicy()
            || CommandInteractionMode::Disabled !== $invocation->interactionMode()
            || CommandExecutionIntent::Observe !== $invocation->executionIntent()
            || 3 !== count($invocation->inputs())) {
            return $this->invalid();
        }
        $definition = $this->definition($operation);
        if (null === $definition) {
            return $this->invalid();
        }
        try {
            $summary = ViewApplicationSummary::fromCurrent($this->workflow->check($definition));
        } catch (ViewApplicationException) {
            return $this->precondition();
        } catch (Throwable) {
            return $this->infrastructure();
        }

        return CommandResult::succeeded($this->record($summary));
    }

    private function definition(CommandOperation $operation): ?ViewApplicationDefinition
    {
        $invocation = $operation->invocation();
        $root = $invocation->input(new CommandInputName('application-root'));
        $source = $invocation->input(new CommandInputName('source-file'));
        $output = $invocation->input(new CommandInputName('output-directory'));
        if (null === $root || null === $source || null === $output) {
            return null;
        }
        try {
            return new ViewApplicationDefinition($root->value()->reveal(), $source->value()->reveal(), $output->value()->reveal());
        } catch (ViewApplicationException) {
            return null;
        }
    }

    private function record(ViewApplicationSummary $summary): CommandResultRecord
    {
        return new CommandResultRecord([
            $this->text('artifact_basename', 'Artifact basename', $summary->artifactBasename()),
            $this->text('compiled_sha256', 'Compiled SHA-256', $summary->compiledSha256()),
            $this->boolean('current', 'Current', $summary->current()),
            $this->text('generated_sha256', 'Generated SHA-256', $summary->generatedSha256()),
            $this->text('model_sha256', 'Model SHA-256', $summary->modelSha256()),
            $this->boolean('publication_required', 'Publication required', $summary->publicationRequired()),
            $this->text('schema', 'Schema', $summary->schema()),
            $this->text('source_sha256', 'Source SHA-256', $summary->sourceSha256()),
            $this->integer('version', 'Version', $summary->version()),
            $this->integer('view_count', 'View count', $summary->viewCount()),
        ]);
    }

    private function text(string $name, string $label, string $value): CommandResultField
    {
        return $this->field($name, $label, CommandResultValue::text($value));
    }
    private function integer(string $name, string $label, int $value): CommandResultField
    {
        return $this->field($name, $label, CommandResultValue::integer($value));
    }
    private function boolean(string $name, string $label, bool $value): CommandResultField
    {
        return $this->field($name, $label, CommandResultValue::boolean($value));
    }
    private function field(string $name, string $label, CommandResultValue $value): CommandResultField
    {
        return new CommandResultField(new CommandResultFieldName($name), new CommandResultFieldLabel($label), $value, CommandResultFieldDisclosure::Safe);
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
