<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Admission;

use Kongono\AgentAdmission\Evidence\CapabilityAuthorizationEvidence;
use Kongono\AgentAdmission\Exception\InvalidAgentAdmissionException;
use Kongono\AgentAdmission\Internal\AdmissionGuard;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\AuthenticationVerification;

/** Holds exact explicit application inputs for one deterministic admission. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentAdmissionRequest
{
    private const int MAX_ARTIFACT_BYTES = 131_072;

    /** @var list<CapabilityIdentity> */
    private array $requestedCapabilities;
    /** @var list<CapabilityAuthorizationEvidence> */
    private array $authorizationEvidence;

    /**
     * @param array<array-key, mixed> $requestedCapabilities
     * @param array<array-key, mixed> $authorizationEvidence
     */
    public function __construct(
        private AgentDeclaration $declaration,
        private string $declarationArtifactBytes,
        private AuthenticationVerification $authenticationVerification,
        array $requestedCapabilities,
        array $authorizationEvidence,
    ) {
        if (strlen($declarationArtifactBytes) > self::MAX_ARTIFACT_BYTES) {
            throw InvalidAgentAdmissionException::invalid('artifact_bytes_limit');
        }
        $this->requestedCapabilities = AdmissionGuard::requestedCapabilities($requestedCapabilities);
        $this->authorizationEvidence = AdmissionGuard::authorizationEvidence($authorizationEvidence);

        $requested = [];
        foreach ($this->requestedCapabilities as $capability) {
            $requested[$capability->value()] = true;
        }
        foreach ($this->authorizationEvidence as $position => $evidence) {
            if (!isset($requested[$evidence->capabilityIdentity()->value()])) {
                throw InvalidAgentAdmissionException::invalid('evidence_without_request', count($authorizationEvidence), $position);
            }
        }
    }

    public function declaration(): AgentDeclaration
    {
        return $this->declaration;
    }

    public function declarationArtifactBytes(): string
    {
        return $this->declarationArtifactBytes;
    }

    public function authenticationVerification(): AuthenticationVerification
    {
        return $this->authenticationVerification;
    }

    /** @return list<CapabilityIdentity> */
    public function requestedCapabilities(): array
    {
        return [...$this->requestedCapabilities];
    }

    /** @return list<CapabilityAuthorizationEvidence> */
    public function authorizationEvidence(): array
    {
        return [...$this->authorizationEvidence];
    }
}
