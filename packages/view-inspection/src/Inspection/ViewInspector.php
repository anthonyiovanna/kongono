<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Inspection;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Artifact\CompiledViewArtifactValidator;
use Kongono\ViewInspection\Exception\ViewArtifactEvidenceException;
use Kongono\ViewInspection\Exception\ViewInspectionBindingException;
use Throwable;

/** Revalidates one supplied in-memory artifact and its exact profile bindings. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewInspector
{
    public function inspect(ViewInspectionRequest $request): ViewInspection
    {
        $profile = $request->profile();
        $current = $profile->currentBuild();
        $suppliedArtifact = $current->artifact();
        try {
            $validatedArtifact = (new CompiledViewArtifactValidator())->validateStructure(
                $suppliedArtifact->metadataBytes(),
                $suppliedArtifact->generatedPhpBytes(),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw ViewArtifactEvidenceException::mismatch('artifact_structure', $failure);
        }
        if (!$validatedArtifact->equals($suppliedArtifact)
            || !hash_equals($validatedArtifact->metadataBytes(), $suppliedArtifact->metadataBytes())
            || !hash_equals($validatedArtifact->generatedPhpBytes(), $suppliedArtifact->generatedPhpBytes())) {
            throw ViewArtifactEvidenceException::mismatch('artifact_comparison');
        }
        if (!$current->sourceIdentity()->equals($validatedArtifact->sourceIdentity())
            || !$current->compiledViewIdentity()->equals($validatedArtifact->compiledViewIdentity())
            || !hash_equals($current->modelSha256(), $validatedArtifact->modelSha256())
            || !hash_equals($current->generatedPhpSha256(), $validatedArtifact->generatedPhpSha256())
            || $current->viewCount() !== $validatedArtifact->viewCount()) {
            throw ViewInspectionBindingException::mismatch('current_build_binding');
        }
        $expectedProfileIdentity = hash('sha256', implode("\n", [
            'kongono.view-application-profile',
            '1',
            $validatedArtifact->sourceIdentity()->value(),
            $validatedArtifact->compiledViewIdentity()->value(),
            $validatedArtifact->modelSha256(),
            $validatedArtifact->generatedPhpSha256(),
            (string) $validatedArtifact->viewCount(),
        ]));
        if (!hash_equals($expectedProfileIdentity, $profile->identity()->value())) {
            throw ViewInspectionBindingException::mismatch('profile_identity');
        }

        return new ViewInspection(
            [
                'profile_sha256' => $profile->identity()->value(),
                'source_sha256' => $validatedArtifact->sourceIdentity()->value(),
                'compiled_sha256' => $validatedArtifact->compiledViewIdentity()->value(),
                'model_sha256' => $validatedArtifact->modelSha256(),
                'generated_sha256' => $validatedArtifact->generatedPhpSha256(),
            ],
            [
                'source_format' => 1,
                'artifact_format' => 1,
                'contract_format' => 1,
                'builder_format' => 1,
            ],
            [
                'view_count' => $validatedArtifact->viewCount(),
                'artifact_file_count' => 2,
            ],
            [
                'structurally_validated' => true,
                'supplied_current_evidence' => true,
                'generated_php_executed' => false,
                'rendering_performed' => false,
                'production_observed' => false,
            ],
        );
    }
}
