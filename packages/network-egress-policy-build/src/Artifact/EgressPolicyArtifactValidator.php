<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Artifact;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressPolicyBuild\Exception\InvalidEgressPolicyArtifactException;
use Kongono\NetworkEgressPolicyBuild\Exception\StaleEgressPolicyArtifactException;
use Kongono\NetworkEgressPolicyBuild\Internal\CanonicalJson;
use Kongono\NetworkEgressPolicyBuild\Policy\AddressScope;
use Kongono\NetworkEgressPolicyBuild\Policy\CookieMode;
use Kongono\NetworkEgressPolicyBuild\Policy\CredentialMode;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicyIdentity;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;
use Kongono\NetworkEgressPolicyBuild\Policy\ProxyMode;
use Kongono\NetworkEgressPolicyBuild\Policy\RedirectMode;
use Kongono\NetworkEgressPolicyBuild\Policy\TransportSecurity;
use Throwable;

/** Strictly validates inert policy evidence and explicit-source currentness. */
#[Api(ApiStatus::Experimental)]
final class EgressPolicyArtifactValidator
{
    public function validateStructure(string $artifactBytes): EgressPolicyArtifact
    {
        if (strlen($artifactBytes) > CanonicalJson::MAX_ARTIFACT_BYTES) {
            throw InvalidEgressPolicyArtifactException::limitExceeded(strlen($artifactBytes), CanonicalJson::MAX_ARTIFACT_BYTES);
        }
        try {
            $data = CanonicalJson::decodeObject($artifactBytes);
            if (!hash_equals(CanonicalJson::encode($data), $artifactBytes)) {
                $this->invalid('noncanonical_json');
            }
        } catch (JsonException $failure) {
            throw InvalidEgressPolicyArtifactException::invalid('invalid_json', artifactBytes: strlen($artifactBytes), previous: $failure);
        }
        $this->keys($data, ['schema', 'artifact_version', 'contract_version', 'builder', 'policy', 'source_sha256']);
        if ('kongono.network-egress-policy-artifact' !== ($data['schema'] ?? null) || 1 !== ($data['artifact_version'] ?? null) || 1 !== ($data['contract_version'] ?? null)) {
            $this->invalid('schema_or_version', 'schema');
        }
        $builder = $this->object($data['builder'] ?? null, 'builder');
        $this->keys($builder, ['package', 'version'], 'builder');
        if ('kongono/network-egress-policy-build' !== ($builder['package'] ?? null) || 1 !== ($builder['version'] ?? null)) {
            $this->invalid('builder_contract', 'builder');
        }

        $source = $this->source($data['policy'] ?? null);
        $sourceSha256 = $this->digest($data['source_sha256'] ?? null, 'source_sha256');
        $rebuilt = (new EgressPolicyArtifactBuilder())->build($source);
        if (!hash_equals($rebuilt->sourceSha256(), $sourceSha256) || !hash_equals($rebuilt->canonicalBytes(), $artifactBytes)) {
            $this->invalid('source_digest_mismatch', 'source_sha256');
        }

        return $rebuilt;
    }

    public function validateCurrent(EgressPolicySource $currentSource, string $artifactBytes): EgressPolicyArtifact
    {
        $artifact = $this->validateStructure($artifactBytes);
        $current = (new EgressPolicyArtifactBuilder())->build($currentSource);
        if (
            !hash_equals($current->canonicalBytes(), $artifactBytes)
            || !hash_equals($current->artifactSha256(), $artifact->artifactSha256())
            || !hash_equals($current->sourceSha256(), $artifact->sourceSha256())
        ) {
            throw StaleEgressPolicyArtifactException::currentnessMismatch();
        }

        return $artifact;
    }

