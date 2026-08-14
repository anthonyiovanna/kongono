<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Tests\Unit;

use Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic;
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\ApplicationGraph\Exception\GraphException;
use Kongono\ApplicationGraph\Exception\InvalidGraphException;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticField;
use Kongono\Diagnostics\Exception\ExceptionInterface as DiagnosticsExceptionInterface;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use RuntimeException;
use Throwable;

final class GraphDiagnosticContextTest extends TestCase
{
    public function testCatalogMetadataAndContextSchemasAreExact(): void
    {
        $expected = [
            'EmptyNodeKey' => [
                'KGN-GRAPH-1001',
                'Empty node-key value',
                'Supply a non-empty opaque graph-local key',
                'Create node key',
                'Node-key value must be non-empty',
                [['actual_length', 'Actual length', 'integer', 'safe', true]],
            ],
            'EmptyNodeKind' => [
                'KGN-GRAPH-1002',
                'Empty node-kind value',
                'Supply a non-empty open node-kind label',
                'Create node kind',
                'Node-kind value must be non-empty',
                [['actual_length', 'Actual length', 'integer', 'safe', true]],
            ],
            'EmptyRelationKind' => [
                'KGN-GRAPH-1003',
                'Empty relation-kind value',
                'Supply a non-empty open relation-kind label',
                'Create relation kind',
                'Relation-kind value must be non-empty',
                [['actual_length', 'Actual length', 'integer', 'safe', true]],
            ],
            'NodesNotList' => [
                'KGN-GRAPH-1004',
                'Nodes input is not a zero-based consecutive list',
                'Reindex the node array as a PHP list',
                'Construct Application Graph',
                'Nodes input must be a PHP list',
                [
                    ['entry_count', 'Entry count', 'integer', 'safe', true],
                    ['first_unexpected_key', 'First unexpected key', 'text', 'protected', false],
                ],
            ],
            'RelationsNotList' => [
                'KGN-GRAPH-1005',
                'Relations input is not a zero-based consecutive list',
                'Reindex the relation array as a PHP list',
                'Construct Application Graph',
                'Relations input must be a PHP list',
                [
                    ['entry_count', 'Entry count', 'integer', 'safe', true],
                    ['first_unexpected_key', 'First unexpected key', 'text', 'protected', false],
                ],
            ],
            'InvalidNodeEntry' => [
                'KGN-GRAPH-1006',
                'A nodes-list entry is not a `Node`',
                'Replace the reported entry with a valid `Node`',
                'Validate node entry',
                'Every nodes-list entry must be a Node',
                [
                    ['entry_position', 'Entry position', 'integer', 'safe', true],
                    ['actual_value_kind', 'Actual value kind', 'text', 'safe', true],
                ],
            ],
            'InvalidRelationEntry' => [
                'KGN-GRAPH-1007',
                'A relations-list entry is not a `Relation`',
                'Replace the reported entry with a valid `Relation`',
                'Validate relation entry',
                'Every relations-list entry must be a Relation',
                [
                    ['entry_position', 'Entry position', 'integer', 'safe', true],
                    ['actual_value_kind', 'Actual value kind', 'text', 'safe', true],
                ],
            ],
            'DuplicateNodeKey' => [
                'KGN-GRAPH-1008',
                'Duplicate node-key value',
                'Remove or rename the duplicate within that snapshot',
                'Validate node uniqueness',
                'Node keys must be unique within a snapshot',
                [['node_key', 'Node key', 'text', 'protected', true]],
            ],
            'MissingRelationSource' => [
                'KGN-GRAPH-1009',
                'Relation source does not reference an existing node',
                'Add the source node or correct the source key',
                'Validate relation source',
                'Relation source must reference an existing node',
                [
                    ['relation_position', 'Relation position', 'integer', 'safe', true],
                    ['source_key', 'Source key', 'text', 'protected', true],
                ],
            ],
            'MissingRelationTarget' => [
                'KGN-GRAPH-1010',
                'Relation target does not reference an existing node',
                'Add the target node or correct the target key',
                'Validate relation target',
                'Relation target must reference an existing node',
                [
                    ['relation_position', 'Relation position', 'integer', 'safe', true],
                    ['target_key', 'Target key', 'text', 'protected', true],
                ],
            ],
            'DuplicateRelationTriple' => [
                'KGN-GRAPH-1011',
                'Duplicate source/kind/target relation triple',
                'Remove the duplicate or use a different relation kind when semantically distinct',
                'Validate relation uniqueness',
                'Source/kind/target triples must be unique',
                [
                    ['source_key', 'Source key', 'text', 'protected', true],
                    ['relation_kind', 'Relation kind', 'text', 'protected', true],
                    ['target_key', 'Target key', 'text', 'protected', true],
                ],
            ],
        ];

        self::assertSame(array_keys($expected), array_map(
            static fn(GraphDiagnostic $diagnostic): string => $diagnostic->name,
            GraphDiagnostic::cases(),
        ));

        foreach (GraphDiagnostic::cases() as $diagnostic) {
            [$id, $title, $remediation, $operation, $invariant, $fields] = $expected[$diagnostic->name];
            $schema = $diagnostic->contextSchema();

            self::assertSame($id, $diagnostic->value);
            self::assertSame($title, $diagnostic->title());
            self::assertSame($remediation, $diagnostic->remediation());
            self::assertSame($operation, $schema->operation());
            self::assertSame($invariant, $schema->violatedInvariant());
            self::assertSame(array_map(
                static fn(array $field): array => array_slice($field, 0, 4),
                $fields,
            ), array_map(
                static fn(DiagnosticField $field): array => [
                    $field->key(),
                    $field->label(),
                    $field->kind()->value,
                    $field->disclosure()->value,
                ],
                $diagnostic->context($this->completeValues($diagnostic))->fields(),
            ));
            self::assertSame($fields, array_map(
                static fn($field): array => [
                    $field->key(),
                    $field->label(),
                    $field->kind()->value,
                    $field->disclosure()->value,
                    $field->isRequired(),
                ],
                $schema->fields(),
            ));
        }
    }

