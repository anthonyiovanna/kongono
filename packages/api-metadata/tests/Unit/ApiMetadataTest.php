<?php

declare(strict_types=1);

namespace Kongono\ApiMetadata\Tests\Unit;

use Attribute;
use BackedEnum;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class ApiMetadataTest extends TestCase
{
    public function testApiStatusVocabularyIsExact(): void
    {
        $reflection = new ReflectionClass(ApiStatus::class);

        self::assertSame(
            [
                ['Internal', 'internal'],
                ['Experimental', 'experimental'],
                ['Public', 'public'],
            ],
            array_map(
                static fn(ApiStatus $status): array => [$status->name, $status->value],
                ApiStatus::cases(),
            ),
        );
        self::assertInstanceOf(BackedEnum::class, ApiStatus::Internal);
        self::assertSame(['cases', 'from', 'tryFrom'], array_map(
            static fn(ReflectionMethod $method): string => $method->getName(),
            $reflection->getMethods(),
        ));
        self::assertSame(['name', 'value'], array_map(
            static fn(\ReflectionProperty $property): string => $property->getName(),
            $reflection->getProperties(),
        ));
        self::assertSame([], $reflection->getTraitNames());
    }

    public function testApiAttributeContractIsExact(): void
    {
        $reflection = new ReflectionClass(Api::class);
        $declarations = $reflection->getAttributes(Attribute::class);
        $properties = $reflection->getProperties();
        $methods = array_values(array_filter(
            $reflection->getMethods(),
            static fn(ReflectionMethod $method): bool => Api::class === $method->getDeclaringClass()->getName(),
        ));
        $constructor = $reflection->getConstructor();

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertCount(1, $declarations);
        self::assertSame(Attribute::TARGET_CLASS, $declarations[0]->newInstance()->flags);
        self::assertSame(0, $declarations[0]->newInstance()->flags & Attribute::IS_REPEATABLE);
        self::assertCount(1, $properties);
        self::assertSame('status', $properties[0]->getName());
        self::assertSame(ApiStatus::class, (string) $properties[0]->getType());
        self::assertTrue($properties[0]->isPublic());
        self::assertTrue($properties[0]->isReadOnly());
        self::assertFalse($properties[0]->isStatic());
        self::assertNotNull($constructor);
        self::assertCount(1, $constructor->getParameters());
        self::assertSame('status', $constructor->getParameters()[0]->getName());
        self::assertSame(ApiStatus::class, (string) $constructor->getParameters()[0]->getType());
        self::assertTrue($constructor->getParameters()[0]->isPromoted());
        self::assertFalse($constructor->getParameters()[0]->isOptional());
        self::assertSame(['__construct'], array_map(
            static fn(ReflectionMethod $method): string => $method->getName(),
            $methods,
        ));
    }

    public function testBootstrapTypesRemainTheOnlySelfClassificationExceptions(): void
    {
        self::assertSame([], (new ReflectionClass(Api::class))->getAttributes(Api::class));
        self::assertSame([], (new ReflectionClass(ApiStatus::class))->getAttributes(Api::class));
    }

    public function testLegacyKernelMetadataTypesAreAbsent(): void
    {
        self::assertTrue(class_exists(Api::class));
        self::assertTrue(enum_exists(ApiStatus::class));
        self::assertFalse(class_exists('Kongono\\Kernel\\Api\\Api'));
        self::assertFalse(enum_exists('Kongono\\Kernel\\Api\\ApiStatus'));
    }

    public function testPackageManifestAndSourceBoundaryAreExact(): void
    {
        $packageDirectory = dirname(__DIR__, 2);
        $manifest = json_decode(
            (string) file_get_contents($packageDirectory . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $sourceFiles = glob($packageDirectory . '/src/*.php');

        self::assertSame([
            'name' => 'kongono/api-metadata',
            'description' => 'Neutral API compatibility metadata for Kongono Framework.',
            'type' => 'library',
            'require' => [
                'php' => '>=8.5.0 <8.6.0',
            ],
            'autoload' => [
                'psr-4' => [
                    'Kongono\\ApiMetadata\\' => 'src/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    'Kongono\\ApiMetadata\\Tests\\' => 'tests/',
                ],
            ],
        ], $manifest);
        self::assertIsArray($sourceFiles);
        sort($sourceFiles);
        self::assertSame([
            $packageDirectory . '/src/Api.php',
            $packageDirectory . '/src/ApiStatus.php',
        ], $sourceFiles);
        self::assertSame('Kongono\\ApiMetadata', (new ReflectionClass(Api::class))->getNamespaceName());
        self::assertSame('Kongono\\ApiMetadata', (new ReflectionClass(ApiStatus::class))->getNamespaceName());

        $combinedSource = implode('', array_map(
            static fn(string $file): string => (string) file_get_contents($file),
            $sourceFiles,
        ));
        self::assertStringNotContainsString('Kongono\\Kernel', $combinedSource);
        self::assertStringNotContainsString('Kongono\\ApplicationGraph', $combinedSource);
        self::assertStringNotContainsString('Kongono\\DependencyInjection', $combinedSource);
        self::assertStringNotContainsString('Kongono\\Diagnostics', $combinedSource);

        foreach (['static $', 'cache', 'registry', 'service', 'file_get_contents(', 'fopen(', 'getenv(', 'diagnosticContext', 'renderer', 'transport'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $combinedSource);
        }
    }
}
