<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Handler;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Exception\ApplicationWorkflowException;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\Composition\Workflow\PlannedApplicationBuild;
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
use Throwable;

/** Plans or explicitly applies the exact current Composition build workflow. */
#[Api(ApiStatus::Experimental)]
final readonly class ApplicationBuildCommandHandler implements CommandHandler
{
    private const APPLICATION_ROOT = 'application-root';
    private const OUTPUT_DIRECTORY = 'output-directory';

    public function __construct(private ApplicationBuildWorkflow $workflow) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        $invocation = $operation->invocation();
        if (
            KongoCommand::ApplicationBuild->value !== $invocation->definition()->identifier()->value()
            || CommandMutationPolicy::MutationCapable !== $invocation->definition()->mutationPolicy()
            || CommandInteractionMode::Disabled !== $invocation->interactionMode()
            || !in_array($invocation->executionIntent(), [CommandExecutionIntent::Plan, CommandExecutionIntent::Apply], true)
            || 2 !== count($invocation->inputs())
        ) {
            return $this->invalidInput();
        }

        $applicationRoot = $invocation->input(new CommandInputName(self::APPLICATION_ROOT));
        $outputDirectory = $invocation->input(new CommandInputName(self::OUTPUT_DIRECTORY));
        if (null === $applicationRoot || null === $outputDirectory) {
            return $this->invalidInput();
        }

        try {
            $plan = $this->workflow->plan($applicationRoot->value()->reveal(), $outputDirectory->value()->reveal());
            if (CommandExecutionIntent::Apply === $invocation->executionIntent()) {
                $this->workflow->build($applicationRoot->value()->reveal(), $outputDirectory->value()->reveal());
                $current = $this->workflow->plan($applicationRoot->value()->reveal(), $outputDirectory->value()->reveal());
                if ($current->publicationRequired() || !$this->sameIdentity($plan, $current)) {
                    return $this->precondition();
                }
                $plan = $current;
            }
        } catch (ApplicationWorkflowException) {
            return $this->precondition();
        } catch (Throwable) {
            return CommandResult::failed(new CommandFailure(
                CommandFailureCategory::Infrastructure,
                ConsoleApplicationDiagnostic::ApplicationFailure->value,
            ));
        }

        return CommandResult::succeeded(new CommandResultRecord([
            $this->textField('artifact_filename', 'Artifact filename', $plan->compositionArtifactBasename()),
            $this->textField('artifact_sha256', 'Artifact SHA-256', $plan->artifactSha256()),
            $this->textField('execution_intent', 'Execution intent', $invocation->executionIntent()->value),
            $this->textField('generated_php_filename', 'Generated PHP filename', $plan->generatedPhpBasename()),
            $this->textField('generated_wiring_sha256', 'Generated wiring SHA-256', $plan->generatedWiringSha256()),
            $this->integerField('output_count', 'Output count', 2),
            $this->booleanField(
                'publication_required',
                'Publication required',
                CommandExecutionIntent::Plan === $invocation->executionIntent() && $plan->publicationRequired(),
            ),
        ]));
    }

    private function sameIdentity(PlannedApplicationBuild $left, PlannedApplicationBuild $right): bool
    {
        return $left->compositionArtifactBasename() === $right->compositionArtifactBasename()
            && $left->generatedPhpBasename() === $right->generatedPhpBasename()
            && $left->artifactSha256() === $right->artifactSha256()
            && $left->generatedWiringSha256() === $right->generatedWiringSha256();
    }

    private function invalidInput(): CommandResult
    {
        return CommandResult::rejected(new CommandFailure(
            CommandFailureCategory::InvalidInput,
            ConsoleApplicationDiagnostic::InvalidProcessRequest->value,
        ));
    }

    private function precondition(): CommandResult
    {
        return CommandResult::rejected(new CommandFailure(
            CommandFailureCategory::Precondition,
            ConsoleApplicationDiagnostic::BuildPrecondition->value,
        ));
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

    private function integerField(string $name, string $label, int $value): CommandResultField
    {
        return new CommandResultField(
            new CommandResultFieldName($name),
            new CommandResultFieldLabel($label),
            CommandResultValue::integer($value),
            CommandResultFieldDisclosure::Safe,
        );
    }

    private function booleanField(string $name, string $label, bool $value): CommandResultField
    {
        return new CommandResultField(
            new CommandResultFieldName($name),
            new CommandResultFieldLabel($label),
            CommandResultValue::boolean($value),
            CommandResultFieldDisclosure::Safe,
        );
    }
}