    public function testCatalogDeclaresOnlyAuthorizedMethodsAndConstructsCompleteSamples(): void
    {
        $reflection = new ReflectionClass(GraphDiagnostic::class);
        $methods = array_values(array_filter(
            $reflection->getMethods(),
            static fn(ReflectionMethod $method): bool => $method->isUserDefined()
                && GraphDiagnostic::class === $method->getDeclaringClass()->getName(),
        ));
        $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($methodNames);

        self::assertSame(['context', 'contextSchema', 'remediation', 'title'], $methodNames);

        foreach (GraphDiagnostic::cases() as $diagnostic) {
            $values = array_reverse($this->completeValues($diagnostic), true);
            $context = $diagnostic->context($values);
            $schemaKeys = array_map(static fn($field): string => $field->key(), $diagnostic->contextSchema()->fields());
            $fieldKeys = array_map(static fn(DiagnosticField $field): string => $field->key(), $context->fields());

            self::assertSame($schemaKeys, $fieldKeys);
            self::assertSame($diagnostic->contextSchema()->operation(), $context->operation());
            self::assertSame($diagnostic->contextSchema()->violatedInvariant(), $context->violatedInvariant());
        }
    }

    public function testClosedSchemasAllowOptionalOmissionAndRejectInvalidValuesThroughDiagnosticsBoundary(): void
    {
        foreach ([GraphDiagnostic::NodesNotList, GraphDiagnostic::RelationsNotList] as $diagnostic) {
            $context = $diagnostic->context(['entry_count' => 1]);

            self::assertSame(['entry_count'], array_map(static fn(DiagnosticField $field): string => $field->key(), $context->fields()));
        }

        foreach ([
            static fn(): DiagnosticContext => GraphDiagnostic::EmptyNodeKey->context([]),
            static fn(): DiagnosticContext => GraphDiagnostic::EmptyNodeKey->context(['actual_length' => '0']),
            static fn(): DiagnosticContext => GraphDiagnostic::EmptyNodeKey->context(['actual_length' => 0, 'unknown' => true]),
        ] as $operation) {
            try {
                $operation();
                self::fail('Invalid Graph context values must fail.');
            } catch (Throwable $exception) {
                self::assertInstanceOf(DiagnosticsExceptionInterface::class, $exception);
                self::assertNotInstanceOf(GraphExceptionInterface::class, $exception);
            }
        }
    }

    public function testManualExceptionPreservesNativeAndDiagnosticBehaviorWithSameContextIdentity(): void
    {
        $cause = new RuntimeException('cause');
        $exception = new InvalidGraphException(
            GraphDiagnostic::MissingRelationSource,
            'message',
            ['source_key' => 'missing', 'relation_position' => 4],
            $cause,
        );
        $context = $exception->diagnosticContext();

        self::assertSame('KGN-GRAPH-1009', $exception->diagnosticId());
        self::assertSame(GraphDiagnostic::MissingRelationSource->title(), $exception->diagnosticTitle());
        self::assertSame(GraphDiagnostic::MissingRelationSource->remediation(), $exception->diagnosticRemediation());
        self::assertSame('message', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($cause, $exception->getPrevious());
        self::assertSame($context, $exception->diagnosticContext());
        self::assertSame(['relation_position', 'source_key'], array_map(
            static fn(DiagnosticField $field): string => $field->key(),
            $context->fields(),
        ));
    }

    public function testGraphMarkerAndBaseReflectionShapesAreExactAndPackageLocal(): void
    {
        $marker = new ReflectionClass(GraphExceptionInterface::class);
        $markerMethods = array_values(array_filter(
            $marker->getMethods(),
            static fn(ReflectionMethod $method): bool => GraphExceptionInterface::class === $method->getDeclaringClass()->getName(),
        ));
        $markerMethodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $markerMethods);
        sort($markerMethodNames);

        self::assertSame([Throwable::class, \Stringable::class], $marker->getInterfaceNames());
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $markerMethodNames);
        self::assertSame(DiagnosticContext::class, (string) $marker->getMethod('diagnosticContext')->getReturnType());
        self::assertSame([], $marker->getMethod('diagnosticContext')->getParameters());

