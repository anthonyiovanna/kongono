<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Failure;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use Kongono\ViewContract\Internal\ContractGuard;
use Kongono\ViewContract\Lifecycle\RenderOperationState;

/** Carries bounded failed-state evidence without raw failure material. */
#[Api(ApiStatus::Experimental)]
final readonly class RenderFailure
{
    public function __construct(
        private ContractVersion $contractVersion,
        private RenderOperationIdentity $operationIdentity,
        private LogicalViewIdentity $logicalViewIdentity,
        private CompiledViewIdentity $compiledViewIdentity,
        private RenderFailureKind $kind,
    ) {}

    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
    }
    public function operationIdentity(): RenderOperationIdentity
    {
        return $this->operationIdentity;
    }
    public function logicalViewIdentity(): LogicalViewIdentity
    {
        return $this->logicalViewIdentity;
    }
    public function compiledViewIdentity(): CompiledViewIdentity
    {
        return $this->compiledViewIdentity;
    }
    public function kind(): RenderFailureKind
    {
        return $this->kind;
    }
    public function state(): RenderOperationState
    {
        return RenderOperationState::Failed;
    }

    public function equals(self $other): bool
    {
        return ContractGuard::sameContractVersion($this->contractVersion, $other->contractVersion)
        && $this->operationIdentity->equals($other->operationIdentity)
        && $this->logicalViewIdentity->equals($other->logicalViewIdentity)
        && $this->compiledViewIdentity->equals($other->compiledViewIdentity)
        && $this->kind === $other->kind;
    }
}
