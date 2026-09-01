<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Artifact;

use JsonException;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclarationIdentity;
use Kongono\AgentDeclarationBuild\Exception\InvalidAgentDeclarationArtifactException;
use Kongono\AgentDeclarationBuild\Exception\StaleAgentDeclarationArtifactException;
use Kongono\AgentDeclarationBuild\Internal\CanonicalJson;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityPurpose;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Constraint\ConstraintKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceIdentity;
use Kongono\AgentPrincipalContract\Evidence\EvidenceKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\AgentPrincipalContract\Provenance\AgentProvenance;
use Kongono\AgentPrincipalContract\Provenance\ProvenanceSource;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use Throwable;

/** Reconstructs strict typed declarations and proves explicit currentness. */
#[Api(ApiStatus::Experimental)]
final class AgentDeclarationArtifactValidator
{
    private const int MAX_ARTIFACT_BYTES = 131_072;

    public function validateStructure(string $artifactBytes): AgentDeclarationArtifact
    {
        if (strlen($artifactBytes) > self::MAX_ARTIFACT_BYTES) {
            throw InvalidAgentDeclarationArtifactException::limit('artifact_bytes', strlen($artifactBytes));
        }

        try {
            $data = CanonicalJson::decodeObject($artifactBytes);
            if (!hash_equals(CanonicalJson::encode($data), $artifactBytes)) {
                $this->invalid('noncanonical_json');
            }
        } catch (JsonException $failure) {
            throw InvalidAgentDeclarationArtifactException::invalid(
                'invalid_json',
                artifactBytes: strlen($artifactBytes),
                previous: $failure,
            );
        }

        $this->keys($data, ['schema', 'artifact_version', 'contract_version', 'builder', 'declaration', 'declaration_sha256']);
        if (
            'kongono.agent-declaration-artifact' !== ($data['schema'] ?? null)
            || 1 !== ($data['artifact_version'] ?? null)
            || 1 !== ($data['contract_version'] ?? null)
        ) {
            $this->invalid('schema_or_version');
        }

        $builder = $this->object($data['builder'] ?? null);
        $this->keys($builder, ['package', 'version']);
        if ('kongono/agent-declaration-build' !== ($builder['package'] ?? null) || 1 !== ($builder['version'] ?? null)) {
            $this->invalid('builder_contract');
        }

        $rawDeclaration = $this->object($data['declaration'] ?? null);
        $this->keys($rawDeclaration, [
            'declaration_id', 'principal', 'capability_requests', 'constraints',
            'evidence_references', 'provenance', 'visibility',
        ]);
        $declarationSha256 = $this->digest($data['declaration_sha256'] ?? null);
        try {
            $expectedDeclarationSha256 = hash('sha256', CanonicalJson::encode($rawDeclaration));
        } catch (JsonException $failure) {
            throw InvalidAgentDeclarationArtifactException::invalid('invalid_json', previous: $failure);
        }
        if (!hash_equals($expectedDeclarationSha256, $declarationSha256)) {
            $this->invalid('declaration_digest_mismatch');
        }

        try {
            $declaration = $this->declaration($rawDeclaration);
            $fresh = (new AgentDeclarationArtifactBuilder())->build($declaration);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidAgentDeclarationArtifactException::invalid('declaration_readmission_failed', previous: $failure);
        }
        if (!hash_equals($fresh->canonicalBytes(), $artifactBytes)) {
            $this->invalid('artifact_rebuild_mismatch');
        }
        if (!hash_equals($fresh->declarationSha256(), $declarationSha256)) {
            $this->invalid('declaration_digest_mismatch');
        }

        return $fresh;
    }

    public function validateCurrent(
        AgentDeclaration $currentDeclaration,
        string $artifactBytes,
    ): AgentDeclarationArtifact {
        $artifact = $this->validateStructure($artifactBytes);
        $current = (new AgentDeclarationArtifactBuilder())->build($currentDeclaration);
        if (!hash_equals($current->canonicalBytes(), $artifactBytes)) {
            throw StaleAgentDeclarationArtifactException::currentnessMismatch();
        }

        return $artifact;
    }

