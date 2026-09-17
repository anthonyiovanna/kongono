<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Inspection;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Artifact\CacheProfileArtifactValidator;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheInspection\Exception\CacheInspectionBindingException;
use Kongono\CacheInspection\RuntimeEvidence\DetachedCacheRuntimeEvidence;
use Throwable;

/** Independently reconstructs one supplied exact-current cache build in memory. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheInspector
{
    public function inspect(CacheInspectionRequest $request): CacheInspection
    {
        $current = $request->current();
        $suppliedProfiles = $current->profiles();
        $suppliedArtifact = $current->artifact();
        try {
            $declarations = [];
            foreach ($suppliedProfiles->profiles() as $profile) {
                $declarations[] = new CacheProfileDeclaration(
                    $profile->contractVersion(),
                    new CacheProfileName($profile->name()->value()),
                    new CacheNamespace($profile->namespace()->value()),
                    new CacheCodecIdentity($profile->codec()->name(), $profile->codec()->version()),
                    new CacheLifetime($profile->lifetime()->microseconds()),
                    new CacheProfileValueLimit($profile->valueLimit()->bytes()),
                );
            }
            $reconstructedProfiles = new CacheProfileSet($declarations);
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw CacheInspectionBindingException::mismatch('profile_set_reconstruction', $failure);
        }
        try {
            $reconstructedArtifact = (new CacheProfileArtifactValidator())->validateStructure($suppliedArtifact->bytes());
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw CacheInspectionBindingException::mismatch('artifact_structure', $failure);
        }
        if (!$reconstructedProfiles->equals($suppliedProfiles)
            || !$reconstructedProfiles->identity()->equals($suppliedProfiles->identity())
            || !$reconstructedArtifact->equals($suppliedArtifact)
            || !$reconstructedArtifact->profiles()->equals($reconstructedProfiles)
            || !$reconstructedArtifact->sourceIdentity()->equals($reconstructedProfiles->identity())
            || !$reconstructedArtifact->buildIdentity()->equals($suppliedArtifact->buildIdentity())
            || !$reconstructedArtifact->artifactIdentity()->equals($suppliedArtifact->artifactIdentity())
            || $reconstructedArtifact->profileCount() !== $reconstructedProfiles->profileCount()) {
            throw CacheInspectionBindingException::mismatch('current_build_binding');
        }

        $runtime = $request->runtimeEvidence();
        $bindings = [
            'profile_set_sha256' => $reconstructedProfiles->identity()->value(),
            'build_sha256' => $reconstructedArtifact->buildIdentity()->value(),
            'artifact_sha256' => $reconstructedArtifact->artifactIdentity()->value(),
        ];
        $outcomes = array_fill_keys(DetachedCacheRuntimeEvidence::OUTCOMES, 0);
        $status = CacheInspection::RUNTIME_NOT_SUPPLIED;
        $total = 0;
        if (null !== $runtime) {
            if ($bindings !== $runtime->bindings()) {
                throw CacheInspectionBindingException::mismatch('runtime_binding');
            }
            $bindings['detached_runtime_sha256'] = $runtime->identity();
            $outcomes = $runtime->outcomeCounts();
            $total = $runtime->total();
            $status = CacheInspection::RUNTIME_SUPPLIED;
        }

        return new CacheInspection(
            $status,
            $bindings,
            ['profile_count' => $reconstructedProfiles->profileCount(), ...$outcomes, 'outcome_total' => $total],
            [
                'structurally_validated' => true,
                'supplied_exact_current_evidence' => true,
                'detached_runtime_evidence_supplied' => null !== $runtime,
                'keys_omitted' => true,
                'values_omitted' => true,
                'filesystem_access' => false,
                'backend_access' => false,
                'runtime_observed' => false,
                'clock_read' => false,
                'mutation_performed' => false,
                'recording_performed' => false,
                'export_performed' => false,
                'production_observed' => false,
            ],
        );
    }
}
