<?php

declare(strict_types=1);

use CacheMinimalApp\CacheWorkflow;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $root = str_replace('\\', '/', dirname(__DIR__));
    $operation = $argv[1] ?? '';
    $workflow = new CacheWorkflow($root);
    $data = match ($operation) {
        'plan' => (static function () use ($workflow): array {
            $result = $workflow->plan();
            return ['operation' => 'plan', 'profile_count' => $result->profileCount(), 'publication_required' => $result->publicationRequired()];
        })(),
        'build' => (static function () use ($workflow): array {
            $result = $workflow->build();
            return ['operation' => 'build', 'profile_count' => $result->profiles()->profileCount(), 'current' => true];
        })(),
        'check' => (static function () use ($workflow): array {
            $result = $workflow->check();
            return ['operation' => 'check', 'profile_count' => $result->profiles()->profileCount(), 'current' => true];
        })(),
        default => throw new RuntimeException('Expected plan, build, or check.'),
    };
    echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
} catch (Throwable) {
    fwrite(STDERR, "{\"error\":\"cache_workflow_failed\"}\n");
    exit(1);
}
