<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Tests\Unit\Diagnostic;

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
use Kongono\Web\Runtime\Diagnostic\WebRuntimeDiagnostic;
use PHPUnit\Framework\TestCase;

final class WebRuntimeDiagnosticTest extends TestCase
{
    public function testCatalogIdentityProseAndEmptySchemasAreExact(): void
    {
        $expected = [
            'RuntimeRetired' => [
                'KGN-WEB-RUNTIME-1001',
                'Web runtime adapter is retired',
                'Replace the retired runtime adapter before accepting more deliveries',
                'Use web runtime adapter',
                'A retired runtime adapter must not accept another delivery',
            ],
            'DeliveryAndCleanupFailed' => [
                'KGN-WEB-RUNTIME-1002',
                'Web delivery and transport cleanup both failed',
                'Retire the adapter and inspect both exact failure objects',
                'Complete web runtime delivery',
                'Delivery and cleanup failures must both remain observable',
            ],
        ];

        self::assertSame(array_keys($expected), array_map(
            static fn(WebRuntimeDiagnostic $diagnostic): string => $diagnostic->name,
            WebRuntimeDiagnostic::cases(),
        ));
        foreach (WebRuntimeDiagnostic::cases() as $diagnostic) {
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
        }
    }

    public function testRepositoryDiagnosticIdentifiersAndDomainSplitAreExact(): void
    {
        $catalogs = [
            LifecycleDiagnostic::cases(),
            GraphDiagnostic::cases(),
            DependencyInjectionDiagnostic::cases(),
            DiagnosticPresentationDiagnostic::cases(),
            DiagnosticsDiagnostic::cases(),
            HttpMessageDiagnostic::cases(),
            RoutingDiagnostic::cases(),
            HttpServerDiagnostic::cases(),
            WebApplicationDiagnostic::cases(),
            WebRuntimeDiagnostic::cases(),
        ];
        $identifiers = [];
        foreach ($catalogs as $cases) {
            foreach ($cases as $diagnostic) {
                $identifiers[] = $diagnostic->value;
            }
        }

        self::assertCount(113, $identifiers);
        self::assertCount(113, array_unique($identifiers));
        foreach ([
            'KERNEL' => 6,
            'GRAPH' => 11,
            'DI' => 25,
            'PRESENTATION' => 15,
            'DIAGNOSTICS' => 11,
            'HTTP' => 28,
            'HTTP-SERVER' => 3,
            'ROUTING' => 10,
            'WEB-APPLICATION' => 2,
            'WEB-RUNTIME' => 2,
        ] as $domain => $count) {
            self::assertCount($count, array_filter(
                $identifiers,
                static fn(string $identifier): bool => 1 === preg_match('/^KGN-' . preg_quote($domain, '/') . '-1\d{3}$/D', $identifier),
            ));
        }
    }

    public function testEnumShapeStatusAndMethodInventoryAreExact(): void
    {
        $reflection = new \ReflectionClass(WebRuntimeDiagnostic::class);
        self::assertSame(ApiStatus::Internal, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertSame(['name', 'value'], $this->declaredPropertyNames($reflection));
        self::assertSame(
            ['context', 'contextFields', 'contextOperation', 'contextViolatedInvariant', 'remediation', 'title'],
            $this->declaredPublicMethodNames($reflection),
        );
        foreach ($reflection->getMethods() as $method) {
            if ($method->isUserDefined() && WebRuntimeDiagnostic::class === $method->getDeclaringClass()->getName()) {
                self::assertSame([], $method->getParameters());
            }
        }
        self::assertFalse($reflection->hasMethod('__toString'));
    }

    /**
     * @template T of object
     * @param \ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPropertyNames(\ReflectionClass $reflection): array
    {
        $names = array_map(
            static fn(\ReflectionProperty $property): string => $property->getName(),
            array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => $reflection->getName() === $property->getDeclaringClass()->getName()),
        );
        sort($names);

        return $names;
    }

    /**
     * @template T of object
     * @param \ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPublicMethodNames(\ReflectionClass $reflection): array
    {
        $names = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter(
                $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
                static fn(\ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName()
                    && !in_array($method->getName(), ['cases', 'from', 'tryFrom'], true),
            ),
        );
        sort($names);

        return $names;
    }
}
