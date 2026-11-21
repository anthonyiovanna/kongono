<?php

declare(strict_types=1);

use Kongono\Http\App;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

/** @var App $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$app->run();
