<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Inspection;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Artifact\StorageProfileArtifactValidator;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageInspection\Exception\StorageInspectionBindingException;
use Kongono\StorageInspection\RuntimeEvidence\DetachedStorageRuntimeEvidence;
use Throwable;

/** Independently reconstructs one supplied exact-current storage build in memory. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageInspector
{
    public function inspect(StorageInspectionRequest $request): StorageInspection
    {
        $current = $request->current();
        $suppliedProfiles = $current->profiles();
        $suppliedArtifact = $current->artifact();
        try {
            $declarations = [];
            foreach ($suppliedProfiles->profiles() as $profile) {
                $declarations[] = new StorageProfileDeclaration(
                    $profile->contractVersion(),
                    new StorageProfileName($profile->name()->value()),
                    new StorageNamespace($profile->namespace()->value()),
                    new StorageCapabilitySet($profile->capabilities()->capabilities()),
                    $profile->digestAlgorithm(),
                    new StorageProfileLimits(
                        $profile->limits()->maxChunkBytes(),
                        $profile->limits()->maxObjectBytes(),
                        $profile->limits()->maxListPageSize(),
                    ),
                );
            }
            $reconstructedProfiles = new StorageProfileSet($declarations);
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw StorageInspectionBindingException::mismatch('profile_set_reconstruction', $failure);
        }
        try {
            $reconstructedArtifact = (new StorageProfileArtifactValidator())->validateStructure($suppliedArtifact->bytes());
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw StorageInspectionBindingException::mismatch('artifact_structure', $failure);
        }
        if (!$reconstructedProfiles->equals($suppliedProfiles)
            || !$reconstructedProfiles->identity()->equals($suppliedProfiles->identity())
            || !$reconstructedArtifact->equals($suppliedArtifact)
            || !$reconstructedArtifact->profiles()->equals($reconstructedProfiles)
            || !$reconstructedArtifact->sourceIdentity()->equals($reconstructedProfiles->identity())
            || !$reconstructedArtifact->buildIdentity()->equals($suppliedArtifact->buildIdentity())
            || !$reconstructedArtifact->artifactIdentity()->equals($suppliedArtifact->artifactIdentity())
            || $reconstructedArtifact->profileCount() !== $reconstructedProfiles->profileCount()) {
            throw StorageInspectionBindingException::mismatch('current_build_binding');
        }

        $runtime = $request->runtimeEvidence();
        $bindings = [
            'profile_set_sha256' => $reconstructedProfiles->identity()->value(),
            'build_sha256' => $reconstructedArtifact->buildIdentity()->value(),
            'artifact_sha256' => $reconstructedArtifact->artifactIdentity()->value(),
        ];
        $outcomes = array_fill_keys(DetachedStorageRuntimeEvidence::OUTCOMES, 0);
        $status = StorageInspection::RUNTIME_NOT_SUPPLIED;
        $total = 0;
        if (null !== $runtime) {
            if ($bindings !== $runtime->bindings()) {
                throw StorageInspectionBindingException::mismatch('runtime_binding');
            }
            $bindings['detached_runtime_sha256'] = $runtime->identity();
            $outcomes = $runtime->outcomeCounts();
            $total = $runtime->total();
            $status = StorageInspection::RUNTIME_SUPPLIED;
        }

        return new StorageInspection(
            $status,
            $bindings,
            ['profile_count' => $reconstructedProfiles->profileCount(), ...$outcomes, 'outcome_total' => $total],
            [
                'structurally_validated' => true,
                'supplied_exact_current_evidence' => true,
                'detached_runtime_evidence_supplied' => null !== $runtime,
                'object_identities_omitted' => true,
                'object_contents_omitted' => true,
                'filesystem_access' => false,
                'provider_access' => false,
                'adapter_access' => false,
                'credential_validation' => false,
                'authorization_execution' => false,
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
