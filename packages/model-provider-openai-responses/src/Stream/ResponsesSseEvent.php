<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Stream;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one strictly decoded provider event for immediate state validation. */
#[Api(ApiStatus::Internal)]
final readonly class ResponsesSseEvent
{
    /** @param array<string, mixed> $members */
    public function __construct(
        private ResponsesSseEventKind $kind,
        private int $sequenceNumber,
        private array $members,
    ) {}

    public function kind(): ResponsesSseEventKind
    {
        return $this->kind;
    }

    public function sequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    /** @return array<string, mixed> */
    public function members(): array
    {
        return $this->members;
    }
}
