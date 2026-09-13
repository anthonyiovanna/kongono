<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Render;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Exception\InvalidViewContractException;

/** Carries deterministic metadata derived from completed output. */
#[Api(ApiStatus::Experimental)]
final readonly class RenderMetadata
{
    private function __construct(
        private int $outputByteCount,
        private string $outputSha256,
        private int $renderStepCount,
        private int $escapedValueCount,
    ) {}

    public static function from(RenderedOutput $output, RenderLimits $limits, int $renderStepCount, int $escapedValueCount): self
    {
        if ($renderStepCount < 0 || $renderStepCount > $limits->maxRenderSteps()) {
            throw InvalidViewContractException::invalidRenderResult('render_step_count_range', limit: $limits->maxRenderSteps());
        }
        if ($escapedValueCount < 0 || $escapedValueCount > $renderStepCount) {
            throw InvalidViewContractException::invalidRenderResult('escaped_value_count_relation');
        }

        return new self($output->byteCount(), hash('sha256', $output->bytes()), $renderStepCount, $escapedValueCount);
    }

    public function outputByteCount(): int
    {
        return $this->outputByteCount;
    }

    public function outputSha256(): string
    {
        return $this->outputSha256;
    }

    public function renderStepCount(): int
    {
        return $this->renderStepCount;
    }

    public function escapedValueCount(): int
    {
        return $this->escapedValueCount;
    }

    public function equals(self $other): bool
    {
        return $this->outputByteCount === $other->outputByteCount
            && $this->outputSha256 === $other->outputSha256
            && $this->renderStepCount === $other->renderStepCount
            && $this->escapedValueCount === $other->escapedValueCount;
    }
}
