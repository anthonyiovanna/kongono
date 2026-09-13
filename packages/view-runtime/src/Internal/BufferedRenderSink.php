<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Generated\RenderSink;
use Kongono\ViewContract\Lifecycle\RenderOperationState;
use Kongono\ViewContract\Output\OutputContext;
use Kongono\ViewContract\Render\RenderedOutput;
use Kongono\ViewContract\Render\RenderLimits;
use Kongono\ViewContract\Render\RenderMetadata;
use Kongono\ViewContract\Value\PresentationValue;
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewRuntime\Exception\InvalidRenderRequestException;
use Kongono\ViewRuntime\Exception\RenderBoundExceededException;
use Kongono\ViewRuntime\Exception\ViewRenderingException;

/** Owns one operation-local output buffer, protocol state, and finite counters. */
#[Api(ApiStatus::Internal)]
final class BufferedRenderSink implements RenderSink
{
    private string $bytes = '';
    private int $stepCount = 0;
    private int $escapedValueCount = 0;
    private ?int $pendingOperationIndex = null;
    private RenderOperationState $state = RenderOperationState::Requested;

    public function __construct(private readonly RenderLimits $limits, private readonly ContextualEscaper $escaper = new ContextualEscaper()) {}

    public function begin(): void
    {
        if (RenderOperationState::Requested !== $this->state) {
            throw InvalidRenderRequestException::invalid('operation_state');
        }
        $this->state = RenderOperationState::Rendering;
    }

    public function step(int $operationIndex): void
    {
        $this->assertRendering();
        if ($operationIndex < 0) {
            throw ViewRenderingException::failed('protocol_violation', max(0, $operationIndex));
        }
        $observed = $this->stepCount + 1;
        if ($observed > $this->limits->maxRenderSteps()) {
            throw RenderBoundExceededException::exceeded('render_steps', $this->limits->maxRenderSteps(), $observed, $operationIndex);
        }
        $this->stepCount = $observed;
        $this->pendingOperationIndex = $operationIndex;
    }

    public function literal(string $bytes, int $operationIndex): void
    {
        $this->consume($bytes, $operationIndex, false);
    }

    public function value(PresentationValue $value, OutputContext $context, int $operationIndex): void
    {
        if (!$value instanceof StringValue) {
            throw ViewRenderingException::failed('non_string_value', max(0, $operationIndex));
        }
        $this->consume($this->escaper->escape($value->value(), $context, $operationIndex), $operationIndex, true);
    }

    /** @return array{RenderedOutput, RenderMetadata} */
    public function complete(): array
    {
        $this->assertRendering();
        try {
            $output = new RenderedOutput($this->bytes, $this->limits);
            $metadata = RenderMetadata::from($output, $this->limits, $this->stepCount, $this->escapedValueCount);
        } catch (\Throwable $failure) {
            throw ViewRenderingException::failed('output_invalid', previous: $failure);
        }
        $this->state = RenderOperationState::Completed;

        return [$output, $metadata];
    }

    public function fail(): void
    {
        if (RenderOperationState::Retired !== $this->state) {
            $this->state = RenderOperationState::Failed;
        }
    }

    public function retire(): void
    {
        $this->bytes = '';
        $this->stepCount = 0;
        $this->escapedValueCount = 0;
        $this->pendingOperationIndex = null;
        $this->state = RenderOperationState::Retired;
    }

    public function state(): RenderOperationState
    {
        return $this->state;
    }

    public function outputByteCount(): int
    {
        return strlen($this->bytes);
    }

    public function renderStepCount(): int
    {
        return $this->stepCount;
    }

    private function consume(string $bytes, int $operationIndex, bool $escaped): void
    {
        $this->assertRendering();
        if ($operationIndex < 0 || null === $this->pendingOperationIndex || $escaped && $this->pendingOperationIndex !== $operationIndex || !$escaped && $operationIndex < $this->pendingOperationIndex) {
            throw ViewRenderingException::failed('protocol_violation', max(0, $operationIndex));
        }
        $observed = strlen($this->bytes) + strlen($bytes);
        if ($observed > $this->limits->maxOutputBytes()) {
            throw RenderBoundExceededException::exceeded('output_bytes', $this->limits->maxOutputBytes(), $observed, $operationIndex);
        }
        $this->bytes .= $bytes;
        if ($escaped) {
            ++$this->escapedValueCount;
        }
    }

    private function assertRendering(): void
    {
        if (RenderOperationState::Rendering !== $this->state) {
            throw InvalidRenderRequestException::invalid('sink_protocol');
        }
    }
}
