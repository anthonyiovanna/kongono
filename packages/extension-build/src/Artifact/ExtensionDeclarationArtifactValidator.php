<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Artifact;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\ArtifactVersion;
use Kongono\ExtensionBuild\Exception\InvalidExtensionBuildException;
use Kongono\ExtensionBuild\Exception\StaleExtensionDeclarationArtifactException;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
use Kongono\ExtensionBuild\Internal\CanonicalJson;
use Kongono\ExtensionContract\Capability\CapabilityIdentity;
use Kongono\ExtensionContract\Capability\CapabilityPurpose;
use Kongono\ExtensionContract\Capability\CapabilityRequest;
use Kongono\ExtensionContract\Capability\CapabilityRequirement;
use Kongono\ExtensionContract\ContractVersion;
use Kongono\ExtensionContract\Contribution\ContributionDeclaration;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;
use Kongono\ExtensionContract\Contribution\ContributionSchema;
use Kongono\ExtensionContract\Contribution\ContributionVisibility;
use Kongono\ExtensionContract\Declaration\ExtensionDeclaration;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionContract\Provenance\ExtensionProvenance;
use Kongono\ExtensionContract\Provenance\ExtensionSourceIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use Throwable;

/** Reconstructs strict declaration values and proves caller-relative currentness. */
#[Api(ApiStatus::Experimental)]
final class ExtensionDeclarationArtifactValidator
{
    public function validateStructure(string $artifactBytes): ExtensionDeclarationArtifact
    {
        if (strlen($artifactBytes) > ExtensionDeclarationArtifactBuilder::MAX_ARTIFACT_BYTES) {
            throw InvalidExtensionBuildException::limit('artifact_bytes', 'artifact', null, strlen($artifactBytes));
        }

        try {
            $data = CanonicalJson::decodeObject($artifactBytes, 64, ExtensionDeclarationArtifactBuilder::MAX_ARTIFACT_BYTES);
        } catch (JsonException $failure) {
            throw InvalidExtensionBuildException::artifact('invalid_json', 'artifact', artifactBytes: strlen($artifactBytes), previous: $failure);
        }
        if (CanonicalJson::encode($data) !== $artifactBytes) {
            $this->invalid('noncanonical_json');
        }

        $this->keys($data, [
            'schema',
            'version',
            'declaration_set_identity',
            'declaration_set_sha256',
            'builder',
            'declaration_count',
            'declarations',
        ]);
        if ('kongono.extension-declaration-artifact' !== ($data['schema'] ?? null) || ArtifactVersion::Version1->value !== ($data['version'] ?? null)) {
            $this->invalid('schema_or_version');
        }

        $builder = $this->object($data['builder'] ?? null, 'builder');
        $this->keys($builder, ['package', 'contract_version']);
        if ('kongono/extension-build' !== ($builder['package'] ?? null) || ExtensionDeclarationArtifactBuilder::CONTRACT_VERSION !== ($builder['contract_version'] ?? null)) {
            $this->invalid('builder_contract', 'builder');
        }

        $rawDeclarations = $this->list($data['declarations'] ?? null, 'declaration');
        $declarationCount = $data['declaration_count'] ?? null;
        if (!is_int($declarationCount) || $declarationCount !== count($rawDeclarations)) {
            $this->invalid('count_mismatch', 'declaration');
        }
        if ($declarationCount > ExtensionDeclarationArtifactBuilder::MAX_DECLARATIONS) {
            throw InvalidExtensionBuildException::limit('declaration_count', 'declaration', $declarationCount);
        }

        $declarationSetSha256 = $this->digest($data['declaration_set_sha256'] ?? null);
        $identityValue = $this->digest($data['declaration_set_identity'] ?? null);
        if (!hash_equals($declarationSetSha256, hash('sha256', CanonicalJson::encode(['declarations' => $rawDeclarations])))) {
            $this->invalid('declaration_set_digest_mismatch', 'declaration');
        }
        if (!hash_equals($declarationSetSha256, $identityValue)) {
            $this->invalid('declaration_set_identity_mismatch', 'identity');
        }
        $identity = new DeclarationSetIdentity($identityValue);

        try {
            $declarations = $this->declarations($rawDeclarations);
            $fresh = (new ExtensionDeclarationArtifactBuilder())->build($declarations);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidExtensionBuildException::artifact(
                'declaration_readmission_failed',
                'declaration',
                $declarationCount,
                previous: $failure,
            );
        }
        if (!hash_equals($fresh->canonicalBytes(), $artifactBytes)) {
            $this->invalid('artifact_rebuild_mismatch');
        }
        if (!$identity->equals($fresh->declarationSetIdentity())) {
            $this->invalid('declaration_set_identity_mismatch', 'identity');
        }

        return $fresh;
    }

    /** @param list<ExtensionDeclaration> $currentDeclarations */
    public function validateCurrent(array $currentDeclarations, string $artifactBytes): ExtensionDeclarationArtifact
    {
        $artifact = $this->validateStructure($artifactBytes);
        $current = (new ExtensionDeclarationArtifactBuilder())->build($currentDeclarations);
        if (
            !hash_equals($current->artifactSha256(), $artifact->artifactSha256())
            || !hash_equals($current->canonicalBytes(), $artifactBytes)
        ) {
            throw StaleExtensionDeclarationArtifactException::currentnessMismatch();
        }

        return $artifact;
    }

