<?php

declare(strict_types=1);

namespace Kongono\EventContract\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\Diagnostic\EventContractDiagnostic;
use Kongono\EventContract\EventHandler;
use Kongono\EventContract\Exception\EventContractException;
use Kongono\EventContract\Exception\ExceptionInterface;
use Kongono\EventContract\Exception\InvalidEventContractException;
use Kongono\EventContract\Registration\EventRegistration;
use Kongono\EventContract\Registration\EventRegistrationSet;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class EventContractBoundaryTest extends TestCase
{
    public function testExactEightDeclarationsAndApiStatuses(): void
    {
        $statuses = [
            ApplicationEvent::class => ApiStatus::Experimental,
            EventHandler::class => ApiStatus::Experimental,
            EventRegistration::class => ApiStatus::Experimental,
            EventRegistrationSet::class => ApiStatus::Experimental,
            ExceptionInterface::class => ApiStatus::Experimental,
            InvalidEventContractException::class => ApiStatus::Experimental,
            EventContractDiagnostic::class => ApiStatus::Internal,
            EventContractException::class => ApiStatus::Internal,
        ];
        $root = dirname(__DIR__, 2);
        $files = glob($root . '/src/{*,*/*}.php', GLOB_BRACE);
        self::assertIsArray($files);
        self::assertCount(8, $files);
        foreach ($statuses as $class => $expected) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            self::assertSame($expected, $attributes[0]->newInstance()->status, $class);
        }
        self::assertSame([], (new ReflectionClass(ApplicationEvent::class))->getMethods());
        self::assertSame(['handle'], array_map(static fn($method): string => $method->getName(), (new ReflectionClass(EventHandler::class))->getMethods()));
        self::assertNotContains('dispatch', array_map(static fn($method): string => $method->getName(), (new ReflectionClass(EventRegistrationSet::class))->getMethods()));
    }

    public function testOnlyTwoNeutralProductionDependenciesAndNoDispatchCall(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
        ], $manifest['require']);
        foreach (['Registration/EventRegistration.php', 'Registration/EventRegistrationSet.php'] as $file) {
            $source = (string) file_get_contents($root . '/src/' . $file);
            self::assertStringNotContainsString('->handle(', $source);
            self::assertStringNotContainsString('eval(', $source);
            self::assertStringNotContainsString('get_declared_classes(', $source);
            self::assertStringNotContainsString('spl_autoload_register(', $source);
        }
    }
}
