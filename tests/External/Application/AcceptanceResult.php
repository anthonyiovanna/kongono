<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Application;

use RuntimeException;

/** Holds the deterministic successful Phase 5 external-acceptance envelope. */
final readonly class AcceptanceResult
{
    /** @var list<string> */
    public const array SCENARIOS = [
        'external-install',
        'build-first',
        'check-first',
        'run-home',
        'run-unmatched',
        'build-repeat-byte-identical',
        'check-repeat-write-free',
        'run-repeat-write-free',
        'missing-source-build-fails',
        'invalid-source-build-fails',
        'stale-source-check-fails',
        'stale-source-run-fails',
        'missing-artifact-check-fails',
        'missing-artifact-run-fails',
        'tampered-artifact-check-fails',
        'tampered-artifact-run-fails',
        'missing-wiring-check-fails',
        'missing-wiring-run-fails',
        'tampered-wiring-check-fails',
        'tampered-wiring-run-fails',
        'crossed-pair-fails',
        'renamed-output-no-scan-fails',
        'failed-build-no-partial-publication',
        'sequential-root-isolation',
        'operation-retirement',
        'offline-success',
        'nondisclosure',
        'owned-temporary-cleanup',
    ];

    public function __construct(
        private string $compositionArtifactSha256,
        private string $generatedWiringSha256,
        private string $skeletonSha256,
    ) {
        foreach ([$compositionArtifactSha256, $generatedWiringSha256, $skeletonSha256] as $digest) {
            if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $digest)) {
                throw new RuntimeException('acceptance_identity_invalid');
            }
        }
    }

    public function canonicalBytes(): string
    {
        $scenarios = [];
        foreach (self::SCENARIOS as $id) {
            $scenarios[] = ['id' => $id, 'status' => 'PASS'];
        }

        return json_encode([
            'schema' => 'kongono.phase5-external-acceptance',
            'version' => 1,
            'status' => 'PASS',
            'scenario_count' => 28,
            'passed' => 28,
            'failed' => 0,
            'scenarios' => $scenarios,
            'composition_artifact_sha256' => $this->compositionArtifactSha256,
            'generated_wiring_sha256' => $this->generatedWiringSha256,
            'skeleton_sha256' => $this->skeletonSha256,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR, 16) . "\n";
    }
}
