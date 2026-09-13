<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Source\SourcePointer;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Output\OutputContext;

/** Carries one bounded source-to-generated-operation evidence record. */
#[Api(ApiStatus::Internal)]
final readonly class SourceMapEntry
{
    public function __construct(
        private LogicalViewIdentity $viewIdentity,
        private int $operationIndex,
        private SourcePointer $sourcePointer,
        private string $operationKind,
        private ?OutputContext $outputContext,
    ) {}

    public function viewIdentity(): LogicalViewIdentity
    {
        return $this->viewIdentity;
    }
    public function operationIndex(): int
    {
        return $this->operationIndex;
    }
    public function sourcePointer(): SourcePointer
    {
        return $this->sourcePointer;
    }
    public function operationKind(): string
    {
        return $this->operationKind;
    }
    public function outputContext(): ?OutputContext
    {
        return $this->outputContext;
    }

    public function equals(self $other): bool
    {
        return $this->viewIdentity->equals($other->viewIdentity)
            && $this->operationIndex === $other->operationIndex
            && $this->sourcePointer->equals($other->sourcePointer)
            && $this->operationKind === $other->operationKind
            && $this->outputContext === $other->outputContext;
    }
}
