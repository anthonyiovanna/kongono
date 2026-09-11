<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponsesInspection\Exception\InvalidOpenAiResponsesIntelligenceEvidenceException;
use Kongono\ModelProviderOpenAiResponsesInspection\Role\OpenAiResponsesInspectionDeveloperRole;

/** Carries fixed ordered PASS or FAIL comparison evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesIntelligenceVerification
{
    public const array CHECK_IDS = [
        'role-purpose',
        'inspection-identity-schema',
        'protected-binding-evidence',
        'request-aggregate-evidence',
        'runtime-summary-evidence',
        'graph-evidence',
        'disclosure-currentness-authority-semantics',
        'role-execution-repair-hidden-access-provider-semantics',
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
        private OpenAiResponsesIntelligenceVerdict $verdict,
        private OpenAiResponsesInspectionDeveloperRole $role,
        private string $inspectionIdentity,
        array $checks,
        array $failures,
    ) {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $inspectionIdentity) || self::CHECK_IDS !== array_keys($checks)) {
            throw InvalidOpenAiResponsesIntelligenceEvidenceException::invalid('verification');
        }
        $accepted = [];
        $expectedFailures = [];
        foreach (self::CHECK_IDS as $id) {
            if (!is_bool($checks[$id])) {
                throw InvalidOpenAiResponsesIntelligenceEvidenceException::invalid('verification');
            }
            $accepted[$id] = $checks[$id];
            if (!$checks[$id]) {
                $expectedFailures[] = $id . '-failed';
            }
        }
        if ($expectedFailures !== $failures || ([] === $expectedFailures ? OpenAiResponsesIntelligenceVerdict::Pass : OpenAiResponsesIntelligenceVerdict::Fail) !== $verdict) {
            throw InvalidOpenAiResponsesIntelligenceEvidenceException::invalid('verification');
        }
        $this->checks = $accepted;
        $this->failures = [...$expectedFailures];
    }

    public function verdict(): OpenAiResponsesIntelligenceVerdict
    {
        return $this->verdict;
    }
    public function role(): OpenAiResponsesInspectionDeveloperRole
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
            'schema' => 'kongono.openai-responses-intelligence-verification',
            'version' => 1,
            'verdict' => $this->verdict->value,
            'role' => $this->role->value,
            'inspection_identity' => $this->inspectionIdentity,
            'checks' => [...$this->checks],
            'failures' => [...$this->failures],
        ];
    }
}
