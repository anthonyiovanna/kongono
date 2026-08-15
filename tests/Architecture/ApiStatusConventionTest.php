<?php

declare(strict_types=1);

namespace Kongono\Tests\Architecture;

use Attribute;
use FilesystemIterator;
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
use Kongono\Kernel\Diagnostic\LifecycleDiagnostic;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use Kongono\Kernel\Exception\KernelException;
use Kongono\Kernel\Exception\LifecycleException;
use PHPUnit\Framework\TestCase;
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
    ];

    /** @var list<class-string> */
    private const BOOTSTRAP_TYPES = [
        Api::class,
        ApiStatus::class,
    ];

    public function testProductionDeclarationsExactlyMatchAuthorizedInventoryAndAreAutoloadable(): void
    {
        $types = $this->discoverProductionTypes();

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
        ];

        foreach ($markers as $position => $marker) {
            foreach (array_slice($markers, $position + 1) as $otherMarker) {
                self::assertMarkersDistinct($marker, $otherMarker);
            }
        }

        self::assertExceptionMarkerMembership(LifecycleException::class, KernelExceptionInterface::class, [
            GraphExceptionInterface::class,
            DependencyInjectionExceptionInterface::class,
            DiagnosticPresentationExceptionInterface::class,
            DiagnosticsExceptionInterface::class,
        ]);
        self::assertExceptionMarkerMembership(InvalidGraphException::class, GraphExceptionInterface::class, [
            KernelExceptionInterface::class,
            DependencyInjectionExceptionInterface::class,
            DiagnosticPresentationExceptionInterface::class,
            DiagnosticsExceptionInterface::class,
        ]);
        self::assertExceptionMarkerMembership(InvalidDefinitionException::class, DependencyInjectionExceptionInterface::class, [
            KernelExceptionInterface::class,
            GraphExceptionInterface::class,
            DiagnosticPresentationExceptionInterface::class,
            DiagnosticsExceptionInterface::class,
        ]);
        self::assertExceptionMarkerMembership(UnknownServiceException::class, DependencyInjectionExceptionInterface::class, [
            KernelExceptionInterface::class,
            GraphExceptionInterface::class,
            DiagnosticPresentationExceptionInterface::class,
            DiagnosticsExceptionInterface::class,
        ]);
        self::assertExceptionMarkerMembership(InvalidDiagnosticProjectionException::class, DiagnosticPresentationExceptionInterface::class, [
            KernelExceptionInterface::class,
            GraphExceptionInterface::class,
            DependencyInjectionExceptionInterface::class,
            DiagnosticsExceptionInterface::class,
        ]);
        self::assertExceptionMarkerMembership(InvalidDiagnosticValueException::class, DiagnosticsExceptionInterface::class, [
            KernelExceptionInterface::class,
            GraphExceptionInterface::class,
            DependencyInjectionExceptionInterface::class,
            DiagnosticPresentationExceptionInterface::class,
        ]);
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
            ],
        );

        self::assertCount(68, $identifiers);
        self::assertCount(68, array_unique($identifiers));
        self::assertCount(6, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-KERNEL-1\\d{3}$/D', $identifier)));
        self::assertCount(11, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-GRAPH-1\\d{3}$/D', $identifier)));
        self::assertCount(25, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-DI-1\\d{3}$/D', $identifier)));
        self::assertCount(15, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-PRESENTATION-1\\d{3}$/D', $identifier)));
        self::assertCount(11, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-DIAGNOSTICS-1\\d{3}$/D', $identifier)));

        foreach ([
            KernelExceptionInterface::class,
            GraphExceptionInterface::class,
            DependencyInjectionExceptionInterface::class,
            DiagnosticPresentationExceptionInterface::class,
            DiagnosticsExceptionInterface::class,
        ] as $marker) {
            $reflection = new ReflectionClass($marker);
            $methods = array_values(array_filter(
                $reflection->getMethods(),
                static fn(ReflectionMethod $method): bool => $marker === $method->getDeclaringClass()->getName(),
            ));
            $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
            sort($methodNames);

            self::assertSame([Throwable::class, \Stringable::class], $reflection->getInterfaceNames());
            $expectedMethods = in_array($marker, [KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class], true)
                ? ['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle']
                : ['diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'];
            self::assertSame($expectedMethods, $methodNames);

            foreach ($methods as $method) {
                $expectedReturnType = in_array($marker, [KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class], true)
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
            'kongono/diagnostic-presentation' => '@dev',
            'kongono/diagnostics' => '@dev',
            'kongono/kernel' => '@dev',
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
    }

    /** @return list<string> */
    private function discoverProductionTypes(): array
    {
        $packageSourceDirectories = glob(dirname(__DIR__, 2) . '/packages/*/src', GLOB_ONLYDIR);

        if (false === $packageSourceDirectories) {
            throw new RuntimeException('Unable to discover package source directories.');
        }

        sort($packageSourceDirectories);
        self::assertCount(7, $packageSourceDirectories);
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
