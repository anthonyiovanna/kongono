<?php

declare(strict_types=1);

namespace Kongono\DatabasePdoPgsql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseConnector;
use Kongono\Database\DatabaseSession;

/** Opens independent PostgreSQL PDO sessions from one explicit immutable configuration. */
#[Api(ApiStatus::Experimental)]
final readonly class PdoPgsqlConnector implements DatabaseConnector
{
    public function __construct(private PdoPgsqlConfiguration $configuration) {}

    public function openSession(): DatabaseSession
    {
        return $this->configuration->createSession();
    }
}
