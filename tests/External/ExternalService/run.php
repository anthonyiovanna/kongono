<?php

declare(strict_types=1);

use Kongono\Tests\External\ExternalService\Phase16ExternalServiceAcceptance;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
require __DIR__ . '/ProcessResult.php';
require __DIR__ . '/TemporaryExternalServiceWorkspace.php';
require __DIR__ . '/Phase16ExternalServiceAcceptance.php';

/** @var list<string> $argv */
exit((new Phase16ExternalServiceAcceptance($root))->run(array_slice($argv, 1), STDOUT, STDERR));
