<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Generated;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Exception\InvalidGeneratedNetworkEgressWiringException;
use Kongono\NetworkEgressApplication\Internal\GeneratedWiringEmitter;
use Kongono\NetworkEgressApplication\Internal\GeneratedWiringImplementation;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileIdentity;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactValidator;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicyIdentity;
use Throwable;

/** Separates structural validation from caller-relative exact-current proof. */
#[Api(ApiStatus::Internal)]
final class NetworkEgressWiringValidator
{
    public function validateStructure(string $bytes): GeneratedNetworkEgressWiring
    {
        if ('' === $bytes || strlen($bytes) > NetworkEgressWiringGenerator::MAX_GENERATED_BYTES || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            throw InvalidGeneratedNetworkEgressWiringException::invalid('generated_bytes');
        }
        if (1 !== preg_match('/\A<\?php\n\ndeclare\(strict_types=1\);\n\n\/\/ (\{[^\n]+\})\n\nnamespace ([A-Za-z0-9_\\\\]+);\n/D', $bytes, $matches)) {
            throw InvalidGeneratedNetworkEgressWiringException::invalid('generated_header');
        }
        try {
            $decoded = json_decode($matches[1], true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            throw InvalidGeneratedNetworkEgressWiringException::invalid('metadata_json', $failure);
        }
        $keys = ['schema', 'version', 'contract_version', 'application_profile_identity', 'policy_identity', 'artifact_sha256', 'source_sha256', 'required_capabilities_sha256', 'generator_implementation_sha256', 'generated_namespace', 'generated_class', 'generated_interface'];
        if (!is_array($decoded) || array_is_list($decoded) || array_keys($decoded) !== $keys || 'kongono.network-egress-application.generated-wiring' !== ($decoded['schema'] ?? null) || 1 !== ($decoded['version'] ?? null) || 1 !== ($decoded['contract_version'] ?? null)) {
            throw InvalidGeneratedNetworkEgressWiringException::invalid('metadata_shape');
        }
        /** @var array<string, mixed> $metadata */
        $metadata = $decoded;
        try {
            if (json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !== $matches[1]) {
                throw InvalidGeneratedNetworkEgressWiringException::invalid('metadata_json');
            }
        } catch (JsonException $failure) {
            throw InvalidGeneratedNetworkEgressWiringException::invalid('metadata_json', $failure);
        }
        try {
            $applicationProfile = new NetworkEgressApplicationProfileIdentity($this->digest($metadata['application_profile_identity'] ?? null));
            $policy = new EgressPolicyIdentity($this->text($metadata['policy_identity'] ?? null));
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidGeneratedNetworkEgressWiringException::invalid('identity_grammar', $failure);
        }
        $artifact = $this->digest($metadata['artifact_sha256'] ?? null);
        $source = $this->digest($metadata['source_sha256'] ?? null);
        $capabilities = $this->digest($metadata['required_capabilities_sha256'] ?? null);
        $implementation = $this->digest($metadata['generator_implementation_sha256'] ?? null);
        if (!hash_equals(GeneratedWiringImplementation::sha256(), $implementation)) {
            throw InvalidGeneratedNetworkEgressWiringException::invalid('implementation_mismatch');
        }
        $namespace = $this->text($metadata['generated_namespace'] ?? null);
        $class = $this->text($metadata['generated_class'] ?? null);
        if ($namespace !== 'KongonoGenerated\\NetworkEgress\\V1\\P' . $applicationProfile->value() || $class !== $namespace . '\\NetworkEgressApplicationBoundary' || GeneratedNetworkEgressApplicationBoundary::class !== ($metadata['generated_interface'] ?? null) || $matches[2] !== $namespace) {
            throw InvalidGeneratedNetworkEgressWiringException::invalid('identity_grammar');
        }
        try {
            if (!hash_equals(GeneratedWiringEmitter::emit($metadata), $bytes)) {
                throw InvalidGeneratedNetworkEgressWiringException::invalid('template_mismatch');
            }
        } catch (Throwable $failure) {
            if ($failure instanceof InvalidGeneratedNetworkEgressWiringException || $failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidGeneratedNetworkEgressWiringException::invalid('template_mismatch', $failure);
        }

        return new GeneratedNetworkEgressWiring($applicationProfile, $policy, $artifact, $source, $capabilities, $implementation, $namespace, $class, $bytes, hash('sha256', $bytes));
    }

    public function validateCurrent(NetworkEgressApplicationProfile $profile, string $bytes): GeneratedNetworkEgressWiring
    {
        try {
            (new EgressPolicyArtifactValidator())->validateCurrent($profile->source(), $profile->artifact()->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidGeneratedNetworkEgressWiringException::invalid('profile_mismatch', $failure);
        }
        $validated = $this->validateStructure($bytes);
        $expected = (new NetworkEgressWiringGenerator())->generate($profile);
        if (!hash_equals($expected->canonicalBytes(), $bytes)) {
            throw InvalidGeneratedNetworkEgressWiringException::invalid('profile_mismatch');
        }

        return $validated;
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidGeneratedNetworkEgressWiringException::invalid('identity_grammar');
        }

        return $value;
    }

    private function text(mixed $value): string
    {
        if (!is_string($value)) {
            throw InvalidGeneratedNetworkEgressWiringException::invalid('metadata_shape');
        }

        return $value;
    }
}
