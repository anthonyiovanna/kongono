<?php

declare(strict_types=1);

namespace Kongono\Database;

/** Deterministic database seeder contract executed inside explicit connections. */
interface SeederInterface
{
    public function run(ConnectionInterface $db): void;
}
