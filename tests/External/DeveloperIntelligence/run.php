<?php

declare(strict_types=1);

use Kongono\Tests\External\DeveloperIntelligence\Phase4AcceptanceRunner;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$arguments = $_SERVER['argv'] ?? [];
if (!is_array($arguments)) {
    $arguments = [];
}

exit((new Phase4AcceptanceRunner())->run(array_slice($arguments, 1), STDOUT, STDERR));
