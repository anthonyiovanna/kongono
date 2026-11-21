<?php

declare(strict_types=1);

namespace App\Http;

use Kongono\Http\Response;
use Kongono\Http\Routing\Get;
use Kongono\View\ViewEngine;

final readonly class HomeController
{
    public function __construct(
        private ViewEngine $views,
    ) {}

    #[Get('/', name: 'home')]
    public function index(): Response
    {
        return Response::html($this->views->render('home', [
            'title' => 'My App',
            'phpVersion' => PHP_VERSION,
        ]));
    }

    #[Get('/api/health', name: 'api.health')]
    public function health(): Response
    {
        return Response::json(['status' => 'ok', 'app' => 'My App']);
    }
}
