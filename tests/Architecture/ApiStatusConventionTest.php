<?php

declare(strict_types=1);

namespace Kongono\Tests\Architecture;

use Attribute;
use FilesystemIterator;
use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic;
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\ApplicationGraph\Exception\GraphException;
use Kongono\ApplicationGraph\Exception\InvalidGraphException;
use Kongono\DependencyInjection\Contract\ResolverInterface;
use Kongono\DependencyInjection\Definition\ServiceDefinition;
use Kongono\DependencyInjection\DependencyResolver;
use Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic;
use Kongono\DependencyInjection\Exception\DependencyInjectionException;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DependencyInjection\Exception\InvalidDefinitionException;
use Kongono\DependencyInjection\Exception\UnknownServiceException;
use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility;
use Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter;
use Kongono\DiagnosticPresentation\Developer\DeveloperDiagnosticOwner;
use Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnostic;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticField;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticProjector;
use Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic;
use Kongono\DiagnosticPresentation\Exception\DiagnosticPresentationException;
use Kongono\DiagnosticPresentation\Exception\ExceptionInterface as DiagnosticPresentationExceptionInterface;
use Kongono\DiagnosticPresentation\Exception\InvalidDiagnosticProjectionException;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticField;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use Kongono\Diagnostics\Exception\DiagnosticsException;
use Kongono\Diagnostics\Exception\ExceptionInterface as DiagnosticsExceptionInterface;
use Kongono\Diagnostics\Exception\InvalidDiagnosticValueException;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\ExceptionInterface as HttpMessageExceptionInterface;
use Kongono\Http\Message\Exception\HttpMessageRuntimeException;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Server\Diagnostic\HttpServerDiagnostic;
use Kongono\Http\Server\Exception\ExceptionInterface as HttpServerExceptionInterface;
use Kongono\Http\Server\Exception\InvalidHttpServerDefinitionException;
use Kongono\Http\Server\Internal\MiddlewareLayer;
use Kongono\Http\Server\MiddlewarePipeline;
use Kongono\Http\Server\RoutingRequestHandler;
use Kongono\Kernel\Contract\KernelInterface;
use Kongono\Kernel\Diagnostic\LifecycleDiagnostic;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use Kongono\Kernel\Exception\KernelException;
use Kongono\Kernel\Exception\LifecycleException;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Routing\Diagnostic\RoutingDiagnostic;
use Kongono\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Kongono\Routing\Exception\InvalidRouteDefinitionException;
use Kongono\Routing\Exception\InvalidRouteRequestException;
use Kongono\Routing\Internal\RouteSyntax;
use Kongono\Routing\Match\RouteMatch;
use Kongono\Routing\Matcher\RouteMatcher;
use Kongono\Routing\Matcher\RouteMatcherInterface;
use Kongono\Web\Application\Diagnostic\WebApplicationDiagnostic;
use Kongono\Web\Application\Exception\ExceptionInterface as WebApplicationExceptionInterface;
use Kongono\Web\Application\Exception\OperationCleanupException;
use Kongono\Web\Application\Exception\RetiredWebApplicationException;
use Kongono\Web\Application\Exception\WebApplicationException;
use Kongono\Web\Application\WebApplication;
use Kongono\Web\Runtime\Contract\WebDeliveryInterface;
use Kongono\Web\Runtime\Diagnostic\WebRuntimeDiagnostic;
use Kongono\Web\Runtime\Exception\DeliveryCleanupException;
use Kongono\Web\Runtime\Exception\ExceptionInterface as WebRuntimeExceptionInterface;
use Kongono\Web\Runtime\Exception\RetiredWebRuntimeException;
use Kongono\Web\Runtime\Exception\WebRuntimeException;
use Kongono\Web\Runtime\Internal\FailureResponseFactory;
use Kongono\Web\Runtime\WebRuntimeAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use SplFileInfo;
use Throwable;

