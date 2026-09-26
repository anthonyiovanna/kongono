<?php

declare(strict_types=1);

use Kongono\Tests\External\Storage\Phase21StorageAcceptance;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
require __DIR__ . '/ProcessResult.php';
require __DIR__ . '/TemporaryStorageWorkspace.php';
require __DIR__ . '/Phase21StorageAcceptance.php';

/** @var list<string> $argv */
exit((new Phase21StorageAcceptance($root))->run(array_slice($argv, 1), STDOUT, STDERR));
