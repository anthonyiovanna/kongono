<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetInspection\Exception\AssetInspectionVerificationException;
use Kongono\AssetInspection\Role\AssetInspectionDeveloperRole;

/** Carries fixed ordered PASS or FAIL comparison evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetIntelligenceVerification
{
    public const array CHECK_IDS = [
        'request-artifact-reconstruction',
        'inspection-identity-and-evidence',
        'role-purpose',
        'ordered-statements-and-limitations',
        'graph-evidence',
        'no-effect-markers-and-serialization',
        'observation-projection',
        'security-audit-projection',
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
        private AssetIntelligenceVerdict $verdict,
        private AssetInspectionDeveloperRole $role,
        private string $inspectionIdentity,
        array $checks,
        array $failures,
    ) {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $inspectionIdentity) || self::CHECK_IDS !== array_keys($checks)) {
            throw AssetInspectionVerificationException::failed('verification');
        }
        $accepted = [];
        $expectedFailures = [];
        foreach (self::CHECK_IDS as $id) {
            if (!is_bool($checks[$id])) {
                throw AssetInspectionVerificationException::failed('verification');
            }
            $accepted[$id] = $checks[$id];
            if (!$checks[$id]) {
                $expectedFailures[] = $id . '-failed';
            }
        }
        if ($expectedFailures !== $failures || ([] === $expectedFailures ? AssetIntelligenceVerdict::Pass : AssetIntelligenceVerdict::Fail) !== $verdict) {
            throw AssetInspectionVerificationException::failed('verification');
        }
        $this->checks = $accepted;
        $this->failures = $expectedFailures;
    }

    public function verdict(): AssetIntelligenceVerdict
    {
        return $this->verdict;
    }

    public function role(): AssetInspectionDeveloperRole
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
        return [...$this->checks];
    }

    /** @return list<string> */
    public function failureTokens(): array
    {
        return [...$this->failures];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'kongono.asset-intelligence-verification',
            'version' => 1,
            'verdict' => $this->verdict->value,
            'role' => $this->role->value,
            'inspection_identity' => $this->inspectionIdentity,
            'checks' => $this->checks,
            'failures' => $this->failures,
        ];
    }
}
