<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Stores exact transaction isolation and access requirements. */
#[Api(ApiStatus::Experimental)]
final readonly class TransactionOptions
{
    public function __construct(
        private TransactionIsolation $isolation = TransactionIsolation::Default,
        private TransactionAccess $access = TransactionAccess::ReadWrite,
    ) {}

    public function isolation(): TransactionIsolation
    {
        return $this->isolation;
    }

    public function access(): TransactionAccess
    {
        return $this->access;
    }
}
