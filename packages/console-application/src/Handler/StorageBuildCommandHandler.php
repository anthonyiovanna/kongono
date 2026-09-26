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
use Kongono\StorageApplication\Definition\StorageApplicationDefinition;
use Kongono\StorageApplication\Exception\ExceptionInterface as StorageApplicationException;
use Kongono\StorageApplication\Summary\StorageApplicationSummary;
use Kongono\StorageApplication\Workflow\StorageApplicationWorkflow;
use Throwable;

/** Plans or explicitly applies one deterministic storage-profile build. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageBuildCommandHandler implements CommandHandler
{
    public function __construct(private StorageApplicationWorkflow $workflow) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        $invocation = $operation->invocation();
        if (KongoCommand::StorageBuild->value !== $invocation->definition()->identifier()->value()
            || CommandMutationPolicy::MutationCapable !== $invocation->definition()->mutationPolicy()
            || CommandInteractionMode::Disabled !== $invocation->interactionMode()
            || !in_array($invocation->executionIntent(), [CommandExecutionIntent::Plan, CommandExecutionIntent::Apply], true)
            || 3 !== count($invocation->inputs())) {
            return $this->invalid();
        }
        $definition = self::definition($operation);
        if (null === $definition) {
            return $this->invalid();
        }
        try {
            $summary = CommandExecutionIntent::Apply === $invocation->executionIntent()
                ? StorageApplicationSummary::fromCurrent($this->workflow->build($definition), $invocation->executionIntent()->value)
                : StorageApplicationSummary::fromPlan($this->workflow->plan($definition), $invocation->executionIntent()->value);
            return CommandResult::succeeded(self::record($summary, true));
        } catch (StorageApplicationException) {
            return $this->precondition();
        } catch (Throwable) {
            return $this->infrastructure();
        }
    }

    public static function definition(CommandOperation $operation): ?StorageApplicationDefinition
    {
        $invocation = $operation->invocation();
        $root = $invocation->input(new CommandInputName('application-root'));
        $source = $invocation->input(new CommandInputName('source-file'));
        $output = $invocation->input(new CommandInputName('output-directory'));
        if (null === $root || null === $source || null === $output) {
            return null;
        }
        try {
            return new StorageApplicationDefinition($root->value()->reveal(), $source->value()->reveal(), $output->value()->reveal());
        } catch (StorageApplicationException) {
            return null;
        }
    }

    public static function record(StorageApplicationSummary $summary, bool $includeIntent = false): CommandResultRecord
    {
        $fields = [
            self::boolean('current', 'Current', $summary->current()),
        ];
        if ($includeIntent) {
            $fields[] = self::text('execution_intent', 'Execution intent', (string) $summary->executionIntent());
        }
        array_push(
            $fields,
            self::integer('profile_count', 'Profile count', $summary->profileCount()),
            self::boolean('publication_required', 'Publication required', $summary->publicationRequired()),
            self::text('schema', 'Schema', $summary->schema()),
            self::integer('version', 'Version', $summary->version()),
        );
        return new CommandResultRecord($fields);
    }

    private static function text(string $name, string $label, string $value): CommandResultField
    {
        return self::field($name, $label, CommandResultValue::text($value));
    }
    private static function integer(string $name, string $label, int $value): CommandResultField
    {
        return self::field($name, $label, CommandResultValue::integer($value));
    }
    private static function boolean(string $name, string $label, bool $value): CommandResultField
    {
        return self::field($name, $label, CommandResultValue::boolean($value));
    }
    private static function field(string $name, string $label, CommandResultValue $value): CommandResultField
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
