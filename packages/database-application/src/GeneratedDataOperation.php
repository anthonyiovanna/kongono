<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Runtime\GeneratedOperationBoundary;
use Kongono\DatabaseApplication\Exception\DataApplicationLifecycleException;
use Throwable;

/** Owns one generated operation boundary and its exact typed root. */
#[Api(ApiStatus::Experimental)]
final class GeneratedDataOperation
{
    private ?GeneratedOperationBoundary $boundary;
    private ?DataOperationRoot $root;

    /** @internal Constructed only after generated root extraction succeeds. */
    public function __construct(GeneratedOperationBoundary $boundary, DataOperationRoot $root)
    {
        $this->boundary = $boundary;
        $this->root = $root;
    }

    private function __clone(): void {}

    public function root(): DataOperationRoot
    {
        if (null === $this->root) {
            throw new DataApplicationLifecycleException('operation_closed');
        }

        return $this->root;
    }

    public function close(?Throwable $primaryFailure = null): void
    {
        $boundary = $this->boundary;
        if (null === $boundary) {
            return;
        }

        $this->boundary = null;
        $this->root = null;
        $boundary->close($primaryFailure);
    }

    public function isClosed(): bool
    {
        return null === $this->boundary;
    }
}
