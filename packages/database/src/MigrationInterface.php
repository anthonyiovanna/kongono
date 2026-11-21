<?php

declare(strict_types=1);

namespace Kongono\Database;

interface MigrationInterface
{
    public function id(): string;

    public function up(ConnectionInterface $db): void;

    public function down(ConnectionInterface $db): void;
}
