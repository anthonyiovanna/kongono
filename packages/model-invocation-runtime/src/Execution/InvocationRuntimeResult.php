<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Execution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Receipt\InvocationReceipt;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationContract\Stream\ModelStreamEvent;
use Kongono\ModelInvocationRuntime\Exception\InvalidInvocationRuntimeException;

/** Returns detached terminal evidence after deterministic session retirement. */
#[Api(ApiStatus::Experimental)]
final readonly class InvocationRuntimeResult
{
    /** @var list<ModelStreamEvent> */
    private array $events;

    /** @param array<array-key, mixed> $events */
    public function __construct(
        private InvocationReceipt $receipt,
        private ?ModelResult $result,
        array $events,
        private InvocationState $state,
        private int $elapsedMilliseconds,
        private int $retainedEvidenceBytes,
    ) {
        if (!array_is_list($events)) {
            throw InvalidInvocationRuntimeException::invalid('events_not_list');
        }
        foreach ($events as $event) {
            if (!$event instanceof ModelStreamEvent) {
                throw InvalidInvocationRuntimeException::invalid('invalid_event_entry');
            }
        }
        if (InvocationState::Retired !== $state || $elapsedMilliseconds < 0 || $retainedEvidenceBytes < 0 || !$receipt->cleanupComplete()) {
            throw InvalidInvocationRuntimeException::invalid('terminal_result_invariant');
        }
        if (!in_array($receipt->outcome(), [InvocationOutcome::Succeeded, InvocationOutcome::Failed, InvocationOutcome::Cancelled, InvocationOutcome::Rejected], true)) {
            throw InvalidInvocationRuntimeException::invalid('result_outcome_relation');
        }
        $succeeded = InvocationOutcome::Succeeded === $receipt->outcome();
        $receiptResultIdentity = $receipt->resultIdentity();
        if ($succeeded !== (null !== $result) || $succeeded !== (null !== $receiptResultIdentity)) {
            throw InvalidInvocationRuntimeException::invalid('result_outcome_relation');
        }
        if (null !== $result && null !== $receiptResultIdentity && !$result->identity()->equals($receiptResultIdentity)) {
            throw InvalidInvocationRuntimeException::invalid('result_identity_relation');
        }
        $expectedAttemptCount = InvocationOutcome::Rejected === $receipt->outcome() ? 0 : 1;
        if ($expectedAttemptCount !== $receipt->attemptCount()) {
            throw InvalidInvocationRuntimeException::invalid('attempt_outcome_relation');
        }

        $this->events = [...$events];
    }

    public function receipt(): InvocationReceipt
    {
        return $this->receipt;
    }

    public function result(): ?ModelResult
    {
        return $this->result;
    }

    /** @return list<ModelStreamEvent> */
    public function events(): array
    {
        return [...$this->events];
    }

    public function state(): InvocationState
    {
        return $this->state;
    }

    public function elapsedMilliseconds(): int
    {
        return $this->elapsedMilliseconds;
    }

    public function retainedEvidenceBytes(): int
    {
        return $this->retainedEvidenceBytes;
    }
}
