<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Exception\InvalidDatabaseValueException;

/** Carries a provider-bounded nullable affected-row count without generated-key semantics. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandResult
{
    public function __construct(private ?int $affectedRows)
    {
        if (null !== $affectedRows && $affectedRows < 0) {
            throw InvalidDatabaseValueException::validation();
        }
    }

    public function affectedRows(): ?int
    {
        return $this->affectedRows;
    }
}
