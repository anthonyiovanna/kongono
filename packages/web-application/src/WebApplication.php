<?php

declare(strict_types=1);

namespace Kongono\Web\Application;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Server\MiddlewarePipeline;
use Kongono\Http\Server\RoutingRequestHandler;
use Kongono\Kernel\Contract\KernelInterface;
use Kongono\Kernel\Exception\LifecycleException;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Web\Application\Exception\OperationCleanupException;
use Kongono\Web\Application\Exception\RetiredWebApplicationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/** Explicit transport-free application composition and Kernel operation owner. */
#[Api(ApiStatus::Experimental)]
final class WebApplication implements RequestHandlerInterface
{
    private readonly KernelInterface $kernel;
    private readonly RequestHandlerInterface $pipeline;
    private bool $retired = false;

    public function __construct(KernelInterface $kernel, RequestHandlerInterface $pipeline)
    {
        $this->kernel = $kernel;
        $this->pipeline = $pipeline;
        $this->kernel->boot();
    }

    private function __clone(): void {}

    /** @param array<array-key, mixed> $routeHandlers */
    public static function boot(
        KernelInterface $kernel,
        RouteTable $routes,
        array $routeHandlers,
        RequestHandlerInterface $unmatchedHandler,
        MiddlewareInterface ...$middleware,
    ): self {
        $routingHandler = new RoutingRequestHandler($routes, $routeHandlers, $unmatchedHandler);
        $pipeline = new MiddlewarePipeline($routingHandler, ...$middleware);
        return new self($kernel, $pipeline);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->retired) {
            throw new RetiredWebApplicationException();
        }

        try {
            $scope = $this->kernel->beginOperation();
        } catch (LifecycleException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            $this->retired = true;

            throw $failure;
        }

        try {
            $response = $this->pipeline->handle($request);
        } catch (Throwable $primaryFailure) {
            try {
                $this->kernel->endOperation($scope);
            } catch (Throwable $cleanupFailure) {
                $this->retired = true;

                throw new OperationCleanupException($primaryFailure, $cleanupFailure);
            }

            throw $primaryFailure;
        }

        try {
            $this->kernel->endOperation($scope);
        } catch (Throwable $cleanupFailure) {
            $this->retired = true;

            throw $cleanupFailure;
        }

        return $response;
    }

    public function shutdown(): void
    {
        if ($this->retired) {
            throw new RetiredWebApplicationException();
        }

        try {
            $this->kernel->shutdown();
        } catch (LifecycleException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            $this->retired = true;

            throw $failure;
        }
    }

    public function isRetired(): bool
    {
        return $this->retired;
    }
}
