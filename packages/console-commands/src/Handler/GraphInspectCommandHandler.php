<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Handler;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Model\ApplicationGraph;
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

/** Projects only bounded structural counts from one injected immutable graph. */
#[Api(ApiStatus::Experimental)]
final readonly class GraphInspectCommandHandler implements CommandHandler
{
    public function __construct(private ApplicationGraph $graph) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        if (!$this->accepts($operation)) {
            return CommandResult::rejected(new CommandFailure(
                CommandFailureCategory::InvalidInput,
                ConsoleCommandsDiagnostic::InvalidInvocation->value,
            ));
        }

        $nodeKinds = [];
        foreach ($this->graph->nodes() as $node) {
            $nodeKinds["\0" . $node->kind()->value()] = true;
        }
        $relationKinds = [];
        foreach ($this->graph->relations() as $relation) {
            $relationKinds["\0" . $relation->kind()->value()] = true;
        }

        return CommandResult::succeeded(new CommandResultRecord([
            $this->integerField('node_count', 'Nodes', count($this->graph->nodes())),
            $this->integerField('node_kind_count', 'Node kinds', count($nodeKinds)),
            $this->integerField('relation_count', 'Relations', count($this->graph->relations())),
            $this->integerField('relation_kind_count', 'Relation kinds', count($relationKinds)),
        ]));
    }

    private function accepts(CommandOperation $operation): bool
    {
        $invocation = $operation->invocation();

        return ReadOnlyFrameworkCommand::Graph->value === $invocation->definition()->identifier()->value()
            && CommandExecutionIntent::Observe === $invocation->executionIntent()
            && CommandInteractionMode::Disabled === $invocation->interactionMode()
            && CommandMutationPolicy::ReadOnly === $invocation->definition()->mutationPolicy()
            && [] === $invocation->inputs();
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
}
