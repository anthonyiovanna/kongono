<?php

declare(strict_types=1);

namespace Kongono\Database;

/**
 * Marker interface for migrations that must execute outside an explicit transaction block
 * (for example PostgreSQL `CREATE INDEX CONCURRENTLY` or `DROP INDEX CONCURRENTLY`).
 */
interface NonTransactionalMigrationInterface extends MigrationInterface {}
