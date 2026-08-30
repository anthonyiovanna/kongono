<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphIntelligence\Exception\InvalidApplicationGraphIntelligenceValueException;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole;

/** Carries deterministic bounded evidence from one independent comparison. */
#[Api(ApiStatus::Experimental)]
final readonly class ApplicationGraphIntelligenceVerification
{
    /** @var list<string> */
    public const array CHECK_IDS = [
        'identity-match',
        'contract-match',
        'totals-match',
        'node-vocabulary-match',
        'relation-vocabulary-match',
        'visibility-semantics-match',
    ];

    /** @var array<string, bool> */
    private array $checkOutcomes;

    /** @var list<string> */
    private array $failureTokens;

    /**
     * @param array<array-key, mixed> $checkOutcomes
     * @param array<array-key, mixed> $failureTokens
     */
    public function __construct(
        private ApplicationGraphIntelligenceVerdict $verdict,
        private DeveloperIntelligenceRole $role,
        private string $artifactSha256,
        private GraphIdentity $graphIdentity,
        array $checkOutcomes,
        array $failureTokens,
    ) {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $artifactSha256)) {
            throw InvalidApplicationGraphIntelligenceValueException::verification('artifact_digest');
        }
        if (self::CHECK_IDS !== array_keys($checkOutcomes)) {
            throw InvalidApplicationGraphIntelligenceValueException::verification('check_order');
        }

        $normalized = [];
        foreach (self::CHECK_IDS as $checkId) {
            $outcome = $checkOutcomes[$checkId];
            if (!is_bool($outcome)) {
                throw InvalidApplicationGraphIntelligenceValueException::verification('check_value');
            }
            $normalized[$checkId] = $outcome;
        }

        $expectedFailures = [];
        foreach ($normalized as $checkId => $outcome) {
            if (!$outcome) {
                $expectedFailures[] = $checkId . '-failed';
            }
        }
        if ($expectedFailures !== $failureTokens) {
            throw InvalidApplicationGraphIntelligenceValueException::verification('failure_order');
        }
        $expectedVerdict = [] === $expectedFailures
            ? ApplicationGraphIntelligenceVerdict::Pass
            : ApplicationGraphIntelligenceVerdict::Fail;
        if ($expectedVerdict !== $verdict) {
            throw InvalidApplicationGraphIntelligenceValueException::verification('verdict');
        }

        $this->checkOutcomes = $normalized;
        $this->failureTokens = [...$expectedFailures];
    }

    public function verdict(): ApplicationGraphIntelligenceVerdict
    {
        return $this->verdict;
    }

    public function role(): DeveloperIntelligenceRole
    {
        return $this->role;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function graphIdentity(): GraphIdentity
    {
        return $this->graphIdentity;
    }

    /** @return array<string, bool> */
    public function checkOutcomes(): array
    {
        return [...$this->checkOutcomes];
    }

    /** @return list<string> */
    public function failureTokens(): array
    {
        return [...$this->failureTokens];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'kongono.application-graph-intelligence-verification',
            'version' => 1,
            'verdict' => $this->verdict->value,
            'role' => $this->role->value,
            'artifact_sha256' => $this->artifactSha256,
            'graph_identity' => $this->graphIdentity->value(),
            'checks' => [...$this->checkOutcomes],
            'failures' => [...$this->failureTokens],
        ];
    }
}
