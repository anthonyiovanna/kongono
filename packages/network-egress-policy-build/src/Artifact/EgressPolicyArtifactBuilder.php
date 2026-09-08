<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Artifact;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressPolicyBuild\Exception\InvalidEgressPolicyArtifactException;
use Kongono\NetworkEgressPolicyBuild\Internal\CanonicalJson;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;
use Kongono\NetworkEgressPolicyBuild\Version\EgressPolicyArtifactVersion;

/** Builds canonical inert policy evidence from one explicit typed source. */
#[Api(ApiStatus::Experimental)]
final class EgressPolicyArtifactBuilder
{
    public function build(EgressPolicySource $source): EgressPolicyArtifact
    {
        try {
            $policy = self::policyData($source);
            $sourceSha256 = hash('sha256', CanonicalJson::encode([
                'contract_version' => 1,
                'policy' => $policy,
            ]));
            $bytes = CanonicalJson::encode([
                'schema' => 'kongono.network-egress-policy-artifact',
                'artifact_version' => EgressPolicyArtifactVersion::V1->value,
                'contract_version' => 1,
                'builder' => ['package' => 'kongono/network-egress-policy-build', 'version' => 1],
                'policy' => $policy,
                'source_sha256' => $sourceSha256,
            ]);
        } catch (JsonException $failure) {
            throw InvalidEgressPolicyArtifactException::invalid('encoding_failed', previous: $failure);
        }
        if (strlen($bytes) > CanonicalJson::MAX_ARTIFACT_BYTES) {
            throw InvalidEgressPolicyArtifactException::limitExceeded(strlen($bytes), CanonicalJson::MAX_ARTIFACT_BYTES);
        }

        return new EgressPolicyArtifact($bytes, hash('sha256', $bytes), $sourceSha256, $source->identity());
    }

    /** @return array<string, mixed> */
    private static function policyData(EgressPolicySource $source): array
    {
        return [
            'policy_id' => $source->identity()->value(),
            'allowed_origins' => array_map(static fn(CanonicalOrigin $origin): array => [
                'scheme' => $origin->scheme()->value,
                'host' => $origin->host(),
                'port' => $origin->port(),
            ], $source->allowedOrigins()),
            'address_scope' => $source->addressScope()->value,
            'transport_security' => $source->transportSecurity()->value,
            'redirect_mode' => $source->redirectMode()->value,
            'proxy_mode' => $source->proxyMode()->value,
            'cookie_mode' => $source->cookieMode()->value,
            'credential_mode' => $source->credentialMode()->value,
            'budget' => self::budgetData($source->budget()),
            'required_capabilities' => array_map(static fn(TransportCapability $capability): string => $capability->value, $source->requiredCapabilities()->all()),
        ];
    }

    /** @return array<string, int> */
    private static function budgetData(EgressBudget $budget): array
    {
        return [
            'maximum_connection_attempts' => $budget->maximumConnectionAttempts(),
            'maximum_redirects' => $budget->maximumRedirects(),
            'maximum_resolution_candidates' => $budget->maximumResolutionCandidates(),
            'maximum_request_bytes' => $budget->maximumRequestBytes(),
            'maximum_response_bytes' => $budget->maximumResponseBytes(),
            'maximum_header_count' => $budget->maximumHeaderCount(),
            'maximum_aggregate_header_bytes' => $budget->maximumAggregateHeaderBytes(),
            'maximum_buffered_bytes' => $budget->maximumBufferedBytes(),
            'maximum_retained_evidence_bytes' => $budget->maximumRetainedEvidenceBytes(),
            'connect_timeout_milliseconds' => $budget->connectTimeoutMilliseconds(),
            'first_byte_timeout_milliseconds' => $budget->firstByteTimeoutMilliseconds(),
            'total_timeout_milliseconds' => $budget->totalTimeoutMilliseconds(),
        ];
    }
}
