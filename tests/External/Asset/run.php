<?php

declare(strict_types=1);

use Kongono\Tests\External\Asset\Phase19AssetAcceptance;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
require __DIR__ . '/ProcessResult.php';
require __DIR__ . '/TemporaryAssetWorkspace.php';
require __DIR__ . '/Phase19AssetAcceptance.php';

/** @var list<string> $argv */
exit((new Phase19AssetAcceptance($root))->run(array_slice($argv, 1), STDOUT, STDERR));