        foreach ([KernelExceptionInterface::class, DependencyInjectionExceptionInterface::class, DiagnosticsExceptionInterface::class] as $other) {
            self::assertFalse(is_a(GraphExceptionInterface::class, $other, true));
            self::assertFalse(is_a($other, GraphExceptionInterface::class, true));
        }

        $base = new ReflectionClass(GraphException::class);
        $properties = array_values(array_filter(
            $base->getProperties(),
            static fn(ReflectionProperty $property): bool => GraphException::class === $property->getDeclaringClass()->getName(),
        ));
        usort($properties, static fn(ReflectionProperty $left, ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));

        self::assertSame(['diagnostic', 'diagnosticContext'], array_map(static fn(ReflectionProperty $property): string => $property->getName(), $properties));
        self::assertSame([GraphDiagnostic::class, DiagnosticContext::class], array_map(static fn(ReflectionProperty $property): string => (string) $property->getType(), $properties));

        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
            self::assertFalse($property->isStatic());
        }

        $methods = array_values(array_filter(
            $base->getMethods(),
            static fn(ReflectionMethod $method): bool => GraphException::class === $method->getDeclaringClass()->getName(),
        ));
        $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($methodNames);
        self::assertSame(['__construct', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $methodNames);

        foreach ($methods as $method) {
            self::assertTrue($method->isFinal());
            self::assertTrue($method->isPublic());
        }

        $parameters = $base->getConstructor()?->getParameters();
        self::assertNotNull($parameters);
        self::assertCount(4, $parameters);
        self::assertSame([GraphDiagnostic::class, 'string', 'array', '?Throwable'], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $parameters,
        ));
        self::assertTrue($parameters[3]->isDefaultValueAvailable());
        self::assertNull($parameters[3]->getDefaultValue());
    }

    public function testContextValuesAndReturnedArraysAreDetachedAndExceptionsDoNotShareContext(): void
    {
        $source = 'first';
        $values = ['source_key' => &$source, 'relation_position' => 2];
        $first = new InvalidGraphException(GraphDiagnostic::MissingRelationSource, 'first', $values);
        $second = new InvalidGraphException(GraphDiagnostic::MissingRelationSource, 'second', $values);
        $fields = $first->diagnosticContext()->fields();

        $source = 'changed';
        array_pop($fields);

        self::assertNotSame($first->diagnosticContext(), $second->diagnosticContext());
        self::assertSame('first', $first->diagnosticContext()->fields()[1]->value());
        self::assertSame(2, $first->diagnosticContext()->fields()[0]->value());
        self::assertCount(2, $first->diagnosticContext()->fields());
        self::assertCount(2, $second->diagnosticContext()->fields());
    }

    public function testPublicMarkerDoesNotExposeInternalCatalogType(): void
    {
        $marker = new ReflectionClass(GraphExceptionInterface::class);

        foreach ($marker->getMethods() as $method) {
            $returnType = $method->getReturnType();

            if ($returnType instanceof ReflectionNamedType) {
                self::assertNotSame(GraphDiagnostic::class, $returnType->getName());
            }

            foreach ($method->getParameters() as $parameter) {
                self::assertNotSame(GraphDiagnostic::class, (string) $parameter->getType());
            }
        }
    }

    /** @return array<string, string|int|bool> */
    private function completeValues(GraphDiagnostic $diagnostic): array
    {
        return match ($diagnostic) {
            GraphDiagnostic::EmptyNodeKey,
            GraphDiagnostic::EmptyNodeKind,
            GraphDiagnostic::EmptyRelationKind => ['actual_length' => 0],
            GraphDiagnostic::NodesNotList,
            GraphDiagnostic::RelationsNotList => ['entry_count' => 2, 'first_unexpected_key' => '4'],
            GraphDiagnostic::InvalidNodeEntry,
            GraphDiagnostic::InvalidRelationEntry => ['entry_position' => 1, 'actual_value_kind' => 'object'],
            GraphDiagnostic::DuplicateNodeKey => ['node_key' => 'duplicate'],
            GraphDiagnostic::MissingRelationSource => ['relation_position' => 2, 'source_key' => 'missing-source'],
            GraphDiagnostic::MissingRelationTarget => ['relation_position' => 3, 'target_key' => 'missing-target'],
            GraphDiagnostic::DuplicateRelationTriple => [
                'source_key' => 'source',
                'relation_kind' => 'kind',
                'target_key' => 'target',
            ],
        };
    }
}