final class ApiStatusConventionTest extends TestCase
{
    /** @var array<string, ApiStatus> */
    private const EXPECTED_STATUS = [
        GraphDiagnostic::class => ApiStatus::Internal,
        'Kongono\\ApplicationGraph\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ApplicationGraph\\Exception\\GraphException' => ApiStatus::Internal,
        'Kongono\\ApplicationGraph\\Exception\\InvalidGraphException' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraph\\Model\\ApplicationGraph' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraph\\Model\\Node' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraph\\Model\\NodeKey' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraph\\Model\\NodeKind' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraph\\Model\\Relation' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraph\\Model\\RelationKind' => ApiStatus::Experimental,
        ResolverInterface::class => ApiStatus::Experimental,
        ServiceDefinition::class => ApiStatus::Experimental,
        DependencyResolver::class => ApiStatus::Experimental,
        DependencyInjectionDiagnostic::class => ApiStatus::Internal,
        DependencyInjectionExceptionInterface::class => ApiStatus::Public,
        DependencyInjectionException::class => ApiStatus::Internal,
        InvalidDefinitionException::class => ApiStatus::Experimental,
        UnknownServiceException::class => ApiStatus::Experimental,
        AdapterControlledDeliveryPermission::class => ApiStatus::Experimental,
        TrustedDeveloperBootstrapPermission::class => ApiStatus::Experimental,
        TrustedDeveloperPresentationEligibility::class => ApiStatus::Experimental,
        TrustedDeveloperDiagnosticHtmlPresenter::class => ApiStatus::Experimental,
        DeveloperDiagnosticOwner::class => ApiStatus::Experimental,
        TrustedDeveloperDiagnostic::class => ApiStatus::Experimental,
        TrustedDeveloperDiagnosticField::class => ApiStatus::Experimental,
        TrustedDeveloperDiagnosticProjector::class => ApiStatus::Experimental,
        TrustedDeveloperHtmlRenderer::class => ApiStatus::Experimental,
        DiagnosticPresentationDiagnostic::class => ApiStatus::Internal,
        DiagnosticPresentationException::class => ApiStatus::Internal,
        DiagnosticPresentationExceptionInterface::class => ApiStatus::Public,
        InvalidDiagnosticProjectionException::class => ApiStatus::Experimental,
        DiagnosticContext::class => ApiStatus::Public,
        DiagnosticContextSchema::class => ApiStatus::Public,
        DiagnosticDisclosure::class => ApiStatus::Public,
        DiagnosticField::class => ApiStatus::Public,
        DiagnosticFieldSchema::class => ApiStatus::Public,
        DiagnosticValueKind::class => ApiStatus::Public,
        DiagnosticsDiagnostic::class => ApiStatus::Internal,
        DiagnosticsException::class => ApiStatus::Internal,
        DiagnosticsExceptionInterface::class => ApiStatus::Public,
        InvalidDiagnosticValueException::class => ApiStatus::Experimental,
        Api::class => ApiStatus::Public,
        ApiStatus::class => ApiStatus::Public,
        LifecycleDiagnostic::class => ApiStatus::Internal,
        'Kongono\\Kernel\\Contract\\KernelInterface' => ApiStatus::Experimental,
        'Kongono\\Kernel\\Contract\\KernelLifecycleInterface' => ApiStatus::Experimental,
        'Kongono\\Kernel\\Contract\\OperationLifecycleInterface' => ApiStatus::Experimental,
        'Kongono\\Kernel\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\Kernel\\Exception\\KernelException' => ApiStatus::Internal,
        'Kongono\\Kernel\\Exception\\LifecycleException' => ApiStatus::Experimental,
        'Kongono\\Kernel\\Kernel' => ApiStatus::Experimental,
        'Kongono\\Kernel\\Lifecycle\\KernelState' => ApiStatus::Experimental,
        'Kongono\\Kernel\\Lifecycle\\OperationScope' => ApiStatus::Experimental,
        HttpMessageDiagnostic::class => ApiStatus::Internal,
        'Kongono\\Http\\Message\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\Http\\Message\\Exception\\HttpMessageRuntimeException' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Exception\\InvalidMessageArgumentException' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Factory\\ResponseFactory' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Factory\\ServerRequestFactory' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Factory\\StreamFactory' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Factory\\UploadedFileFactory' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Factory\\UriFactory' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Internal\\HeaderCollection' => ApiStatus::Internal,
        'Kongono\\Http\\Message\\Internal\\HttpMessageValidation' => ApiStatus::Internal,
        'Kongono\\Http\\Message\\Message\\AbstractMessage' => ApiStatus::Internal,
        'Kongono\\Http\\Message\\Message\\Response' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Message\\ServerRequest' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Message\\Stream' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Message\\UploadedFile' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Message\\Uri' => ApiStatus::Experimental,
        HttpServerDiagnostic::class => ApiStatus::Internal,
        HttpServerExceptionInterface::class => ApiStatus::Public,
        InvalidHttpServerDefinitionException::class => ApiStatus::Experimental,
        MiddlewareLayer::class => ApiStatus::Internal,
        MiddlewarePipeline::class => ApiStatus::Experimental,
        RoutingRequestHandler::class => ApiStatus::Experimental,
        RoutingDiagnostic::class => ApiStatus::Internal,
        RoutingExceptionInterface::class => ApiStatus::Public,
        InvalidRouteDefinitionException::class => ApiStatus::Experimental,
        InvalidRouteRequestException::class => ApiStatus::Experimental,
        RouteDefinition::class => ApiStatus::Experimental,
        RouteTable::class => ApiStatus::Experimental,
        RouteMatch::class => ApiStatus::Experimental,
        RouteMatcherInterface::class => ApiStatus::Experimental,
        RouteMatcher::class => ApiStatus::Experimental,
        RouteSyntax::class => ApiStatus::Internal,
        WebApplicationDiagnostic::class => ApiStatus::Internal,
        WebApplicationExceptionInterface::class => ApiStatus::Public,
        WebApplicationException::class => ApiStatus::Internal,
        OperationCleanupException::class => ApiStatus::Experimental,
        RetiredWebApplicationException::class => ApiStatus::Experimental,
        WebApplication::class => ApiStatus::Experimental,
        WebDeliveryInterface::class => ApiStatus::Public,
        WebRuntimeDiagnostic::class => ApiStatus::Internal,
        WebRuntimeExceptionInterface::class => ApiStatus::Public,
        WebRuntimeException::class => ApiStatus::Internal,
        RetiredWebRuntimeException::class => ApiStatus::Experimental,
        DeliveryCleanupException::class => ApiStatus::Experimental,
        FailureResponseFactory::class => ApiStatus::Internal,
        WebRuntimeAdapter::class => ApiStatus::Experimental,
    ];

    /** @var list<class-string> */
    private const BOOTSTRAP_TYPES = [
        Api::class,
        ApiStatus::class,
    ];

    public function testProductionDeclarationsExactlyMatchAuthorizedInventoryAndAreAutoloadable(): void
    {
        $types = $this->discoverProductionTypes();
        self::assertCount(100, $types);

        self::assertFalse(class_exists('Kongono\\Kernel\\Api\\Api'));
        self::assertFalse(enum_exists('Kongono\\Kernel\\Api\\ApiStatus'));

        foreach ($types as $type) {
            self::assertSame($type, $this->autoloadedClassLike($type));
        }

        $expected = array_keys(self::EXPECTED_STATUS);
        sort($expected);

        self::assertSame($expected, $types);
    }

    public function testEveryNonBootstrapTypeHasExactlyOneExpectedApiStatus(): void
    {
        foreach ($this->discoverProductionTypes() as $type) {
            $reflection = new ReflectionClass($this->autoloadedClassLike($type));
            $attributes = $reflection->getAttributes(Api::class);

            if (in_array($type, self::BOOTSTRAP_TYPES, true)) {
                self::assertCount(0, $attributes, sprintf('%s is a documented bootstrap exception.', $type));
                self::assertSame(ApiStatus::Public, self::EXPECTED_STATUS[$type]);

                continue;
            }

            self::assertCount(1, $attributes, sprintf('%s must have exactly one API status.', $type));
            $attribute = $attributes[0]->newInstance();
            self::assertInstanceOf(Api::class, $attribute);
            self::assertInstanceOf(ApiStatus::class, $attribute->status);
            self::assertSame(self::EXPECTED_STATUS[$type], $attribute->status);
        }
    }

    public function testApiStatusDefinesOnlyTheAuthorizedCasesAndValues(): void
    {
        $actual = [];

        foreach (ApiStatus::cases() as $status) {
            $actual[$status->name] = $status->value;
        }

        self::assertSame([
            'Internal' => 'internal',
            'Experimental' => 'experimental',
            'Public' => 'public',
        ], $actual);
    }

    public function testApiAttributeIsNonRepeatableAndTargetsClassLikeDeclarations(): void
    {
        $reflection = new ReflectionClass(Api::class);
        $attributes = $reflection->getAttributes(Attribute::class);

        self::assertCount(1, $attributes);
        $declaration = $attributes[0]->newInstance();
        self::assertInstanceOf(Attribute::class, $declaration);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertSame(Attribute::TARGET_CLASS, $declaration->flags);
        self::assertSame(0, $declaration->flags & Attribute::IS_REPEATABLE);
    }

