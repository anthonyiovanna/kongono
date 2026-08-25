<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;
use Kongono\DatabaseMigration\Internal\MigrationCanonicalJson;

/** Stores one ordered migration with exact Apply, optional Revert, and derived identity. */
#[Api(ApiStatus::Experimental)]
final readonly class MigrationDefinition
{
    private string $canonicalBytes;

    private string $sha256;

    public function __construct(
        private int $ordinal,
        private string $id,
        private MigrationDirectionPlan $apply,
        private ?MigrationDirectionPlan $revert = null,
    ) {
        if ($ordinal < 1 || $ordinal > 4_096 || strlen($id) > 64 || 1 !== preg_match('/\A[a-z][a-z0-9_]*\z/D', $id)) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
        $this->canonicalBytes = MigrationCanonicalJson::definition($ordinal, $id, $apply, $revert);
        $this->sha256 = hash('sha256', $this->canonicalBytes);
    }

    public function ordinal(): int
    {
        return $this->ordinal;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function apply(): MigrationDirectionPlan
    {
        return $this->apply;
    }

    public function revert(): ?MigrationDirectionPlan
    {
        return $this->revert;
    }

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }
}
