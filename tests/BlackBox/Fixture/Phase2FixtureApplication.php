<?php

declare(strict_types=1);

namespace Kongono\Tests\BlackBox\Fixture;

use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\Http\Message\Factory\ServerRequestFactory;
use Kongono\Kernel\Kernel;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Web\Application\WebApplication;
use Kongono\Web\Runtime\WebRuntimeAdapter;
use RuntimeException;

/** Explicit test-owned composition root for the complete Phase 2 server-side path. */
final class Phase2FixtureApplication
{
    private bool $shutdown = false;

    private function __construct(
        private readonly Kernel $kernel,
        private readonly WebApplication $application,
        private readonly WebRuntimeAdapter $runtime,
        private readonly ServerRequestFactory $requests,
    ) {}

    public static function boot(TrustedDeveloperBootstrapPermission $bootstrapPermission): self
    {
        $kernel = new Kernel();
        $routes = new RouteTable([
            new RouteDefinition('fixture.diagnostic_failure', ['GET'], '/fail/diagnostic'),
            new RouteDefinition('fixture.generic_failure', ['GET'], '/fail/generic'),
            new RouteDefinition('fixture.greeting', ['GET'], '/hello/{name}'),
        ]);
        $application = WebApplication::boot(
            $kernel,
            $routes,
            [
                'fixture.diagnostic_failure' => new DiagnosticFailureHandler(),
                'fixture.generic_failure' => new GenericFailureHandler(),
                'fixture.greeting' => new GreetingHandler(),
            ],
            new NotFoundHandler(),
            new TraceMiddleware('outer'),
            new TraceMiddleware('inner'),
        );

        return new self(
            $kernel,
            $application,
            new WebRuntimeAdapter($application, $bootstrapPermission),
            new ServerRequestFactory(),
        );
    }

    public function deliver(
        string $method,
        string $uri,
        AdapterControlledDeliveryPermission $deliveryPermission,
    ): FixtureDelivery {
        if ($this->shutdown) {
            throw new RuntimeException('Fixture application is shut down.');
        }

        $request = $this->requests->createServerRequest($method, $uri, []);
        $delivery = new FixtureDelivery($request, $deliveryPermission);
        $this->runtime->handle($delivery);
        if (!$delivery->isClosed()) {
            throw new RuntimeException('Fixture delivery cleanup is incomplete.');
        }

        return $delivery;
    }

    public function activeOperationCount(): int
    {
        return $this->kernel->activeOperationCount();
    }

    public function shutdown(): void
    {
        if ($this->shutdown) {
            throw new RuntimeException('Fixture application was shut down more than once.');
        }
        $this->application->shutdown();
        $this->shutdown = true;
    }
}
