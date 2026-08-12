<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Tests\Unit;

use Kongono\ApplicationGraph\Exception\ExceptionInterface;
use Kongono\ApplicationGraph\Exception\InvalidGraphException;
use Kongono\ApplicationGraph\Model\ApplicationGraph;
use Kongono\ApplicationGraph\Model\Node;
use Kongono\ApplicationGraph\Model\NodeKey;
use Kongono\ApplicationGraph\Model\NodeKind;
use Kongono\ApplicationGraph\Model\Relation;
use Kongono\ApplicationGraph\Model\RelationKind;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Serializable;
use Throwable;

final class StructuralValueTest extends TestCase
{
    public function testNodeKeyPreservesNonEmptyValueExactly(): void
    {
        $value = " Node\0Key \n";

        self::assertSame($value, (new NodeKey($value))->value());
    }

    public function testEmptyNodeKeyIsRejectedThroughGraphBoundaryOnly(): void
    {
        $this->assertInvalidGraphValue(static fn(): NodeKey => new NodeKey(''), 'key');
    }

    public function testNodeKindPreservesNonEmptyValueExactly(): void
    {
        $value = " Open\0Kind \n";

        self::assertSame($value, (new NodeKind($value))->value());
    }

    public function testEmptyNodeKindIsRejectedThroughGraphBoundaryOnly(): void
    {
        $this->assertInvalidGraphValue(static fn(): NodeKind => new NodeKind(''), 'kind');
    }

    public function testRelationKindPreservesNonEmptyValueExactly(): void
    {
        $value = " Relation\0Kind \n";

        self::assertSame($value, (new RelationKind($value))->value());
    }

    public function testEmptyRelationKindIsRejectedThroughGraphBoundaryOnly(): void
    {
        $this->assertInvalidGraphValue(static fn(): RelationKind => new RelationKind(''), 'kind');
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
    private function assertInvalidGraphValue(callable $operation, string $messageCategory): void
    {
        try {
            $operation();
            self::fail('An empty structural value must fail.');
        } catch (Throwable $exception) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertNotInstanceOf(KernelExceptionInterface::class, $exception);
            self::assertInstanceOf(InvalidGraphException::class, $exception);
            self::assertStringContainsString($messageCategory, strtolower($exception->getMessage()));
        }
    }
}
