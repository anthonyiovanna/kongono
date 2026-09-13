<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Render;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use Kongono\ViewContract\Internal\ContractGuard;
use Kongono\ViewContract\Lifecycle\RenderOperationState;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewContract\Value\ViewContext;

/** Carries one validated neutral render request. */
#[Api(ApiStatus::Experimental)]
final readonly class RenderRequest
{
    public function __construct(
        private ContractVersion $contractVersion,
        private RenderOperationIdentity $operationIdentity,
        private LogicalViewIdentity $logicalViewIdentity,
        private CompiledViewIdentity $compiledViewIdentity,
        private ViewContextSchema $contextSchema,
        private ViewContext $context,
        private RenderLimits $limits,
    ) {
        $contextSchema->validate($context);
    }

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

    public function contextSchema(): ViewContextSchema
    {
        return $this->contextSchema;
    }

    public function context(): ViewContext
    {
        return $this->context;
    }

    public function limits(): RenderLimits
    {
        return $this->limits;
    }

    public function state(): RenderOperationState
    {
        return RenderOperationState::Requested;
    }

    public function equals(self $other): bool
    {
        return ContractGuard::sameContractVersion($this->contractVersion, $other->contractVersion)
        && $this->operationIdentity->equals($other->operationIdentity)
        && $this->logicalViewIdentity->equals($other->logicalViewIdentity)
        && $this->compiledViewIdentity->equals($other->compiledViewIdentity)
        && $this->contextSchema->equals($other->contextSchema)
        && $this->context->equals($other->context)
        && $this->limits->equals($other->limits);
    }
}
