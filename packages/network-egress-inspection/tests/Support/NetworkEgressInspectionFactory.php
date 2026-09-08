<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Tests\Support;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\Http\Message\Message\Response;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\NetworkEgressApplication\Generated\GeneratedNetworkEgressWiring;
use Kongono\NetworkEgressApplication\Generated\NetworkEgressWiringGenerator;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
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
use Kongono\NetworkEgressContract\Operation\EgressOperation;
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

final class NetworkEgressInspectionFactory
{
    public const string RAW_HOST = 'private-origin.example';
    public const string RAW_URI = 'https://private-origin.example/resource?credential=secret';
    public const string RAW_POLICY = 'private-policy-identity';
    public const string RAW_OPERATION = 'private-operation-identity';
    public const string RAW_FAILURE = 'transport_failure';

    public static function budget(): EgressBudget
    {
        return new EgressBudget(1, 0, 8, 1_024, 2_048, 32, 4_096, 4_096, 4_096, 1_000, 1_000, 5_000);
    }

    public static function capabilities(): TransportCapabilitySet
    {
        return new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::Tls]);
    }

    public static function origin(string $host = self::RAW_HOST): CanonicalOrigin
    {
        return new CanonicalOrigin(OriginScheme::Https, $host, 443);
    }

    public static function source(string $suffix = ''): EgressPolicySource
    {
        $identity = '' === $suffix ? self::RAW_POLICY : 'private-policy-' . $suffix;
        $host = '' === $suffix ? self::RAW_HOST : $suffix . '.example';

        return new EgressPolicySource(
            new EgressPolicyIdentity($identity),
            [self::origin($host)],
            AddressScope::PublicOnly,
            TransportSecurity::HttpsOnly,
            RedirectMode::Deny,
            ProxyMode::Deny,
            CookieMode::Deny,
            CredentialMode::None,
            self::budget(),
            self::capabilities(),
        );
    }

    public static function profile(?EgressPolicySource $source = null): NetworkEgressApplicationProfile
    {
        $source ??= self::source();
        $bytes = (new EgressPolicyArtifactBuilder())->build($source)->canonicalBytes();

        return (new NetworkEgressApplicationProfileFactory())->create($source, $bytes);
    }

    public static function wiring(?NetworkEgressApplicationProfile $profile = null): GeneratedNetworkEgressWiring
    {
        return (new NetworkEgressWiringGenerator())->generate($profile ?? self::profile());
    }

    public static function operation(
        ?EgressPolicySource $source = null,
        ?StreamInterface $body = null,
        string $identity = self::RAW_OPERATION,
    ): EgressOperation {
        $source ??= self::source();
        $artifact = (new EgressPolicyArtifactBuilder())->build($source);
        $host = $source->allowedOrigins()[0]->host();

        return new EgressOperation(
            new EgressOperationIdentity($identity),
            new ServerRequest('GET', 'https://' . $host . '/resource?credential=secret', body: $body),
            $source->allowedOrigins()[0],
            new EgressAuthorityReference('private-authority-reference', 1),
            $artifact->policyReference(),
            $source->budget(),
        );
    }

    public static function runtime(EgressOperation $operation, EgressOutcome $outcome = EgressOutcome::Succeeded, ?StreamInterface $responseBody = null): EgressRuntimeResult
    {
        $attempt = match ($outcome) {
            EgressOutcome::RejectedBeforeAttempt, EgressOutcome::FailedBeforeEffect, EgressOutcome::CancelledBeforeEffect => null,
            default => new EgressAttemptIdentity('private-attempt-identity'),
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
        $protocol = null === $attempt ? NegotiatedProtocol::Unknown : NegotiatedProtocol::Http11;
        $receipt = new EgressReceipt(
            new EgressReceiptIdentity('private-receipt-identity'),
            $operation->identity(),
            $attempt,
            $lifecycle,
            $outcome,
            null === $attempt ? 0 : 1,
            $transfer,
            $cancellation,
            $protocol,
            self::capabilities(),
        );
        $result = new EgressOperationResult(
            $receipt,
            EgressOutcome::Succeeded === $outcome ? new Response(body: $responseBody) : null,
            EgressOutcome::Succeeded === $outcome ? null : new EgressFailure(
                EgressOutcome::UncertainEffect === $outcome ? EgressFailureKind::UncertainEffect : EgressFailureKind::Connection,
                self::RAW_FAILURE,
            ),
        );

        return new EgressRuntimeResult($result, 12, null === $attempt ? 0 : 1, 3, 64, 48);
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

    private function __construct() {}
}
