<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Tests\Support;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationResult;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileFactory;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceProfileIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Endpoint\EndpointBasePath;
use Kongono\ExternalServiceContract\Endpoint\ServiceEndpoint;
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailure;
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailureKind;
use Kongono\ExternalServiceContract\Lifecycle\ExternalServiceLifecycleState;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOperationResult;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOutcome;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use Kongono\Http\Message\Message\Response;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Cancellation\CancellationEvidence;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressOperationIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Lifecycle\EgressLifecycleState;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressContract\Outcome\EgressOperationResult;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Receipt\EgressReceipt;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactBuilder;
use Kongono\NetworkEgressPolicyBuild\Policy\AddressScope;
use Kongono\NetworkEgressPolicyBuild\Policy\CookieMode;
use Kongono\NetworkEgressPolicyBuild\Policy\CredentialMode;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicyIdentity;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;
use Kongono\NetworkEgressPolicyBuild\Policy\ProxyMode;
use Kongono\NetworkEgressPolicyBuild\Policy\RedirectMode;
use Kongono\NetworkEgressPolicyBuild\Policy\TransportSecurity;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Psr\Http\Message\StreamInterface;

final class ExternalServiceInspectionFactory
{
    public const string RAW_HOST = 'private-service.example.test';
    public const string RAW_BINDING = 'binding.private';
    public const string RAW_PROFILE = 'profile.private';
    public const string RAW_CREDENTIAL_SOURCE = 'credential-source.private';
    public const string RAW_CREDENTIAL_REFERENCE = 'credential-reference.private';
    public const string RAW_OPERATION = 'operation.private';
    public const string RAW_RECEIPT = 'receipt.private';
    public const string RAW_FAILURE = 'connection';

