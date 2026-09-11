<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Stream\ModelStreamEvent;
use Kongono\ModelInvocationContract\Stream\StreamEventKind;
use Kongono\ModelInvocationContract\Stream\StreamSequence;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageQuantity;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Response\DecodedResponsesResponse;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseEventKind;
use Throwable;

/** Maps accepted provider evidence to contiguous neutral stream evidence. */
#[Api(ApiStatus::Internal)]
final class OpenAiResponsesStreamNormalizer
{
    private int $sequence = 0;

    public function started(InvocationRequest $request): ModelStreamEvent
    {
        return $this->event($request, StreamEventKind::Started);
    }

    public function delta(InvocationRequest $request, string $delta): ModelStreamEvent
    {
        try {
            return $this->event($request, StreamEventKind::ContentDelta, $delta);
        } catch (Throwable $failure) {
            throw OpenAiResponsesInvocationException::streamingMapping('delta', eventKind: ResponsesSseEventKind::OutputTextDelta, previous: $failure);
        }
    }

    public function usage(InvocationRequest $request, DecodedResponsesResponse $response): ?ModelStreamEvent
    {
        $usage = $response->usage();
        if (null === $usage) {
            return null;
        }
        $evidence = new UsageEvidence([
            new UsageQuantity(new UsageMetricName('input_tokens'), new UsageUnitName('count'), $usage->inputTokens()),
            new UsageQuantity(new UsageMetricName('output_tokens'), new UsageUnitName('count'), $usage->outputTokens()),
            new UsageQuantity(new UsageMetricName('total_tokens'), new UsageUnitName('count'), $usage->totalTokens()),
        ]);

        return $this->event($request, StreamEventKind::Usage, usage: $evidence);
    }

    public function completed(InvocationRequest $request): ModelStreamEvent
    {
        return $this->event($request, StreamEventKind::Completed);
    }

    public function failed(InvocationRequest $request): ModelStreamEvent
    {
        return $this->event($request, StreamEventKind::Failed);
    }

    private function event(InvocationRequest $request, StreamEventKind $kind, ?string $payload = null, ?UsageEvidence $usage = null): ModelStreamEvent
    {
        return new ModelStreamEvent($request->identity(), new StreamSequence(++$this->sequence), $kind, $payload, $usage);
    }
}
