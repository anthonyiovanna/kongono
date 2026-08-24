<?php

declare(strict_types=1);

namespace App;

use App\Http\HomeHandler;
use App\Http\NotFoundHandler;
use Kongono\Http\Server\MiddlewarePipeline;
use Kongono\Http\Server\RoutingRequestHandler;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;

/** Constructs the application's explicit route and middleware pipeline. */
final class Bootstrap
{
    private function __construct() {}

    public static function pipeline(): MiddlewarePipeline
    {
        $routes = new RouteTable([new RouteDefinition('home', ['GET'], '/')]);
        $routing = new RoutingRequestHandler($routes, ['home' => new HomeHandler()], new NotFoundHandler());

        return new MiddlewarePipeline($routing);
    }
}
