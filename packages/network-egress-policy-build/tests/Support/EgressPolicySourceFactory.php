<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Tests\Support;

use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressPolicyBuild\Policy\AddressScope;
use Kongono\NetworkEgressPolicyBuild\Policy\CookieMode;
use Kongono\NetworkEgressPolicyBuild\Policy\CredentialMode;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicyIdentity;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;
use Kongono\NetworkEgressPolicyBuild\Policy\ProxyMode;
use Kongono\NetworkEgressPolicyBuild\Policy\RedirectMode;
use Kongono\NetworkEgressPolicyBuild\Policy\TransportSecurity;

final class EgressPolicySourceFactory
{
    /** @param list<CanonicalOrigin>|null $origins
     * @param list<TransportCapability>|null $capabilities
     */
    public static function create(
        string $identity = 'application.primary',
        ?array $origins = null,
        AddressScope $addressScope = AddressScope::PublicOnly,
        TransportSecurity $transportSecurity = TransportSecurity::HttpsOnly,
        RedirectMode $redirectMode = RedirectMode::Deny,
        ProxyMode $proxyMode = ProxyMode::Deny,
        CookieMode $cookieMode = CookieMode::Deny,
        CredentialMode $credentialMode = CredentialMode::None,
        ?EgressBudget $budget = null,
        ?array $capabilities = null,
    ): EgressPolicySource {
        return new EgressPolicySource(
            new EgressPolicyIdentity($identity),
            $origins ?? [new CanonicalOrigin(OriginScheme::Https, 'api.example.test', 443)],
            $addressScope,
            $transportSecurity,
            $redirectMode,
            $proxyMode,
            $cookieMode,
            $credentialMode,
            $budget ?? self::budget(),
            new TransportCapabilitySet($capabilities ?? [TransportCapability::Http11, TransportCapability::Tls]),
        );
    }

    public static function budget(int $redirects = 0, int $responseBytes = 2_000_000): EgressBudget
    {
        return new EgressBudget(1, $redirects, 4, 1_000_000, $responseBytes, 64, 65_536, 262_144, 32_768, 5_000, 10_000, 30_000);
    }

    private function __construct() {}
}
