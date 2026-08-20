<?php

declare(strict_types=1);

namespace Kongono\Tests\BlackBox\Fixture;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/** Produces one unsupported native application failure. */
final class GenericFailureHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new RuntimeException('fixture-generic-failure-sentinel');
    }
}
