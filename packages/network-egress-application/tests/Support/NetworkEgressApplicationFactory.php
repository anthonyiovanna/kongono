<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Tests\Support;

use Kongono\Http\Message\Message\Response;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\NetworkEgressApplication\Generated\GeneratedNetworkEgressWiring;
use Kongono\NetworkEgressApplication\Generated\NetworkEgressWiringGenerator;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplicationRunInput;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressOperationIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Operation\EgressOperation;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
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
use Kongono\NetworkEgressRuntime\Address\ResolutionEvidence;
use Kongono\NetworkEgressRuntime\Address\ResolvedAddress;
use Kongono\NetworkEgressRuntime\Authority\EgressAuthorityCurrentness;
use Kongono\NetworkEgressRuntime\Cancellation\EgressCancellation;
use Kongono\NetworkEgressRuntime\Clock\EgressMonotonicClock;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use Throwable;

final class NetworkEgressApplicationFactory
{
    public static function budget(): EgressBudget
    {
        return new EgressBudget(1, 0, 8, 1_024, 1_024, 32, 4_096, 4_096, 4_096, 1_000, 1_000, 5_000);
    }

    public static function capabilities(): TransportCapabilitySet
    {
        return new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::Tls]);
    }

    public static function policy(string $identity = 'application-policy', ?TransportCapabilitySet $capabilities = null): EgressPolicySource
    {
        return new EgressPolicySource(
            new EgressPolicyIdentity($identity),
            [self::origin()],
            AddressScope::PublicOnly,
            TransportSecurity::HttpsOnly,
            RedirectMode::Deny,
            ProxyMode::Deny,
            CookieMode::Deny,
            CredentialMode::None,
            self::budget(),
            $capabilities ?? self::capabilities(),
        );
    }

    public static function origin(): CanonicalOrigin
    {
        return new CanonicalOrigin(OriginScheme::Https, 'example.com', 443);
    }

    public static function artifactBytes(?EgressPolicySource $source = null): string
    {
        return (new EgressPolicyArtifactBuilder())->build($source ?? self::policy())->canonicalBytes();
    }

    public static function profile(?EgressPolicySource $source = null): NetworkEgressApplicationProfile
    {
        $source ??= self::policy();

        return (new NetworkEgressApplicationProfileFactory())->create($source, self::artifactBytes($source));
    }

    public static function wiring(?NetworkEgressApplicationProfile $profile = null): GeneratedNetworkEgressWiring
    {
        return (new NetworkEgressWiringGenerator())->generate($profile ?? self::profile());
    }

    public static function resolution(): EgressResolutionResult
    {
        $address = new ResolvedAddress('93.184.216.34');

        return new EgressResolutionResult(new ResolutionEvidence([$address], $address), null);
    }

    public static function session(?Throwable $closeFailure = null, mixed $onNext = null): RecordingTransportSession
    {
        return new RecordingTransportSession([
            new EgressTransportEmission(
                new EgressTransferEvidence(EgressTransferState::ResponseReceived, 1, 1),
                NegotiatedProtocol::Http11,
                0,
                0,
                new Response(),
                null,
            ),
            null,
        ], $closeFailure, $onNext);
    }

    public static function operation(?EgressPolicySource $source = null): EgressOperation
    {
        $source ??= self::policy();
        $artifact = (new EgressPolicyArtifactBuilder())->build($source);

        return new EgressOperation(
            new EgressOperationIdentity('operation.application'),
            new ServerRequest('GET', 'https://example.com/resource'),
            self::origin(),
            new EgressAuthorityReference('authority.application', 1),
            $artifact->policyReference(),
            $source->budget(),
        );
    }

    public static function runInput(
        ?EgressPolicySource $source = null,
        ?EgressAuthorityCurrentness $authority = null,
        ?EgressCancellation $cancellation = null,
        ?EgressMonotonicClock $clock = null,
    ): NetworkEgressApplicationRunInput {
        return new NetworkEgressApplicationRunInput(
            self::operation($source),
            new EgressReceiptIdentity('receipt.application'),
            new EgressAttemptIdentity('attempt.application'),
            $authority ?? new RecordingAuthorityCurrentness(),
            $cancellation ?? new RecordingCancellation(),
            $clock ?? new RecordingClock(),
        );
    }

    private function __construct() {}
}
