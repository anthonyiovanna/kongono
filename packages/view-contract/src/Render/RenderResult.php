<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Render;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use Kongono\ViewContract\Internal\ContractGuard;
use Kongono\ViewContract\Lifecycle\RenderOperationState;

/** Carries one completed neutral render result. */
#[Api(ApiStatus::Experimental)]
final readonly class RenderResult
{
    public function __construct(
        private ContractVersion $contractVersion,
        private RenderOperationIdentity $operationIdentity,
        private LogicalViewIdentity $logicalViewIdentity,
        private CompiledViewIdentity $compiledViewIdentity,
        private RenderedOutput $output,
        private RenderMetadata $metadata,
    ) {
        if ($output->byteCount() !== $metadata->outputByteCount()
            || hash('sha256', $output->bytes()) !== $metadata->outputSha256()) {
            throw InvalidViewContractException::invalidRenderResult('output_metadata_relation');
        }
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
    public function output(): RenderedOutput
    {
        return $this->output;
    }
    public function metadata(): RenderMetadata
    {
        return $this->metadata;
    }
    public function state(): RenderOperationState
    {
        return RenderOperationState::Completed;
    }

    public function equals(self $other): bool
    {
        return ContractGuard::sameContractVersion($this->contractVersion, $other->contractVersion)
        && $this->operationIdentity->equals($other->operationIdentity)
        && $this->logicalViewIdentity->equals($other->logicalViewIdentity)
        && $this->compiledViewIdentity->equals($other->compiledViewIdentity)
        && $this->output->equals($other->output)
        && $this->metadata->equals($other->metadata);
    }
}
