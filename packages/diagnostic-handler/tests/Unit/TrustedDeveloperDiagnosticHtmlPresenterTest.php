<?php

declare(strict_types=1);

namespace Kongono\DiagnosticHandler\Tests\Unit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\ApplicationGraph\Exception\InvalidGraphException;
use Kongono\ApplicationGraph\Model\ApplicationGraph;
use Kongono\ApplicationGraph\Model\Node;
use Kongono\ApplicationGraph\Model\NodeKey;
use Kongono\ApplicationGraph\Model\NodeKind;
use Kongono\DependencyInjection\Definition\ServiceDefinition;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DependencyInjection\Exception\InvalidDefinitionException;
use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility;
use Kongono\DiagnosticHandler\Tests\Fixture\ExplodingKernelDiagnosticException;
use Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter;
use Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticProjector;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use Kongono\Kernel\Exception\LifecycleException;
use Kongono\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;
use stdClass;
use Throwable;

final class TrustedDeveloperDiagnosticHtmlPresenterTest extends TestCase
{
    public function testDeclarationShapeAndExperimentalStatusAreExact(): void
    {
        $reflection = new ReflectionClass(TrustedDeveloperDiagnosticHtmlPresenter::class);
        $methods = array_values(array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn(ReflectionMethod $method): bool => TrustedDeveloperDiagnosticHtmlPresenter::class === $method->getDeclaringClass()->getName(),
        ));
        $attributes = $reflection->getAttributes(Api::class);
        $parameterType = $methods[0]->getParameters()[1]->getType();

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertFalse($reflection->getParentClass());
        self::assertSame([], $reflection->getInterfaceNames());
        self::assertNull($reflection->getConstructor());
        self::assertSame([], $reflection->getProperties());
        self::assertSame([], $reflection->getConstants());
        self::assertCount(1, $attributes);
        self::assertSame(ApiStatus::Experimental, $attributes[0]->newInstance()->status);
        self::assertCount(1, $methods);
        self::assertSame('renderIfEligible', $methods[0]->getName());
        self::assertCount(2, $methods[0]->getParameters());
        self::assertSame(TrustedDeveloperPresentationEligibility::class, (string) $methods[0]->getParameters()[0]->getType());
        self::assertInstanceOf(ReflectionUnionType::class, $parameterType);

        $sourceTypes = [];
        foreach ($parameterType->getTypes() as $type) {
            self::assertInstanceOf(ReflectionNamedType::class, $type);
            $sourceTypes[] = $type->getName();
        }
        sort($sourceTypes);
        $expectedTypes = [
            DependencyInjectionExceptionInterface::class,
            GraphExceptionInterface::class,
            KernelExceptionInterface::class,
        ];
        sort($expectedTypes);

