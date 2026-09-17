<?php

declare(strict_types=1);

use CacheMinimalApp\CacheDemo;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $result = (new CacheDemo())->run(str_replace('\\', '/', dirname(__DIR__)));
    ksort($result, SORT_STRING);
    echo json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
} catch (Throwable) {
    fwrite(STDERR, "{\"error\":\"cache_demo_failed\"}\n");
    exit(1);
}