    public function testPackageExceptionMarkersArePairwiseDistinctAndConcreteFailuresStaySeparated(): void
    {
        $markers = [
            KernelExceptionInterface::class,
            GraphExceptionInterface::class,
            DependencyInjectionExceptionInterface::class,
            DiagnosticPresentationExceptionInterface::class,
            DiagnosticsExceptionInterface::class,
            HttpMessageExceptionInterface::class,
            HttpServerExceptionInterface::class,
            RoutingExceptionInterface::class,
            WebApplicationExceptionInterface::class,
            WebRuntimeExceptionInterface::class,
        ];

        foreach ($markers as $position => $marker) {
            foreach (array_slice($markers, $position + 1) as $otherMarker) {
                self::assertMarkersDistinct($marker, $otherMarker);
            }
        }

        foreach ([
            [LifecycleException::class, KernelExceptionInterface::class],
            [InvalidGraphException::class, GraphExceptionInterface::class],
            [InvalidDefinitionException::class, DependencyInjectionExceptionInterface::class],
            [UnknownServiceException::class, DependencyInjectionExceptionInterface::class],
            [InvalidDiagnosticProjectionException::class, DiagnosticPresentationExceptionInterface::class],
            [InvalidDiagnosticValueException::class, DiagnosticsExceptionInterface::class],
            [InvalidMessageArgumentException::class, HttpMessageExceptionInterface::class],
            [HttpMessageRuntimeException::class, HttpMessageExceptionInterface::class],
            [InvalidRouteDefinitionException::class, RoutingExceptionInterface::class],
            [InvalidRouteRequestException::class, RoutingExceptionInterface::class],
            [InvalidHttpServerDefinitionException::class, HttpServerExceptionInterface::class],
            [OperationCleanupException::class, WebApplicationExceptionInterface::class],
            [RetiredWebApplicationException::class, WebApplicationExceptionInterface::class],
            [RetiredWebRuntimeException::class, WebRuntimeExceptionInterface::class],
            [DeliveryCleanupException::class, WebRuntimeExceptionInterface::class],
        ] as [$failure, $expectedMarker]) {
            self::assertExceptionMarkerMembership($failure, $expectedMarker, array_values(array_diff($markers, [$expectedMarker])));
        }
    }

