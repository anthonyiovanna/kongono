<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\RuntimeEvidence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageInspection\Exception\InvalidStorageIntelligenceEvidenceException;
use Kongono\StorageInspection\Internal\CanonicalJson;

/** Carries caller-supplied detached aggregate counts without observing a runtime. */
#[Api(ApiStatus::Experimental)]
final readonly class DetachedStorageRuntimeEvidence
{
    public const int MAX_OUTCOME_COUNT = 1_000_000;
    public const array OUTCOMES = [
        'read_completed',
        'read_absent',
        'read_rejected',
        'read_unsupported',
        'read_unavailable',
        'mutation_completed',
        'mutation_absent',
        'mutation_already_exists',
        'mutation_conflict',
        'mutation_rejected',
        'mutation_unsupported',
        'mutation_unavailable',
        'mutation_uncertain',
        'list_completed',
        'list_rejected',
        'list_unsupported',
        'list_unavailable',
    ];
    public const array FLAGS = [
        'caller_supplied' => true,
        'detached' => true,
        'object_identities_omitted' => true,
        'object_contents_omitted' => true,
        'live_provider_state_established' => false,
        'runtime_observed' => false,
    ];

    private string $identity;
    /** @var array<string, string> */
    private array $bindings;
    /** @var array<string, int> */
    private array $outcomeCounts;
    /** @var array<string, bool> */
    private array $flags;
    private int $total;

    /**
     * @param array<array-key, mixed> $bindings
     * @param array<array-key, mixed> $outcomeCounts
     * @param array<array-key, mixed> $flags
     */
    public function __construct(
        array $bindings,
        array $outcomeCounts,
        array $flags = self::FLAGS,
        ?int $total = null,
        ?string $identity = null,
    ) {
        $bindingKeys = ['profile_set_sha256', 'build_sha256', 'artifact_sha256'];
        if ($bindingKeys !== array_keys($bindings)) {
            throw InvalidStorageIntelligenceEvidenceException::invalid('runtime_evidence');
        }
        $acceptedBindings = [];
        foreach ($bindingKeys as $key) {
            $value = $bindings[$key];
            if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
                throw InvalidStorageIntelligenceEvidenceException::invalid('runtime_evidence');
            }
            $acceptedBindings[$key] = $value;
        }
        if (self::OUTCOMES !== array_keys($outcomeCounts)) {
            throw InvalidStorageIntelligenceEvidenceException::invalid('runtime_evidence');
        }
        $acceptedCounts = [];
        foreach (self::OUTCOMES as $outcome) {
            $value = $outcomeCounts[$outcome];
            if (!is_int($value) || $value < 0 || $value > self::MAX_OUTCOME_COUNT) {
                throw InvalidStorageIntelligenceEvidenceException::invalid('runtime_evidence');
            }
            $acceptedCounts[$outcome] = $value;
        }
        $observedTotal = array_sum($acceptedCounts);
        if ($observedTotal > self::MAX_OUTCOME_COUNT || (null !== $total && $total !== $observedTotal)) {
            throw InvalidStorageIntelligenceEvidenceException::invalid('runtime_evidence');
        }
        if (self::FLAGS !== $flags) {
            throw InvalidStorageIntelligenceEvidenceException::invalid('runtime_evidence');
        }
        $this->bindings = $acceptedBindings;
        $this->outcomeCounts = $acceptedCounts;
        $this->flags = self::FLAGS;
        $this->total = $observedTotal;
        $expected = hash('sha256', CanonicalJson::encode($this->identityPayload()));
        if (null !== $identity && (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $identity) || !hash_equals($expected, $identity))) {
            throw InvalidStorageIntelligenceEvidenceException::invalid('runtime_evidence');
        }
        $this->identity = $identity ?? $expected;
    }

    public function identity(): string
    {
        return $this->identity;
    }

    /** @return array<string, string> */
    public function bindings(): array
    {
        return [...$this->bindings];
    }

    /** @return array<string, int> */
    public function outcomeCounts(): array
    {
        return [...$this->outcomeCounts];
    }

    /** @return array<string, bool> */
    public function flags(): array
    {
        return [...$this->flags];
    }

    public function total(): int
    {
        return $this->total;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->identityPayload() + ['runtime_evidence_identity' => $this->identity];
    }

    public function canonicalBytes(): string
    {
        return CanonicalJson::encode($this->toArray());
    }

    /** @return array<string, mixed> */
    private function identityPayload(): array
    {
        return [
            'schema' => 'kongono.detached-storage-runtime-evidence',
            'version' => 1,
            'bindings' => $this->bindings,
            'outcome_counts' => $this->outcomeCounts,
            'outcome_total' => $this->total,
            'flags' => $this->flags,
        ];
    }
}
