<?php

declare(strict_types=1);

use Kongono\Tests\External\ModelInvocation\Phase14ModelInvocationAcceptance;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
require __DIR__ . '/ProcessResult.php';
require __DIR__ . '/TemporaryModelInvocationWorkspace.php';
require __DIR__ . '/Phase14ModelInvocationAcceptance.php';

/** @var list<string> $argv */
exit((new Phase14ModelInvocationAcceptance($root))->run(array_slice($argv, 1), STDOUT, STDERR));