    private function source(mixed $value): EgressPolicySource
    {
        $policy = $this->object($value, 'policy');
        $this->keys($policy, [
            'policy_id', 'allowed_origins', 'address_scope', 'transport_security', 'redirect_mode',
            'proxy_mode', 'cookie_mode', 'credential_mode', 'budget', 'required_capabilities',
        ], 'policy');
        try {
            return new EgressPolicySource(
                new EgressPolicyIdentity($this->text($policy['policy_id'] ?? null, 'policy_id')),
                $this->origins($policy['allowed_origins'] ?? null),
                $this->enum(AddressScope::class, $policy['address_scope'] ?? null, 'address_scope'),
                $this->enum(TransportSecurity::class, $policy['transport_security'] ?? null, 'transport_security'),
                $this->enum(RedirectMode::class, $policy['redirect_mode'] ?? null, 'redirect_mode'),
                $this->enum(ProxyMode::class, $policy['proxy_mode'] ?? null, 'proxy_mode'),
                $this->enum(CookieMode::class, $policy['cookie_mode'] ?? null, 'cookie_mode'),
                $this->enum(CredentialMode::class, $policy['credential_mode'] ?? null, 'credential_mode'),
                $this->budget($policy['budget'] ?? null),
                $this->capabilities($policy['required_capabilities'] ?? null),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidEgressPolicyArtifactException::invalid('compatibility_invalid', previous: $failure);
        }
    }

    /** @return list<CanonicalOrigin> */
    private function origins(mixed $value): array
    {
        $list = $this->list($value, 64, 'allowed_origins', false);
        $origins = [];
        foreach ($list as $position => $raw) {
            $origin = $this->object($raw, 'allowed_origins', $position);
            $this->keys($origin, ['scheme', 'host', 'port'], 'allowed_origins', $position);
            $scheme = OriginScheme::tryFrom($this->text($origin['scheme'] ?? null, 'scheme', $position));
            if (null === $scheme) {
                $this->invalid('origin_grammar', 'scheme', count($list), $position);
            }
            try {
                $origins[] = new CanonicalOrigin(
                    $scheme,
                    $this->text($origin['host'] ?? null, 'host', $position),
                    $this->boundedInt($origin['port'] ?? null, 1, 65_535, 'port', $position),
                );
            } catch (Throwable $failure) {
                if ($failure instanceof \Error) {
                    throw $failure;
                }
                throw InvalidEgressPolicyArtifactException::invalid('origin_grammar', 'allowed_origins', count($list), $position, previous: $failure);
            }
        }

        return $origins;
    }

    private function budget(mixed $value): EgressBudget
    {
        $budget = $this->object($value, 'budget');
        $fields = [
            'maximum_connection_attempts', 'maximum_redirects', 'maximum_resolution_candidates', 'maximum_request_bytes',
            'maximum_response_bytes', 'maximum_header_count', 'maximum_aggregate_header_bytes', 'maximum_buffered_bytes',
            'maximum_retained_evidence_bytes', 'connect_timeout_milliseconds', 'first_byte_timeout_milliseconds',
            'total_timeout_milliseconds',
        ];
        $this->keys($budget, $fields, 'budget');
        try {
            return new EgressBudget(
                $this->boundedInt($budget['maximum_connection_attempts'] ?? null, 1, 16, 'maximum_connection_attempts'),
                $this->boundedInt($budget['maximum_redirects'] ?? null, 0, 8, 'maximum_redirects'),
                $this->boundedInt($budget['maximum_resolution_candidates'] ?? null, 1, 64, 'maximum_resolution_candidates'),
                $this->boundedInt($budget['maximum_request_bytes'] ?? null, 0, 1_073_741_824, 'maximum_request_bytes'),
                $this->boundedInt($budget['maximum_response_bytes'] ?? null, 0, 1_073_741_824, 'maximum_response_bytes'),
                $this->boundedInt($budget['maximum_header_count'] ?? null, 1, 256, 'maximum_header_count'),
                $this->boundedInt($budget['maximum_aggregate_header_bytes'] ?? null, 1, 1_048_576, 'maximum_aggregate_header_bytes'),
                $this->boundedInt($budget['maximum_buffered_bytes'] ?? null, 1, 16_777_216, 'maximum_buffered_bytes'),
                $this->boundedInt($budget['maximum_retained_evidence_bytes'] ?? null, 1, 1_048_576, 'maximum_retained_evidence_bytes'),
                $this->boundedInt($budget['connect_timeout_milliseconds'] ?? null, 1, 300_000, 'connect_timeout_milliseconds'),
                $this->boundedInt($budget['first_byte_timeout_milliseconds'] ?? null, 1, 300_000, 'first_byte_timeout_milliseconds'),
                $this->boundedInt($budget['total_timeout_milliseconds'] ?? null, 1, 3_600_000, 'total_timeout_milliseconds'),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidEgressPolicyArtifactException::invalid('budget_invalid', 'budget', previous: $failure);
        }
    }

    private function capabilities(mixed $value): TransportCapabilitySet
    {
        $list = $this->list($value, 11, 'required_capabilities', false);
        $capabilities = [];
        foreach ($list as $position => $raw) {
            if (!is_string($raw) || null === ($capability = TransportCapability::tryFrom($raw))) {
                $this->invalid('capability_invalid', 'required_capabilities', count($list), $position);
            }
            $capabilities[] = $capability;
        }
        try {
            return new TransportCapabilitySet($capabilities);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidEgressPolicyArtifactException::invalid('capability_invalid', 'required_capabilities', count($list), previous: $failure);
        }
    }

    /** @template T of \BackedEnum
     * @param class-string<T> $class
     * @return T
     */
    private function enum(string $class, mixed $value, string $field): \BackedEnum
    {
        if (!is_string($value) || null === ($case = $class::tryFrom($value))) {
            $this->invalid('invalid_scalar', $field);
        }

        return $case;
    }

    /** @param array<string, mixed> $object
     * @param list<string> $expected
     */
    private function keys(array $object, array $expected, ?string $field = null, ?int $position = null): void
    {
        if (array_keys($object) !== $expected) {
            $this->invalid('invalid_shape', $field, position: $position);
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, ?string $field = null, ?int $position = null): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid('invalid_shape', $field, position: $position);
        }
        $object = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                $this->invalid('invalid_shape', $field, position: $position);
            }
            $object[$key] = $entry;
        }

        return $object;
    }

    /** @return list<mixed> */
    private function list(mixed $value, int $maximum, string $field, bool $emptyAllowed): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->invalid('invalid_list', $field);
        }
        if (count($value) > $maximum || (!$emptyAllowed && [] === $value)) {
            $this->invalid('invalid_list', $field, count($value), limit: $maximum);
        }

        return $value;
    }

    private function text(mixed $value, string $field, ?int $position = null): string
    {
        if (!is_string($value)) {
            $this->invalid('invalid_scalar', $field, position: $position);
        }

        return $value;
    }

    private function digest(mixed $value, string $field): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            $this->invalid('digest_grammar', $field);
        }

        return $value;
    }

    private function boundedInt(mixed $value, int $minimum, int $maximum, string $field, ?int $position = null): int
    {
        if (!is_int($value) || $value < $minimum || $value > $maximum) {
            $this->invalid('invalid_scalar', $field, position: $position, limit: $maximum);
        }

        return $value;
    }

    private function invalid(
        string $violation,
        ?string $field = null,
        ?int $count = null,
        ?int $position = null,
        ?int $limit = null,
        ?int $artifactBytes = null,
    ): never {
        throw InvalidEgressPolicyArtifactException::invalid($violation, $field, $count, $position, $limit, $artifactBytes);
    }
}
