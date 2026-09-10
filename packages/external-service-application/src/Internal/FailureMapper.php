<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailure;
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailureKind;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;

/** Reduces Phase 15 failure evidence to closed service-level kinds and reasons. */
#[Api(ApiStatus::Internal)]
final class FailureMapper
{
    public static function map(?EgressFailure $failure): ?ExternalServiceFailure
    {
        if (null === $failure) {
            return null;
        }

        $kind = match ($failure->kind()) {
            EgressFailureKind::Validation => ExternalServiceFailureKind::Binding,
            EgressFailureKind::PolicyDenied => ExternalServiceFailureKind::Policy,
            EgressFailureKind::Resolution => ExternalServiceFailureKind::Resolution,
            EgressFailureKind::Cancellation => ExternalServiceFailureKind::Cancelled,
            EgressFailureKind::Cleanup => ExternalServiceFailureKind::Cleanup,
            EgressFailureKind::Connection,
            EgressFailureKind::Tls,
            EgressFailureKind::Protocol,
            EgressFailureKind::Redirect,
            EgressFailureKind::Budget,
            EgressFailureKind::Timeout,
            EgressFailureKind::Response,
            EgressFailureKind::UncertainEffect => ExternalServiceFailureKind::Transport,
        };

        return new ExternalServiceFailure($kind, $failure->kind()->value);
    }

    private function __construct() {}
}
