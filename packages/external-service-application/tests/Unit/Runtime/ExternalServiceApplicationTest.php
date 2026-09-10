<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Unit\Runtime;

use Kongono\ExternalServiceApplication\Exception\ExternalServiceApplicationLifecycleException;
use Kongono\ExternalServiceApplication\Exception\ExternalServiceApplicationOperationException;
use Kongono\ExternalServiceApplication\Exception\InvalidExternalServiceApplicationProfileException;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationRunInput;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileFactory;
use Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplication;
use Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplicationState;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeCredentialLease;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeCredentialLeaseSource;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeCredentialMonotonicClock;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeEgressAuthorityCurrentness;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeEgressCancellation;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeEgressTransport;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeEgressTransportSession;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeEndpointResolver;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeExternalServiceRequestFactory;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceProfileIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Endpoint\EndpointBasePath;
use Kongono\ExternalServiceContract\Endpoint\ServiceEndpoint;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseCleanupException;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseOperationException;
use Kongono\Http\Message\Message\Response;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\NetworkEgressApplication\Generated\GeneratedNetworkEgressApplicationBoundary;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressApplication\Runtime\LoadedNetworkEgressWiring;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplication as NetworkApplication;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
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
use Kongono\NetworkEgressRuntime\Clock\EgressMonotonicClock;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExternalServiceApplicationTest extends TestCase
{
    public function testSuccessfulRunPreservesOrderRelationsAndExactlyOnceRetirement(): void
    {
        $events = [];
        $observer = static function (string $event) use (&$events): void {
            $events[] = $event;
        };
        [$application, $input, $lease, $source, $factory, $resolver, $transport, $session] = $this->fixture(observer: $observer);

        $result = $application->run($input);

        self::assertSame(EgressOutcome::Succeeded->value, $result->operationResult()->outcome()->value);
        self::assertSame(1, $source->calls);
        self::assertSame(1, $factory->calls);
        self::assertSame(1, $resolver->calls);
        self::assertSame(1, $transport->openCalls);
        self::assertSame(1, $session->closeCalls);
        self::assertSame(1, $lease->materialCalls);
        self::assertSame(1, $lease->retireCalls);
        self::assertSame(ExternalServiceApplicationState::Retired, $application->state());
        self::assertSame('operation.external-service', $transport->request?->operation()->identity()->value());
        self::assertSame($input->authorityReference(), $transport->request->operation()->authorityReference());
        self::assertTrue($transport->request->operation()->budget()->equals($application->profile()->networkEgressProfile()->source()->budget()));
        self::assertSame([
            'source.acquire',
            'lease.with_material',
            'request_factory.create',
            'resolver.resolve',
            'transport.open',
            'transport.next',
            'transport.next',
            'transport.close',
            'lease.retire',
        ], $events);

        $this->expectException(ExternalServiceApplicationLifecycleException::class);
        $application->run($input);
    }

    /** @return iterable<string, array{string, string}> */
    public static function allowedPaths(): iterable
    {
        yield 'empty base accepts absolute path' => ['', '/anything'];
        yield 'exact base' => ['/api', '/api'];
        yield 'descendant' => ['/api', '/api/v1'];
    }

    #[DataProvider('allowedPaths')]
    public function testEmptyExactAndDescendantPathsAreAccepted(string $basePath, string $requestPath): void
    {
        [$application, $input] = $this->fixture(basePath: $basePath, requestUri: 'https://api.example.test' . $requestPath);
        self::assertTrue($application->run($input)->operationResult()->succeeded());
    }

    /** @return iterable<string, array{string}> */
    public static function rejectedUris(): iterable
    {
        yield 'lexical prefix' => ['https://api.example.test/api2'];
        yield 'wrong origin' => ['https://other.example.test/api'];
        yield 'user info' => ['https://user@api.example.test/api'];
        yield 'fragment' => ['https://api.example.test/api#fragment'];
        yield 'relative path' => ['https://api.example.test'];
    }

    #[DataProvider('rejectedUris')]
    public function testInvalidRequestNeverReachesResolverOrTransportAndLeaseStillRetires(string $requestUri): void
    {
        [$application, $input, $lease, , $factory, $resolver, $transport] = $this->fixture(requestUri: $requestUri);
        try {
            $application->run($input);
            self::fail('Invalid request must fail.');
        } catch (ExternalServiceApplicationOperationException $failure) {
            self::assertSame(1, $factory->calls);
            self::assertSame(0, $resolver->calls);
            self::assertSame(0, $transport->openCalls);
            self::assertSame(1, $lease->retireCalls);
            self::assertSame(ExternalServiceApplicationState::Retired, $application->state());
        }
    }

    public function testFactoryFailureIdentityAndRetirementArePreserved(): void
    {
        $foreign = new RuntimeException('factory-high-entropy-foreign');
        [$application, $input, $lease, , , $resolver] = $this->fixture(factoryFailure: $foreign);
        try {
            $application->run($input);
            self::fail('Factory failure must fail.');
        } catch (ExternalServiceApplicationOperationException $failure) {
            self::assertInstanceOf(CredentialLeaseOperationException::class, $failure->getPrevious());
            $credentialFailure = $failure->getPrevious();
            self::assertInstanceOf(ExternalServiceApplicationOperationException::class, $credentialFailure->primaryFailure());
            self::assertSame($foreign, $credentialFailure->primaryFailure()->getPrevious());
            self::assertSame(1, $lease->retireCalls);
            self::assertSame(0, $resolver->calls);
        }
    }

    public function testRetirementFailureExposesExactEgressResultAndCleanupIdentity(): void
    {
        $cleanup = new RuntimeException('cleanup-high-entropy-foreign');
        [$application, $input] = $this->fixture(retirementFailure: $cleanup);
        try {
            $application->run($input);
            self::fail('Retirement failure must fail.');
        } catch (ExternalServiceApplicationOperationException $failure) {
            self::assertNotNull($failure->egressResult());
            self::assertInstanceOf(CredentialLeaseCleanupException::class, $failure->getPrevious());
            self::assertSame([$cleanup], $failure->getPrevious()->cleanupFailures());
        }
    }

    public function testCredentialSourceMismatchFailsBeforeLoadedWiringConsumption(): void
    {
        [$profile, $wiring, $resolver, $transport, $source] = $this->constructionFixture();
        try {
            new ExternalServiceApplication($profile, $wiring, $resolver, $transport, new CredentialSourceIdentity('credentials.other'), $source);
            self::fail('Credential source mismatch must fail.');
        } catch (InvalidExternalServiceApplicationProfileException) {
            self::assertFalse($wiring->started());
            self::assertSame(0, $transport->capabilityCalls);
        }
    }

    /** @return array{ExternalServiceApplication, ExternalServiceApplicationRunInput, FakeCredentialLease, FakeCredentialLeaseSource, FakeExternalServiceRequestFactory, FakeEndpointResolver, FakeEgressTransport, FakeEgressTransportSession} */
    private function fixture(
        string $basePath = '/api',
        string $requestUri = 'https://api.example.test/api/resource',
        ?RuntimeException $factoryFailure = null,
        ?RuntimeException $retirementFailure = null,
        ?\Closure $observer = null,
    ): array {
        [$profile, $wiring, $resolver, $transport, , $session] = $this->constructionFixture($basePath, $observer);
        $purpose = new CredentialPurpose('invoke.service');
        $lease = new FakeCredentialLease(
            $profile->binding()->credentialReference(),
            $purpose,
            'secret-material-9b7830',
            $observer,
            retirementFailure: $retirementFailure,
        );
        $source = new FakeCredentialLeaseSource($lease, $observer);
        $sourceIdentity = $profile->binding()->credentialReference()->source();
        $application = new ExternalServiceApplication($profile, $wiring, $resolver, $transport, $sourceIdentity, $source);
        $factory = new FakeExternalServiceRequestFactory(
            $factoryFailure ?? new ServerRequest('POST', $requestUri),
            $observer,
        );
        $input = new ExternalServiceApplicationRunInput(
            new ExternalServiceOperationIdentity('operation.external-service'),
            $purpose,
            5000,
            $factory,
            new EgressReceiptIdentity('receipt.external-service'),
            new EgressAttemptIdentity('attempt.external-service'),
            new EgressAuthorityReference('authority.external-service', 1),
            new FakeEgressAuthorityCurrentness(),
            new FakeEgressCancellation(),
            new FakeCredentialMonotonicClock([0, 1, 2, 3]),
            new class implements EgressMonotonicClock {
                public function milliseconds(): int
                {
                    return 0;
                }
            },
        );

        return [$application, $input, $lease, $source, $factory, $resolver, $transport, $session];
    }

    /** @return array{ExternalServiceApplicationProfile, LoadedNetworkEgressWiring, FakeEndpointResolver, FakeEgressTransport, FakeCredentialLeaseSource, FakeEgressTransportSession} */
    private function constructionFixture(string $basePath = '/api', ?\Closure $observer = null): array
    {
        $origin = new CanonicalOrigin(OriginScheme::Https, 'api.example.test', 443);
        $capabilities = new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::Tls]);
        $binding = new ServiceBinding(
            new ServiceBindingIdentity('binding.primary'),
            new ServiceProfileIdentity('profile.primary'),
            new ServiceEndpoint($origin, new EndpointBasePath($basePath)),
            new CredentialReference(new CredentialSourceIdentity('credentials.primary'), 'reference.primary'),
            $capabilities,
        );
        $bindingSource = new ExternalServiceBindingSource(new ExternalServiceBindingSourceIdentity('bindings.current'), [$binding]);
        $bindingBytes = (new ExternalServiceBindingArtifactBuilder())->build($bindingSource)->canonicalBytes();
        $policy = new EgressPolicySource(
            new EgressPolicyIdentity('policy.external-service'),
            [$origin],
            AddressScope::PublicOnly,
            TransportSecurity::HttpsOnly,
            RedirectMode::Deny,
            ProxyMode::Deny,
            CookieMode::Deny,
            CredentialMode::OperationScoped,
            new EgressBudget(1, 0, 8, 4096, 4096, 32, 4096, 4096, 4096, 1000, 1000, 5000),
            $capabilities,
        );
        $policyBytes = (new EgressPolicyArtifactBuilder())->build($policy)->canonicalBytes();
        $networkProfile = (new NetworkEgressApplicationProfileFactory())->create($policy, $policyBytes);
        $profile = (new ExternalServiceApplicationProfileFactory())->create($bindingSource, $bindingBytes, $binding->identity(), $networkProfile);
        $boundary = new class implements GeneratedNetworkEgressApplicationBoundary {
            public function start(NetworkEgressApplicationProfile $profile, EgressResolver $resolver, EgressTransport $transport): NetworkApplication
            {
                return new NetworkApplication($profile, $resolver, $transport);
            }
        };
        $wiring = new LoadedNetworkEgressWiring($boundary, $networkProfile);
        $address = new ResolvedAddress('93.184.216.34');
        $resolver = new FakeEndpointResolver(new EgressResolutionResult(new ResolutionEvidence([$address], $address), null), $observer);
        $session = new FakeEgressTransportSession([
            new EgressTransportEmission(
                new EgressTransferEvidence(EgressTransferState::ResponseReceived, 1, 1),
                NegotiatedProtocol::Http11,
                0,
                0,
                new Response(),
                null,
            ),
            null,
        ], $observer);
        $transport = new FakeEgressTransport($capabilities, $session, $observer);
        $unusedPurpose = new CredentialPurpose('invoke.service');
        $unusedLease = new FakeCredentialLease($binding->credentialReference(), $unusedPurpose, 'unused');
        $unusedSource = new FakeCredentialLeaseSource($unusedLease);

        return [$profile, $wiring, $resolver, $transport, $unusedSource, $session];
    }
}
