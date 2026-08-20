<?php

declare(strict_types=1);

namespace Kongono\Http\Server\Tests\Unit\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Http\Server\Diagnostic\HttpServerDiagnostic;
use PHPUnit\Framework\TestCase;

final class HttpServerDiagnosticTest extends TestCase
{
    public function testCatalogIdentityProseAndSchemasAreExact(): void
    {
        $expected = [
            'TooManyMiddleware' => [
                'KGN-HTTP-SERVER-1001',
                'Too many middleware components',
                'Reduce the middleware pipeline to the supported maximum',
                'Build middleware pipeline',
                'A middleware pipeline may contain at most 128 components',
                [['middleware_count', 'Middleware count', 'integer'], ['maximum_middleware', 'Maximum middleware', 'integer']],
            ],
            'InvalidRouteHandlerKeys' => [
                'KGN-HTTP-SERVER-1002',
                'Invalid route handler keys',
                'Map every declared route name to exactly one request handler',
                'Build terminal route handler',
                'Handler keys must equal the complete declared route-name set',
                [['route_count', 'Route count', 'integer'], ['handler_count', 'Handler count', 'integer'], ['missing_count', 'Missing count', 'integer'], ['unexpected_count', 'Unexpected count', 'integer']],
            ],
            'InvalidRouteHandlerValue' => [
                'KGN-HTTP-SERVER-1003',
                'Invalid route handler value',
                'Supply a PSR-15 request handler for every declared route',
                'Build terminal route handler',
                'Every mapped route value must implement RequestHandlerInterface',
                [['handler_position', 'Handler position', 'integer'], ['violation', 'Violation', 'text']],
            ],
        ];

        self::assertSame(array_keys($expected), array_map(static fn(HttpServerDiagnostic $diagnostic): string => $diagnostic->name, HttpServerDiagnostic::cases()));
        foreach (HttpServerDiagnostic::cases() as $diagnostic) {
            [$id, $title, $remediation, $operation, $invariant, $fields] = $expected[$diagnostic->name];
            self::assertSame($id, $diagnostic->value);
            self::assertSame($title, $diagnostic->title());
            self::assertSame($remediation, $diagnostic->remediation());
            self::assertSame($operation, $diagnostic->contextOperation());
            self::assertSame($invariant, $diagnostic->contextViolatedInvariant());
            self::assertSame($fields, array_map(
                static fn($field): array => [$field->key(), $field->label(), $field->kind()->value],
                $diagnostic->contextFields(),
            ));
            foreach ($diagnostic->contextFields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
                self::assertTrue($field->isRequired());
            }
        }

        $identifiers = array_map(static fn(HttpServerDiagnostic $diagnostic): string => $diagnostic->value, HttpServerDiagnostic::cases());
        self::assertCount(3, array_unique($identifiers));
        foreach ($identifiers as $identifier) {
            self::assertMatchesRegularExpression('/^KGN-HTTP-SERVER-1\d{3}$/D', $identifier);
        }
    }

    public function testContextAndPublicMethodInventoryAreExact(): void
    {
        $context = HttpServerDiagnostic::InvalidRouteHandlerValue->context([
            'handler_position' => 1,
            'violation' => 'not_request_handler',
        ]);
        self::assertSame(['handler_position', 'violation'], array_map(static fn($field): string => $field->key(), $context->fields()));
        self::assertSame([1, 'not_request_handler'], array_map(static fn($field): string|int|bool => $field->value(), $context->fields()));

        $reflection = new \ReflectionClass(HttpServerDiagnostic::class);
        self::assertSame(ApiStatus::Internal, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        $methods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), static fn(\ReflectionMethod $method): bool => $method->isUserDefined() && HttpServerDiagnostic::class === $method->getDeclaringClass()->getName()),
        );
        sort($methods);
        self::assertSame(['context', 'contextFields', 'contextOperation', 'contextViolatedInvariant', 'remediation', 'title'], $methods);
    }
}
