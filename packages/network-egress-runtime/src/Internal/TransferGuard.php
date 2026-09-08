<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;

/** Enforces cumulative transfer, protocol, emission, and evidence bounds. */
#[Api(ApiStatus::Internal)]
final class TransferGuard
{
    private EgressTransferEvidence $transfer;
    private NegotiatedProtocol $protocol = NegotiatedProtocol::Unknown;
    private int $emissions = 0;
    private int $progress = 0;
    private int $peakBuffered = 0;
    private int $retainedEvidence = 0;

    public function __construct(private readonly EgressBudget $budget, private readonly TransportCapabilitySet $capabilities)
    {
        $this->transfer = new EgressTransferEvidence(EgressTransferState::None, 0, 0);
    }

    public function accept(EgressTransportEmission $emission): void
    {
        ++$this->emissions;
        if ($this->emissions > 4_096) {
            throw InvalidEgressRuntimeException::invalid('emission_limit');
        }
        $next = $emission->transfer();
        $order = array_flip(array_map(static fn(EgressTransferState $state): string => $state->value, EgressTransferState::cases()));
        if ($order[$next->state()->value] < $order[$this->transfer->state()->value] || $next->requestBytes() < $this->transfer->requestBytes() || $next->responseBytes() < $this->transfer->responseBytes()) {
            throw InvalidEgressRuntimeException::invalid('transfer_regression');
        }
        if (NegotiatedProtocol::Unknown !== $this->protocol && $emission->negotiatedProtocol() !== $this->protocol) {
            throw InvalidEgressRuntimeException::invalid('protocol_change');
        }
        if (NegotiatedProtocol::Unknown !== $emission->negotiatedProtocol()) {
            $this->protocol = $emission->negotiatedProtocol();
        }
        TransportGuard::validateProtocol($emission->negotiatedProtocol(), $this->capabilities);
        if ($next->requestBytes() > $this->budget->maximumRequestBytes()) {
            throw InvalidEgressRuntimeException::invalid('request_budget');
        }
        if ($next->responseBytes() > $this->budget->maximumResponseBytes()) {
            throw InvalidEgressRuntimeException::invalid('response_budget');
        }
        if ($emission->bufferedBytes() > $this->budget->maximumBufferedBytes()) {
            throw InvalidEgressRuntimeException::invalid('buffer_budget');
        }
        if ($emission->retainedEvidenceBytes() > $this->budget->maximumRetainedEvidenceBytes()) {
            throw InvalidEgressRuntimeException::invalid('evidence_budget');
        }
        if (!$emission->terminal()) {
            ++$this->progress;
        }
        $this->peakBuffered = max($this->peakBuffered, $emission->bufferedBytes());
        $this->retainedEvidence = $emission->retainedEvidenceBytes();
        $this->transfer = $next;
    }

    public function transfer(): EgressTransferEvidence
    {
        return $this->transfer;
    }
    public function protocol(): NegotiatedProtocol
    {
        return $this->protocol;
    }
    public function progressCount(): int
    {
        return $this->progress;
    }
    public function peakBufferedBytes(): int
    {
        return $this->peakBuffered;
    }
    public function retainedEvidenceBytes(): int
    {
        return $this->retainedEvidence;
    }
}
