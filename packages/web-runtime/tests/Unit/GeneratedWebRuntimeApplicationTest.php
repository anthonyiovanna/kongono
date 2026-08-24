<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Tests\Unit;

use Kongono\Composition\Runtime\LoadedGeneratedWiring;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\Kernel\Contract\KernelInterface;
use Kongono\Web\Application\WebApplication;
use Kongono\Web\Runtime\GeneratedWebRuntimeApplication;
use Kongono\Web\Runtime\GeneratedWebRuntimeBootstrap;
use Kongono\Web\Runtime\Tests\Fixture\Phase5FApplicationAssembly;
use Kongono\Web\Runtime\Tests\Fixture\Phase5FOperationAssembly;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionMethod;

final class GeneratedWebRuntimeApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        Phase5FApplicationAssembly::$beginOperationCalls = 0;
        Phase5FApplicationAssembly::$shutdownCalls = 0;
    }

    public function testExactProfileStartsAndGeneratedShutdownOwnsWebApplicationCleanupOnce(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('shutdown');
        $webApplication = new WebApplication($kernel, self::createStub(RequestHandlerInterface::class));
        $bootstrap = new GeneratedWebRuntimeBootstrap(TrustedDeveloperBootstrapPermission::Denied);
        $loaded = $this->loaded();

        $application = GeneratedWebRuntimeApplication::start($loaded, $webApplication, $bootstrap);

        self::assertFalse($application->isRetired());
        self::assertFalse($application->runtime()->isRetired());
        self::assertSame(0, Phase5FApplicationAssembly::$beginOperationCalls);
        $application->shutdown();
        $application->shutdown();
        self::assertTrue($application->isRetired());
        self::assertSame(1, Phase5FApplicationAssembly::$shutdownCalls);
    }

    public function testWrongInputFailsBeforeAssemblyStartAndDoesNotConsumeShutdown(): void
    {
        $loaded = $this->loaded();

        $this->expectException(\Kongono\Composition\Exception\GeneratedWiringLoadException::class);
        try {
            GeneratedWebRuntimeApplication::start($loaded, new \stdClass(), new \stdClass());
        } finally {
            self::assertSame(0, Phase5FApplicationAssembly::$shutdownCalls);
        }
    }

    private function loaded(): LoadedGeneratedWiring
    {
        return new LoadedGeneratedWiring(
            str_repeat('a', 64),
            Phase5FApplicationAssembly::class,
            Phase5FOperationAssembly::class,
            new ReflectionMethod(Phase5FApplicationAssembly::class, 'start'),
        );
    }
}