    public function testDiagnosticCatalogsAndPackageExceptionShapesAreExact(): void
    {
        $identifiers = array_map(
            static fn(\BackedEnum $diagnostic): string => $diagnostic->value,
            [
                ...LifecycleDiagnostic::cases(),
                ...GraphDiagnostic::cases(),
                ...DependencyInjectionDiagnostic::cases(),
                ...DiagnosticPresentationDiagnostic::cases(),
                ...DiagnosticsDiagnostic::cases(),
                ...HttpMessageDiagnostic::cases(),
                ...HttpServerDiagnostic::cases(),
                ...RoutingDiagnostic::cases(),
                ...WebApplicationDiagnostic::cases(),
                ...WebRuntimeDiagnostic::cases(),
            ],
        );

        self::assertCount(113, $identifiers);
        self::assertCount(113, array_unique($identifiers));
        self::assertCount(6, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-KERNEL-1\\d{3}$/D', $identifier)));
        self::assertCount(11, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-GRAPH-1\\d{3}$/D', $identifier)));
        self::assertCount(25, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-DI-1\\d{3}$/D', $identifier)));
        self::assertCount(15, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-PRESENTATION-1\\d{3}$/D', $identifier)));
        self::assertCount(11, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-DIAGNOSTICS-1\\d{3}$/D', $identifier)));
        self::assertCount(28, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-HTTP-1\\d{3}$/D', $identifier)));
        self::assertCount(3, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-HTTP-SERVER-1\\d{3}$/D', $identifier)));
        self::assertCount(10, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-ROUTING-1\\d{3}$/D', $identifier)));
        self::assertCount(2, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-WEB-APPLICATION-1\\d{3}$/D', $identifier)));
        self::assertCount(2, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-WEB-RUNTIME-1\\d{3}$/D', $identifier)));

        foreach ([
            KernelExceptionInterface::class,
            GraphExceptionInterface::class,
            DependencyInjectionExceptionInterface::class,
            DiagnosticPresentationExceptionInterface::class,
            DiagnosticsExceptionInterface::class,
            HttpMessageExceptionInterface::class,
            HttpServerExceptionInterface::class,
            RoutingExceptionInterface::class,
            WebApplicationExceptionInterface::class,
            WebRuntimeExceptionInterface::class,
        ] as $marker) {
            $reflection = new ReflectionClass($marker);
            $methods = array_values(array_filter(
                $reflection->getMethods(),
                static fn(ReflectionMethod $method): bool => $marker === $method->getDeclaringClass()->getName(),
            ));
            $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
            sort($methodNames);

            self::assertSame([Throwable::class, \Stringable::class], $reflection->getInterfaceNames());
            $expectedMethods = in_array($marker, [KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class, HttpMessageExceptionInterface::class, HttpServerExceptionInterface::class, RoutingExceptionInterface::class, WebApplicationExceptionInterface::class, WebRuntimeExceptionInterface::class], true)
                ? ['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle']
                : ['diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'];
            self::assertSame($expectedMethods, $methodNames);

            foreach ($methods as $method) {
                $expectedReturnType = in_array($marker, [KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class, HttpMessageExceptionInterface::class, HttpServerExceptionInterface::class, RoutingExceptionInterface::class, WebApplicationExceptionInterface::class, WebRuntimeExceptionInterface::class], true)
                    && 'diagnosticContext' === $method->getName()
                    ? DiagnosticContext::class
                    : 'string';
                self::assertSame($expectedReturnType, (string) $method->getReturnType());
                self::assertSame([], $method->getParameters());
            }
        }

        foreach ([
            [KernelException::class, LifecycleDiagnostic::class],
            [GraphException::class, GraphDiagnostic::class],
            [DependencyInjectionException::class, DependencyInjectionDiagnostic::class],
            [DiagnosticPresentationException::class, DiagnosticPresentationDiagnostic::class],
            [DiagnosticsException::class, DiagnosticsDiagnostic::class],
        ] as [$base, $diagnostic]) {
            $reflection = new ReflectionClass($base);
            $properties = array_values(array_filter(
                $reflection->getProperties(),
                static fn(\ReflectionProperty $property): bool => $base === $property->getDeclaringClass()->getName(),
            ));
            $methods = array_values(array_filter(
                $reflection->getMethods(),
                static fn(ReflectionMethod $method): bool => $base === $method->getDeclaringClass()->getName(),
            ));
            $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
            sort($methodNames);

            self::assertTrue($reflection->isAbstract());
            usort($properties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));

            if (in_array($base, [KernelException::class, GraphException::class, DependencyInjectionException::class], true)) {
                self::assertSame(['diagnostic', 'diagnosticContext'], array_map(
                    static fn(\ReflectionProperty $property): string => $property->getName(),
                    $properties,
                ));
                self::assertSame([$diagnostic, DiagnosticContext::class], array_map(
                    static fn(\ReflectionProperty $property): string => (string) $property->getType(),
                    $properties,
                ));
                self::assertSame(['__construct', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $methodNames);
            } else {
                self::assertCount(1, $properties);
                self::assertSame('diagnostic', $properties[0]->getName());
                self::assertSame($diagnostic, (string) $properties[0]->getType());
                self::assertSame(['__construct', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $methodNames);
            }

            foreach ($properties as $property) {
                self::assertTrue($property->isPrivate());
                self::assertTrue($property->isReadOnly());
                self::assertFalse($property->isStatic());
            }

            foreach ($methods as $method) {
                self::assertTrue($method->isFinal());
                self::assertTrue($method->isPublic());
            }

            $parameters = $reflection->getConstructor()?->getParameters();
            self::assertNotNull($parameters);
            self::assertCount(in_array($base, [KernelException::class, GraphException::class, DependencyInjectionException::class], true) ? 4 : 3, $parameters);
            self::assertSame($diagnostic, (string) $parameters[0]->getType());
            self::assertSame('string', (string) $parameters[1]->getType());

            $previousParameter = $parameters[2];

            if (in_array($base, [KernelException::class, GraphException::class, DependencyInjectionException::class], true)) {
                self::assertSame('array', (string) $parameters[2]->getType());
                $previousParameter = $parameters[3];
            }

            self::assertSame('?Throwable', (string) $previousParameter->getType());
            self::assertTrue($previousParameter->isDefaultValueAvailable());
            self::assertNull($previousParameter->getDefaultValue());
        }

        foreach ([InvalidMessageArgumentException::class, HttpMessageRuntimeException::class] as $exceptionType) {
            $reflection = new ReflectionClass($exceptionType);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->implementsInterface(HttpMessageExceptionInterface::class));
            $properties = array_values(array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => $exceptionType === $property->getDeclaringClass()->getName()));
            usort($properties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));
            self::assertSame(['diagnostic', 'diagnosticContext'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties));
            foreach ($properties as $property) {
                self::assertTrue($property->isPrivate());
                self::assertTrue($property->isReadOnly());
            }
            $parameters = $reflection->getConstructor()?->getParameters();
            self::assertNotNull($parameters);
            self::assertSame([HttpMessageDiagnostic::class, 'array', '?Throwable'], array_map(static fn($parameter): string => (string) $parameter->getType(), $parameters));
        }

        foreach ([InvalidRouteDefinitionException::class, InvalidRouteRequestException::class] as $exceptionType) {
            $reflection = new ReflectionClass($exceptionType);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->implementsInterface(RoutingExceptionInterface::class));
            $properties = array_values(array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => $exceptionType === $property->getDeclaringClass()->getName()));
            usort($properties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));
            self::assertSame(['diagnostic', 'diagnosticContext'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties));
            foreach ($properties as $property) {
                self::assertTrue($property->isPrivate());
                self::assertTrue($property->isReadOnly());
            }
            $parameters = $reflection->getConstructor()?->getParameters();
            self::assertNotNull($parameters);
            self::assertSame([RoutingDiagnostic::class, 'array', '?Throwable'], array_map(static fn($parameter): string => (string) $parameter->getType(), $parameters));
        }
        $routeDefinitionParent = (new ReflectionClass(InvalidRouteDefinitionException::class))->getParentClass();
        self::assertNotFalse($routeDefinitionParent);
        self::assertSame(InvalidArgumentException::class, $routeDefinitionParent->getName());
        $routeRequestParent = (new ReflectionClass(InvalidRouteRequestException::class))->getParentClass();
        self::assertNotFalse($routeRequestParent);
        self::assertSame(RuntimeException::class, $routeRequestParent->getName());

        $httpServerException = new ReflectionClass(InvalidHttpServerDefinitionException::class);
        self::assertTrue($httpServerException->isFinal());
        self::assertTrue($httpServerException->implementsInterface(HttpServerExceptionInterface::class));
        $httpServerParent = $httpServerException->getParentClass();
        self::assertNotFalse($httpServerParent);
        self::assertSame(InvalidArgumentException::class, $httpServerParent->getName());
        self::assertSame(['diagnostic', 'diagnosticContext'], $this->declaredPropertyNames($httpServerException));
        self::assertSame([HttpServerDiagnostic::class, 'array', '?Throwable'], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $httpServerException->getConstructor()?->getParameters() ?? [],
        ));

        $httpServerSchemas = [
            'TooManyMiddleware' => ['Build middleware pipeline', 'A middleware pipeline may contain at most 128 components', ['middleware_count', 'maximum_middleware']],
            'InvalidRouteHandlerKeys' => ['Build terminal route handler', 'Handler keys must equal the complete declared route-name set', ['route_count', 'handler_count', 'missing_count', 'unexpected_count']],
            'InvalidRouteHandlerValue' => ['Build terminal route handler', 'Every mapped route value must implement RequestHandlerInterface', ['handler_position', 'violation']],
        ];
        foreach (HttpServerDiagnostic::cases() as $diagnostic) {
            [$operation, $invariant, $fields] = $httpServerSchemas[$diagnostic->name];
            self::assertSame($operation, $diagnostic->contextOperation());
            self::assertSame($invariant, $diagnostic->contextViolatedInvariant());
            self::assertSame($fields, array_map(static fn($field): string => $field->key(), $diagnostic->contextFields()));
        }
        self::assertSame('not_request_handler', HttpServerDiagnostic::InvalidRouteHandlerValue->context([
            'handler_position' => 0,
            'violation' => 'not_request_handler',
        ])->fields()[1]->value());

        $webApplicationBase = new ReflectionClass(WebApplicationException::class);
        self::assertTrue($webApplicationBase->isAbstract());
        $webApplicationBaseParent = $webApplicationBase->getParentClass();
        self::assertNotFalse($webApplicationBaseParent);
        self::assertSame(RuntimeException::class, $webApplicationBaseParent->getName());
        self::assertSame(['diagnostic', 'diagnosticContext'], $this->declaredPropertyNames($webApplicationBase));
        $webApplicationConstructor = $webApplicationBase->getConstructor();
        self::assertNotNull($webApplicationConstructor);
        self::assertTrue($webApplicationConstructor->isProtected());
        self::assertSame([WebApplicationDiagnostic::class, '?Throwable'], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $webApplicationConstructor->getParameters(),
        ));
        foreach (['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'] as $method) {
            self::assertTrue($webApplicationBase->getMethod($method)->isFinal());
        }

        $operationCleanup = new ReflectionClass(OperationCleanupException::class);
        self::assertTrue($operationCleanup->isFinal());
        self::assertSame(['cleanupFailure', 'primaryFailure'], $this->declaredPropertyNames($operationCleanup));
        self::assertSame(['__construct', 'cleanupFailure', 'primaryFailure'], $this->declaredPublicMethodNames($operationCleanup));
        self::assertSame(['Throwable', 'Throwable'], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $operationCleanup->getConstructor()?->getParameters() ?? [],
        ));

        $retiredApplication = new ReflectionClass(RetiredWebApplicationException::class);
        self::assertTrue($retiredApplication->isFinal());
        self::assertSame([], $this->declaredPropertyNames($retiredApplication));
        self::assertSame(['__construct'], $this->declaredPublicMethodNames($retiredApplication));
        self::assertSame([], $retiredApplication->getConstructor()?->getParameters());

        $webApplicationSchemas = [
            'ApplicationRetired' => ['Use web application lifecycle', 'A retired application must not accept handling or shutdown work'],
            'OperationAndCleanupFailed' => ['Complete web application operation', 'Primary and cleanup failures must both remain observable'],
        ];
        foreach (WebApplicationDiagnostic::cases() as $diagnostic) {
            [$operation, $invariant] = $webApplicationSchemas[$diagnostic->name];
            self::assertSame($operation, $diagnostic->contextOperation());
            self::assertSame($invariant, $diagnostic->contextViolatedInvariant());
            self::assertSame([], $diagnostic->contextFields());
            self::assertSame([], $diagnostic->context()->fields());
        }

        $graphManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/application-graph/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($graphManifest);
        self::assertArrayHasKey('require', $graphManifest);
        self::assertIsArray($graphManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5',
        ], $graphManifest['require']);

        $kernelManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/kernel/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($kernelManifest);
        self::assertArrayHasKey('require', $kernelManifest);
        self::assertIsArray($kernelManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5',
        ], $kernelManifest['require']);

        $dependencyInjectionManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/dependency-injection/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($dependencyInjectionManifest);
        self::assertArrayHasKey('require', $dependencyInjectionManifest);
        self::assertIsArray($dependencyInjectionManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5',
        ], $dependencyInjectionManifest['require']);

