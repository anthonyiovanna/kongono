<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies one column with an optional single-segment qualifier. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlColumn
{
    public function __construct(
        private SqlIdentifier $identifier,
        private ?SqlIdentifier $qualifier = null,
    ) {}

    public function identifier(): SqlIdentifier
    {
        return $this->identifier;
    }

    public function qualifier(): ?SqlIdentifier
    {
        return $this->qualifier;
    }
}
