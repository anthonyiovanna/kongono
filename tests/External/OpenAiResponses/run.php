<?php

declare(strict_types=1);

use Kongono\Tests\External\OpenAiResponses\Phase17OpenAiResponsesAcceptance;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
require __DIR__ . '/ProcessResult.php';
require __DIR__ . '/TemporaryOpenAiResponsesWorkspace.php';
require __DIR__ . '/Phase17OpenAiResponsesAcceptance.php';

/** @var list<string> $argv */
exit((new Phase17OpenAiResponsesAcceptance($root))->run(array_slice($argv, 1), STDOUT, STDERR));
