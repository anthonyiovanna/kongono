<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageInspection\Exception\StorageInspectionVerificationException;
use Kongono\StorageInspection\Role\StorageInspectionDeveloperRole;

/** Carries fixed ordered PASS or FAIL comparison evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageIntelligenceVerification
{
    public const array CHECK_IDS = [
        'request-profile-artifact-reconstruction',
        'inspection-identity-and-evidence',
        'runtime-status-counts-and-bindings',
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
        private StorageIntelligenceVerdict $verdict,
        private StorageInspectionDeveloperRole $role,
        private string $inspectionIdentity,
        array $checks,
        array $failures,
    ) {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $inspectionIdentity) || self::CHECK_IDS !== array_keys($checks)) {
            throw StorageInspectionVerificationException::failed('verification');
        }
        $accepted = [];
        $expectedFailures = [];
        foreach (self::CHECK_IDS as $id) {
            if (!is_bool($checks[$id])) {
                throw StorageInspectionVerificationException::failed('verification');
            }
            $accepted[$id] = $checks[$id];
            if (!$checks[$id]) {
                $expectedFailures[] = $id . '-failed';
            }
        }
        if ($expectedFailures !== $failures || ([] === $expectedFailures ? StorageIntelligenceVerdict::Pass : StorageIntelligenceVerdict::Fail) !== $verdict) {
            throw StorageInspectionVerificationException::failed('verification');
        }
        $this->checks = $accepted;
        $this->failures = $expectedFailures;
    }

    public function verdict(): StorageIntelligenceVerdict
    {
        return $this->verdict;
    }

    public function role(): StorageInspectionDeveloperRole
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
            'schema' => 'kongono.storage-intelligence-verification',
            'version' => 1,
            'verdict' => $this->verdict->value,
            'role' => $this->role->value,
            'inspection_identity' => $this->inspectionIdentity,
            'checks' => $this->checks,
            'failures' => $this->failures,
        ];
    }
}
