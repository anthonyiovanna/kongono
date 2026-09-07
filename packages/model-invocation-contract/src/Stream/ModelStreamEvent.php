<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Stream;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Internal\ContractGuard;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;

/** Carries one inert normalized partial-evidence event. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelStreamEvent
{
    private ?string $payload;

    public function __construct(
        private InvocationIdentity $invocationIdentity,
        private StreamSequence $sequence,
        private StreamEventKind $kind,
        ?string $payload = null,
        private ?UsageEvidence $usage = null,
    ) {
        $payloadOnly = in_array($kind, [StreamEventKind::ContentDelta, StreamEventKind::ActionName, StreamEventKind::ActionArgumentsDelta], true);
        $usageOnly = StreamEventKind::Usage === $kind;
        $empty = in_array($kind, [StreamEventKind::Started, StreamEventKind::Completed, StreamEventKind::Failed], true);
        if (($payloadOnly && (null === $payload || null !== $usage)) || ($usageOnly && (null !== $payload || null === $usage)) || ($empty && (null !== $payload || null !== $usage))) {
            throw InvalidModelInvocationContractException::invalidStreamEvent('stream_event_shape', $kind->value);
        }

        $this->payload = null === $payload ? null : ContractGuard::streamPayload($payload);
    }

    public function invocationIdentity(): InvocationIdentity
    {
        return $this->invocationIdentity;
    }

    public function sequence(): StreamSequence
    {
        return $this->sequence;
    }

    public function kind(): StreamEventKind
    {
        return $this->kind;
    }

    public function payload(): ?string
    {
        return $this->payload;
    }

    public function usage(): ?UsageEvidence
    {
        return $this->usage;
    }

    public function equals(self $other): bool
    {
        return $this->invocationIdentity->equals($other->invocationIdentity)
            && $this->sequence->equals($other->sequence)
            && $this->kind === $other->kind
            && $this->payload === $other->payload
            && (null === $this->usage ? null === $other->usage : null !== $other->usage && $this->usage->equals($other->usage));
    }
}
