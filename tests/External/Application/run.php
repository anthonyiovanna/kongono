<?php

declare(strict_types=1);

use Kongono\Tests\External\Application\ApplicationAcceptance;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$arguments = $_SERVER['argv'] ?? [];
if (!is_array($arguments)) {
    $arguments = [];
}

exit((new ApplicationAcceptance())->run(array_slice($arguments, 1), STDOUT, STDERR));
