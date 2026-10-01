<?php

declare(strict_types=1);

use Kongono\Tests\External\ApplicationEvents\Phase22ApplicationEventsAcceptance;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
require __DIR__ . '/ProcessResult.php';
require __DIR__ . '/TemporaryEventWorkspace.php';
require __DIR__ . '/Phase22ApplicationEventsAcceptance.php';

/** @var list<string> $argv */
exit((new Phase22ApplicationEventsAcceptance($root))->run(array_slice($argv, 1), STDOUT, STDERR));