        self::assertSame($expectedTypes, $sourceTypes);
        self::assertSame('?string', (string) $methods[0]->getReturnType());
    }

    public function testEveryDeniedPermissionCombinationReturnsNullWithoutSourceAccess(): void
    {
        $presenter = new TrustedDeveloperDiagnosticHtmlPresenter();
        $source = new ExplodingKernelDiagnosticException(new RuntimeException('source access sentinel'));

        foreach ([
            [TrustedDeveloperBootstrapPermission::Denied, AdapterControlledDeliveryPermission::Denied],
            [TrustedDeveloperBootstrapPermission::Denied, AdapterControlledDeliveryPermission::Allowed],
            [TrustedDeveloperBootstrapPermission::Allowed, AdapterControlledDeliveryPermission::Denied],
        ] as [$bootstrapPermission, $deliveryPermission]) {
            self::assertNull($presenter->renderIfEligible(
                new TrustedDeveloperPresentationEligibility($bootstrapPermission, $deliveryPermission),
                $source,
            ));
        }
    }

    public function testAllowedKernelFailureReturnsTheExactExistingPipelineHtml(): void
    {
        $source = $this->kernelFailure();

        self::assertSame(
            $this->existingPipelineHtml($source),
            (new TrustedDeveloperDiagnosticHtmlPresenter())->renderIfEligible($this->allowedEligibility(), $source),
        );
    }

    public function testAllowedGraphFailureReturnsTheExactExistingPipelineHtml(): void
    {
        $source = $this->graphFailure();

        self::assertSame(
            $this->existingPipelineHtml($source),
            (new TrustedDeveloperDiagnosticHtmlPresenter())->renderIfEligible($this->allowedEligibility(), $source),
        );
    }

    public function testAllowedDependencyInjectionFailureReturnsTheExactExistingPipelineHtml(): void
    {
        $source = $this->dependencyInjectionFailure();

        self::assertSame(
            $this->existingPipelineHtml($source),
            (new TrustedDeveloperDiagnosticHtmlPresenter())->renderIfEligible($this->allowedEligibility(), $source),
        );
    }

    public function testAllowedSourceAccessorThrowablePropagatesWithIdentity(): void
    {
        $sentinel = new RuntimeException('source access sentinel');

        try {
            (new TrustedDeveloperDiagnosticHtmlPresenter())->renderIfEligible(
                $this->allowedEligibility(),
                new ExplodingKernelDiagnosticException($sentinel),
            );
            self::fail('The source accessor throwable must propagate.');
        } catch (Throwable $caught) {
            self::assertSame($sentinel, $caught);
        }
    }

    public function testRepeatedAndInterleavedCallsRetainNoState(): void
    {
        $presenter = new TrustedDeveloperDiagnosticHtmlPresenter();
        $kernel = $this->kernelFailure();
        $graph = $this->graphFailure();
        $dependencyInjection = $this->dependencyInjectionFailure();
        $kernelHtml = $this->existingPipelineHtml($kernel);
        $graphHtml = $this->existingPipelineHtml($graph);
        $dependencyInjectionHtml = $this->existingPipelineHtml($dependencyInjection);

        self::assertSame($kernelHtml, $presenter->renderIfEligible($this->allowedEligibility(), $kernel));
        self::assertSame($kernelHtml, $presenter->renderIfEligible($this->allowedEligibility(), $kernel));
        self::assertSame($graphHtml, $presenter->renderIfEligible($this->allowedEligibility(), $graph));
        self::assertSame($dependencyInjectionHtml, $presenter->renderIfEligible($this->allowedEligibility(), $dependencyInjection));
        self::assertSame($kernelHtml, $presenter->renderIfEligible($this->allowedEligibility(), $kernel));
        self::assertNotSame($kernelHtml, $graphHtml);
        self::assertNotSame($graphHtml, $dependencyInjectionHtml);
        self::assertSame([], (new ReflectionClass($presenter))->getProperties());
    }

    private function allowedEligibility(): TrustedDeveloperPresentationEligibility
    {
        return new TrustedDeveloperPresentationEligibility(
            TrustedDeveloperBootstrapPermission::Allowed,
            AdapterControlledDeliveryPermission::Allowed,
        );
    }

    private function existingPipelineHtml(
        KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface $source,
    ): string {
        $diagnostic = (new TrustedDeveloperDiagnosticProjector())->project($source);

        return (new TrustedDeveloperHtmlRenderer())->render($diagnostic);
    }

    private function kernelFailure(): LifecycleException
    {
        $kernel = new Kernel();
        $kernel->boot();

        try {
            $kernel->boot();
            self::fail('The real Kernel lifecycle path must fail.');
        } catch (LifecycleException $exception) {
            return $exception;
        }
    }

    private function graphFailure(): InvalidGraphException
    {
        $node = new Node(new NodeKey('duplicate'), new NodeKind('service'));

        try {
            new ApplicationGraph([$node, $node], []);
            self::fail('The real Application Graph validation path must fail.');
        } catch (InvalidGraphException $exception) {
            return $exception;
        }
    }

    private function dependencyInjectionFailure(): InvalidDefinitionException
    {
        try {
            new ServiceDefinition(Throwable::class, stdClass::class, []);
            self::fail('The real dependency-definition validation path must fail.');
        } catch (InvalidDefinitionException $exception) {
            return $exception;
        }
    }
}
