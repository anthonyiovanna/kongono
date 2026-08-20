<?php

declare(strict_types=1);

namespace Kongono\Tests\BlackBox\Fixture;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Produces one supported test-only Kernel diagnostic failure. */
final class DiagnosticFailureHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new FixtureKernelDiagnosticException();
    }
}
