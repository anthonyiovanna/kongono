<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Response;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesResponseDecodingException;

/** Carries bounded provider token claims without asserting billing or cost truth. */
#[Api(ApiStatus::Internal)]
final readonly class ResponseUsage
{
    public const int MAXIMUM_TOKEN_VALUE = 2147483647;

    public function __construct(
        private int $inputTokens,
        private int $outputTokens,
        private int $totalTokens,
    ) {
        if ($inputTokens < 0 || $outputTokens < 0 || $totalTokens < 0
            || $inputTokens > self::MAXIMUM_TOKEN_VALUE
            || $outputTokens > self::MAXIMUM_TOKEN_VALUE
            || $totalTokens > self::MAXIMUM_TOKEN_VALUE
        ) {
            throw ResponsesResponseDecodingException::invalidStructure('usage_range', 'usage');
        }
        if ($totalTokens < $inputTokens || $totalTokens < $outputTokens) {
            throw ResponsesResponseDecodingException::invalidStructure('usage_relation', 'usage');
        }
    }

    public function inputTokens(): int
    {
        return $this->inputTokens;
    }

    public function outputTokens(): int
    {
        return $this->outputTokens;
    }

    public function totalTokens(): int
    {
        return $this->totalTokens;
    }

    public function equals(self $other): bool
    {
        return $this->inputTokens === $other->inputTokens
            && $this->outputTokens === $other->outputTokens
            && $this->totalTokens === $other->totalTokens;
    }
}
