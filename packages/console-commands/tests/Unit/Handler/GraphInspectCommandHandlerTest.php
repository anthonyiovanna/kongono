<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Tests\Unit\Handler;

use Kongono\ApplicationGraph\Model\ApplicationGraph;
use Kongono\ApplicationGraph\Model\Node;
use Kongono\ApplicationGraph\Model\NodeKey;
use Kongono\ApplicationGraph\Model\NodeKind;
use Kongono\ApplicationGraph\Model\Relation;
use Kongono\ApplicationGraph\Model\RelationKind;
use Kongono\Console\CommandCancellation;
use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandInvocation;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandOperation;
use Kongono\Console\CommandOutcome;
use Kongono\Console\CommandResultRecord;
use Kongono\Console\CommandSummary;
use Kongono\ConsoleCommands\Diagnostic\ConsoleCommandsDiagnostic;
use Kongono\ConsoleCommands\Handler\GraphInspectCommandHandler;
use Kongono\ConsoleRuntime\CommandDispatcher;
use Kongono\ConsoleRuntime\CommandHandlerBinding;
use Kongono\ConsoleRuntime\CommandHandlerSet;
use PHPUnit\Framework\TestCase;

final class GraphInspectCommandHandlerTest extends TestCase
{
    public function testEmptyAndPopulatedGraphsReturnOnlyExactCounts(): void
    {
        self::assertSame([
            'node_count' => 0,
            'node_kind_count' => 0,
            'relation_count' => 0,
            'relation_kind_count' => 0,
        ], $this->execute(new ApplicationGraph([], [])));

        $alpha = new NodeKey('node-secret-alpha');
        $beta = new NodeKey('node-secret-beta');
        $gamma = new NodeKey('node-secret-gamma');
        $graph = new ApplicationGraph(
            [new Node($alpha, new NodeKind('kind-secret-a')), new Node($beta, new NodeKind('kind-secret-a')), new Node($gamma, new NodeKind('kind-secret-b'))],
            [new Relation($alpha, new RelationKind('relation-secret-a'), $beta), new Relation($beta, new RelationKind('relation-secret-b'), $gamma)],
        );
        $result = $this->executeResult($graph);
        self::assertSame([
            'node_count' => 3,
            'node_kind_count' => 2,
            'relation_count' => 2,
            'relation_kind_count' => 2,
        ], self::values($result));
        $serialized = json_encode(self::values($result), JSON_THROW_ON_ERROR);
        foreach (['node-secret', 'kind-secret', 'relation-secret'] as $sentinel) {
            self::assertStringNotContainsString($sentinel, $serialized);
        }
    }

    public function testInvalidDirectInvocationIsSafeAndCancellationPreventsExecution(): void
    {
        $handler = new GraphInspectCommandHandler(new ApplicationGraph([], []));
        $operation = self::createStub(CommandOperation::class);
        $operation->method('invocation')->willReturn(self::invocation('framework:quality'));
        $invalid = $handler->execute($operation);
        self::assertSame(CommandOutcome::Rejected, $invalid->outcome());
        $failure = $invalid->failure();
        self::assertNotNull($failure);
        self::assertSame(CommandFailureCategory::InvalidInput, $failure->category());
        self::assertSame(ConsoleCommandsDiagnostic::InvalidInvocation->value, $failure->diagnosticIdentifier());

        $dispatcher = new CommandDispatcher(new CommandHandlerSet([
            new CommandHandlerBinding(new CommandIdentifier('framework:graph'), $handler),
        ]));
        $cancellation = self::createStub(CommandCancellation::class);
        $cancellation->method('isRequested')->willReturn(true);
        $cancelled = $dispatcher->dispatch(self::invocation('framework:graph'), $cancellation);
        self::assertSame(CommandOutcome::Cancelled, $cancelled->outcome());
        self::assertNull($cancelled->data());
    }

    /** @return array<string, int> */
    private function execute(ApplicationGraph $graph): array
    {
        return self::values($this->executeResult($graph));
    }

    private function executeResult(ApplicationGraph $graph): CommandResultRecord
    {
        $operation = self::createStub(CommandOperation::class);
        $operation->method('invocation')->willReturn(self::invocation('framework:graph'));
        $result = (new GraphInspectCommandHandler($graph))->execute($operation);
        self::assertSame(CommandOutcome::Succeeded, $result->outcome());
        self::assertInstanceOf(CommandResultRecord::class, $result->data());

        return $result->data();
    }

    private static function invocation(string $identifier): CommandInvocation
    {
        return new CommandInvocation(
            new CommandDefinition(new CommandIdentifier($identifier), new CommandSummary('Sentinel summary'), CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly),
            [],
            CommandExecutionIntent::Observe,
            CommandInteractionMode::Disabled,
        );
    }

    /** @return array<string, int> */
    private static function values(CommandResultRecord $record): array
    {
        $values = [];
        foreach ($record->fields() as $field) {
            self::assertSame('safe', $field->disclosure()->value);
            self::assertIsInt($field->value()->value());
            $values[$field->name()->value()] = $field->value()->value();
        }

        return $values;
    }
}
