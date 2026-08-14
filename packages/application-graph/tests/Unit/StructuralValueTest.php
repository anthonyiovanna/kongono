<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Tests\Unit;

use Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic;
use Kongono\ApplicationGraph\Exception\ExceptionInterface;
use Kongono\ApplicationGraph\Exception\InvalidGraphException;
use Kongono\ApplicationGraph\Model\ApplicationGraph;
use Kongono\ApplicationGraph\Model\Node;
use Kongono\ApplicationGraph\Model\NodeKey;
use Kongono\ApplicationGraph\Model\NodeKind;
use Kongono\ApplicationGraph\Model\Relation;
use Kongono\ApplicationGraph\Model\RelationKind;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Serializable;
use Throwable;

final class StructuralValueTest extends TestCase
{
    public function testGraphDiagnosticCatalogAndMetadataContract(): void
    {
        $expected = [
            ['EmptyNodeKey', 'KGN-GRAPH-1001', 'Empty node-key value', 'Supply a non-empty opaque graph-local key'],
            ['EmptyNodeKind', 'KGN-GRAPH-1002', 'Empty node-kind value', 'Supply a non-empty open node-kind label'],
            ['EmptyRelationKind', 'KGN-GRAPH-1003', 'Empty relation-kind value', 'Supply a non-empty open relation-kind label'],
            ['NodesNotList', 'KGN-GRAPH-1004', 'Nodes input is not a zero-based consecutive list', 'Reindex the node array as a PHP list'],
            ['RelationsNotList', 'KGN-GRAPH-1005', 'Relations input is not a zero-based consecutive list', 'Reindex the relation array as a PHP list'],
            ['InvalidNodeEntry', 'KGN-GRAPH-1006', 'A nodes-list entry is not a `Node`', 'Replace the reported entry with a valid `Node`'],
            ['InvalidRelationEntry', 'KGN-GRAPH-1007', 'A relations-list entry is not a `Relation`', 'Replace the reported entry with a valid `Relation`'],
            ['DuplicateNodeKey', 'KGN-GRAPH-1008', 'Duplicate node-key value', 'Remove or rename the duplicate within that snapshot'],
            ['MissingRelationSource', 'KGN-GRAPH-1009', 'Relation source does not reference an existing node', 'Add the source node or correct the source key'],
            ['MissingRelationTarget', 'KGN-GRAPH-1010', 'Relation target does not reference an existing node', 'Add the target node or correct the target key'],
            ['DuplicateRelationTriple', 'KGN-GRAPH-1011', 'Duplicate source/kind/target relation triple', 'Remove the duplicate or use a different relation kind when semantically distinct'],
        ];
        $actual = array_map(
            static fn(GraphDiagnostic $diagnostic): array => [
                $diagnostic->name,
                $diagnostic->value,
                $diagnostic->title(),
                $diagnostic->remediation(),
            ],
            GraphDiagnostic::cases(),
        );
        $reflection = new ReflectionClass(GraphDiagnostic::class);
        $declaredMethods = array_values(array_filter(
            $reflection->getMethods(),
            static fn(\ReflectionMethod $method): bool => $method->isUserDefined()
                && GraphDiagnostic::class === $method->getDeclaringClass()->getName(),
        ));
        $declaredProperties = array_values(array_filter(
            $reflection->getProperties(),
            static fn(\ReflectionProperty $property): bool => GraphDiagnostic::class === $property->getDeclaringClass()->getName(),
        ));

        self::assertSame($expected, $actual);
        self::assertSame(['context', 'contextSchema', 'remediation', 'title'], $this->sortedMethodNames($declaredMethods));
        self::assertSame(
            ['name', 'value'],
            array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $declaredProperties),
        );
        self::assertFalse($reflection->hasMethod('__toString'));

        $cause = new RuntimeException('cause');
        $exception = new InvalidGraphException(GraphDiagnostic::EmptyNodeKey, 'message', ['actual_length' => 0], $cause);

        self::assertSame('KGN-GRAPH-1001', $exception->diagnosticId());
        self::assertSame(GraphDiagnostic::EmptyNodeKey->title(), $exception->diagnosticTitle());
        self::assertSame(GraphDiagnostic::EmptyNodeKey->remediation(), $exception->diagnosticRemediation());
        self::assertSame('message', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($cause, $exception->getPrevious());
    }

    public function testNodeKeyPreservesNonEmptyValueExactly(): void
    {
        $value = " Node\0Key \n";

        self::assertSame($value, (new NodeKey($value))->value());
    }

    public function testEmptyNodeKeyIsRejectedThroughGraphBoundaryOnly(): void
    {
        $this->assertInvalidGraphValue(
            static fn(): NodeKey => new NodeKey(''),
            GraphDiagnostic::EmptyNodeKey,
            'Node key must not be empty.',
        );
    }

    public function testNodeKindPreservesNonEmptyValueExactly(): void
    {
        $value = " Open\0Kind \n";

        self::assertSame($value, (new NodeKind($value))->value());
    }

