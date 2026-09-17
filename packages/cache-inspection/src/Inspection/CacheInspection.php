<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheInspection\Exception\InvalidCacheInspectionException;
use Kongono\CacheInspection\Internal\CanonicalJson;
use Kongono\CacheInspection\RuntimeEvidence\DetachedCacheRuntimeEvidence;

/** Carries only closed tokens, Protected digests, and bounded Safe aggregates. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheInspection
{
    public const string SCHEMA = 'kongono.cache-inspection';
    public const int VERSION = 1;
    public const string SCOPE = 'supplied-current-cache-evidence';
    public const string POSTURE = 'offline-read-only-stateless';
    public const string DISCLOSURE = 'safe-and-protected-aggregates-only';
    public const string AUTHORITY = 'evidence-only';
    public const string RUNTIME_SUPPLIED = 'supplied';
    public const string RUNTIME_NOT_SUPPLIED = 'not-supplied';
    public const array ROLE_PURPOSES = ['chie' => 'explain', 'hantei' => 'verify', 'oshie' => 'teach'];
    public const array LIMITATIONS = [
        'currentness-is-supplied-in-memory-evidence',
        'live-backend-and-runtime-state-not-established',
        'keys-values-namespaces-profiles-codecs-and-providers-omitted',
        'freshness-time-and-individual-operations-omitted',
        'graph-relationship-not-established',
        'role-execution-repair-apply-recording-and-export-not-performed',
        'production-observation-and-certification-not-performed',
    ];
    public const array FLAG_KEYS = [
        'structurally_validated',
        'supplied_exact_current_evidence',
        'detached_runtime_evidence_supplied',
        'keys_omitted',
        'values_omitted',
        'filesystem_access',
        'backend_access',
        'runtime_observed',
        'clock_read',
        'mutation_performed',
        'recording_performed',
        'export_performed',
        'production_observed',
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
        private string $runtimeEvidenceStatus,
        array $protectedBindings,
        array $counts,
        array $flags,
        ?string $identity = null,
    ) {
        if (!in_array($runtimeEvidenceStatus, [self::RUNTIME_SUPPLIED, self::RUNTIME_NOT_SUPPLIED], true)) {
            throw InvalidCacheInspectionException::invalid('token');
        }
        $bindingKeys = ['profile_set_sha256', 'build_sha256', 'artifact_sha256'];
        if (self::RUNTIME_SUPPLIED === $runtimeEvidenceStatus) {
            $bindingKeys[] = 'detached_runtime_sha256';
        }
        if ($bindingKeys !== array_keys($protectedBindings)) {
            throw InvalidCacheInspectionException::invalid('shape');
        }
        $acceptedBindings = [];
        foreach ($bindingKeys as $key) {
            $value = $protectedBindings[$key];
            if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
                throw InvalidCacheInspectionException::invalid('digest');
            }
            $acceptedBindings[$key] = $value;
        }
        $countKeys = ['profile_count', ...DetachedCacheRuntimeEvidence::OUTCOMES, 'outcome_total'];
        if ($countKeys !== array_keys($counts)) {
            throw InvalidCacheInspectionException::invalid('shape');
        }
        $acceptedCounts = [];
        foreach ($countKeys as $key) {
            $value = $counts[$key];
            $limit = 'profile_count' === $key ? 256 : DetachedCacheRuntimeEvidence::MAX_OUTCOME_COUNT;
            if (!is_int($value) || $value < 0 || $value > $limit) {
                throw InvalidCacheInspectionException::invalid('count');
            }
            $acceptedCounts[$key] = $value;
        }
        $outcomes = array_intersect_key($acceptedCounts, array_fill_keys(DetachedCacheRuntimeEvidence::OUTCOMES, true));
        if ($acceptedCounts['outcome_total'] !== array_sum($outcomes)) {
            throw InvalidCacheInspectionException::invalid('total');
        }
        if (self::RUNTIME_NOT_SUPPLIED === $runtimeEvidenceStatus && 0 !== $acceptedCounts['outcome_total']) {
            throw InvalidCacheInspectionException::invalid('count');
        }
        if (self::FLAG_KEYS !== array_keys($flags)) {
            throw InvalidCacheInspectionException::invalid('shape');
        }
        $acceptedFlags = [];
        foreach (self::FLAG_KEYS as $key) {
            if (!is_bool($flags[$key])) {
                throw InvalidCacheInspectionException::invalid('flag');
            }
            $acceptedFlags[$key] = $flags[$key];
        }
        $supplied = self::RUNTIME_SUPPLIED === $runtimeEvidenceStatus;
        if (!$acceptedFlags['structurally_validated'] || !$acceptedFlags['supplied_exact_current_evidence']
            || $supplied !== $acceptedFlags['detached_runtime_evidence_supplied']
            || !$acceptedFlags['keys_omitted'] || !$acceptedFlags['values_omitted']
            || $acceptedFlags['filesystem_access'] || $acceptedFlags['backend_access']
            || $acceptedFlags['runtime_observed'] || $acceptedFlags['clock_read']
            || $acceptedFlags['mutation_performed'] || $acceptedFlags['recording_performed']
            || $acceptedFlags['export_performed'] || $acceptedFlags['production_observed']) {
            throw InvalidCacheInspectionException::invalid('flag');
        }
        $this->protectedBindings = $acceptedBindings;
        $this->counts = $acceptedCounts;
        $this->flags = $acceptedFlags;
        $expected = hash('sha256', CanonicalJson::encode($this->identityPayload()));
        if (null !== $identity && (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $identity) || !hash_equals($expected, $identity))) {
            throw InvalidCacheInspectionException::invalid('digest');
        }
        $this->identity = $identity ?? $expected;
    }

    public function identity(): string
    {
        return $this->identity;
    }

    public function runtimeEvidenceStatus(): string
    {
        return $this->runtimeEvidenceStatus;
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
        return $this->counts[$key] ?? throw InvalidCacheInspectionException::invalid('count');
    }

    public function flag(string $key): bool
    {
        return $this->flags[$key] ?? throw InvalidCacheInspectionException::invalid('flag');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => self::SCHEMA,
            'version' => self::VERSION,
            'scope' => self::SCOPE,
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
            'posture' => self::POSTURE,
            'disclosure' => self::DISCLOSURE,
            'authority' => self::AUTHORITY,
        ] + $this->evidencePayload();
    }

    /** @return array<string, mixed> */
    private function evidencePayload(): array
    {
        return [
            'runtime_evidence_status' => $this->runtimeEvidenceStatus,
            'protected_bindings' => $this->protectedBindings,
            'counts' => $this->counts,
            'flags' => $this->flags,
            'role_purposes' => self::ROLE_PURPOSES,
            'limitations' => self::LIMITATIONS,
        ];
    }
}
