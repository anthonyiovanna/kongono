<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewInspection\Exception\InvalidViewIntelligenceEvidenceException;
use Kongono\ViewInspection\Role\ViewInspectionDeveloperRole;

/** Carries fixed ordered PASS or FAIL comparison evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewIntelligenceVerification
{
    public const array CHECK_IDS = [
        'role-purpose',
        'inspection-identity-schema',
        'protected-binding-evidence',
        'format-aggregate-and-flag-evidence',
        'ordered-statement-evidence',
        'ordered-limitation-evidence',
        'graph-evidence',
        'no-effect-markers',
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
        private ViewIntelligenceVerdict $verdict,
        private ViewInspectionDeveloperRole $role,
        private string $inspectionIdentity,
        array $checks,
        array $failures,
    ) {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $inspectionIdentity) || self::CHECK_IDS !== array_keys($checks)) {
            throw InvalidViewIntelligenceEvidenceException::invalid('verification');
        }
        $accepted = [];
        $expectedFailures = [];
        foreach (self::CHECK_IDS as $id) {
            if (!is_bool($checks[$id])) {
                throw InvalidViewIntelligenceEvidenceException::invalid('verification');
            }
            $accepted[$id] = $checks[$id];
            if (!$checks[$id]) {
                $expectedFailures[] = $id . '-failed';
            }
        }
        if ($expectedFailures !== $failures || ([] === $expectedFailures ? ViewIntelligenceVerdict::Pass : ViewIntelligenceVerdict::Fail) !== $verdict) {
            throw InvalidViewIntelligenceEvidenceException::invalid('verification');
        }
        $this->checks = $accepted;
        $this->failures = $expectedFailures;
    }

    public function verdict(): ViewIntelligenceVerdict
    {
        return $this->verdict;
    }

    public function role(): ViewInspectionDeveloperRole
    {
        return $this->role;
    }

    public function inspectionIdentity(): string
    {
        return $this->inspectionIdentity;
    }

    /** @return array<string, bool> */
    public function checks(): array
    {
        return $this->checks;
    }

    /** @return list<string> */
    public function failureTokens(): array
    {
        return $this->failures;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'kongono.view-intelligence-verification',
            'version' => 1,
            'verdict' => $this->verdict->value,
            'role' => $this->role->value,
            'inspection_identity' => $this->inspectionIdentity,
            'checks' => $this->checks,
            'failures' => $this->failures,
        ];
    }
}
