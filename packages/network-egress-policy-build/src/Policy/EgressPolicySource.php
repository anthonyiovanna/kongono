<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressPolicyBuild\Exception\InvalidEgressPolicySourceException;

/** Binds one complete explicit policy without consulting ambient or live state. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressPolicySource
{
    /** @var list<CanonicalOrigin> */
    private array $allowedOrigins;

    /** @param list<CanonicalOrigin> $allowedOrigins */
    public function __construct(
        private EgressPolicyIdentity $identity,
        array $allowedOrigins,
        private AddressScope $addressScope,
        private TransportSecurity $transportSecurity,
        private RedirectMode $redirectMode,
        private ProxyMode $proxyMode,
        private CookieMode $cookieMode,
        private CredentialMode $credentialMode,
        private EgressBudget $budget,
        private TransportCapabilitySet $requiredCapabilities,
    ) {
        self::validateOrigins($allowedOrigins);
        self::validateCompatibility($allowedOrigins, $transportSecurity, $redirectMode, $proxyMode, $budget, $requiredCapabilities);
        $this->allowedOrigins = [...$allowedOrigins];
    }

    public function identity(): EgressPolicyIdentity
    {
        return $this->identity;
    }

    /** @return list<CanonicalOrigin> */
    public function allowedOrigins(): array
    {
        return [...$this->allowedOrigins];
    }

    public function addressScope(): AddressScope
    {
        return $this->addressScope;
    }

    public function transportSecurity(): TransportSecurity
    {
        return $this->transportSecurity;
    }

    public function redirectMode(): RedirectMode
    {
        return $this->redirectMode;
    }

    public function proxyMode(): ProxyMode
    {
        return $this->proxyMode;
    }

    public function cookieMode(): CookieMode
    {
        return $this->cookieMode;
    }

    public function credentialMode(): CredentialMode
    {
        return $this->credentialMode;
    }

    public function budget(): EgressBudget
    {
        return $this->budget;
    }

    public function requiredCapabilities(): TransportCapabilitySet
    {
        return $this->requiredCapabilities;
    }

    public function equals(self $other): bool
    {
        if (
            !$this->identity->equals($other->identity)
            || $this->addressScope !== $other->addressScope
            || $this->transportSecurity !== $other->transportSecurity
            || $this->redirectMode !== $other->redirectMode
            || $this->proxyMode !== $other->proxyMode
            || $this->cookieMode !== $other->cookieMode
            || $this->credentialMode !== $other->credentialMode
            || !$this->budget->equals($other->budget)
            || $this->requiredCapabilities->all() !== $other->requiredCapabilities->all()
            || count($this->allowedOrigins) !== count($other->allowedOrigins)
        ) {
            return false;
        }
        foreach ($this->allowedOrigins as $position => $origin) {
            if (!$origin->equals($other->allowedOrigins[$position])) {
                return false;
            }
        }

        return true;
    }

    /** @param array<array-key, mixed> $origins */
    private static function validateOrigins(array $origins): void
    {
        if (!array_is_list($origins)) {
            throw InvalidEgressPolicySourceException::invalidSource('origins_not_list', 'allowed_origins', count($origins));
        }
        $count = count($origins);
        if ($count < 1 || $count > 64) {
            throw InvalidEgressPolicySourceException::invalidSource('origin_count', 'allowed_origins', $count, limit: 64);
        }
        $previous = null;
        foreach ($origins as $position => $origin) {
            if (!$origin instanceof CanonicalOrigin) {
                throw InvalidEgressPolicySourceException::invalidSource('invalid_origin_entry', 'allowed_origins', $count, $position);
            }
            $key = $origin->scheme()->value . '|' . $origin->host() . '|' . sprintf('%05d', $origin->port());
            if (null !== $previous && 0 <= strcmp($previous, $key)) {
                $violation = $previous === $key ? 'duplicate_origin' : 'noncanonical_origin_order';
                throw InvalidEgressPolicySourceException::invalidSource($violation, 'allowed_origins', $count, $position);
            }
            $previous = $key;
        }
    }

    /** @param list<CanonicalOrigin> $origins */
    private static function validateCompatibility(
        array $origins,
        TransportSecurity $transportSecurity,
        RedirectMode $redirectMode,
        ProxyMode $proxyMode,
        EgressBudget $budget,
        TransportCapabilitySet $capabilities,
    ): void {
        foreach ($origins as $origin) {
            if (TransportSecurity::HttpsOnly === $transportSecurity && OriginScheme::Http === $origin->scheme()) {
                throw InvalidEgressPolicySourceException::incompatible('http_not_allowed', 'transport_security');
            }
            if (OriginScheme::Https === $origin->scheme() && !$capabilities->contains(TransportCapability::Tls)) {
                throw InvalidEgressPolicySourceException::incompatible('tls_capability_required', 'required_capabilities');
            }
        }
        $redirects = $budget->maximumRedirects();
        if ((RedirectMode::Deny === $redirectMode && 0 !== $redirects) || (RedirectMode::SameOrigin === $redirectMode && $redirects < 1)) {
            throw InvalidEgressPolicySourceException::incompatible('redirect_budget_mismatch', 'redirect_mode');
        }
        if (ProxyMode::ExplicitOnly === $proxyMode && !$capabilities->contains(TransportCapability::Proxy)) {
            throw InvalidEgressPolicySourceException::incompatible('proxy_capability_required', 'required_capabilities');
        }
    }
}
