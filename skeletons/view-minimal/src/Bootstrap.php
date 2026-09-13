<?php

declare(strict_types=1);

namespace ViewMinimalApp;

use Kongono\Http\Server\MiddlewarePipeline;
use Kongono\Http\Server\RoutingRequestHandler;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\ViewApplication\Runtime\ViewApplication;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use ViewMinimalApp\Http\HomeHandler;
use ViewMinimalApp\Http\NotFoundHandler;

/** Constructs the application's explicit route and middleware pipeline. */
final class Bootstrap
{
    private function __construct() {}

    public static function pipeline(ViewApplication $views, CompiledViewIdentity $identity): MiddlewarePipeline
    {
        $routes = new RouteTable([new RouteDefinition('home', ['GET'], '/')]);
        $routing = new RoutingRequestHandler($routes, ['home' => new HomeHandler($views, $identity)], new NotFoundHandler());

        return new MiddlewarePipeline($routing);
    }
}
