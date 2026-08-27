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
use Kongono\SecurityPolicyBuild\Exception\SecurityPolicyWorkflowException;
use Kongono\SecurityPolicyBuild\Workflow\SecurityPolicyBuildWorkflow;
use Throwable;

/** Performs one non-mutating policy identity inspection. */
#[Api(ApiStatus::Experimental)]
final readonly class SecurityInspectCommandHandler implements CommandHandler
{
    public function __construct(private SecurityPolicyBuildWorkflow $workflow) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        $invocation = $operation->invocation();
        if (
            KongoCommand::SecurityInspect->value !== $invocation->definition()->identifier()->value()
            || CommandMutationPolicy::ReadOnly !== $invocation->definition()->mutationPolicy()
            || CommandInteractionMode::Disabled !== $invocation->interactionMode()
            || CommandExecutionIntent::Observe !== $invocation->executionIntent()
            || 2 !== count($invocation->inputs())
        ) {
            return $this->invalid();
        }
        $root = $invocation->input(new CommandInputName('application-root'));
        $output = $invocation->input(new CommandInputName('output-directory'));
        if (null === $root || null === $output) {
            return $this->invalid();
        }
        try {
            $plan = $this->workflow->inspect($root->value()->reveal(), $output->value()->reveal());
        } catch (SecurityPolicyWorkflowException) {
            return $this->precondition();
        } catch (Throwable) {
            return $this->infrastructure();
        }

        return CommandResult::succeeded(new CommandResultRecord([
            $this->text('schema', 'Schema', 'kongono.security-policy-inspection'),
            $this->integer('version', 'Version', 1),
            $this->text('artifact_filename', 'Artifact filename', $plan->artifactBasename()),
            $this->text('artifact_sha256', 'Artifact SHA-256', $plan->artifactSha256()),
            $this->text('source_sha256', 'Source SHA-256', $plan->sourceSha256()),
            $this->boolean('publication_required', 'Publication required', $plan->publicationRequired()),
        ]));
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
}
