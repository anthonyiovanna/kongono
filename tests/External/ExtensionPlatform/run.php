<?php

declare(strict_types=1);

use Kongono\Tests\External\ExtensionPlatform\Phase12ExtensionPlatformAcceptance;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$rawArguments = $_SERVER['argv'] ?? [];
if (!is_array($rawArguments)) {
    fwrite(STDERR, "phase12_acceptance_failed\n");
    exit(1);
}
$arguments = [];
foreach (array_slice($rawArguments, 1) as $argument) {
    if (!is_string($argument)) {
        fwrite(STDERR, "phase12_acceptance_failed\n");
        exit(1);
    }
    $arguments[] = $argument;
}

exit((new Phase12ExtensionPlatformAcceptance(dirname(__DIR__, 3)))->run($arguments, STDOUT, STDERR));
