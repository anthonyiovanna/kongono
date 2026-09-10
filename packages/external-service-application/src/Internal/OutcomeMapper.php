<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOutcome;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;

/** Narrows each Phase 15 outcome to the exact neutral Phase 16 outcome. */
#[Api(ApiStatus::Internal)]
final class OutcomeMapper
{
    public static function map(EgressOutcome $outcome): ExternalServiceOutcome
    {
        return match ($outcome) {
            EgressOutcome::RejectedBeforeAttempt => ExternalServiceOutcome::RejectedBeforeAttempt,
            EgressOutcome::Succeeded => ExternalServiceOutcome::Succeeded,
            EgressOutcome::FailedBeforeEffect => ExternalServiceOutcome::FailedBeforeEffect,
            EgressOutcome::CancelledBeforeEffect => ExternalServiceOutcome::CancelledBeforeEffect,
            EgressOutcome::CancelledAfterPossibleEffect => ExternalServiceOutcome::CancelledAfterPossibleEffect,
            EgressOutcome::UncertainEffect => ExternalServiceOutcome::UncertainEffect,
        };
    }

    private function __construct() {}
}
