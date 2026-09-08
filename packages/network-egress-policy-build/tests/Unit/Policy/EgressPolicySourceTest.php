<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Tests\Unit\Policy;

use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressPolicyBuild\Exception\InvalidEgressPolicySourceException;
use Kongono\NetworkEgressPolicyBuild\Policy\AddressScope;
use Kongono\NetworkEgressPolicyBuild\Policy\CookieMode;
use Kongono\NetworkEgressPolicyBuild\Policy\CredentialMode;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicyIdentity;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;
use Kongono\NetworkEgressPolicyBuild\Policy\ProxyMode;
use Kongono\NetworkEgressPolicyBuild\Policy\RedirectMode;
use Kongono\NetworkEgressPolicyBuild\Policy\TransportSecurity;
use Kongono\NetworkEgressPolicyBuild\Tests\Support\EgressPolicySourceFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class EgressPolicySourceTest extends TestCase
{
    public function testAccessorsAreDetachedAndEqualityIsDeep(): void
    {
        $source = EgressPolicySourceFactory::create();
        $origins = $source->allowedOrigins();
        $origins[] = new CanonicalOrigin(OriginScheme::Https, 'later.example.test', 443);
        self::assertCount(1, $source->allowedOrigins());
        self::assertTrue($source->equals(EgressPolicySourceFactory::create()));
        self::assertFalse($source->equals(EgressPolicySourceFactory::create(identity: 'application.changed')));
    }

    public function testOriginCountMaximumAndCanonicalOrdering(): void
    {
        $origins = [];
        for ($index = 0; $index < 64; ++$index) {
            $origins[] = new CanonicalOrigin(OriginScheme::Https, sprintf('h%02d.example.test', $index), 443);
        }
        self::assertCount(64, EgressPolicySourceFactory::create(origins: $origins)->allowedOrigins());
        foreach ([[], [...$origins, new CanonicalOrigin(OriginScheme::Https, 'z.example.test', 443)], [$origins[1], $origins[0]], [$origins[0], $origins[0]]] as $invalid) {
            try {
                EgressPolicySourceFactory::create(origins: $invalid);
                self::fail('Expected invalid origin collection.');
            } catch (InvalidEgressPolicySourceException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testNonListAndWrongTypeOriginsAreRejected(): void
    {
        $arguments = $this->arguments();
        foreach ([[1 => new CanonicalOrigin(OriginScheme::Https, 'api.example.test', 443)], ['wrong']] as $origins) {
            $arguments[1] = $origins;
            try {
                (new ReflectionClass(EgressPolicySource::class))->newInstanceArgs($arguments);
                self::fail('Expected invalid origin shape.');
            } catch (InvalidEgressPolicySourceException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testCompatibilityRelationsFailClosed(): void
    {
        $http = [new CanonicalOrigin(OriginScheme::Http, 'api.example.test', 80)];
        $cases = [
            fn() => EgressPolicySourceFactory::create(origins: $http),
            fn() => EgressPolicySourceFactory::create(capabilities: [TransportCapability::Http11]),
            fn() => EgressPolicySourceFactory::create(redirectMode: RedirectMode::Deny, budget: EgressPolicySourceFactory::budget(1)),
            fn() => EgressPolicySourceFactory::create(redirectMode: RedirectMode::SameOrigin, budget: EgressPolicySourceFactory::budget(0)),
            fn() => EgressPolicySourceFactory::create(proxyMode: ProxyMode::ExplicitOnly),
        ];
        foreach ($cases as $case) {
            try {
                $case();
                self::fail('Expected compatibility failure.');
            } catch (InvalidEgressPolicySourceException $failure) {
                self::assertSame('KGN-EGRESS-POLICY-1512', $failure->diagnosticId());
            }
        }
        self::assertInstanceOf(EgressPolicySource::class, EgressPolicySourceFactory::create(
            origins: $http,
            transportSecurity: TransportSecurity::ExplicitHttpAllowed,
            redirectMode: RedirectMode::SameOrigin,
            proxyMode: ProxyMode::ExplicitOnly,
            budget: EgressPolicySourceFactory::budget(1),
            capabilities: [TransportCapability::Http11, TransportCapability::Tls, TransportCapability::Proxy],
        ));
    }

    /** @return list<mixed> */
    private function arguments(): array
    {
        return [
            new EgressPolicyIdentity('application.primary'),
            [new CanonicalOrigin(OriginScheme::Https, 'api.example.test', 443)],
            AddressScope::PublicOnly,
            TransportSecurity::HttpsOnly,
            RedirectMode::Deny,
            ProxyMode::Deny,
            CookieMode::Deny,
            CredentialMode::None,
            new EgressBudget(1, 0, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1),
            new TransportCapabilitySet([TransportCapability::Tls]),
        ];
    }
}
