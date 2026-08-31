<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Plan;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionAdmission\Identity\AdmissionIdentity;
use Kongono\ExtensionComposition\Grant\CapabilityDecision;
use Kongono\ExtensionComposition\Identity\GrantPlanIdentity;
use Kongono\ExtensionComposition\Implementation\TrustedExtensionImplementation;
use Kongono\ExtensionComposition\Internal\CanonicalJson;
use Kongono\ExtensionComposition\Internal\ExtensionGrantPlanValidator;

/** Stores one exact application-owned implementation and capability decision plan. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionGrantPlan
{
    /** @var list<TrustedExtensionImplementation> */
    private array $implementations;
    /** @var list<CapabilityDecision> */
    private array $decisions;
    private GrantPlanIdentity $identity;

    /**
     * @param array<array-key, mixed> $implementations
     * @param array<array-key, mixed> $decisions
     */
    public function __construct(
        private AdmissionIdentity $admissionIdentity,
        array $implementations,
        array $decisions,
    ) {
        $this->implementations = ExtensionGrantPlanValidator::implementations($implementations);
        $this->decisions = ExtensionGrantPlanValidator::decisions($decisions);
        $this->identity = new GrantPlanIdentity(hash('sha256', CanonicalJson::encode([
            'schema' => 'kongono.extension-grant-plan',
            'version' => 1,
            'admission_identity' => $admissionIdentity->value(),
            'implementations' => array_map(static fn(TrustedExtensionImplementation $implementation): array => [
                'extension_identity' => $implementation->identity()->value(),
                'extension_version' => $implementation->version()->value(),
                'implementation_class' => $implementation->className(),
            ], $this->implementations),
            'decisions' => array_map(static fn(CapabilityDecision $decision): array => [
                'extension_identity' => $decision->extensionIdentity()->value(),
                'extension_version' => $decision->extensionVersion()->value(),
                'capability_identity' => $decision->capabilityIdentity()->value(),
                'capability_version' => $decision->capabilityIdentity()->version(),
                'purpose' => $decision->purpose()->value(),
                'requirement' => $decision->requirement()->value,
                'constructor_parameter' => $decision->constructorParameter(),
                'provider_contract' => $decision->providerContract(),
                'disposition' => $decision->disposition()->value,
                'provider_lifetime' => $decision->providerLifetime()?->value,
            ], $this->decisions),
        ])));
    }

    public function admissionIdentity(): AdmissionIdentity
    {
        return $this->admissionIdentity;
    }
    /** @return list<TrustedExtensionImplementation> */
    public function implementations(): array
    {
        return [...$this->implementations];
    }
    /** @return list<CapabilityDecision> */
    public function decisions(): array
    {
        return [...$this->decisions];
    }
    public function identity(): GrantPlanIdentity
    {
        return $this->identity;
    }
}
