<?php

declare(strict_types=1);

namespace Kongono\Tests\External\PhpCgi\Fixture;

use Kongono\Kernel\Kernel;
use Kongono\PhpCgi\Bootstrap\CgiOperatorBootstrap;
use Kongono\PhpCgi\Bootstrap\CgiOperatorProfile;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Tests\BlackBox\Fixture\DiagnosticFailureHandler;
use Kongono\Tests\BlackBox\Fixture\GenericFailureHandler;
use Kongono\Tests\BlackBox\Fixture\GreetingHandler;
use Kongono\Tests\BlackBox\Fixture\NotFoundHandler;
use Kongono\Tests\BlackBox\Fixture\TraceMiddleware;
use Kongono\Web\Application\WebApplication;
use Kongono\Web\Runtime\WebRuntimeAdapter;
use RuntimeException;

/** Explicit test composition for one real PHP-CGI application request. */
final class Phase3AcceptanceApplication
{
    private bool $shutdown = false;

    private function __construct(
        private readonly Kernel $kernel,
        private readonly WebApplication $application,
        private readonly WebRuntimeAdapter $runtime,
    ) {}

    public static function boot(CgiOperatorProfile $profile): self
    {
        $bootstrap = CgiOperatorBootstrap::load($profile);
        $kernel = new Kernel();
        $routes = new RouteTable([
            new RouteDefinition('fixture.diagnostic_failure', ['GET'], '/fail/diagnostic'),
            new RouteDefinition('fixture.generic_failure', ['GET'], '/fail/generic'),
            new RouteDefinition('fixture.greeting', ['GET', 'HEAD'], '/hello/{name}'),
            new RouteDefinition('phase3.response', ['GET', 'HEAD'], '/response/{case}'),
        ]);
        $application = WebApplication::boot(
            $kernel,
            $routes,
            [
                'fixture.diagnostic_failure' => new DiagnosticFailureHandler(),
                'fixture.generic_failure' => new GenericFailureHandler(),
                'fixture.greeting' => new GreetingHandler(),
                'phase3.response' => new Phase3ResponseHandler(),
            ],
            new NotFoundHandler(),
            new TraceMiddleware('outer'),
            new TraceMiddleware('inner'),
        );

        return new self(
            $kernel,
            $application,
            new WebRuntimeAdapter($application, $bootstrap->bootstrapPermission()),
        );
    }

    public function runtime(): WebRuntimeAdapter
    {
        return $this->runtime;
    }

    public function shutdown(): void
    {
        if ($this->shutdown) {
            throw new RuntimeException('phase3_application_shutdown_repeated');
        }
        $this->application->shutdown();
        $this->shutdown = true;
    }

    public function shutdownComplete(): bool
    {
        return $this->shutdown;
    }

    public function activeOperationCount(): int
    {
        return $this->kernel->activeOperationCount();
    }
}
