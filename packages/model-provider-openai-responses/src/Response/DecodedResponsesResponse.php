<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Response;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Result\ActionSuggestion;
use Kongono\ModelInvocationContract\Result\ResultText;
use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesResponseDecodingException;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;

/** Stores bounded decoded provider claims without normalizing them into a model result. */
#[Api(ApiStatus::Internal)]
final readonly class DecodedResponsesResponse
{
    public function __construct(
        private OpenAiResponsesMappingVersion $mappingVersion,
        private string $responseId,
        private string $modelClaim,
        private ResponseStatus $status,
        private ?ResponseIncompleteReason $incompleteReason,
        private ResultMode $resultMode,
        private ?ResultText $text,
        private ?ActionSuggestion $actionSuggestion,
        private ?ResponseUsage $usage,
    ) {
        if (1 !== preg_match('/\Aresp_[A-Za-z0-9_-]{1,128}\z/D', $responseId)) {
            throw ResponsesResponseDecodingException::invalidStructure('response_id', 'id', mappingVersion: $mappingVersion);
        }
        if (1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $modelClaim) || strlen($modelClaim) > 128) {
            throw ResponsesResponseDecodingException::invalidStructure('model_claim', 'model', mappingVersion: $mappingVersion);
        }
        if ((ResponseStatus::Completed === $status && null !== $incompleteReason)
            || (ResponseStatus::Incomplete === $status && null === $incompleteReason)
        ) {
            throw ResponsesResponseDecodingException::invalidStructure('response_relation', 'status', mappingVersion: $mappingVersion);
        }
        $actionMode = ResultMode::ActionSuggestion === $resultMode;
        if (($actionMode && (null === $actionSuggestion || null !== $text))
            || (!$actionMode && (null === $text || null !== $actionSuggestion))
        ) {
            throw ResponsesResponseDecodingException::invalidStructure('response_relation', 'output', mappingVersion: $mappingVersion);
        }
    }

    public function mappingVersion(): OpenAiResponsesMappingVersion
    {
        return $this->mappingVersion;
    }

    public function responseId(): string
    {
        return $this->responseId;
    }

    public function modelClaim(): string
    {
        return $this->modelClaim;
    }

    public function status(): ResponseStatus
    {
        return $this->status;
    }

    public function incompleteReason(): ?ResponseIncompleteReason
    {
        return $this->incompleteReason;
    }

    public function resultMode(): ResultMode
    {
        return $this->resultMode;
    }

    public function text(): ResultText
    {
        if (null === $this->text) {
            throw ResponsesResponseDecodingException::invalidStructure('response_relation', 'output', mappingVersion: $this->mappingVersion);
        }

        return $this->text;
    }

    public function optionalText(): ?ResultText
    {
        return $this->text;
    }

    public function actionSuggestion(): ?ActionSuggestion
    {
        return $this->actionSuggestion;
    }

    public function usage(): ?ResponseUsage
    {
        return $this->usage;
    }

    public function equals(self $other): bool
    {
        return match ($this->mappingVersion) {
            OpenAiResponsesMappingVersion::Version1 => match ($other->mappingVersion) {
                OpenAiResponsesMappingVersion::Version1 => $this->responseId === $other->responseId
                    && $this->modelClaim === $other->modelClaim
                    && $this->status === $other->status
                    && $this->incompleteReason === $other->incompleteReason
                    && $this->resultMode === $other->resultMode
                    && (null === $this->text ? null === $other->text : null !== $other->text && $this->text->equals($other->text))
                    && (null === $this->actionSuggestion ? null === $other->actionSuggestion : null !== $other->actionSuggestion && $this->actionSuggestion->equals($other->actionSuggestion))
                    && ((null === $this->usage && null === $other->usage)
                        || (null !== $this->usage && null !== $other->usage && $this->usage->equals($other->usage))),
                OpenAiResponsesMappingVersion::Version2 => false,
            },
            OpenAiResponsesMappingVersion::Version2 => match ($other->mappingVersion) {
                OpenAiResponsesMappingVersion::Version1 => false,
                OpenAiResponsesMappingVersion::Version2 => $this->responseId === $other->responseId
                    && $this->modelClaim === $other->modelClaim
                    && $this->status === $other->status
                    && $this->incompleteReason === $other->incompleteReason
                    && $this->resultMode === $other->resultMode
                    && (null === $this->text ? null === $other->text : null !== $other->text && $this->text->equals($other->text))
                    && (null === $this->actionSuggestion ? null === $other->actionSuggestion : null !== $other->actionSuggestion && $this->actionSuggestion->equals($other->actionSuggestion))
                    && ((null === $this->usage && null === $other->usage)
                        || (null !== $this->usage && null !== $other->usage && $this->usage->equals($other->usage))),
            },
        };
    }
}
