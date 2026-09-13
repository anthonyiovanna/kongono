<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewInspection\Exception\InvalidViewInspectionException;
use Kongono\ViewInspection\Internal\CanonicalJson;

/** Carries only fixed tokens, Protected bindings, and bounded Safe aggregates. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewInspection
{
    public const string SCHEMA = 'kongono.view-inspection';
    public const int VERSION = 1;
    public const string SCOPE = 'exact-current-artifact-evidence';
    public const string DISCLOSURE = 'safe-and-protected-aggregates-only';
    public const string AUTHORITY = 'evidence-only';
    public const array LIMITATIONS = [
        'exact-current-is-caller-supplied-in-memory-evidence',
        'live-filesystem-currentness-not-established',
        'source-and-artifact-files-not-read-or-discovered',
        'generated-php-not-loaded-executed-reflected-or-invoked',
        'rendering-and-production-observation-not-performed',
        'graph-relationship-not-established',
        'authority-authentication-authorization-and-capabilities-not-granted',
        'recording-exporting-retention-and-certification-not-performed',
    ];

    private string $identity;
    /** @var array<string, string> */
    private array $protectedBindings;
    /** @var array<string, int> */
    private array $formatVersions;
    /** @var array<string, int> */
    private array $counts;
    /** @var array<string, bool> */
    private array $flags;

    /**
     * @param array<array-key, mixed> $protectedBindings
     * @param array<array-key, mixed> $formatVersions
     * @param array<array-key, mixed> $counts
     * @param array<array-key, mixed> $flags
     */
    public function __construct(array $protectedBindings, array $formatVersions, array $counts, array $flags)
    {
        $bindingKeys = ['profile_sha256', 'source_sha256', 'compiled_sha256', 'model_sha256', 'generated_sha256'];
        $versionKeys = ['source_format', 'artifact_format', 'contract_format', 'builder_format'];
        $countKeys = ['view_count', 'artifact_file_count'];
        $flagKeys = ['structurally_validated', 'supplied_current_evidence', 'generated_php_executed', 'rendering_performed', 'production_observed'];
        if ($bindingKeys !== array_keys($protectedBindings) || $versionKeys !== array_keys($formatVersions)
            || $countKeys !== array_keys($counts) || $flagKeys !== array_keys($flags)) {
            throw InvalidViewInspectionException::invalid('shape');
        }
        $acceptedBindings = [];
        foreach ($bindingKeys as $key) {
            $digest = $protectedBindings[$key];
            if (!is_string($digest) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $digest)) {
                throw InvalidViewInspectionException::invalid('digest');
            }
            $acceptedBindings[$key] = $digest;
        }
        $acceptedVersions = [];
        foreach ($versionKeys as $key) {
            $version = $formatVersions[$key];
            if (!is_int($version) || 1 !== $version) {
                throw InvalidViewInspectionException::invalid('format_version');
            }
            $acceptedVersions[$key] = $version;
        }
        if (!is_int($counts['view_count']) || $counts['view_count'] < 1 || $counts['view_count'] > 128
            || 2 !== $counts['artifact_file_count']) {
            throw InvalidViewInspectionException::invalid('aggregate');
        }
        $acceptedFlags = [];
        foreach ($flagKeys as $key) {
            if (!is_bool($flags[$key])) {
                throw InvalidViewInspectionException::invalid('flag');
            }
            $acceptedFlags[$key] = $flags[$key];
        }
        if (!$acceptedFlags['structurally_validated'] || !$acceptedFlags['supplied_current_evidence']
            || $acceptedFlags['generated_php_executed'] || $acceptedFlags['rendering_performed'] || $acceptedFlags['production_observed']) {
            throw InvalidViewInspectionException::invalid('flag');
        }
        $this->protectedBindings = $acceptedBindings;
        $this->formatVersions = $acceptedVersions;
        $this->counts = ['view_count' => $counts['view_count'], 'artifact_file_count' => $counts['artifact_file_count']];
        $this->flags = $acceptedFlags;
        $this->identity = hash('sha256', CanonicalJson::encode($this->identityPayload()));
    }

    public function identity(): string
    {
        return $this->identity;
    }

    /** @return array<string, string> */
    public function protectedBindings(): array
    {
        return $this->protectedBindings;
    }

    /** @return array<string, int> */
    public function formatVersions(): array
    {
        return $this->formatVersions;
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return $this->counts;
    }

    /** @return array<string, bool> */
    public function flags(): array
    {
        return $this->flags;
    }

    public function viewCount(): int
    {
        return $this->counts['view_count'];
    }

    public function artifactFileCount(): int
    {
        return $this->counts['artifact_file_count'];
    }

    public function structurallyValidated(): bool
    {
        return $this->flags['structurally_validated'];
    }

    public function suppliedCurrentEvidence(): bool
    {
        return $this->flags['supplied_current_evidence'];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => self::SCHEMA,
            'version' => self::VERSION,
            'scope' => self::SCOPE,
            'disclosure' => self::DISCLOSURE,
            'authority' => self::AUTHORITY,
            'inspection_identity' => $this->identity,
        ] + $this->evidencePayload();
    }

    public function canonicalBytes(): string
    {
        return CanonicalJson::encode($this->toArray());
    }

    /** @return array<string, mixed> */
    private function identityPayload(): array
    {
        return [
            'schema' => self::SCHEMA,
            'version' => self::VERSION,
            'scope' => self::SCOPE,
            'disclosure' => self::DISCLOSURE,
            'authority' => self::AUTHORITY,
        ] + $this->evidencePayload();
    }

    /** @return array<string, mixed> */
    private function evidencePayload(): array
    {
        return [
            'protected_bindings' => $this->protectedBindings,
            'format_versions' => $this->formatVersions,
            'counts' => $this->counts,
            'flags' => $this->flags,
            'limitations' => self::LIMITATIONS,
        ];
    }
}
