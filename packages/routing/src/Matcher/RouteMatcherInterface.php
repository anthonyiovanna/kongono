<?php

declare(strict_types=1);

namespace Kongono\Routing\Matcher;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Routing\Match\RouteMatch;
use Psr\Http\Message\ServerRequestInterface;

/** Matches bounded PSR-7 request routing inputs without dispatching application behavior. */
#[Api(ApiStatus::Experimental)]
interface RouteMatcherInterface
{
    public function match(ServerRequestInterface $request): ?RouteMatch;
}
