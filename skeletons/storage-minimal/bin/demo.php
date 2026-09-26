<?php

declare(strict_types=1);

use StorageMinimalApp\StorageDemo;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $arguments = $_SERVER['argv'] ?? [];
    if (!is_array($arguments) || !array_is_list($arguments) || 5 !== count($arguments)
        || !is_string($arguments[1]) || !is_string($arguments[2]) || !is_string($arguments[3]) || !is_string($arguments[4])) {
        throw new RuntimeException('Expected application root, source file, profile output and object root.');
    }
    [, $applicationRoot, $sourceFile, $outputDirectory, $objectRoot] = $arguments;
    $result = (new StorageDemo())->run($applicationRoot, $sourceFile, $outputDirectory, $objectRoot);
    echo json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
} catch (Throwable) {
    fwrite(STDERR, "{\"error\":\"storage_demo_failed\"}\n");
    exit(1);
}
