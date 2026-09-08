<?php

declare(strict_types=1);

use Kongono\Tests\External\NetworkEgress\Phase15NetworkEgressAcceptance;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
require __DIR__ . '/ProcessResult.php';
require __DIR__ . '/TemporaryNetworkEgressWorkspace.php';
require __DIR__ . '/Phase15NetworkEgressAcceptance.php';

/** @var list<string> $argv */
exit((new Phase15NetworkEgressAcceptance($root))->run(array_slice($argv, 1), STDOUT, STDERR));
