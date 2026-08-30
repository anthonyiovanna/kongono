<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Handler;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactLoader;
use Kongono\ApplicationGraphArtifact\Exception\ExceptionInterface as ApplicationGraphArtifactException;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
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

/** Loads and projects one explicit structurally valid Application Graph artifact. */
#[Api(ApiStatus::Experimental)]
final readonly class ApplicationGraphInspectCommandHandler implements CommandHandler
{
    private const ARTIFACT_PATH = 'artifact-path';

    public function __construct(private ApplicationGraphArtifactLoader $loader) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        $invocation = $operation->invocation();
        if (
            KongoCommand::ApplicationGraphInspect->value !== $invocation->definition()->identifier()->value()
            || CommandMutationPolicy::ReadOnly !== $invocation->definition()->mutationPolicy()
            || CommandInteractionMode::Disabled !== $invocation->interactionMode()
            || CommandExecutionIntent::Observe !== $invocation->executionIntent()
            || 1 !== count($invocation->inputs())
        ) {
            return $this->invalid();
        }
        $artifactPath = $invocation->input(new CommandInputName(self::ARTIFACT_PATH));
        if (null === $artifactPath) {
            return $this->invalid();
        }

        try {
            $inspection = $this->loader->load($artifactPath->value()->reveal())->inspection();
        } catch (ApplicationGraphArtifactException) {
            return $this->precondition();
        } catch (Throwable) {
            return $this->infrastructure();
        }

        return CommandResult::succeeded(new CommandResultRecord([
            $this->text('schema', 'Schema', 'kongono.application-graph-inspection'),
            $this->integer('version', 'Version', 1),
            $this->text('artifact_sha256', 'Artifact SHA-256', $inspection->artifactSha256()),
            $this->text('graph_identity', 'Graph identity', $inspection->graphIdentity()->value()),
            $this->integer('graph_contract_version', 'Graph contract version', $inspection->contractVersion()->value),
            $this->integer('node_count', 'Node count', $inspection->nodeCount()),
            $this->integer('relation_count', 'Relation count', $inspection->relationCount()),
            $this->integer('composition_input_count', 'Composition input count', $inspection->nodeTypeCount(NodeType::CompositionInput)),
            $this->integer('composition_service_count', 'Composition service count', $inspection->nodeTypeCount(NodeType::CompositionService)),
            $this->integer('composition_contract_count', 'Composition contract count', $inspection->nodeTypeCount(NodeType::CompositionContract)),
            $this->integer('composition_root_count', 'Composition root count', $inspection->nodeTypeCount(NodeType::CompositionRoot)),
            $this->integer('composition_depends_on_count', 'Composition depends-on count', $inspection->relationTypeCount(RelationType::DependsOn)),
            $this->integer('composition_consumes_input_count', 'Composition consumes-input count', $inspection->relationTypeCount(RelationType::ConsumesInput)),
            $this->integer('composition_implements_contract_count', 'Composition implements-contract count', $inspection->relationTypeCount(RelationType::ImplementsContract)),
            $this->integer('composition_exposes_root_count', 'Composition exposes-root count', $inspection->relationTypeCount(RelationType::ExposesRoot)),
        ]));
    }

    private function invalid(): CommandResult
    {
        return CommandResult::rejected(new CommandFailure(CommandFailureCategory::InvalidInput, ConsoleApplicationDiagnostic::InvalidProcessRequest->value));
    }

    private function precondition(): CommandResult
    {
        return CommandResult::rejected(new CommandFailure(CommandFailureCategory::Precondition, ConsoleApplicationDiagnostic::GraphInspectionPrecondition->value));
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

    private function field(string $name, string $label, CommandResultValue $value): CommandResultField
    {
        return new CommandResultField(new CommandResultFieldName($name), new CommandResultFieldLabel($label), $value, CommandResultFieldDisclosure::Safe);
    }
}
