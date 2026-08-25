<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;

/** Retains one exact applied-history identity without a timestamp or provider state. */
#[Api(ApiStatus::Experimental)]
final readonly class AppliedMigration
{
    public function __construct(private int $ordinal, private string $id, private string $checksum)
    {
        if ($ordinal < 1 || $ordinal > 4_096 || strlen($id) > 64 || 1 !== preg_match('/\A[a-z][a-z0-9_]*\z/D', $id)
            || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $checksum)) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
    }

    public function ordinal(): int
    {
        return $this->ordinal;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function checksum(): string
    {
        return $this->checksum;
    }
}
