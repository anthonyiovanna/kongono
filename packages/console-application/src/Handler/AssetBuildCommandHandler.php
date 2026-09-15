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
use Kongono\Console\CommandResultField;
use Kongono\Console\CommandResultFieldDisclosure;
use Kongono\Console\CommandResultFieldLabel;
use Kongono\Console\CommandResultFieldName;
use Kongono\Console\CommandResultRecord;
use Kongono\Console\CommandResultValue;
use Kongono\ConsoleApplication\Diagnostic\ConsoleApplicationDiagnostic;
use Kongono\ConsoleApplication\KongoCommand;
use Throwable;

/** Plans or explicitly applies one deterministic native asset build. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetBuildCommandHandler implements CommandHandler
{
    public function __construct(private AssetApplicationWorkflow $workflow) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        $invocation = $operation->invocation();
        if (KongoCommand::AssetBuild->value !== $invocation->definition()->identifier()->value()
            || CommandMutationPolicy::MutationCapable !== $invocation->definition()->mutationPolicy()
            || CommandInteractionMode::Disabled !== $invocation->interactionMode()
            || !in_array($invocation->executionIntent(), [CommandExecutionIntent::Plan, CommandExecutionIntent::Apply], true)
            || 3 !== count($invocation->inputs())) {
            return $this->invalid();
        }
        $definition = $this->definition($operation);
        if (null === $definition) {
            return $this->invalid();
        }
        try {
            $summary = CommandExecutionIntent::Apply === $invocation->executionIntent()
                ? AssetApplicationSummary::fromCurrent($this->workflow->build($definition), $invocation->executionIntent()->value)
                : AssetApplicationSummary::fromPlan($this->workflow->plan($definition), $invocation->executionIntent()->value);
        } catch (AssetApplicationException) {
            return $this->precondition();
        } catch (Throwable) {
            return $this->infrastructure();
        }

        return CommandResult::succeeded(self::record($summary, true));
    }

    private function definition(CommandOperation $operation): ?AssetApplicationDefinition
    {
        $invocation = $operation->invocation();
        $root = $invocation->input(new CommandInputName('application-root'));
        $source = $invocation->input(new CommandInputName('source-file'));
        $output = $invocation->input(new CommandInputName('output-directory'));
        if (null === $root || null === $source || null === $output) {
            return null;
        }
        try {
            return new AssetApplicationDefinition($root->value()->reveal(), $source->value()->reveal(), $output->value()->reveal());
        } catch (AssetApplicationException) {
            return null;
        }
    }

    public static function record(AssetApplicationSummary $summary, bool $includeIntent = false): CommandResultRecord
    {
        $fields = [
            self::text('artifact_basename', 'Artifact basename', $summary->artifactBasename()),
            self::text('build_identity', 'Build identity', $summary->buildIdentity()),
            self::boolean('current', 'Current', $summary->current()),
            self::integer('declaration_count', 'Declaration count', $summary->declarationCount()),
            self::integer('dependency_count', 'Dependency count', $summary->dependencyCount()),
            self::integer('entry_point_count', 'Entry point count', $summary->entryPointCount()),
        ];
        if ($includeIntent) {
            $fields[] = self::text('execution_intent', 'Execution intent', (string) $summary->executionIntent());
        }
        array_push(
            $fields,
            self::text('graph_identity', 'Graph identity', $summary->graphIdentity()),
            self::integer('output_count', 'Output count', $summary->outputCount()),
            self::text('producer_kind', 'Producer kind', $summary->producerKind()),
            self::boolean('publication_required', 'Publication required', $summary->publicationRequired()),
            self::text('resolution_identity', 'Resolution identity', $summary->resolutionIdentity()),
            self::text('schema', 'Schema', $summary->schema()),
            self::text('source_set_identity', 'Source set identity', $summary->sourceSetIdentity()),
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
