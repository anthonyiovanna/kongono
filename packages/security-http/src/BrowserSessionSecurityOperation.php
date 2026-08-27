<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\SecurityContext;
use Kongono\SecurityRuntime\Exception\SecurityRuntimeLifecycleException;
use Kongono\SecurityRuntime\GeneratedSecurityOperation;
use Kongono\SecurityRuntime\SecurityOperationRoot;
use Throwable;

/** Owns and explicitly closes one generated browser-session security operation. */
#[Api(ApiStatus::Experimental)]
final class BrowserSessionSecurityOperation
{
    private ?GeneratedSecurityOperation $operation;

    public function __construct(GeneratedSecurityOperation $operation)
    {
        $this->operation = $operation;
    }

    public function root(): SecurityOperationRoot
    {
        return $this->openOperation()->root();
    }

    public function securityContext(): SecurityContext
    {
        return $this->openOperation()->securityContext();
    }

    public function close(?Throwable $primaryFailure = null): void
    {
        $operation = $this->operation;
        if (null === $operation) {
            return;
        }

        $this->operation = null;
        $operation->close($primaryFailure);
    }

    public function isClosed(): bool
    {
        return null === $this->operation;
    }

    private function openOperation(): GeneratedSecurityOperation
    {
        if (null === $this->operation) {
            throw SecurityRuntimeLifecycleException::operationClosed();
        }

        return $this->operation;
    }
}