    public static function capabilities(): TransportCapabilitySet
    {
        return new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::Tls]);
    }

    public static function budget(): EgressBudget
    {
        return new EgressBudget(1, 0, 8, 4_096, 4_096, 32, 4_096, 4_096, 4_096, 1_000, 1_000, 5_000);
    }

    public static function profile(string $suffix = ''): ExternalServiceApplicationProfile
    {
        $host = '' === $suffix ? self::RAW_HOST : 'private-' . $suffix . '.example.test';
        $bindingIdentity = new ServiceBindingIdentity('' === $suffix ? self::RAW_BINDING : 'binding.' . $suffix);
        $origin = new CanonicalOrigin(OriginScheme::Https, $host, 443);
        $binding = new ServiceBinding(
            $bindingIdentity,
            new ServiceProfileIdentity('' === $suffix ? self::RAW_PROFILE : 'profile.' . $suffix),
            new ServiceEndpoint($origin, new EndpointBasePath('/private-api')),
            new CredentialReference(
                new CredentialSourceIdentity('' === $suffix ? self::RAW_CREDENTIAL_SOURCE : 'credential-source.' . $suffix),
                '' === $suffix ? self::RAW_CREDENTIAL_REFERENCE : 'credential-reference.' . $suffix,
            ),
            self::capabilities(),
        );
        $source = new ExternalServiceBindingSource(
            new ExternalServiceBindingSourceIdentity('' === $suffix ? 'binding-source.private' : 'binding-source.' . $suffix),
            [$binding],
        );
        $policy = new EgressPolicySource(
            new EgressPolicyIdentity('' === $suffix ? 'policy.private' : 'policy.' . $suffix),
            [$origin],
            AddressScope::PublicOnly,
            TransportSecurity::HttpsOnly,
            RedirectMode::Deny,
            ProxyMode::Deny,
            CookieMode::Deny,
            CredentialMode::OperationScoped,
            self::budget(),
            self::capabilities(),
        );
        $networkProfile = self::networkProfile($policy);
        $artifactBytes = (new ExternalServiceBindingArtifactBuilder())->build($source)->canonicalBytes();

        return (new ExternalServiceApplicationProfileFactory())->create($source, $artifactBytes, $bindingIdentity, $networkProfile);
    }

    public static function result(
        ExternalServiceApplicationProfile $profile,
        EgressOutcome $outcome = EgressOutcome::Succeeded,
        ?StreamInterface $responseBody = null,
        int $elapsedMilliseconds = 12,
    ): ExternalServiceApplicationResult {
        $attempt = match ($outcome) {
            EgressOutcome::RejectedBeforeAttempt, EgressOutcome::FailedBeforeEffect, EgressOutcome::CancelledBeforeEffect => null,
            default => new EgressAttemptIdentity('attempt.private'),
        };
        $lifecycle = match ($outcome) {
            EgressOutcome::RejectedBeforeAttempt, EgressOutcome::FailedBeforeEffect => EgressLifecycleState::Failed,
            EgressOutcome::CancelledBeforeEffect, EgressOutcome::CancelledAfterPossibleEffect => EgressLifecycleState::Cancelled,
            EgressOutcome::Succeeded => EgressLifecycleState::Succeeded,
            EgressOutcome::UncertainEffect => EgressLifecycleState::Uncertain,
        };
        $transfer = match ($outcome) {
            EgressOutcome::Succeeded => new EgressTransferEvidence(EgressTransferState::ResponseReceived, 9, 11),
            EgressOutcome::CancelledAfterPossibleEffect => new EgressTransferEvidence(EgressTransferState::RequestSent, 9, 0),
            EgressOutcome::UncertainEffect => new EgressTransferEvidence(EgressTransferState::RequestPartiallySent, 4, 0),
            default => new EgressTransferEvidence(EgressTransferState::None, 0, 0),
        };
        $cancellation = match ($outcome) {
            EgressOutcome::CancelledBeforeEffect => new CancellationEvidence(CancellationState::RequestedBeforeAttempt, 1),
            EgressOutcome::CancelledAfterPossibleEffect => new CancellationEvidence(CancellationState::RequestedAfterPossibleEffect, 2),
            default => new CancellationEvidence(CancellationState::NotRequested, 0),
        };
        $egressFailureKind = match ($outcome) {
            EgressOutcome::Succeeded => null,
            EgressOutcome::RejectedBeforeAttempt => EgressFailureKind::PolicyDenied,
            EgressOutcome::CancelledBeforeEffect, EgressOutcome::CancelledAfterPossibleEffect => EgressFailureKind::Cancellation,
            EgressOutcome::UncertainEffect => EgressFailureKind::UncertainEffect,
            EgressOutcome::FailedBeforeEffect => EgressFailureKind::Connection,
        };
        $receipt = new EgressReceipt(
            new EgressReceiptIdentity(self::RAW_RECEIPT),
            new EgressOperationIdentity(self::RAW_OPERATION),
            $attempt,
            $lifecycle,
            $outcome,
            null === $attempt ? 0 : 1,
            $transfer,
            $cancellation,
            null === $attempt ? NegotiatedProtocol::Unknown : NegotiatedProtocol::Http11,
            self::capabilities(),
        );
        $egressFailure = null === $egressFailureKind ? null : new EgressFailure($egressFailureKind, $egressFailureKind->value);
        $egress = new EgressRuntimeResult(
            new EgressOperationResult($receipt, EgressOutcome::Succeeded === $outcome ? new Response(body: $responseBody) : null, $egressFailure),
            $elapsedMilliseconds,
            null === $attempt ? 0 : 1,
            3,
            64,
            48,
        );
        $externalOutcome = match ($outcome) {
            EgressOutcome::RejectedBeforeAttempt => ExternalServiceOutcome::RejectedBeforeAttempt,
            EgressOutcome::Succeeded => ExternalServiceOutcome::Succeeded,
            EgressOutcome::FailedBeforeEffect => ExternalServiceOutcome::FailedBeforeEffect,
            EgressOutcome::CancelledBeforeEffect => ExternalServiceOutcome::CancelledBeforeEffect,
            EgressOutcome::CancelledAfterPossibleEffect => ExternalServiceOutcome::CancelledAfterPossibleEffect,
            EgressOutcome::UncertainEffect => ExternalServiceOutcome::UncertainEffect,
        };
        $externalFailureKind = match ($egressFailureKind) {
            null => null,
            EgressFailureKind::PolicyDenied => ExternalServiceFailureKind::Policy,
            EgressFailureKind::Cancellation => ExternalServiceFailureKind::Cancelled,
            default => ExternalServiceFailureKind::Transport,
        };
        $operation = new ExternalServiceOperationResult(
            new ExternalServiceOperationIdentity(self::RAW_OPERATION),
            $profile->binding()->identity(),
            in_array($externalOutcome, [ExternalServiceOutcome::CancelledAfterPossibleEffect, ExternalServiceOutcome::UncertainEffect], true)
                ? ExternalServiceLifecycleState::Failed
                : ExternalServiceLifecycleState::Retired,
            $externalOutcome,
            null === $externalFailureKind
                ? null
                : new ExternalServiceFailure($externalFailureKind, null === $egressFailureKind ? self::RAW_FAILURE : $egressFailureKind->value),
        );
        $credential = new CredentialLeaseRuntimeResult(
            CredentialLeaseRuntimeOutcome::Succeeded,
            CredentialLeaseRuntimeState::Retired,
            null,
            4,
            1,
            1,
            1,
            true,
        );

        return new ExternalServiceApplicationResult($operation, $credential, $egress);
    }

    public static function graphView(GraphRole $role = GraphRole::Chie): ApplicationGraphIntelligenceView
    {
        return new ApplicationGraphIntelligenceView(
            $role,
            str_repeat('a', 64),
            new GraphIdentity(str_repeat('b', 64)),
            GraphContractVersion::Version1,
            10,
            6,
            ['composition.input' => 1, 'composition.service' => 4, 'composition.contract' => 3, 'composition.root' => 2],
            ['composition.depends_on' => 2, 'composition.consumes_input' => 1, 'composition.implements_contract' => 2, 'composition.exposes_root' => 1],
        );
    }

    private static function networkProfile(EgressPolicySource $policy): NetworkEgressApplicationProfile
    {
        return (new NetworkEgressApplicationProfileFactory())->create(
            $policy,
            (new EgressPolicyArtifactBuilder())->build($policy)->canonicalBytes(),
        );
    }

    private function __construct() {}
}
