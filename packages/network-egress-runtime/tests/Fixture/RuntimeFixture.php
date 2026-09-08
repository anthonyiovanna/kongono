<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Fixture;

use Kongono\Http\Message\Message\Response;
use Kongono\Http\Message\Message\ServerRequest;
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
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeRequest;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use Psr\Http\Message\ServerRequestInterface;

final class RuntimeFixture
{
    public static function budget(int $requestBytes = 1024, int $responseBytes = 1024): EgressBudget
    {
        return new EgressBudget(1, 0, 8, $requestBytes, $responseBytes, 32, 4096, 4096, 4096, 1000, 1000, 5000);
    }

    public static function policy(?EgressBudget $budget = null, AddressScope $scope = AddressScope::PublicOnly): EgressPolicySource
    {
        return new EgressPolicySource(
            new EgressPolicyIdentity('runtime-policy'),
            [new CanonicalOrigin(OriginScheme::Https, 'example.com', 443)],
            $scope,
            TransportSecurity::HttpsOnly,
            RedirectMode::Deny,
            ProxyMode::Deny,
            CookieMode::Deny,
            CredentialMode::None,
            $budget ?? self::budget(),
            self::capabilities(),
        );
    }

    public static function capabilities(): TransportCapabilitySet
    {
        return new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::Tls]);
    }

    public static function resolution(string $address = '93.184.216.34'): EgressResolutionResult
    {
        $selected = new ResolvedAddress($address);

        return new EgressResolutionResult(new ResolutionEvidence([$selected], $selected), null);
    }

    public static function session(): FakeTransportSession
    {
        return new FakeTransportSession([
            new EgressTransportEmission(
                new EgressTransferEvidence(EgressTransferState::ResponseReceived, 1, 1),
                NegotiatedProtocol::Http11,
                0,
                0,
                new Response(),
                null,
            ),
            null,
        ]);
    }

    public static function request(
        ?FakeAuthorityCurrentness $authority = null,
        ?FakeResolver $resolver = null,
        ?FakeTransport $transport = null,
        ?FakeCancellation $cancellation = null,
        ?FakeClock $clock = null,
        ?EgressPolicySource $policy = null,
        ?ServerRequestInterface $message = null,
    ): EgressRuntimeRequest {
        $policy ??= self::policy();
        $artifact = (new EgressPolicyArtifactBuilder())->build($policy);
        $operation = new EgressOperation(
            new EgressOperationIdentity('operation'),
            $message ?? new ServerRequest('GET', 'https://example.com/resource'),
            new CanonicalOrigin(OriginScheme::Https, 'example.com', 443),
            new EgressAuthorityReference('authority', 1),
            $artifact->policyReference(),
            $policy->budget(),
        );

        return new EgressRuntimeRequest(
            $operation,
            $policy,
            $artifact->canonicalBytes(),
            new EgressReceiptIdentity('receipt'),
            new EgressAttemptIdentity('attempt'),
            $authority ?? new FakeAuthorityCurrentness(),
            $resolver ?? new FakeResolver(self::resolution()),
            $transport ?? new FakeTransport([self::capabilities()], self::session()),
            $cancellation ?? new FakeCancellation(),
            $clock ?? new FakeClock(),
        );
    }

    private function __construct() {}
}
