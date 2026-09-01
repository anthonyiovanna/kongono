<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Generated;

use JsonException;
use Kongono\AgentAdmission\Identity\AgentAdmissionIdentity;
use Kongono\AgentApplication\Exception\InvalidGeneratedAgentWiringException;
use Kongono\AgentApplication\Internal\GeneratedWiringEmitter;
use Kongono\AgentApplication\Internal\GeneratedWiringImplementation;
use Kongono\AgentApplication\Profile\AgentApplicationProfile;
use Kongono\AgentApplication\Profile\AgentApplicationProfileIdentity;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclarationIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Separates complete structural validation from caller-relative exact-current validation. */
#[Api(ApiStatus::Experimental)]
final class AgentWiringValidator
{
    public function validateStructure(string $bytes): GeneratedAgentWiring
    {
        if ('' === $bytes || strlen($bytes) > AgentWiringGenerator::MAX_GENERATED_BYTES || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            throw InvalidGeneratedAgentWiringException::invalid('generated_bytes');
        }
        if (1 !== preg_match('/\A<\?php\n\ndeclare\(strict_types=1\);\n\n\/\/ (\{[^\n]+\})\n\nnamespace ([A-Za-z0-9_\\\\]+);\n/D', $bytes, $matches)) {
            throw InvalidGeneratedAgentWiringException::invalid('generated_header');
        }
        try {
            $decoded = json_decode($matches[1], true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            throw InvalidGeneratedAgentWiringException::invalid('metadata_json', $failure);
        }
        $keys = ['schema', 'version', 'profile_identity', 'declaration_artifact_sha256', 'declaration_identity', 'admission_identity', 'permitted_capability_identities', 'generator_implementation_sha256', 'generated_namespace', 'generated_class', 'generated_interface'];
        if (!is_array($decoded) || array_is_list($decoded) || array_keys($decoded) !== $keys || 'kongono.agent-application.generated-wiring' !== ($decoded['schema'] ?? null) || 1 !== ($decoded['version'] ?? null)) {
            throw InvalidGeneratedAgentWiringException::invalid('metadata_shape');
        }
        $metadata = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                throw InvalidGeneratedAgentWiringException::invalid('metadata_shape');
            }
            $metadata[$key] = $value;
        }
        try {
            if (json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !== $matches[1]) {
                throw InvalidGeneratedAgentWiringException::invalid('metadata_json');
            }
        } catch (JsonException $failure) {
            throw InvalidGeneratedAgentWiringException::invalid('metadata_json', $failure);
        }
        $profile = new AgentApplicationProfileIdentity($this->digest($metadata['profile_identity'] ?? null));
        $artifact = $this->digest($metadata['declaration_artifact_sha256'] ?? null);
        $declaration = new AgentDeclarationIdentity($this->text($metadata['declaration_identity'] ?? null));
        $admission = new AgentAdmissionIdentity($this->digest($metadata['admission_identity'] ?? null));
        $implementation = $this->digest($metadata['generator_implementation_sha256'] ?? null);
        if (!hash_equals(GeneratedWiringImplementation::sha256(), $implementation)) {
            throw InvalidGeneratedAgentWiringException::invalid('implementation_mismatch');
        }
        $namespace = $this->text($metadata['generated_namespace'] ?? null);
        $class = $this->text($metadata['generated_class'] ?? null);
        if ($namespace !== 'KongonoGenerated\\Agent\\V1\\P' . $profile->value() || $class !== $namespace . '\\AgentApplicationBoundary' || GeneratedAgentApplicationBoundary::class !== ($metadata['generated_interface'] ?? null) || $matches[2] !== $namespace) {
            throw InvalidGeneratedAgentWiringException::invalid('identity_grammar');
        }
        $rawCapabilities = $metadata['permitted_capability_identities'] ?? null;
        if (!is_array($rawCapabilities) || !array_is_list($rawCapabilities) || count($rawCapabilities) > 32) {
            throw InvalidGeneratedAgentWiringException::invalid('metadata_shape');
        }
        $capabilities = [];
        $previous = null;
        foreach ($rawCapabilities as $value) {
            $identity = new CapabilityIdentity($this->text($value));
            if (null !== $previous && strcmp($previous, $identity->value()) >= 0) {
                throw InvalidGeneratedAgentWiringException::invalid('metadata_shape');
            }
            $previous = $identity->value();
            $capabilities[] = $identity;
        }
        try {
            if (!hash_equals(GeneratedWiringEmitter::emit($metadata), $bytes)) {
                throw InvalidGeneratedAgentWiringException::invalid('template_mismatch');
            }
        } catch (Throwable $failure) {
            if ($failure instanceof InvalidGeneratedAgentWiringException || $failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidGeneratedAgentWiringException::invalid('template_mismatch', $failure);
        }

        return new GeneratedAgentWiring($profile, $artifact, $declaration, $admission, $capabilities, $implementation, $namespace, $class, $bytes, hash('sha256', $bytes));
    }

    public function validateCurrent(AgentApplicationProfile $profile, string $bytes): GeneratedAgentWiring
    {
        $validated = $this->validateStructure($bytes);
        $expected = (new AgentWiringGenerator())->generate($profile);
        if (!hash_equals($expected->canonicalBytes(), $bytes)) {
            throw InvalidGeneratedAgentWiringException::invalid('profile_mismatch');
        }

        return $validated;
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidGeneratedAgentWiringException::invalid('identity_grammar');
        }

        return $value;
    }

    private function text(mixed $value): string
    {
        if (!is_string($value)) {
            throw InvalidGeneratedAgentWiringException::invalid('metadata_shape');
        }

        return $value;
    }
}
