<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetInspection\Exception\InvalidAssetInspectionException;
use Kongono\AssetInspection\Internal\CanonicalJson;

/** Carries only closed tokens, Protected digests, and bounded Safe aggregates. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetInspection
{
    public const string SCHEMA = 'kongono.asset-inspection';
    public const int VERSION = 1;
    public const string SCOPE = 'supplied-current-asset-evidence';
    public const string POSTURE = 'offline-read-only-stateless';
    public const string DISCLOSURE = 'safe-and-protected-aggregates-only';
    public const string AUTHORITY = 'evidence-only';
    public const array LIMITATIONS = [
        'currentness-is-supplied-in-memory-evidence',
        'live-filesystem-currentness-not-established',
        'deployment-serving-and-browser-behavior-not-established',
        'security-accessibility-and-performance-not-certified',
        'graph-relationship-not-established',
        'role-execution-repair-recording-and-export-not-performed',
        'production-observation-and-certification-not-performed',
    ];

    private string $identity;
    /** @var array<string, string> */
    private array $protectedBindings;
    /** @var array<string, int> */
    private array $counts;
    /** @var array<string, bool> */
    private array $flags;

    /**
     * @param array<array-key, mixed> $protectedBindings
     * @param array<array-key, mixed> $counts
     * @param array<array-key, mixed> $flags
     */
    public function __construct(
        private string $producerKind,
        array $protectedBindings,
        array $counts,
        array $flags,
    ) {
        if (!in_array($producerKind, ['native', 'adapter'], true)) {
            throw InvalidAssetInspectionException::invalid('token');
        }
        $bindingKeys = ['resolution_sha256', 'build_sha256', 'source_set_sha256', 'graph_sha256', 'content_set_sha256'];
        $countKeys = ['declaration_count', 'dependency_count', 'entry_point_count', 'output_count', 'resolution_entry_count', 'reachable_object_count', 'integrity_present_count', 'preload_present_count'];
        $flagKeys = ['structurally_validated', 'supplied_current_evidence', 'filesystem_read', 'build_performed', 'resolution_performed', 'production_observed'];
        if ($bindingKeys !== array_keys($protectedBindings) || $countKeys !== array_keys($counts) || $flagKeys !== array_keys($flags)) {
            throw InvalidAssetInspectionException::invalid('shape');
        }
        $acceptedBindings = [];
        foreach ($bindingKeys as $key) {
            $value = $protectedBindings[$key];
            if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
                throw InvalidAssetInspectionException::invalid('digest');
            }
            $acceptedBindings[$key] = $value;
        }
        $acceptedCounts = [];
        foreach ($countKeys as $key) {
            $value = $counts[$key];
            if (!is_int($value) || $value < 0 || $value > 4096) {
                throw InvalidAssetInspectionException::invalid('count');
            }
            $acceptedCounts[$key] = $value;
        }
        if ($acceptedCounts['resolution_entry_count'] !== $acceptedCounts['declaration_count']
            || $acceptedCounts['entry_point_count'] > $acceptedCounts['declaration_count']
            || $acceptedCounts['output_count'] !== $acceptedCounts['reachable_object_count']
            || $acceptedCounts['integrity_present_count'] !== $acceptedCounts['reachable_object_count']
            || $acceptedCounts['preload_present_count'] > $acceptedCounts['reachable_object_count']) {
            throw InvalidAssetInspectionException::invalid('count');
        }
        $acceptedFlags = [];
        foreach ($flagKeys as $key) {
            if (!is_bool($flags[$key])) {
                throw InvalidAssetInspectionException::invalid('flag');
            }
            $acceptedFlags[$key] = $flags[$key];
        }
        if (!$acceptedFlags['structurally_validated'] || !$acceptedFlags['supplied_current_evidence']
            || $acceptedFlags['filesystem_read'] || $acceptedFlags['build_performed']
            || $acceptedFlags['resolution_performed'] || $acceptedFlags['production_observed']) {
            throw InvalidAssetInspectionException::invalid('flag');
        }
        $this->protectedBindings = $acceptedBindings;
        $this->counts = $acceptedCounts;
        $this->flags = $acceptedFlags;
        $this->identity = hash('sha256', CanonicalJson::encode($this->identityPayload()));
    }

    public function identity(): string
    {
        return $this->identity;
    }

    public function producerKind(): string
    {
        return $this->producerKind;
    }

    /** @return array<string, string> */
    public function protectedBindings(): array
    {
        return [...$this->protectedBindings];
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return [...$this->counts];
    }

    /** @return array<string, bool> */
    public function flags(): array
    {
        return [...$this->flags];
    }

    public function count(string $key): int
    {
        return $this->counts[$key] ?? throw InvalidAssetInspectionException::invalid('count');
    }

    public function flag(string $key): bool
    {
        return $this->flags[$key] ?? throw InvalidAssetInspectionException::invalid('flag');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => self::SCHEMA,
            'version' => self::VERSION,
            'scope' => self::SCOPE,
            'producer_kind' => $this->producerKind,
            'posture' => self::POSTURE,
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
            'producer_kind' => $this->producerKind,
            'posture' => self::POSTURE,
            'disclosure' => self::DISCLOSURE,
            'authority' => self::AUTHORITY,
        ] + $this->evidencePayload();
    }

    /** @return array<string, mixed> */
    private function evidencePayload(): array
    {
        return [
            'protected_bindings' => $this->protectedBindings,
            'counts' => $this->counts,
            'flags' => $this->flags,
            'limitations' => self::LIMITATIONS,
        ];
    }
}
