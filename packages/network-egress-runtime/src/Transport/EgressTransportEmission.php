<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Transport;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use Psr\Http\Message\ResponseInterface;

/** Carries one bounded progress or terminal observation without consuming a body. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressTransportEmission
{
    public function __construct(
        private EgressTransferEvidence $transfer,
        private NegotiatedProtocol $negotiatedProtocol,
        private int $bufferedBytes,
        private int $retainedEvidenceBytes,
        private ?ResponseInterface $response,
        private ?EgressFailure $failure,
    ) {
        if ($bufferedBytes < 0 || $bufferedBytes > 16_777_216 || $retainedEvidenceBytes < 0 || $retainedEvidenceBytes > 1_048_576) {
            throw InvalidEgressRuntimeException::invalid('emission_bounds');
        }
        if (null !== $response && null !== $failure) {
            throw InvalidEgressRuntimeException::invalid('emission_shape');
        }
        if (null !== $response && (EgressTransferState::ResponseReceived !== $transfer->state() || NegotiatedProtocol::Unknown === $negotiatedProtocol)) {
            throw InvalidEgressRuntimeException::invalid('response_shape');
        }
    }

    public function transfer(): EgressTransferEvidence
    {
        return $this->transfer;
    }
    public function negotiatedProtocol(): NegotiatedProtocol
    {
        return $this->negotiatedProtocol;
    }
    public function bufferedBytes(): int
    {
        return $this->bufferedBytes;
    }
    public function retainedEvidenceBytes(): int
    {
        return $this->retainedEvidenceBytes;
    }
    public function response(): ?ResponseInterface
    {
        return $this->response;
    }
    public function failure(): ?EgressFailure
    {
        return $this->failure;
    }
    public function terminal(): bool
    {
        return null !== $this->response || null !== $this->failure;
    }
}
