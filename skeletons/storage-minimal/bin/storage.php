<?php

declare(strict_types=1);

use StorageMinimalApp\StorageWorkflow;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $arguments = $_SERVER['argv'] ?? [];
    if (!is_array($arguments) || !array_is_list($arguments) || 5 !== count($arguments)
        || !is_string($arguments[1]) || !is_string($arguments[2]) || !is_string($arguments[3]) || !is_string($arguments[4])) {
        throw new RuntimeException('Expected operation, application root, source file and output directory.');
    }
    [, $operation, $applicationRoot, $sourceFile, $outputDirectory] = $arguments;
    $workflow = new StorageWorkflow($applicationRoot, $sourceFile, $outputDirectory);
    $result = match ($operation) {
        'plan' => \Kongono\StorageApplication\Summary\StorageApplicationSummary::fromPlan($workflow->plan()),
        'build' => \Kongono\StorageApplication\Summary\StorageApplicationSummary::fromCurrent($workflow->build(), 'apply'),
        'check' => \Kongono\StorageApplication\Summary\StorageApplicationSummary::fromCurrent($workflow->check()),
        default => throw new RuntimeException('Unsupported operation.'),
    };
    echo json_encode([
        'schema' => $result->schema(),
        'version' => $result->version(),
        'profile_count' => $result->profileCount(),
        'current' => $result->current(),
        'publication_required' => $result->publicationRequired(),
        'execution_intent' => $result->executionIntent(),
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
} catch (Throwable) {
    fwrite(STDERR, "{\"error\":\"storage_workflow_failed\"}\n");
    exit(1);
}
