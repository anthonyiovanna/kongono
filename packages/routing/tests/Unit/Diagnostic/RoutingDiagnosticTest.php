<?php

declare(strict_types=1);

namespace Kongono\Routing\Tests\Unit\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Routing\Diagnostic\RoutingDiagnostic;
use PHPUnit\Framework\TestCase;

final class RoutingDiagnosticTest extends TestCase
{
    public function testCatalogIdentityAndSafeSchemasAreExact(): void
    {
        $expected = [
            'InvalidRouteName' => ['KGN-ROUTING-1001', 'Invalid route name', 'Supply a bounded canonical route name', 'Declare route', 'Route name must use the canonical bounded grammar', ['name_length', 'violation']],
            'InvalidRouteMethods' => ['KGN-ROUTING-1002', 'Invalid route method list', 'Supply a bounded PHP list of canonical HTTP methods', 'Declare route', 'Route methods must form a bounded non-empty list', ['entry_count', 'violation']],
            'InvalidRouteMethod' => ['KGN-ROUTING-1003', 'Invalid route method', 'Correct the reported route method entry', 'Declare route', 'Every declared method must be a unique canonical HTTP token', ['method_position', 'method_length', 'violation']],
            'InvalidRouteTemplate' => ['KGN-ROUTING-1004', 'Invalid route path template', 'Supply a bounded route template using literal or parameter segments', 'Declare route', 'Route template must use the bounded Phase 2D grammar', ['template_length', 'violation']],
            'DuplicateRouteParameter' => ['KGN-ROUTING-1005', 'Duplicate route parameter', 'Give every route parameter a unique name', 'Declare route', 'Parameter names must be unique within one template', ['parameter_position']],
            'InvalidRouteTable' => ['KGN-ROUTING-1006', 'Invalid route table', 'Supply a bounded PHP list containing only route definitions', 'Build route table', 'Route table must be a bounded list of route definitions', ['entry_count', 'violation']],
            'DuplicateRouteName' => ['KGN-ROUTING-1007', 'Duplicate route name', 'Give every route declaration a unique name', 'Build route table', 'Route names must be unique within one table', ['first_route_position', 'duplicate_route_position']],
            'ConflictingRoutePattern' => ['KGN-ROUTING-1008', 'Conflicting route pattern', 'Remove or change the overlapping method and structural route pattern', 'Build route table', 'One method and structural pattern may identify at most one route', ['first_route_position', 'conflicting_route_position']],
            'InvalidRouteMatch' => ['KGN-ROUTING-1009', 'Invalid route match value', 'Supply exactly the bounded parameters declared by the matched route', 'Create route match', 'Route-match parameters must exactly satisfy the route template', ['parameter_count', 'violation']],
            'InvalidRouteRequest' => ['KGN-ROUTING-1010', 'Invalid request routing input', 'Supply a PSR request with a bounded valid method and encoded path', 'Match request', 'Request routing input must satisfy the bounded encoded-path contract', ['component', 'actual_length', 'violation']],
        ];
        $expectedFields = [
            'InvalidRouteName' => [['name_length', 'Name length', 'integer'], ['violation', 'Violation', 'text']],
            'InvalidRouteMethods' => [['entry_count', 'Entry count', 'integer'], ['violation', 'Violation', 'text']],
            'InvalidRouteMethod' => [['method_position', 'Method position', 'integer'], ['method_length', 'Method length', 'integer'], ['violation', 'Violation', 'text']],
            'InvalidRouteTemplate' => [['template_length', 'Template length', 'integer'], ['violation', 'Violation', 'text']],
            'DuplicateRouteParameter' => [['parameter_position', 'Parameter position', 'integer']],
            'InvalidRouteTable' => [['entry_count', 'Entry count', 'integer'], ['violation', 'Violation', 'text']],
            'DuplicateRouteName' => [['first_route_position', 'First route position', 'integer'], ['duplicate_route_position', 'Duplicate route position', 'integer']],
            'ConflictingRoutePattern' => [['first_route_position', 'First route position', 'integer'], ['conflicting_route_position', 'Conflicting route position', 'integer']],
            'InvalidRouteMatch' => [['parameter_count', 'Parameter count', 'integer'], ['violation', 'Violation', 'text']],
            'InvalidRouteRequest' => [['component', 'Component', 'text'], ['actual_length', 'Actual length', 'integer'], ['violation', 'Violation', 'text']],
        ];

        self::assertSame(array_keys($expected), array_map(static fn(RoutingDiagnostic $diagnostic): string => $diagnostic->name, RoutingDiagnostic::cases()));
        foreach (RoutingDiagnostic::cases() as $diagnostic) {
            [$id, $title, $remediation, $operation, $invariant, $fieldKeys] = $expected[$diagnostic->name];
            self::assertSame($id, $diagnostic->value);
            self::assertSame($title, $diagnostic->title());
            self::assertSame($remediation, $diagnostic->remediation());
            self::assertSame($operation, $diagnostic->contextSchema()->operation());
            self::assertSame($invariant, $diagnostic->contextSchema()->violatedInvariant());
            self::assertSame($fieldKeys, array_column($expectedFields[$diagnostic->name], 0));
            self::assertSame($expectedFields[$diagnostic->name], array_map(
                static fn($field): array => [$field->key(), $field->label(), $field->kind()->value],
                $diagnostic->contextSchema()->fields(),
            ));
            foreach ($diagnostic->contextSchema()->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
                self::assertTrue($field->isRequired());
            }
        }

        $identifiers = array_map(static fn(RoutingDiagnostic $diagnostic): string => $diagnostic->value, RoutingDiagnostic::cases());
        self::assertCount(10, array_unique($identifiers));
        foreach ($identifiers as $identifier) {
            self::assertMatchesRegularExpression('/^KGN-ROUTING-1\d{3}$/D', $identifier);
        }
    }

    public function testEnumShapeAndStatusAreExact(): void
    {
        $reflection = new \ReflectionClass(RoutingDiagnostic::class);
        self::assertSame(ApiStatus::Internal, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        $methods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), static fn(\ReflectionMethod $method): bool => $method->isUserDefined() && RoutingDiagnostic::class === $method->getDeclaringClass()->getName()),
        );
        sort($methods);
        self::assertSame(['context', 'contextSchema', 'remediation', 'title'], $methods);
    }
}
