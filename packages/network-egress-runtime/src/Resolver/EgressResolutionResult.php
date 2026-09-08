<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Resolver;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressRuntime\Address\ResolutionEvidence;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;

/** Carries exactly successful resolution evidence or one closed resolution failure. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressResolutionResult
{
    public function __construct(private ?ResolutionEvidence $evidence, private ?EgressFailure $failure)
    {
        if ((null === $evidence) === (null === $failure) || (null !== $failure && EgressFailureKind::Resolution !== $failure->kind())) {
            throw InvalidEgressRuntimeException::invalid('resolution_result_shape');
        }
    }

    public function evidence(): ?ResolutionEvidence
    {
        return $this->evidence;
    }
    public function failure(): ?EgressFailure
    {
        return $this->failure;
    }
    public function succeeded(): bool
    {
        return null !== $this->evidence;
    }
}
