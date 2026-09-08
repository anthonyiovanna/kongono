<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Transfer;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;

/** Stores bounded transfer claims without reading streams or claiming remote processing. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressTransferEvidence
{
    public function __construct(
        private EgressTransferState $state,
        private int $requestBytes,
        private int $responseBytes,
    ) {
        self::bytes($requestBytes, 'transfer_request_bytes');
        self::bytes($responseBytes, 'transfer_response_bytes');

        $valid = match ($state) {
            EgressTransferState::None => 0 === $requestBytes && 0 === $responseBytes,
            EgressTransferState::RequestPartiallySent, EgressTransferState::RequestSent => $requestBytes > 0 && 0 === $responseBytes,
            EgressTransferState::ResponsePartiallyReceived, EgressTransferState::ResponseReceived => $requestBytes > 0 && $responseBytes > 0,
        };
        if (!$valid) {
            throw InvalidNetworkEgressContractException::invalidOutcomeOrReceipt('transfer_state_relation', state: $state->value);
        }
    }

    public function state(): EgressTransferState
    {
        return $this->state;
    }

    public function requestBytes(): int
    {
        return $this->requestBytes;
    }

    public function responseBytes(): int
    {
        return $this->responseBytes;
    }

    public function equals(self $other): bool
    {
        return $this->state === $other->state
            && $this->requestBytes === $other->requestBytes
            && $this->responseBytes === $other->responseBytes;
    }

    private static function bytes(int $value, string $limitViolation): void
    {
        if ($value > 1073741824) {
            throw InvalidNetworkEgressContractException::contractLimitExceeded($limitViolation, 1073741824);
        }
        if ($value < 0) {
            throw InvalidNetworkEgressContractException::invalidOutcomeOrReceipt('transfer_byte_range');
        }
    }
}