    /** @param array<string, mixed> $data */
    private function declaration(array $data): AgentDeclaration
    {
        $principal = $this->object($data['principal'] ?? null);
        $this->keys($principal, ['kind', 'id']);
        if ('agent' !== ($principal['kind'] ?? null)) {
            $this->invalid('invalid_scalar');
        }

        $capabilities = [];
        foreach ($this->list($data['capability_requests'] ?? null, 32) as $position => $value) {
            $entry = $this->object($value, $position);
            $this->keys($entry, ['identity', 'purpose', 'requirement'], $position);
            $requirement = CapabilityRequirement::tryFrom($this->text($entry['requirement'] ?? null, $position));
            if (null === $requirement) {
                $this->invalid('invalid_scalar', position: $position);
            }
            $capabilities[] = new CapabilityRequest(
                new CapabilityIdentity($this->text($entry['identity'] ?? null, $position)),
                new CapabilityPurpose($this->text($entry['purpose'] ?? null, $position)),
                $requirement,
            );
        }

        $constraints = [];
        foreach ($this->list($data['constraints'] ?? null, 9) as $position => $value) {
            $entry = $this->object($value, $position);
            $this->keys($entry, ['kind', 'value'], $position);
            $kind = ConstraintKind::tryFrom($this->text($entry['kind'] ?? null, $position));
            $constraintValue = $entry['value'] ?? null;
            if (null === $kind || !is_int($constraintValue)) {
                $this->invalid('invalid_scalar', position: $position);
            }
            $constraints[] = new AgentConstraint($kind, $constraintValue);
        }

        $evidence = [];
        foreach ($this->list($data['evidence_references'] ?? null, 32) as $position => $value) {
            $entry = $this->object($value, $position);
            $this->keys($entry, ['kind', 'identity', 'sha256'], $position);
            $kind = EvidenceKind::tryFrom($this->text($entry['kind'] ?? null, $position));
            if (null === $kind) {
                $this->invalid('invalid_scalar', position: $position);
            }
            $evidence[] = new EvidenceReference(
                $kind,
                new EvidenceIdentity($this->text($entry['identity'] ?? null, $position)),
                $this->digest($entry['sha256'] ?? null, $position),
            );
        }

        $provenance = $this->object($data['provenance'] ?? null);
        $this->keys($provenance, ['source', 'source_identity', 'sha256']);
        $source = ProvenanceSource::tryFrom($this->text($provenance['source'] ?? null));
        $visibility = AgentVisibility::tryFrom($this->text($data['visibility'] ?? null));
        if (null === $source || null === $visibility) {
            $this->invalid('invalid_scalar');
        }

        return new AgentDeclaration(
            new AgentDeclarationIdentity($this->text($data['declaration_id'] ?? null)),
            new AgentPrincipalReference(new PrincipalReference(
                PrincipalKind::Agent,
                new PrincipalId($this->text($principal['id'] ?? null)),
            )),
            $capabilities,
            $constraints,
            $evidence,
            new AgentProvenance(
                $source,
                $this->text($provenance['source_identity'] ?? null),
                $this->digest($provenance['sha256'] ?? null),
            ),
            $visibility,
        );
    }

    /**
     * @param array<string, mixed> $object
     * @param list<string> $expected
     */
    private function keys(array $object, array $expected, ?int $position = null): void
    {
        if (array_keys($object) !== $expected) {
            $this->invalid('invalid_shape', position: $position);
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, ?int $position = null): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid('invalid_shape', position: $position);
        }
        $object = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                $this->invalid('invalid_shape', position: $position);
            }
            $object[$key] = $entry;
        }

        return $object;
    }

    /** @return list<mixed> */
    private function list(mixed $value, int $maximum): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->invalid('invalid_list');
        }
        if (count($value) > $maximum) {
            throw InvalidAgentDeclarationArtifactException::invalid('invalid_list', count($value));
        }

        return $value;
    }

    private function text(mixed $value, ?int $position = null): string
    {
        if (!is_string($value)) {
            $this->invalid('invalid_scalar', position: $position);
        }

        return $value;
    }

    private function digest(mixed $value, ?int $position = null): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            $this->invalid('digest_grammar', position: $position);
        }

        return $value;
    }

    private function invalid(string $violation, ?int $count = null, ?int $position = null): never
    {
        throw InvalidAgentDeclarationArtifactException::invalid($violation, $count, $position);
    }
}