        $diagnosticsManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/diagnostics/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($diagnosticsManifest);
        self::assertArrayHasKey('require', $diagnosticsManifest);
        self::assertIsArray($diagnosticsManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'php' => '>=8.5',
        ], $diagnosticsManifest['require']);

        $diagnosticActivationManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/diagnostic-activation/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($diagnosticActivationManifest);
        self::assertArrayHasKey('require', $diagnosticActivationManifest);
        self::assertIsArray($diagnosticActivationManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'php' => '>=8.5',
        ], $diagnosticActivationManifest['require']);
        self::assertArrayNotHasKey('kongono/diagnostic-handler', $diagnosticActivationManifest['require']);

        $diagnosticHandlerManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/diagnostic-handler/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($diagnosticHandlerManifest);
        self::assertArrayHasKey('require', $diagnosticHandlerManifest);
        self::assertIsArray($diagnosticHandlerManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/application-graph' => '*',
            'kongono/dependency-injection' => '*',
            'kongono/diagnostic-activation' => '*',
            'kongono/diagnostic-presentation' => '*',
            'kongono/kernel' => '*',
            'php' => '>=8.5',
        ], $diagnosticHandlerManifest['require']);
        self::assertArrayNotHasKey('kongono/diagnostics', $diagnosticHandlerManifest['require']);

        $diagnosticPresentationManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/diagnostic-presentation/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($diagnosticPresentationManifest);
        self::assertArrayHasKey('require', $diagnosticPresentationManifest);
        self::assertIsArray($diagnosticPresentationManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/application-graph' => '*',
            'kongono/dependency-injection' => '*',
            'kongono/diagnostics' => '*',
            'kongono/kernel' => '*',
            'php' => '>=8.5',
        ], $diagnosticPresentationManifest['require']);
        self::assertArrayNotHasKey('kongono/diagnostic-handler', $diagnosticPresentationManifest['require']);

        $httpMessageManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/http-message/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($httpMessageManifest);
        self::assertArrayHasKey('require', $httpMessageManifest);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'php' => '^8.5',
            'psr/http-factory' => '^1.1',
            'psr/http-message' => '^2.0',
        ], $httpMessageManifest['require']);

        $routingManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/routing/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($routingManifest);
        self::assertArrayHasKey('require', $routingManifest);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'php' => '^8.5',
            'psr/http-message' => '^2.0',
        ], $routingManifest['require']);
        self::assertArrayNotHasKey('kongono/http-message', $routingManifest['require']);

        $httpServerManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/http-server/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($httpServerManifest);
        self::assertArrayHasKey('require', $httpServerManifest);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/routing' => '*',
            'php' => '^8.5',
            'psr/http-message' => '^2.0',
            'psr/http-server-handler' => '^1.0',
            'psr/http-server-middleware' => '^1.0',
        ], $httpServerManifest['require']);
        foreach (['kongono/http-message', 'kongono/kernel', 'kongono/application-graph', 'kongono/dependency-injection', 'kongono/diagnostic-activation', 'kongono/diagnostic-presentation', 'kongono/diagnostic-handler'] as $forbiddenDependency) {
            self::assertArrayNotHasKey($forbiddenDependency, $httpServerManifest['require']);
        }

        $webApplicationManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/web-application/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($webApplicationManifest);
        self::assertSame('kongono/web-application', $webApplicationManifest['name']);
        self::assertSame('Explicit web application composition and Kernel operation ownership for Kongono.', $webApplicationManifest['description']);
        self::assertSame('library', $webApplicationManifest['type']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/http-server' => '*',
            'kongono/kernel' => '*',
            'kongono/routing' => '*',
            'php' => '^8.5',
            'psr/http-message' => '^2.0',
            'psr/http-server-handler' => '^1.0',
            'psr/http-server-middleware' => '^1.0',
        ], $webApplicationManifest['require']);
        self::assertIsArray($webApplicationManifest['autoload']);
        self::assertIsArray($webApplicationManifest['autoload']['psr-4']);
        self::assertIsArray($webApplicationManifest['autoload-dev']);
        self::assertIsArray($webApplicationManifest['autoload-dev']['psr-4']);
        self::assertSame(['Kongono\\Web\\Application\\' => 'src/'], $webApplicationManifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\Web\\Application\\Tests\\' => 'tests/'], $webApplicationManifest['autoload-dev']['psr-4']);
        foreach (['kongono/http-message', 'kongono/application-graph', 'kongono/dependency-injection', 'kongono/diagnostic-activation', 'kongono/diagnostic-presentation', 'kongono/diagnostic-handler'] as $forbiddenDependency) {
            self::assertArrayNotHasKey($forbiddenDependency, $webApplicationManifest['require']);
        }

        self::assertSame(['close', 'evaluateTrustedPresentationPermission', 'request', 'send'], $this->declaredPublicMethodNames(new ReflectionClass(WebDeliveryInterface::class)));
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames(new ReflectionClass(WebRuntimeException::class)));
        self::assertSame(['__construct'], $this->declaredPublicMethodNames(new ReflectionClass(RetiredWebRuntimeException::class)));
        self::assertSame(['__construct', 'cleanupFailure', 'primaryFailure'], $this->declaredPublicMethodNames(new ReflectionClass(DeliveryCleanupException::class)));
        self::assertSame(['__construct', 'generic', 'trusted'], $this->declaredPublicMethodNames(new ReflectionClass(FailureResponseFactory::class)));
        self::assertSame(['__construct', 'handle', 'isRetired'], $this->declaredPublicMethodNames(new ReflectionClass(WebRuntimeAdapter::class)));

        $rootManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($rootManifest);
        self::assertArrayHasKey('require', $rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '@dev',
            'kongono/application-graph' => '@dev',
            'kongono/dependency-injection' => '@dev',
            'kongono/diagnostic-activation' => '@dev',
            'kongono/diagnostic-handler' => '@dev',
            'kongono/diagnostic-presentation' => '@dev',
            'kongono/diagnostics' => '@dev',
            'kongono/http-message' => '@dev',
            'kongono/http-server' => '@dev',
            'kongono/kernel' => '@dev',
            'kongono/routing' => '@dev',
            'kongono/web-application' => '@dev',
            'kongono/web-runtime' => '@dev',
            'php' => '>=8.5',
        ], $rootManifest['require']);

        $diagnosticsSourceFiles = glob(dirname(__DIR__, 2) . '/packages/diagnostics/src/*/*.php');
        self::assertIsArray($diagnosticsSourceFiles);

        foreach ($diagnosticsSourceFiles as $diagnosticsSourceFile) {
            $source = file_get_contents($diagnosticsSourceFile);
            self::assertIsString($source);
            self::assertStringNotContainsString('Kongono\\ApplicationGraph', $source);
            self::assertStringNotContainsString('Kongono\\DependencyInjection', $source);
            self::assertStringNotContainsString('Kongono\\DiagnosticPresentation', $source);
            self::assertStringNotContainsString('Kongono\\Kernel', $source);
        }

        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertSame('packages/http-message/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Http\\Message\\Tests\\']);
        self::assertSame('packages/http-server/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Http\\Server\\Tests\\']);
        self::assertSame('packages/routing/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Routing\\Tests\\']);
        self::assertSame('packages/web-application/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Web\\Application\\Tests\\']);
        self::assertSame('packages/web-runtime/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Web\\Runtime\\Tests\\']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/http-message validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/http-server validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/routing validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        $validatePackages = array_values($rootManifest['scripts']['validate:packages']);
        self::assertNotSame([], $validatePackages);
        self::assertSame('composer --working-dir=packages/web-runtime validate --no-check-publish', $validatePackages[count($validatePackages) - 1]);

        $allPackageManifests = glob(dirname(__DIR__, 2) . '/packages/*/composer.json');
        self::assertIsArray($allPackageManifests);
        foreach ($allPackageManifests as $packageManifestPath) {
            if (str_ends_with(str_replace('\\', '/', $packageManifestPath), '/web-application/composer.json')
                || str_ends_with(str_replace('\\', '/', $packageManifestPath), '/web-runtime/composer.json')) {
                continue;
            }
            $manifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertIsArray($manifest['require']);
            self::assertArrayNotHasKey('kongono/web-application', $manifest['require']);
            self::assertArrayNotHasKey('kongono/web-runtime', $manifest['require']);
        }

        $httpFiles = glob(dirname(__DIR__, 2) . '/packages/http-message/src/*/*.php');
        self::assertIsArray($httpFiles);
        foreach ($httpFiles as $httpFile) {
            $source = file_get_contents($httpFile);
            self::assertIsString($source);
            foreach (['Kongono\\ApplicationGraph', 'Kongono\\DependencyInjection', 'Kongono\\DiagnosticActivation', 'Kongono\\DiagnosticHandler', 'Kongono\\DiagnosticPresentation', 'Kongono\\Kernel'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
            foreach (['use Psr\\Http\\Message\\RequestFactoryInterface;', 'Psr\\Http\\Server', 'Psr\\Http\\Client', 'Symfony', 'Guzzle', 'Laminas', 'Nyholm', 'Slim'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
        }

        $routingFiles = glob(dirname(__DIR__, 2) . '/packages/routing/src/*/*.php');
        self::assertIsArray($routingFiles);
        self::assertCount(10, $routingFiles);
        foreach ($routingFiles as $routingFile) {
            $source = file_get_contents($routingFile);
            self::assertIsString($source);
            foreach (['Kongono\\ApplicationGraph', 'Kongono\\DependencyInjection', 'Kongono\\DiagnosticActivation', 'Kongono\\DiagnosticHandler', 'Kongono\\DiagnosticPresentation', 'Kongono\\Http\\Message', 'Kongono\\Kernel', 'Psr\\Http\\Server'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
            foreach (['$_SERVER', '$_GET', '$_POST', '$_COOKIE', '$_FILES', 'getHeaders(', 'getBody(', 'getCookieParams(', 'getQueryParams(', 'getServerParams(', 'getParsedBody(', 'getUploadedFiles(', 'getAttributes(', 'getAttribute(', 'getRequestTarget('] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
        }

        $httpServerRootFiles = glob(dirname(__DIR__, 2) . '/packages/http-server/src/*.php');
        $httpServerNestedFiles = glob(dirname(__DIR__, 2) . '/packages/http-server/src/*/*.php');
        self::assertIsArray($httpServerRootFiles);
        self::assertIsArray($httpServerNestedFiles);
        $httpServerFiles = [...$httpServerRootFiles, ...$httpServerNestedFiles];
        self::assertCount(6, $httpServerFiles);
        foreach ($httpServerFiles as $httpServerFile) {
            $source = file_get_contents($httpServerFile);
            self::assertIsString($source);
            foreach (['Kongono\\ApplicationGraph', 'Kongono\\DependencyInjection', 'Kongono\\DiagnosticActivation', 'Kongono\\DiagnosticHandler', 'Kongono\\DiagnosticPresentation', 'Kongono\\Http\\Message', 'Kongono\\Kernel'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
            foreach (['$_SERVER', '$_GET', '$_POST', '$_COOKIE', '$_FILES', 'getHeaders(', 'getBody(', 'getCookieParams(', 'getQueryParams(', 'getServerParams(', 'getParsedBody(', 'getUploadedFiles(', 'getAttributes(', 'getRequestTarget(', 'class-string', 'callable', 'ReflectionClass', 'container'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
        }

        $webApplicationRootFiles = glob(dirname(__DIR__, 2) . '/packages/web-application/src/*.php');
        $webApplicationNestedFiles = glob(dirname(__DIR__, 2) . '/packages/web-application/src/*/*.php');
        self::assertIsArray($webApplicationRootFiles);
        self::assertIsArray($webApplicationNestedFiles);
        $webApplicationFiles = [...$webApplicationRootFiles, ...$webApplicationNestedFiles];
        self::assertCount(6, $webApplicationFiles);
        foreach ($webApplicationFiles as $webApplicationFile) {
            $source = file_get_contents($webApplicationFile);
            self::assertIsString($source);
            foreach (['Kongono\\ApplicationGraph', 'Kongono\\DependencyInjection', 'Kongono\\DiagnosticActivation', 'Kongono\\DiagnosticHandler', 'Kongono\\DiagnosticPresentation', 'Kongono\\Http\\Message'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
            foreach (['$GLOBALS', '$_SERVER', '$_ENV', '$_GET', '$_POST', '$_COOKIE', '$_FILES', 'getenv(', 'putenv(', 'microtime(', 'random_', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'curl_', 'ReflectionClass', 'DependencyResolver', 'ApplicationGraph', 'TrustedDeveloper', 'emit', 'header(', 'exit(', 'die('] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
        }

        $routeDefinition = new ReflectionClass(RouteDefinition::class);
        self::assertTrue($routeDefinition->isFinal());
        self::assertTrue($routeDefinition->isReadOnly());
        self::assertSame(['methods', 'name', 'pathTemplate'], $this->declaredPropertyNames($routeDefinition));
        self::assertSame(['__construct', 'methods', 'name', 'pathTemplate'], $this->declaredPublicMethodNames($routeDefinition));
        $routeTable = new ReflectionClass(RouteTable::class);
        self::assertTrue($routeTable->isFinal());
        self::assertTrue($routeTable->isReadOnly());
        self::assertSame(['routes'], $this->declaredPropertyNames($routeTable));
        self::assertSame(['__construct', 'routes'], $this->declaredPublicMethodNames($routeTable));
        $routeMatch = new ReflectionClass(RouteMatch::class);
        self::assertTrue($routeMatch->isFinal());
        self::assertTrue($routeMatch->isReadOnly());
        self::assertSame(['parameters', 'route'], $this->declaredPropertyNames($routeMatch));
        self::assertSame(['__construct', 'parameters', 'route'], $this->declaredPublicMethodNames($routeMatch));
        $matcherInterface = new ReflectionClass(RouteMatcherInterface::class);
        self::assertSame(['match'], $this->declaredPublicMethodNames($matcherInterface));
        $matcher = new ReflectionClass(RouteMatcher::class);
        self::assertTrue($matcher->isFinal());
        self::assertTrue($matcher->isReadOnly());
        self::assertSame(['compiledRoutes', 'routes'], $this->declaredPropertyNames($matcher));
        self::assertSame(['__construct', 'match'], $this->declaredPublicMethodNames($matcher));
        self::assertTrue($matcher->implementsInterface(RouteMatcherInterface::class));
        $syntax = new ReflectionClass(RouteSyntax::class);
        self::assertTrue($syntax->isFinal());
        self::assertSame([], $syntax->getProperties());
        self::assertTrue($syntax->getConstructor()?->isPrivate());
        self::assertSame(['requestMethod', 'requestPath', 'routeMatchParameters', 'routeMethods', 'routeName', 'routeTemplate'], $this->declaredPublicMethodNames($syntax));

        $pipeline = new ReflectionClass(MiddlewarePipeline::class);
        self::assertTrue($pipeline->isFinal());
        self::assertTrue($pipeline->isReadOnly());
        self::assertTrue($pipeline->implementsInterface(RequestHandlerInterface::class));
        self::assertSame(['entryHandler', 'middleware', 'terminalHandler'], $this->declaredPropertyNames($pipeline));
        self::assertSame(['__construct', 'handle', 'middleware', 'terminalHandler'], $this->declaredPublicMethodNames($pipeline));
        self::assertSame(128, $pipeline->getConstant('MAX_MIDDLEWARE'));
        $pipelineParameters = $pipeline->getConstructor()?->getParameters() ?? [];
        self::assertSame([RequestHandlerInterface::class, MiddlewareInterface::class], array_map(static fn($parameter): string => (string) $parameter->getType(), $pipelineParameters));
        self::assertTrue($pipelineParameters[1]->isVariadic());

        $layer = new ReflectionClass(MiddlewareLayer::class);
        self::assertTrue($layer->isFinal());
        self::assertTrue($layer->isReadOnly());
        self::assertTrue($layer->implementsInterface(RequestHandlerInterface::class));
        self::assertSame(['middleware', 'nextHandler'], $this->declaredPropertyNames($layer));
        self::assertSame(['__construct', 'handle'], $this->declaredPublicMethodNames($layer));

        $routingHandler = new ReflectionClass(RoutingRequestHandler::class);
        self::assertTrue($routingHandler->isFinal());
        self::assertTrue($routingHandler->isReadOnly());
        self::assertTrue($routingHandler->implementsInterface(RequestHandlerInterface::class));
        self::assertSame(RouteMatch::class, $routingHandler->getConstant('ROUTE_MATCH_ATTRIBUTE'));
        self::assertSame(['handlers', 'matcher', 'routes', 'unmatchedHandler'], $this->declaredPropertyNames($routingHandler));
        self::assertSame(['__construct', 'handle', 'handlers', 'routes', 'unmatchedHandler'], $this->declaredPublicMethodNames($routingHandler));
        self::assertSame([RouteTable::class, 'array', RequestHandlerInterface::class], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $routingHandler->getConstructor()?->getParameters() ?? [],
        ));

        $webApplication = new ReflectionClass(WebApplication::class);
        self::assertTrue($webApplication->isFinal());
        self::assertFalse($webApplication->isReadOnly());
        self::assertFalse($webApplication->getParentClass());
        self::assertSame([RequestHandlerInterface::class], $webApplication->getInterfaceNames());
        $webProperties = array_values(array_filter(
            $webApplication->getProperties(),
            static fn(\ReflectionProperty $property): bool => WebApplication::class === $property->getDeclaringClass()->getName(),
        ));
        usort($webProperties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));
        self::assertSame(['kernel', 'pipeline', 'retired'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $webProperties));
        foreach ($webProperties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertFalse($property->isStatic());
        }
        self::assertTrue($webProperties[0]->isReadOnly());
        self::assertTrue($webProperties[1]->isReadOnly());
        self::assertFalse($webProperties[2]->isReadOnly());
        $webConstructor = $webApplication->getConstructor();
        self::assertNotNull($webConstructor);
        self::assertTrue($webConstructor->isPrivate());
        self::assertSame([KernelInterface::class, MiddlewarePipeline::class], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $webConstructor->getParameters(),
        ));
        self::assertTrue($webApplication->getMethod('__clone')->isPrivate());
        self::assertFalse($webApplication->hasMethod('__destruct'));
        self::assertSame(['boot', 'handle', 'isRetired', 'shutdown'], $this->declaredPublicMethodNames($webApplication));

        self::assertSame(['handle'], $this->declaredPublicMethodNames(new ReflectionClass(RequestHandlerInterface::class)));
        self::assertSame(['process'], $this->declaredPublicMethodNames(new ReflectionClass(MiddlewareInterface::class)));

        foreach ([
            '.php-cs-fixer.dist.php' => ['packages/routing/src', 'packages/routing/tests', 'packages/web-application/src', 'packages/web-application/tests', 'packages/web-runtime/src', 'packages/web-runtime/tests'],
            'phpstan.neon.dist' => ['packages/routing/src', 'packages/routing/tests', 'packages/http-server/src', 'packages/http-server/tests', 'packages/web-application/src', 'packages/web-application/tests', 'packages/web-runtime/src', 'packages/web-runtime/tests'],
            'phpunit.xml.dist' => ['packages/routing/src', 'packages/routing/tests', 'packages/http-server/src', 'packages/http-server/tests', 'packages/web-application/src', 'packages/web-application/tests', 'packages/web-runtime/src', 'packages/web-runtime/tests'],
        ] as $configuration => $needles) {
            $contents = file_get_contents(dirname(__DIR__, 2) . '/' . $configuration);
            self::assertIsString($contents);
            foreach ($needles as $needle) {
                self::assertStringContainsString($needle, $contents);
                self::assertSame(1, substr_count($contents, $needle));
            }
        }

        $relationships = [
            'Kongono\\Http\\Message\\Message\\Response' => 'Psr\\Http\\Message\\ResponseInterface',
            'Kongono\\Http\\Message\\Message\\ServerRequest' => 'Psr\\Http\\Message\\ServerRequestInterface',
            'Kongono\\Http\\Message\\Message\\Stream' => 'Psr\\Http\\Message\\StreamInterface',
            'Kongono\\Http\\Message\\Message\\UploadedFile' => 'Psr\\Http\\Message\\UploadedFileInterface',
            'Kongono\\Http\\Message\\Message\\Uri' => 'Psr\\Http\\Message\\UriInterface',
            'Kongono\\Http\\Message\\Factory\\ResponseFactory' => 'Psr\\Http\\Message\\ResponseFactoryInterface',
            'Kongono\\Http\\Message\\Factory\\ServerRequestFactory' => 'Psr\\Http\\Message\\ServerRequestFactoryInterface',
            'Kongono\\Http\\Message\\Factory\\StreamFactory' => 'Psr\\Http\\Message\\StreamFactoryInterface',
            'Kongono\\Http\\Message\\Factory\\UploadedFileFactory' => 'Psr\\Http\\Message\\UploadedFileFactoryInterface',
            'Kongono\\Http\\Message\\Factory\\UriFactory' => 'Psr\\Http\\Message\\UriFactoryInterface',
        ];
        foreach ($relationships as $implementation => $interface) {
            self::assertContains($interface, (new ReflectionClass($implementation))->getInterfaceNames());
        }
        foreach (array_slice(array_keys($relationships), 5) as $factory) {
            $reflection = new ReflectionClass($factory);
            self::assertTrue($reflection->isFinal());
            self::assertSame([], $reflection->getProperties());
            self::assertNull($reflection->getConstructor());
        }
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPropertyNames(ReflectionClass $reflection): array
    {
        $properties = array_values(array_filter(
            $reflection->getProperties(),
            static fn(\ReflectionProperty $property): bool => $reflection->getName() === $property->getDeclaringClass()->getName(),
        ));
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
            self::assertFalse($property->isStatic());
        }
        $names = array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties);
        sort($names);

        return $names;
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPublicMethodNames(ReflectionClass $reflection): array
    {
        $methods = array_values(array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn(ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName(),
        ));
        $names = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($names);

        return $names;
    }

    /** @return list<string> */
    private function discoverProductionTypes(): array
    {
        $packageSourceDirectories = glob(dirname(__DIR__, 2) . '/packages/*/src', GLOB_ONLYDIR);

        if (false === $packageSourceDirectories) {
            throw new RuntimeException('Unable to discover package source directories.');
        }

        sort($packageSourceDirectories);
        self::assertCount(13, $packageSourceDirectories);
        $packageManifests = glob(dirname(__DIR__, 2) . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(13, $packageManifests);
        $types = [];

        foreach ($packageSourceDirectories as $sourceDirectory) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS),
            );

            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                if (!$file->isFile() || 'php' !== $file->getExtension()) {
                    continue;
                }

                $source = file_get_contents($file->getPathname());

                if (false === $source) {
                    throw new RuntimeException(sprintf('Unable to read production source %s.', $file->getPathname()));
                }

                array_push($types, ...$this->classLikeDeclarations($source));
            }
        }

        $types = array_values(array_unique($types));
        sort($types);

        return $types;
    }

    /** @return list<string> */
    private function classLikeDeclarations(string $source): array
    {
        $tokens = token_get_all($source);
        $namespace = '';
        $types = [];
        $count = count($tokens);

        for ($index = 0; $index < $count; ++$index) {
            $token = $tokens[$index];

            if (!is_array($token)) {
                continue;
            }

            if (T_NAMESPACE === $token[0]) {
                $namespace = $this->namespaceFollowing($tokens, $index + 1);

                continue;
            }

            if (!in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                continue;
            }

            if (T_CLASS === $token[0]) {
                $previous = $this->previousSignificantTokenId($tokens, $index);

                if (T_NEW === $previous || T_DOUBLE_COLON === $previous) {
                    continue;
                }
            }

            $name = $this->declarationNameFollowing($tokens, $index + 1);

            if (null !== $name) {
                $types[] = '' === $namespace ? $name : $namespace . '\\' . $name;
            }
        }

        return $types;
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private function namespaceFollowing(array $tokens, int $start): string
    {
        $namespace = '';

        for ($index = $start, $count = count($tokens); $index < $count; ++$index) {
            $token = $tokens[$index];

            if (';' === $token || '{' === $token) {
                break;
            }

            if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                $namespace .= $token[1];
            }
        }

        return trim($namespace, '\\');
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private function declarationNameFollowing(array $tokens, int $start): ?string
    {
        for ($index = $start, $count = count($tokens); $index < $count; ++$index) {
            $token = $tokens[$index];

            if (is_array($token) && T_STRING === $token[0]) {
                return $token[1];
            }

            if ('{' === $token || ';' === $token) {
                return null;
            }
        }

        return null;
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private function previousSignificantTokenId(array $tokens, int $before): ?int
    {
        for ($index = $before - 1; $index >= 0; --$index) {
            $token = $tokens[$index];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return is_array($token) ? $token[0] : null;
        }

        return null;
    }

    /** @return class-string */
    private function autoloadedClassLike(string $type): string
    {
        if (class_exists($type) || interface_exists($type) || trait_exists($type) || enum_exists($type)) {
            return $type;
        }

        throw new RuntimeException(sprintf('Production type %s is not autoloadable.', $type));
    }

    /**
     * @param class-string              $exception
     * @param class-string              $expectedMarker
     * @param list<class-string>        $excludedMarkers
     */
    private static function assertExceptionMarkerMembership(
        string $exception,
        string $expectedMarker,
        array $excludedMarkers,
    ): void {
        self::assertTrue(is_a($exception, $expectedMarker, true));

        foreach ($excludedMarkers as $excludedMarker) {
            self::assertFalse(is_a($exception, $excludedMarker, true));
        }
    }

    private static function assertMarkersDistinct(string $left, string $right): void
    {
        self::assertFalse(is_a($left, $right, true));
        self::assertFalse(is_a($right, $left, true));
    }
}
