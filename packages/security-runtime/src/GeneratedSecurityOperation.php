<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Runtime\GeneratedOperationBoundary;
use Kongono\IdentityAccess\SecurityContext;
use Kongono\SecurityRuntime\Exception\SecurityRuntimeLifecycleException;
use Throwable;

/** Owns one generated operation, its exact typed root, and its exact Security Context. */
#[Api(ApiStatus::Experimental)]
final class GeneratedSecurityOperation
{
    private ?GeneratedOperationBoundary $boundary;
    private ?SecurityOperationRoot $root;
    private ?SecurityContext $securityContext;

    /** @internal Constructed only after exact generated root extraction succeeds. */
    public function __construct(
        GeneratedOperationBoundary $boundary,
        SecurityOperationRoot $root,
        SecurityContext $securityContext,
    ) {
        $this->boundary = $boundary;
        $this->root = $root;
        $this->securityContext = $securityContext;
    }

    private function __clone(): void {}

    public function root(): SecurityOperationRoot
    {
        if (null === $this->root) {
            throw SecurityRuntimeLifecycleException::operationClosed();
        }

        return $this->root;
    }

    public function securityContext(): SecurityContext
    {
        if (null === $this->securityContext) {
            throw SecurityRuntimeLifecycleException::operationClosed();
        }

        return $this->securityContext;
    }

    public function close(?Throwable $primaryFailure = null): void
    {
        $boundary = $this->boundary;
        if (null === $boundary) {
            return;
        }

        $this->boundary = null;
        $this->root = null;
        $this->securityContext = null;
        $boundary->close($primaryFailure);
    }

    public function isClosed(): bool
    {
        return null === $this->boundary;
    }
}
