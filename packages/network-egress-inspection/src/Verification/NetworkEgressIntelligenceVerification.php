<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressInspection\Exception\InvalidNetworkEgressIntelligenceEvidenceException;
use Kongono\NetworkEgressInspection\Role\NetworkEgressInspectionDeveloperRole;

/** Carries fixed ordered PASS or FAIL comparison evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class NetworkEgressIntelligenceVerification
{
    public const array CHECK_IDS = [
        'role-purpose',
        'inspection-identity-schema',
        'protected-binding-evidence',
        'policy-aggregate-evidence',
        'runtime-summary-evidence',
        'graph-evidence',
        'disclosure-currentness-authority-semantics',
        'role-statements-and-nonexecution-semantics',
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
        private NetworkEgressIntelligenceVerdict $verdict,
        private NetworkEgressInspectionDeveloperRole $role,
        private string $inspectionIdentity,
        array $checks,
        array $failures,
    ) {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $inspectionIdentity) || self::CHECK_IDS !== array_keys($checks)) {
            throw InvalidNetworkEgressIntelligenceEvidenceException::invalid('verification');
        }
        $accepted = [];
        $expectedFailures = [];
        foreach (self::CHECK_IDS as $id) {
            if (!is_bool($checks[$id])) {
                throw InvalidNetworkEgressIntelligenceEvidenceException::invalid('verification');
            }
            $accepted[$id] = $checks[$id];
            if (!$checks[$id]) {
                $expectedFailures[] = $id . '-failed';
            }
        }
        if ($expectedFailures !== $failures || ([] === $expectedFailures ? NetworkEgressIntelligenceVerdict::Pass : NetworkEgressIntelligenceVerdict::Fail) !== $verdict) {
            throw InvalidNetworkEgressIntelligenceEvidenceException::invalid('verification');
        }
        $this->checks = $accepted;
        $this->failures = [...$expectedFailures];
    }

    public function verdict(): NetworkEgressIntelligenceVerdict
    {
        return $this->verdict;
    }
    public function role(): NetworkEgressInspectionDeveloperRole
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
            'schema' => 'kongono.network-egress-intelligence-verification',
            'version' => 1,
            'verdict' => $this->verdict->value,
            'role' => $this->role->value,
            'inspection_identity' => $this->inspectionIdentity,
            'checks' => [...$this->checks],
            'failures' => [...$this->failures],
        ];
    }
}
