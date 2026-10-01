<?php

declare(strict_types=1);

namespace Kongono\EventRuntime\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\Registration\EventRegistrationSet;
use Kongono\EventRuntime\Diagnostic\EventRuntimeDiagnostic;
use Kongono\EventRuntime\Dispatch\SynchronousEventDispatcher;
use Kongono\EventRuntime\Exception\ExceptionInterface;
use Kongono\EventRuntime\Exception\ReentrantDispatchException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

#[CoversNothing]
final class EventRuntimeBoundaryTest extends TestCase
{
    public function testExactInventoryDependenciesAndApiStatuses(): void
    {
        $package = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/event-runtime', $manifest['name']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/event-contract' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);
        $production = self::phpFiles($package . '/src');
        self::assertCount(4, $production);
        self::assertCount(12, self::phpFiles($package . '/tests'));
        $expected = [
            SynchronousEventDispatcher::class => ApiStatus::Experimental,
            EventRuntimeDiagnostic::class => ApiStatus::Internal,
            ExceptionInterface::class => ApiStatus::Experimental,
            ReentrantDispatchException::class => ApiStatus::Experimental,
        ];
        foreach ($expected as $class => $status) {
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            self::assertSame($status, $attributes[0]->newInstance()->status);
            if (!$reflection->isInterface()) {
                self::assertTrue($reflection->isFinal(), $class);
            }
        }
        self::assertSame(['KGN-EVENT-RUNTIME-2221'], array_column(EventRuntimeDiagnostic::cases(), 'value'));
    }

    public function testNativeDispatcherSurfaceAndRetainedStateAreMinimal(): void
    {
        $reflection = new ReflectionClass(SynchronousEventDispatcher::class);
        self::assertTrue($reflection->isFinal());
        $expectedMethods = ['__construct', 'dispatch'];
        $methods = array_map(static fn($method): string => $method->getName(), $reflection->getMethods());
        sort($expectedMethods, SORT_STRING);
        sort($methods, SORT_STRING);
        self::assertSame($expectedMethods, $methods);
        $expectedProperties = ['registrations', 'busy'];
        $properties = array_map(static fn($property): string => $property->getName(), $reflection->getProperties());
        sort($expectedProperties, SORT_STRING);
        sort($properties, SORT_STRING);
        self::assertSame($expectedProperties, $properties);
        self::assertTrue($reflection->getProperty('registrations')->isReadOnly());
        self::assertFalse($reflection->getProperty('registrations')->isStatic());
        self::assertFalse($reflection->getProperty('busy')->isReadOnly());
        self::assertFalse($reflection->getProperty('busy')->isStatic());
        self::assertSame('bool', (string) $reflection->getProperty('busy')->getType());
        $method = $reflection->getMethod('dispatch');
        self::assertCount(1, $method->getParameters());
        self::assertSame(ApplicationEvent::class, (string) $method->getParameters()[0]->getType());
        self::assertSame('void', (string) $method->getReturnType());
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        self::assertCount(1, $constructor->getParameters());
        self::assertSame(EventRegistrationSet::class, (string) $constructor->getParameters()[0]->getType());
    }

    public function testProductionSurfaceHasNoAmbientOrDeferredCapability(): void
    {
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $file) {
            $source = (string) file_get_contents($file);
            foreach (['class_exists(', 'Reflection', 'serialize(', 'unserialize(', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'curl_', 'getenv(', 'shell_exec(', 'exec(', 'Fiber', 'sleep(', 'trigger_error(', 'error_log(', 'Graph', 'Telemetry'] as $token) {
                self::assertStringNotContainsString($token, $source, $file . ':' . $token);
            }
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $entry) {
            if ($entry instanceof SplFileInfo && $entry->isFile() && 'php' === $entry->getExtension()) {
                $files[] = $entry->getPathname();
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }
}
