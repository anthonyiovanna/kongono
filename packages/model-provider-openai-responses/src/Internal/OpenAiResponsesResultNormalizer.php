<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Result\FinishReason;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationContract\Result\ResultIdentity;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageQuantity;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Response\DecodedResponsesResponse;
use Kongono\ModelProviderOpenAiResponses\Response\ResponseIncompleteReason;
use Kongono\ModelProviderOpenAiResponses\Response\ResponseStatus;
use Throwable;

/** Deterministically normalizes accepted provider claims into one neutral result. */
#[Api(ApiStatus::Internal)]
final class OpenAiResponsesResultNormalizer
{
    public function normalize(InvocationRequest $request, DecodedResponsesResponse $decoded): ModelResult
    {
        try {
            $usage = $decoded->usage();
            $usageEvidence = null === $usage ? null : new UsageEvidence([
                new UsageQuantity(new UsageMetricName('input_tokens'), new UsageUnitName('count'), $usage->inputTokens()),
                new UsageQuantity(new UsageMetricName('output_tokens'), new UsageUnitName('count'), $usage->outputTokens()),
                new UsageQuantity(new UsageMetricName('total_tokens'), new UsageUnitName('count'), $usage->totalTokens()),
            ]);
            $finish = ResultMode::ActionSuggestion === $decoded->resultMode()
                ? FinishReason::ActionSuggested
                : match ($decoded->status()) {
                    ResponseStatus::Completed => FinishReason::Completed,
                    ResponseStatus::Incomplete => match ($decoded->incompleteReason()) {
                        ResponseIncompleteReason::MaxTokens => FinishReason::Length,
                        ResponseIncompleteReason::ContentFilter => FinishReason::ContentFiltered,
                        null => throw OpenAiResponsesInvocationException::normalization('decoded_response'),
                    },
                };
            $identity = 'openai.responses.' . hash(
                'sha256',
                $decoded->mappingVersion()->value . "\0" . $decoded->responseId(),
            );

            return new ModelResult(
                $request->version(),
                new ResultIdentity($identity),
                $request->identity(),
                $request->profile(),
                $decoded->resultMode(),
                $finish,
                $decoded->optionalText(),
                $decoded->actionSuggestion(),
                $usageEvidence,
                null,
                self::visibility($request),
            );
        } catch (OpenAiResponsesInvocationException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw OpenAiResponsesInvocationException::normalization('decoded_response', $failure);
        }
    }

    private static function visibility(InvocationRequest $request): ModelVisibility
    {
        $visibility = ModelVisibility::Application;
        foreach ($request->messages() as $message) {
            if (ModelVisibility::Private === $message->visibility()) {
                return ModelVisibility::Private;
            }
            if (ModelVisibility::Protected === $message->visibility()) {
                $visibility = ModelVisibility::Protected;
            }
        }

        return $visibility;
    }
}