    /**
     * @param list<mixed> $values
     * @return list<ExtensionDeclaration>
     */
    private function declarations(array $values): array
    {
        $declarations = [];
        foreach ($values as $position => $value) {
            $declaration = $this->object($value, 'declaration');
            $this->keys($declaration, [
                'contract_version',
                'identity',
                'extension_version',
                'category',
                'provenance',
                'lifecycle',
                'capability_requests',
                'contributions',
            ]);
            if (ContractVersion::Version1->value !== ($declaration['contract_version'] ?? null)) {
                $this->invalid('invalid_scalar', 'declaration', $position);
            }
            $category = ExtensionCategory::tryFrom($this->text($declaration['category'] ?? null, 'declaration', $position));
            $lifecycle = ExtensionLifecycle::tryFrom($this->text($declaration['lifecycle'] ?? null, 'declaration', $position));
            if (null === $category || null === $lifecycle) {
                $this->invalid('invalid_scalar', 'declaration', $position);
            }

            $provenance = $this->object($declaration['provenance'] ?? null, 'provenance');
            $this->keys($provenance, ['source_identity', 'source_sha256']);
            $declarations[] = new ExtensionDeclaration(
                ContractVersion::Version1,
                new \Kongono\ExtensionContract\Identity\ExtensionIdentity($this->text($declaration['identity'] ?? null, 'identity', $position)),
                new ExtensionVersion($this->text($declaration['extension_version'] ?? null, 'declaration', $position)),
                $category,
                new ExtensionProvenance(
                    new ExtensionSourceIdentity($this->text($provenance['source_identity'] ?? null, 'provenance', $position)),
                    $this->text($provenance['source_sha256'] ?? null, 'provenance', $position),
                ),
                $lifecycle,
                $this->capabilities($this->list($declaration['capability_requests'] ?? null, 'capability')),
                $this->contributions($this->list($declaration['contributions'] ?? null, 'contribution')),
            );
        }

        return $declarations;
    }

    /**
     * @param list<mixed> $values
     * @return list<CapabilityRequest>
     */
    private function capabilities(array $values): array
    {
        $requests = [];
        foreach ($values as $position => $value) {
            $request = $this->object($value, 'capability');
            $this->keys($request, ['identity', 'version', 'purpose', 'requirement']);
            $version = $request['version'] ?? null;
            $requirement = CapabilityRequirement::tryFrom($this->text($request['requirement'] ?? null, 'capability', $position));
            if (!is_int($version) || null === $requirement) {
                $this->invalid('invalid_scalar', 'capability', $position);
            }
            $requests[] = new CapabilityRequest(
                new CapabilityIdentity($this->text($request['identity'] ?? null, 'capability', $position), $version),
                new CapabilityPurpose($this->text($request['purpose'] ?? null, 'capability', $position)),
                $requirement,
            );
        }

        return $requests;
    }

    /**
     * @param list<mixed> $values
     * @return list<ContributionDeclaration>
     */
    private function contributions(array $values): array
    {
        $contributions = [];
        foreach ($values as $position => $value) {
            $contribution = $this->object($value, 'contribution');
            $this->keys($contribution, ['identity', 'schema', 'schema_version', 'visibility', 'source_sha256']);
            $schemaVersion = $contribution['schema_version'] ?? null;
            $visibility = ContributionVisibility::tryFrom($this->text($contribution['visibility'] ?? null, 'contribution', $position));
            if (!is_int($schemaVersion) || null === $visibility) {
                $this->invalid('invalid_scalar', 'contribution', $position);
            }
            $contributions[] = new ContributionDeclaration(
                new ContributionIdentity($this->text($contribution['identity'] ?? null, 'contribution', $position)),
                new ContributionSchema($this->text($contribution['schema'] ?? null, 'contribution', $position), $schemaVersion),
                $visibility,
                $this->text($contribution['source_sha256'] ?? null, 'contribution', $position),
            );
        }

        return $contributions;
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $keys
     */
    private function keys(array $value, array $keys): void
    {
        if (array_keys($value) !== $keys) {
            $this->invalid('invalid_shape');
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, string $kind): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid('invalid_shape', $kind);
        }

        $object = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                $this->invalid('invalid_shape', $kind);
            }
            $object[$key] = $entry;
        }

        return $object;
    }

    /** @return list<mixed> */
    private function list(mixed $value, string $kind): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->invalid('invalid_shape', $kind);
        }

        return $value;
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            $this->invalid('digest_grammar', 'identity');
        }

        return $value;
    }

    private function text(mixed $value, string $kind, int $position): string
    {
        if (!is_string($value)) {
            $this->invalid('invalid_scalar', $kind, $position);
        }

        return $value;
    }

    private function invalid(string $violation, string $kind = 'artifact', ?int $position = null): never
    {
        throw InvalidExtensionBuildException::artifact($violation, $kind, entryPosition: $position);
    }
}
