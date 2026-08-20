<?php

declare(strict_types=1);

namespace Kongono\Http\Server\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed, bounded metadata for HTTP Server composition failures. */
#[Api(ApiStatus::Internal)]
enum HttpServerDiagnostic: string
{
    case TooManyMiddleware = 'KGN-HTTP-SERVER-1001';
    case InvalidRouteHandlerKeys = 'KGN-HTTP-SERVER-1002';
    case InvalidRouteHandlerValue = 'KGN-HTTP-SERVER-1003';

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->contextOperation(),
            $this->contextViolatedInvariant(),
            $this->contextFields(),
        ), $values);
    }

    /** @return list<DiagnosticFieldSchema> */
    public function contextFields(): array
    {
        $integer = DiagnosticValueKind::Integer;
        $text = DiagnosticValueKind::Text;
        $fields = match ($this) {
            self::TooManyMiddleware => [
                ['middleware_count', 'Middleware count', $integer],
                ['maximum_middleware', 'Maximum middleware', $integer],
            ],
            self::InvalidRouteHandlerKeys => [
                ['route_count', 'Route count', $integer],
                ['handler_count', 'Handler count', $integer],
                ['missing_count', 'Missing count', $integer],
                ['unexpected_count', 'Unexpected count', $integer],
            ],
            self::InvalidRouteHandlerValue => [
                ['handler_position', 'Handler position', $integer],
                ['violation', 'Violation', $text],
            ],
        };

        return array_map(
            static fn(array $field): DiagnosticFieldSchema => new DiagnosticFieldSchema(
                $field[0],
                $field[1],
                $field[2],
                DiagnosticDisclosure::Safe,
            ),
            $fields,
        );
    }

    public function contextOperation(): string
    {
        return match ($this) {
            self::TooManyMiddleware => 'Build middleware pipeline',
            self::InvalidRouteHandlerKeys, self::InvalidRouteHandlerValue => 'Build terminal route handler',
        };
    }

    public function contextViolatedInvariant(): string
    {
        return match ($this) {
            self::TooManyMiddleware => 'A middleware pipeline may contain at most 128 components',
            self::InvalidRouteHandlerKeys => 'Handler keys must equal the complete declared route-name set',
            self::InvalidRouteHandlerValue => 'Every mapped route value must implement RequestHandlerInterface',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::TooManyMiddleware => 'Too many middleware components',
            self::InvalidRouteHandlerKeys => 'Invalid route handler keys',
            self::InvalidRouteHandlerValue => 'Invalid route handler value',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::TooManyMiddleware => 'Reduce the middleware pipeline to the supported maximum',
            self::InvalidRouteHandlerKeys => 'Map every declared route name to exactly one request handler',
            self::InvalidRouteHandlerValue => 'Supply a PSR-15 request handler for every declared route',
        };
    }
}
