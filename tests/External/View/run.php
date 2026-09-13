<?php

declare(strict_types=1);

use Kongono\Tests\External\View\Phase18ViewAcceptance;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
require __DIR__ . '/ProcessResult.php';
require __DIR__ . '/TemporaryViewWorkspace.php';
require __DIR__ . '/Phase18ViewAcceptance.php';

/** @var list<string> $argv */
exit((new Phase18ViewAcceptance($root))->run(array_slice($argv, 1), STDOUT, STDERR));
