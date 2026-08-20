<?php

declare(strict_types=1);

namespace Kongono\Routing\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed, safe metadata vocabulary for routing declaration and matching failures. */
#[Api(ApiStatus::Internal)]
enum RoutingDiagnostic: string
{
    case InvalidRouteName = 'KGN-ROUTING-1001';
    case InvalidRouteMethods = 'KGN-ROUTING-1002';
    case InvalidRouteMethod = 'KGN-ROUTING-1003';
    case InvalidRouteTemplate = 'KGN-ROUTING-1004';
    case DuplicateRouteParameter = 'KGN-ROUTING-1005';
    case InvalidRouteTable = 'KGN-ROUTING-1006';
    case DuplicateRouteName = 'KGN-ROUTING-1007';
    case ConflictingRoutePattern = 'KGN-ROUTING-1008';
    case InvalidRouteMatch = 'KGN-ROUTING-1009';
    case InvalidRouteRequest = 'KGN-ROUTING-1010';

    public function contextSchema(): DiagnosticContextSchema
    {
        [$operation, $invariant] = $this->schemaIdentity();
        $integer = DiagnosticValueKind::Integer;
        $text = DiagnosticValueKind::Text;
        $fields = match ($this) {
            self::InvalidRouteName => [
                ['name_length', 'Name length', $integer],
                ['violation', 'Violation', $text],
            ],
            self::InvalidRouteMethods => [
                ['entry_count', 'Entry count', $integer],
                ['violation', 'Violation', $text],
            ],
            self::InvalidRouteMethod => [
                ['method_position', 'Method position', $integer],
                ['method_length', 'Method length', $integer],
                ['violation', 'Violation', $text],
            ],
            self::InvalidRouteTemplate => [
                ['template_length', 'Template length', $integer],
                ['violation', 'Violation', $text],
            ],
            self::DuplicateRouteParameter => [['parameter_position', 'Parameter position', $integer]],
            self::InvalidRouteTable => [
                ['entry_count', 'Entry count', $integer],
                ['violation', 'Violation', $text],
            ],
            self::DuplicateRouteName => [
                ['first_route_position', 'First route position', $integer],
                ['duplicate_route_position', 'Duplicate route position', $integer],
            ],
            self::ConflictingRoutePattern => [
                ['first_route_position', 'First route position', $integer],
                ['conflicting_route_position', 'Conflicting route position', $integer],
            ],
            self::InvalidRouteMatch => [
                ['parameter_count', 'Parameter count', $integer],
                ['violation', 'Violation', $text],
            ],
            self::InvalidRouteRequest => [
                ['component', 'Component', $text],
                ['actual_length', 'Actual length', $integer],
                ['violation', 'Violation', $text],
            ],
        };

        return new DiagnosticContextSchema($operation, $invariant, array_map(
            static fn(array $field): DiagnosticFieldSchema => new DiagnosticFieldSchema(
                $field[0],
                $field[1],
                $field[2],
                DiagnosticDisclosure::Safe,
            ),
            $fields,
        ));
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidRouteName => 'Invalid route name',
            self::InvalidRouteMethods => 'Invalid route method list',
            self::InvalidRouteMethod => 'Invalid route method',
            self::InvalidRouteTemplate => 'Invalid route path template',
            self::DuplicateRouteParameter => 'Duplicate route parameter',
            self::InvalidRouteTable => 'Invalid route table',
            self::DuplicateRouteName => 'Duplicate route name',
            self::ConflictingRoutePattern => 'Conflicting route pattern',
            self::InvalidRouteMatch => 'Invalid route match value',
            self::InvalidRouteRequest => 'Invalid request routing input',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidRouteName => 'Supply a bounded canonical route name',
            self::InvalidRouteMethods => 'Supply a bounded PHP list of canonical HTTP methods',
            self::InvalidRouteMethod => 'Correct the reported route method entry',
            self::InvalidRouteTemplate => 'Supply a bounded route template using literal or parameter segments',
            self::DuplicateRouteParameter => 'Give every route parameter a unique name',
            self::InvalidRouteTable => 'Supply a bounded PHP list containing only route definitions',
            self::DuplicateRouteName => 'Give every route declaration a unique name',
            self::ConflictingRoutePattern => 'Remove or change the overlapping method and structural route pattern',
            self::InvalidRouteMatch => 'Supply exactly the bounded parameters declared by the matched route',
            self::InvalidRouteRequest => 'Supply a PSR request with a bounded valid method and encoded path',
        };
    }

    /** @return array{string, string} */
    private function schemaIdentity(): array
    {
        return match ($this) {
            self::InvalidRouteName => ['Declare route', 'Route name must use the canonical bounded grammar'],
            self::InvalidRouteMethods => ['Declare route', 'Route methods must form a bounded non-empty list'],
            self::InvalidRouteMethod => ['Declare route', 'Every declared method must be a unique canonical HTTP token'],
            self::InvalidRouteTemplate => ['Declare route', 'Route template must use the bounded Phase 2D grammar'],
            self::DuplicateRouteParameter => ['Declare route', 'Parameter names must be unique within one template'],
            self::InvalidRouteTable => ['Build route table', 'Route table must be a bounded list of route definitions'],
            self::DuplicateRouteName => ['Build route table', 'Route names must be unique within one table'],
            self::ConflictingRoutePattern => ['Build route table', 'One method and structural pattern may identify at most one route'],
            self::InvalidRouteMatch => ['Create route match', 'Route-match parameters must exactly satisfy the route template'],
            self::InvalidRouteRequest => ['Match request', 'Request routing input must satisfy the bounded encoded-path contract'],
        };
    }
}
