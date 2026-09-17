<?php

declare(strict_types=1);

use Kongono\Tests\External\Cache\Phase20CacheAcceptance;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
require __DIR__ . '/ProcessResult.php';
require __DIR__ . '/TemporaryCacheWorkspace.php';
require __DIR__ . '/Phase20CacheAcceptance.php';

/** @var list<string> $argv */
exit((new Phase20CacheAcceptance($root))->run(array_slice($argv, 1), STDOUT, STDERR));
