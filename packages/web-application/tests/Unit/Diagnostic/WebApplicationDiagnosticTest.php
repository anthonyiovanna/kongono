<?php

declare(strict_types=1);

namespace Kongono\Web\Application\Tests\Unit\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic;
use Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic;
use Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Server\Diagnostic\HttpServerDiagnostic;
use Kongono\Kernel\Diagnostic\LifecycleDiagnostic;
use Kongono\Routing\Diagnostic\RoutingDiagnostic;
use Kongono\Web\Application\Diagnostic\WebApplicationDiagnostic;
use PHPUnit\Framework\TestCase;

final class WebApplicationDiagnosticTest extends TestCase
{
    public function testCatalogIdentityAndEmptySchemasAreExact(): void
    {
        $expected = [
            'ApplicationRetired' => [
                'KGN-WEB-APPLICATION-1001',
                'Web application is retired',
                'Replace the retired application instance before accepting more work',
                'Use web application lifecycle',
                'A retired application must not accept handling or shutdown work',
            ],
            'OperationAndCleanupFailed' => [
                'KGN-WEB-APPLICATION-1002',
                'Application work and operation cleanup both failed',
                'Retire the application instance and inspect both failure objects',
                'Complete web application operation',
                'Primary and cleanup failures must both remain observable',
            ],
        ];

        self::assertSame(array_keys($expected), array_map(
            static fn(WebApplicationDiagnostic $diagnostic): string => $diagnostic->name,
            WebApplicationDiagnostic::cases(),
        ));
        foreach (WebApplicationDiagnostic::cases() as $diagnostic) {
            [$id, $title, $remediation, $operation, $invariant] = $expected[$diagnostic->name];
            self::assertSame($id, $diagnostic->value);
            self::assertSame($title, $diagnostic->title());
            self::assertSame($remediation, $diagnostic->remediation());
            self::assertSame($operation, $diagnostic->contextOperation());
            self::assertSame($invariant, $diagnostic->contextViolatedInvariant());
            self::assertSame([], $diagnostic->contextFields());
            self::assertSame($operation, $diagnostic->context()->operation());
            self::assertSame($invariant, $diagnostic->context()->violatedInvariant());
            self::assertSame([], $diagnostic->context()->fields());
            self::assertMatchesRegularExpression('/^KGN-WEB-APPLICATION-1\d{3}$/D', $diagnostic->value);
        }
    }

    public function testRepositoryDiagnosticIdentifiersRemainUnique(): void
    {
        $catalogs = [
            LifecycleDiagnostic::cases(),
            GraphDiagnostic::cases(),
            DependencyInjectionDiagnostic::cases(),
            DiagnosticsDiagnostic::cases(),
            DiagnosticPresentationDiagnostic::cases(),
            HttpMessageDiagnostic::cases(),
            RoutingDiagnostic::cases(),
            HttpServerDiagnostic::cases(),
            WebApplicationDiagnostic::cases(),
        ];
        $identifiers = [];
        foreach ($catalogs as $cases) {
            foreach ($cases as $diagnostic) {
                $identifiers[] = $diagnostic->value;
            }
        }

        self::assertCount(111, $identifiers);
        self::assertCount(111, array_unique($identifiers));
    }

    public function testEnumShapeStatusAndPackageOwnedInputsAreExact(): void
    {
        $reflection = new \ReflectionClass(WebApplicationDiagnostic::class);
        self::assertSame(ApiStatus::Internal, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        $properties = array_map(
            static fn(\ReflectionProperty $property): string => $property->getName(),
            array_filter(
                $reflection->getProperties(),
                static fn(\ReflectionProperty $property): bool => WebApplicationDiagnostic::class === $property->getDeclaringClass()->getName(),
            ),
        );
        sort($properties);
        self::assertSame(['name', 'value'], $properties);
        $methods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter(
                $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
                static fn(\ReflectionMethod $method): bool => $method->isUserDefined() && WebApplicationDiagnostic::class === $method->getDeclaringClass()->getName(),
            ),
        );
        sort($methods);
        self::assertSame(['context', 'contextFields', 'contextOperation', 'contextViolatedInvariant', 'remediation', 'title'], $methods);
        foreach (array_filter($reflection->getMethods(), static fn(\ReflectionMethod $method): bool => $method->isUserDefined() && WebApplicationDiagnostic::class === $method->getDeclaringClass()->getName()) as $method) {
            foreach ($method->getParameters() as $parameter) {
                self::fail(sprintf('Diagnostic method %s unexpectedly accepts %s.', $method->getName(), $parameter->getName()));
            }
        }
        self::assertFalse($reflection->hasMethod('__toString'));
    }
}
