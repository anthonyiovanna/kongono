<?php

declare(strict_types=1);

namespace Kongono\Routing\Tests\Unit\Definition;

use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Routing\Diagnostic\RoutingDiagnostic;
use Kongono\Routing\Exception\InvalidRouteDefinitionException;
use PHPUnit\Framework\TestCase;

final class RouteTableTest extends TestCase
{
    public function testRoutesAreDetachedAndCanonicallyOrderedByBinaryName(): void
    {
        $upper = new RouteDefinition('Z', ['GET'], '/z');
        $lower = new RouteDefinition('a', ['GET'], '/a');
        $routes = [$lower, $upper];
        $table = new RouteTable($routes);
        $routes = [];

        self::assertSame([$upper, $lower], $table->routes());
        $returned = $table->routes();
        array_pop($returned);
        self::assertSame([$upper, $lower], $table->routes());
        self::assertSame([], $routes);
    }

    public function testEmptyTableIsValid(): void
    {
        self::assertSame([], (new RouteTable([]))->routes());
    }

    public function testOneAndOneThousandTwentyFourRouteBoundariesAreAccepted(): void
    {
        $single = new RouteDefinition('single', ['GET'], '/single');
        self::assertSame([$single], (new RouteTable([$single]))->routes());

        $routes = [];
        for ($position = 0; $position < 1024; ++$position) {
            $routes[] = new RouteDefinition(sprintf('route-%04d', $position), ['GET'], sprintf('/route-%04d', $position));
        }
        $table = new RouteTable(array_reverse($routes));
        self::assertCount(1024, $table->routes());
        self::assertSame('route-0000', $table->routes()[0]->name());
        self::assertSame('route-1023', $table->routes()[1023]->name());
    }

    public function testReferencedListEntryDetachesFromLaterReplacement(): void
    {
        $route = new RouteDefinition('first', ['GET'], '/first');
        $routes = [&$route];
        $table = new RouteTable($routes);
        $route = new RouteDefinition('second', ['GET'], '/second');

        self::assertSame('first', $table->routes()[0]->name());
    }

    public function testNonListTooManyAndNonRouteInputsAreRejected(): void
    {
        foreach ([
            [[1 => new RouteDefinition('a', ['GET'], '/')], RoutingDiagnostic::InvalidRouteTable, 'not_list'],
            [array_fill(0, 1025, new RouteDefinition('a', ['GET'], '/')), RoutingDiagnostic::InvalidRouteTable, 'too_many'],
            [['not-route'], RoutingDiagnostic::InvalidRouteTable, 'not_route'],
        ] as [$routes, $diagnostic, $violation]) {
            try {
                new RouteTable($routes);
                self::fail('Expected invalid route table.');
            } catch (InvalidRouteDefinitionException $exception) {
                self::assertSame($diagnostic->value, $exception->diagnosticId());
                self::assertSame($violation, $exception->diagnosticContext()->fields()[1]->value());
            }
        }
    }

    public function testDuplicateNamesReportEarlierAndLaterInputPositions(): void
    {
        try {
            new RouteTable([
                new RouteDefinition('duplicate', ['GET'], '/first'),
                new RouteDefinition('other', ['GET'], '/other'),
                new RouteDefinition('duplicate', ['POST'], '/second'),
            ]);
            self::fail('Expected duplicate name.');
        } catch (InvalidRouteDefinitionException $exception) {
            self::assertSame(RoutingDiagnostic::DuplicateRouteName->value, $exception->diagnosticId());
            self::assertSame([0, 2], array_map(static fn($field) => $field->value(), $exception->diagnosticContext()->fields()));
        }
    }

    public function testDuplicateNameFailureIsEquivalentInReversedDeclarationOrder(): void
    {
        $routes = [
            new RouteDefinition('duplicate', ['GET'], '/first'),
            new RouteDefinition('duplicate', ['POST'], '/second'),
        ];

        $forward = $this->routeTableFailureProof($routes);
        $reverse = $this->routeTableFailureProof(array_reverse($routes));

        self::assertSame($forward, $reverse);
        self::assertSame(RoutingDiagnostic::DuplicateRouteName->value, $forward['diagnostic_id']);
        self::assertSame([0, 1], array_column($forward['fields'], 'value'));
    }

    public function testStructuralConflictsIgnoreParameterNamesButRespectMethodAndLiterals(): void
    {
        try {
            new RouteTable([
                new RouteDefinition('first', ['GET', 'POST'], '/users/{id}'),
                new RouteDefinition('safe-method', ['DELETE'], '/users/{value}'),
                new RouteDefinition('safe-literal', ['GET'], '/accounts/{value}'),
                new RouteDefinition('conflict', ['GET'], '/users/{value}'),
            ]);
            self::fail('Expected structural conflict.');
        } catch (InvalidRouteDefinitionException $exception) {
            self::assertSame(RoutingDiagnostic::ConflictingRoutePattern->value, $exception->diagnosticId());
            self::assertSame([0, 3], array_map(static fn($field) => $field->value(), $exception->diagnosticContext()->fields()));
        }
    }

    public function testStructuralConflictFailureIsEquivalentInReversedDeclarationOrder(): void
    {
        $routes = [
            new RouteDefinition('first', ['GET'], '/users/{id}'),
            new RouteDefinition('second', ['GET'], '/users/{value}'),
        ];

        $forward = $this->routeTableFailureProof($routes);
        $reverse = $this->routeTableFailureProof(array_reverse($routes));

        self::assertSame($forward, $reverse);
        self::assertSame(RoutingDiagnostic::ConflictingRoutePattern->value, $forward['diagnostic_id']);
        self::assertSame([0, 1], array_column($forward['fields'], 'value'));
    }

    /**
     * @param list<RouteDefinition> $routes
     * @return array{
     *   diagnostic_id: string,
     *   operation: string,
     *   violated_invariant: string,
     *   fields: list<array{key: string, label: string, kind: string, disclosure: string, value: string|int|bool}>
     * }
     */
    private function routeTableFailureProof(array $routes): array
    {
        try {
            new RouteTable($routes);
            self::fail('Expected invalid route table.');
        } catch (InvalidRouteDefinitionException $exception) {
            $context = $exception->diagnosticContext();

            return [
                'diagnostic_id' => $exception->diagnosticId(),
                'operation' => $context->operation(),
                'violated_invariant' => $context->violatedInvariant(),
                'fields' => array_map(static fn($field): array => [
                    'key' => $field->key(),
                    'label' => $field->label(),
                    'kind' => $field->kind()->value,
                    'disclosure' => $field->disclosure()->value,
                    'value' => $field->value(),
                ], $context->fields()),
            ];
        }
    }
}
