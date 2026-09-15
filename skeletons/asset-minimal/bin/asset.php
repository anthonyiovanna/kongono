<?php

declare(strict_types=1);

use AssetMinimalApp\AssetWorkflow;

require dirname(__DIR__) . '/vendor/autoload.php';

$operation = $argv[1] ?? '';
try {
    $workflow = new AssetWorkflow(dirname(__DIR__));
    $record = match ($operation) {
        'build' => AssetWorkflow::assetRecord($workflow->build()),
        'check' => AssetWorkflow::assetRecord($workflow->check()),
        'resolve' => AssetWorkflow::resolvedRecord($workflow->resolve()),
        default => throw new RuntimeException('Unsupported asset operation.'),
    };
    fwrite(STDOUT, json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");
    exit(0);
} catch (Throwable) {
    fwrite(STDERR, "{\"schema\":\"kongono.asset-minimal-failure\",\"version\":1}\n");
    exit(1);
}
