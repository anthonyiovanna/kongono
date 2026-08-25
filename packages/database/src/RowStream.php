<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Provides forward-only, single-pass access to one ordered row result. */
#[Api(ApiStatus::Public)]
interface RowStream
{
    /** @return list<DatabaseColumn> */
    public function columns(): array;

    public function fetch(): ?DatabaseRow;

    public function close(): void;

    public function isClosed(): bool;
}
