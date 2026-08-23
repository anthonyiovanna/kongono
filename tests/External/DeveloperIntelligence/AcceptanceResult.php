<?php

declare(strict_types=1);

namespace Kongono\Tests\External\DeveloperIntelligence;

use RuntimeException;

/** Holds the exact successful Phase 4 external-acceptance envelope. */
final readonly class AcceptanceResult
{
    /** @var list<string> */
    public const SCENARIOS = [
        'accepted-sources-readable',
        'knowledge-generation-a',
        'knowledge-generation-b-identical',
        'knowledge-current-validation',
        'cross-role-artifact-identity',
        'chie-current-explanation',
        'chie-current-diagnosis',
        'chie-candidate-proposal',
        'chie-candidate-reversal',
        'chie-candidate-unapplied',
        'oshie-current-catalog',
        'oshie-current-lesson',
        'oshie-current-exercise',
        'oshie-current-correct',
        'oshie-current-incorrect',
        'offline-knowledge-available',
        'offline-chie-available',
        'offline-oshie-available',
        'missing-source-visible',
        'stale-chie-visible',
        'stale-oshie-visible',
        'invalid-artifact-visible',
        'malicious-instructions-inert',
        'protected-context-not-disclosed',
        'hantei-current-pass',
        'hantei-synthetic-fail',
        'hantei-synthetic-blocked',
        'roles-do-not-mutate-or-elevate',
    ];

    public function __construct(
        private string $artifactSha256,
        private string $sourceSetSha256,
        private string $hanteiProfileSha256,
        private string $hanteiSubjectSha256,
    ) {
        foreach ([$artifactSha256, $sourceSetSha256, $hanteiProfileSha256, $hanteiSubjectSha256] as $digest) {
            if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $digest)) {
                throw new RuntimeException('acceptance_identity_invalid');
            }
        }
    }

    public function canonicalBytes(): string
    {
        return json_encode([
            'schema' => 'kongono.phase4-external-acceptance',
            'version' => 1,
            'status' => 'pass',
            'scenario_count' => 28,
            'scenarios' => self::SCENARIOS,
            'artifact_sha256' => $this->artifactSha256,
            'source_set_sha256' => $this->sourceSetSha256,
            'hantei_profile_sha256' => $this->hanteiProfileSha256,
            'hantei_subject_sha256' => $this->hanteiSubjectSha256,
            'candidate_status' => 'unapplied-unverified',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR, 16) . "\n";
    }
}
