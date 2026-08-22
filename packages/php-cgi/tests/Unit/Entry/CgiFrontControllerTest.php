<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Tests\Unit\Entry;

use Closure;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\Http\Message\Message\Response;
use Kongono\Kernel\Contract\KernelInterface;
use Kongono\PhpCgi\Entry\CgiFrontController;
use Kongono\PhpCgi\Exception\CgiEntryShutdownException;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Web\Application\WebApplication;
use Kongono\Web\Runtime\Contract\WebDeliveryInterface;
use Kongono\Web\Runtime\Exception\RetiredWebRuntimeException;
use Kongono\Web\Runtime\WebRuntimeAdapter;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class CgiFrontControllerTest extends TestCase
{
    public function testDeclarationAndPublicSurfaceAreExact(): void
    {
        $reflection = new \ReflectionClass(CgiFrontController::class);
        self::assertTrue($reflection->isFinal());
        self::assertSame(ApiStatus::Experimental, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertTrue($reflection->getConstructor()?->isPrivate());
        self::assertSame([], $reflection->getProperties());
        self::assertSame([], $reflection->getConstants());
        self::assertSame([], $reflection->getInterfaceNames());
        self::assertSame([], $reflection->getTraitNames());
        self::assertSame(['run'], array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter(
                $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
                static fn(\ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName(),
            ),
        ));
        $run = $reflection->getMethod('run');
        self::assertTrue($run->isStatic());
        self::assertSame([WebRuntimeAdapter::class, Closure::class], array_map(
            static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(),
            $run->getParameters(),
        ));
        self::assertSame('void', (string) $run->getReturnType());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSuccessRunsEntryThenApplicationShutdownExactlyOnce(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::never())->method('beginOperation');
        $kernel->expects(self::once())->method('shutdown');
        $application = $this->application($kernel);
        $runtime = new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Denied);
        $shutdownCalls = 0;

        CgiFrontController::run($runtime, static function () use ($application, &$shutdownCalls): void {
            ++$shutdownCalls;
            $application->shutdown();
        });

        self::assertSame(1, $shutdownCalls);
        self::assertFalse($runtime->isRetired());
        self::expectOutputString("Internal Server Error\n");
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testPrimaryFailureIsRethrownAfterOneSuccessfulShutdownWithoutOutput(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::never())->method('beginOperation');
        $kernel->expects(self::once())->method('shutdown');
        $application = $this->application($kernel);
        $runtime = new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Denied);
        $this->retire($runtime);
        $shutdownCalls = 0;

        try {
            CgiFrontController::run($runtime, static function () use ($application, &$shutdownCalls): void {
                ++$shutdownCalls;
                $application->shutdown();
            });
            self::fail('The entry failure must escape.');
        } catch (RetiredWebRuntimeException $failure) {
            self::assertSame('Web runtime adapter is retired', $failure->getMessage());
        }

        self::assertSame(1, $shutdownCalls);
        self::expectOutputString('');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testShutdownOnlyFailureIsRethrownByIdentity(): void
    {
        $shutdownFailure = new RuntimeException('shutdown-identity');
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::never())->method('beginOperation');
        $kernel->expects(self::once())->method('shutdown')->willThrowException($shutdownFailure);
        $application = $this->application($kernel);
        $runtime = new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Denied);

        try {
            CgiFrontController::run($runtime, $application->shutdown(...));
            self::fail('The shutdown failure must escape.');
        } catch (RuntimeException $failure) {
            self::assertSame($shutdownFailure, $failure);
        }

        self::expectOutputString("Internal Server Error\n");
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSimultaneousFailuresPreserveRolesAndAttemptShutdownOnce(): void
    {
        $shutdownFailure = new RuntimeException('raw-shutdown-sentinel');
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::never())->method('beginOperation');
        $kernel->expects(self::once())->method('shutdown')->willThrowException($shutdownFailure);
        $application = $this->application($kernel);
        $runtime = new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Denied);
        $this->retire($runtime);

        try {
            CgiFrontController::run($runtime, $application->shutdown(...));
            self::fail('Both failures must produce the composite.');
        } catch (CgiEntryShutdownException $failure) {
            self::assertInstanceOf(RetiredWebRuntimeException::class, $failure->primaryFailure());
            self::assertSame($failure->primaryFailure(), $failure->getPrevious());
            self::assertSame($shutdownFailure, $failure->shutdownFailure());
        }

        self::expectOutputString('');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testIndependentSequentialCompositionsRetainNoFrontControllerState(): void
    {
        $firstKernel = $this->createMock(KernelInterface::class);
        $firstKernel->expects(self::once())->method('boot');
        $firstKernel->expects(self::once())->method('shutdown');
        $secondKernel = $this->createMock(KernelInterface::class);
        $secondKernel->expects(self::once())->method('boot');
        $secondKernel->expects(self::once())->method('shutdown');
        $firstApplication = $this->application($firstKernel);
        $secondApplication = $this->application($secondKernel);

        CgiFrontController::run(
            new WebRuntimeAdapter($firstApplication, TrustedDeveloperBootstrapPermission::Denied),
            $firstApplication->shutdown(...),
        );
        CgiFrontController::run(
            new WebRuntimeAdapter($secondApplication, TrustedDeveloperBootstrapPermission::Denied),
            $secondApplication->shutdown(...),
        );

        self::expectOutputString("Internal Server Error\nInternal Server Error\n");
    }

    private function application(KernelInterface $kernel): WebApplication
    {
        return WebApplication::boot(
            $kernel,
            new RouteTable([]),
            [],
            new readonly class implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return new Response();
                }
            },
        );
    }

    private function retire(WebRuntimeAdapter $runtime): void
    {
        $retirementFailure = new RuntimeException('retire-runtime');
        $delivery = new readonly class ($retirementFailure) implements WebDeliveryInterface {
            public function __construct(private RuntimeException $retirementFailure) {}

            public function evaluateTrustedPresentationPermission(): AdapterControlledDeliveryPermission
            {
                return AdapterControlledDeliveryPermission::Denied;
            }

            public function request(): ServerRequestInterface
            {
                throw new RuntimeException('request-failure');
            }

            public function send(ResponseInterface $response): void
            {
                throw $this->retirementFailure;
            }

            public function close(): void {}
        };

        try {
            $runtime->handle($delivery);
            self::fail('The setup delivery must retire the runtime.');
        } catch (RuntimeException $failure) {
            self::assertSame($retirementFailure, $failure);
        }
        self::assertTrue($runtime->isRetired());
    }
}
