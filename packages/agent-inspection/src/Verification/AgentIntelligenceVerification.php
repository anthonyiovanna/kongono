<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Verification;

use Kongono\AgentInspection\Exception\InvalidAgentIntelligenceEvidenceException;
use Kongono\AgentInspection\Role\DeveloperIntelligenceRole;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries ordered fixed-token results from one independent comparison. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentIntelligenceVerification
{
    public const array CHECK_IDS = [
        'role-purpose',
        'inspection-identity-schema',
        'inspection-aggregate-evidence',
        'runtime-summary-evidence',
        'graph-evidence',
        'disclosure-currentness-authority-semantics',
        'role-execution-repair-hidden-access-semantics',
        'deterministic-serialization',
    ];

    /** @var array<string, bool> */
    private array $checks;
    /** @var list<string> */
    private array $failures;

    /**
     * @param array<array-key, mixed> $checks
     * @param array<array-key, mixed> $failures
     */
    public function __construct(
        private AgentIntelligenceVerdict $verdict,
        private DeveloperIntelligenceRole $role,
        private string $inspectionIdentity,
        array $checks,
        array $failures,
    ) {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $inspectionIdentity) || self::CHECK_IDS !== array_keys($checks)) {
            throw InvalidAgentIntelligenceEvidenceException::invalid('verification');
        }
        $accepted = [];
        $expectedFailures = [];
        foreach (self::CHECK_IDS as $id) {
            if (!is_bool($checks[$id])) {
                throw InvalidAgentIntelligenceEvidenceException::invalid('verification');
            }
            $accepted[$id] = $checks[$id];
            if (!$checks[$id]) {
                $expectedFailures[] = $id . '-failed';
            }
        }
        if ($expectedFailures !== $failures || ([] === $expectedFailures ? AgentIntelligenceVerdict::Pass : AgentIntelligenceVerdict::Fail) !== $verdict) {
            throw InvalidAgentIntelligenceEvidenceException::invalid('verification');
        }
        $this->checks = $accepted;
        $this->failures = [...$expectedFailures];
    }

    public function verdict(): AgentIntelligenceVerdict
    {
        return $this->verdict;
    }
    public function role(): DeveloperIntelligenceRole
    {
        return $this->role;
    }
    public function inspectionIdentity(): string
    {
        return $this->inspectionIdentity;
    }
    /** @return array<string, bool> */ public function checks(): array
    {
        return [...$this->checks];
    }
    /** @return list<string> */ public function failureTokens(): array
    {
        return [...$this->failures];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'kongono.agent-intelligence-verification',
            'version' => 1,
            'verdict' => $this->verdict->value,
            'role' => $this->role->value,
            'inspection_identity' => $this->inspectionIdentity,
            'checks' => [...$this->checks],
            'failures' => [...$this->failures],
        ];
    }
}
