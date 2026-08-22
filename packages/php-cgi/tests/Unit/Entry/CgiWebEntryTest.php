<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Tests\Unit\Entry;

use Closure;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\Http\Message\Message\Response;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\Kernel\Contract\KernelInterface;
use Kongono\Kernel\Lifecycle\OperationScope;
use Kongono\PhpCgi\Entry\CgiWebEntry;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Web\Application\WebApplication;
use Kongono\Web\Runtime\Exception\RetiredWebRuntimeException;
use Kongono\Web\Runtime\WebRuntimeAdapter;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class CgiWebEntryTest extends TestCase
{
    public function testDeclarationAndPublicSurfaceAreExact(): void
    {
        $reflection = new \ReflectionClass(CgiWebEntry::class);
        self::assertTrue($reflection->isFinal());
        self::assertSame(ApiStatus::Experimental, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        self::assertTrue($reflection->getConstructor()?->isPrivate());
        self::assertSame([], $reflection->getProperties());
        self::assertSame(['run'], array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter(
                $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
                static fn(\ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName(),
            ),
        ));
        self::assertSame([WebRuntimeAdapter::class], array_map(
            static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(),
            $reflection->getMethod('run')->getParameters(),
        ));
        self::assertSame('void', (string) $reflection->getMethod('run')->getReturnType());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testNativeCliMismatchUsesGenericResponseAndFreshSequentialDeliveries(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::never())->method('beginOperation');
        $kernel->expects(self::never())->method('shutdown');
        $response = self::createStub(ResponseInterface::class);
        $handled = 0;
        $handler = static function (ServerRequestInterface $request) use (&$handled, $response): ResponseInterface {
            ++$handled;

            return $response;
        };
        $application = WebApplication::boot(
            $kernel,
            new RouteTable([]),
            [],
            new readonly class ($handler) implements RequestHandlerInterface {
                /** @param Closure(ServerRequestInterface): ResponseInterface $handler */
                public function __construct(private Closure $handler) {}

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return ($this->handler)($request);
                }
            },
        );
        $runtime = new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Denied);

        CgiWebEntry::run($runtime);
        CgiWebEntry::run($runtime);

        self::assertSame(0, $handled);
        self::assertFalse($runtime->isRetired());
        self::expectOutputString("Internal Server Error\nInternal Server Error\n");
    }

    public function testRunPropagatesRuntimeRefusalWithoutEntryRecovery(): void
    {
        $scope = new OperationScope();
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())->method('boot');
        $kernel->expects(self::once())->method('beginOperation')->willReturn($scope);
        $kernel->expects(self::once())->method('endOperation')->with(self::identicalTo($scope))->willThrowException(new RuntimeException('retire-application'));
        $response = new Response();
        $application = WebApplication::boot(
            $kernel,
            new RouteTable([]),
            [],
            new readonly class ($response) implements RequestHandlerInterface {
                public function __construct(private ResponseInterface $response) {}

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->response;
                }
            },
        );
        try {
            $application->handle(new ServerRequest('GET', '/retire'));
            self::fail('Application cleanup must retire the application.');
        } catch (RuntimeException $failure) {
            self::assertSame('retire-application', $failure->getMessage());
        }
        $runtime = new WebRuntimeAdapter($application, TrustedDeveloperBootstrapPermission::Denied);

        $this->expectException(RetiredWebRuntimeException::class);
        CgiWebEntry::run($runtime);
    }
}