    public function testEmptyNodeKindIsRejectedThroughGraphBoundaryOnly(): void
    {
        $this->assertInvalidGraphValue(
            static fn(): NodeKind => new NodeKind(''),
            GraphDiagnostic::EmptyNodeKind,
            'Node kind must not be empty.',
        );
    }

    public function testRelationKindPreservesNonEmptyValueExactly(): void
    {
        $value = " Relation\0Kind \n";

        self::assertSame($value, (new RelationKind($value))->value());
    }

    public function testEmptyRelationKindIsRejectedThroughGraphBoundaryOnly(): void
    {
        $this->assertInvalidGraphValue(
            static fn(): RelationKind => new RelationKind(''),
            GraphDiagnostic::EmptyRelationKind,
            'Relation kind must not be empty.',
        );
    }

    public function testNodeExposesExactlyItsKeyAndKind(): void
    {
        $key = new NodeKey('node');
        $kind = new NodeKind('open-kind');
        $node = new Node($key, $kind);

        self::assertSame($key, $node->key());
        self::assertSame($kind, $node->kind());
    }

    public function testRelationExposesItsDirectedTriple(): void
    {
        $source = new NodeKey('source');
        $kind = new RelationKind('open-relation');
        $target = new NodeKey('target');
        $relation = new Relation($source, $kind, $target);

        self::assertSame($source, $relation->source());
        self::assertSame($kind, $relation->kind());
        self::assertSame($target, $relation->target());
    }

    public function testStructuralModelTypesAreFinalAndReadonly(): void
    {
        foreach ([
            ApplicationGraph::class,
            Node::class,
            NodeKey::class,
            NodeKind::class,
            Relation::class,
            RelationKind::class,
        ] as $type) {
            $reflection = new ReflectionClass($type);

            self::assertTrue($reflection->isFinal(), sprintf('%s must be final.', $type));
            self::assertTrue($reflection->isReadOnly(), sprintf('%s must be readonly.', $type));
        }
    }

    public function testStructuralModelDefinesNoStringConversionOrSerializationContract(): void
    {
        foreach ([
            ApplicationGraph::class,
            Node::class,
            NodeKey::class,
            NodeKind::class,
            Relation::class,
            RelationKind::class,
        ] as $type) {
            $reflection = new ReflectionClass($type);

            self::assertFalse($reflection->hasMethod('__toString'), sprintf('%s must not convert to string.', $type));
            self::assertFalse(
                $reflection->implementsInterface(Serializable::class),
                sprintf('%s must not implement serialization.', $type),
            );
            self::assertFalse($reflection->hasMethod('serialize'), sprintf('%s must not define serialization.', $type));
            self::assertFalse($reflection->hasMethod('unserialize'), sprintf('%s must not define serialization.', $type));
            self::assertFalse($reflection->hasMethod('__serialize'), sprintf('%s must not define serialization.', $type));
            self::assertFalse($reflection->hasMethod('__unserialize'), sprintf('%s must not define serialization.', $type));
        }
    }

    /** @param callable(): object $operation */
    private function assertInvalidGraphValue(
        callable $operation,
        GraphDiagnostic $diagnostic,
        string $message,
    ): void {
        try {
            $operation();
            self::fail('An empty structural value must fail.');
        } catch (Throwable $exception) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertNotInstanceOf(KernelExceptionInterface::class, $exception);
            self::assertInstanceOf(InvalidGraphException::class, $exception);
            self::assertSame($diagnostic->value, $exception->diagnosticId());
            self::assertSame($diagnostic->title(), $exception->diagnosticTitle());
            self::assertSame($diagnostic->remediation(), $exception->diagnosticRemediation());
            self::assertSame($message, $exception->getMessage());
            self::assertSame(0, $exception->getCode());
            self::assertNull($exception->getPrevious());

            $context = $exception->diagnosticContext();
            $fields = $context->fields();

            self::assertSame($diagnostic->contextSchema()->operation(), $context->operation());
            self::assertSame($diagnostic->contextSchema()->violatedInvariant(), $context->violatedInvariant());
            self::assertCount(1, $fields);
            self::assertSame('actual_length', $fields[0]->key());
            self::assertSame('Actual length', $fields[0]->label());
            self::assertSame(DiagnosticValueKind::Integer, $fields[0]->kind());
            self::assertSame(0, $fields[0]->value());
            self::assertSame(DiagnosticDisclosure::Safe, $fields[0]->disclosure());
            self::assertNull($fields[0]->textWasTruncated());
            self::assertSame($context, $exception->diagnosticContext());
        }
    }

    /**
     * @param list<\ReflectionMethod> $methods
     *
     * @return list<string>
     */
    private function sortedMethodNames(array $methods): array
    {
        $names = array_map(static fn(\ReflectionMethod $method): string => $method->getName(), $methods);
        sort($names);

        return $names;
    }
}
