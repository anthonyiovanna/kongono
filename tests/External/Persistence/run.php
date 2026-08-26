<?php

declare(strict_types=1);

use Kongono\Tests\External\Persistence\PersistenceAcceptance;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$arguments = $_SERVER['argv'] ?? [];
if (!is_array($arguments)) {
    $arguments = [];
}

exit((new PersistenceAcceptance())->run(array_values(array_slice($arguments, 1)), STDOUT, STDERR));
