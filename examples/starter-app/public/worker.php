<?php

declare(strict_types=1);

use Kongono\Http\App;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

/**
 * FrankenPHP persistent worker entrypoint.
 * Boots the Kongono App once and serves up to 1,000 isolated requests per worker lifecycle.
 *
 * @var App $app
 */
$app = require dirname(__DIR__) . '/app/bootstrap.php';

if (function_exists('frankenphp_handle_request')) {
    $app->runWorker(
        static fn(Closure $handle): bool => (bool) frankenphp_handle_request($handle),
        maxRequests: 1000,
    );
} else {
    // Graceful fallback when executed under PHP-FPM or `kongo serve`
    $app->run();
}
