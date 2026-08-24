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
            'InvalidGeneratedIntegration' => [
                'KGN-WEB-RUNTIME-1003',
                'Invalid generated web-runtime integration',
                'Regenerate the exact supported web profile and supply its ordered typed startup inputs',
                'Start generated web runtime integration',
                'The loaded generated assembly must expose exactly one supported web root and no operation roots',
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
            $context = WebRuntimeDiagnostic::InvalidGeneratedIntegration === $diagnostic
                ? $diagnostic->context('application_root_shape')
                : $diagnostic->context();
            self::assertSame($operation, $context->operation());
            self::assertSame($invariant, $context->violatedInvariant());
            self::assertCount(WebRuntimeDiagnostic::InvalidGeneratedIntegration === $diagnostic ? 1 : 0, $context->fields());
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

        self::assertCount(114, $identifiers);
        self::assertCount(114, array_unique($identifiers));
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
            'WEB-RUNTIME' => 3,
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
        self::assertSame(['?string'], array_map(
            static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(),
            $reflection->getMethod('context')->getParameters(),
        ));
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
