<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Internal;

use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifact;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Produces one closed length-framed profile-v1 identity input. */
#[Api(ApiStatus::Internal)]
final class CanonicalProfile
{
    public static function bytes(AgentDeclaration $declaration, AgentDeclarationArtifact $artifact, AgentAuthorityPlan $plan): string
    {
        $claims = [
            ['profile_version', '1'],
            ['artifact_sha256', $artifact->artifactSha256()],
            ['declaration_identity', $declaration->identity()->value()],
            ['declaration_sha256', $artifact->declarationSha256()],
            ['admission_identity', $plan->identity()->value()],
            ['principal_kind', $declaration->principal()->reference()->kind()->value],
            ['principal_id', $declaration->principal()->reference()->id()->value()],
            ['authentication_outcome', $plan->authenticationOutcome()->value],
            ['admission_decision', $plan->admissionDecision()->value],
        ];
        foreach ($declaration->capabilityRequests() as $position => $request) {
            $claims[] = ['capability.' . $position . '.identity', $request->identity()->value()];
            $claims[] = ['capability.' . $position . '.purpose', $request->purpose()->value()];
            $claims[] = ['capability.' . $position . '.requirement', $request->requirement()->value];
            $decision = $plan->capabilityDecisions()[$position];
            $claims[] = ['capability.' . $position . '.decision', $decision->status()->value];
        }
        foreach ($declaration->constraints() as $position => $constraint) {
            $claims[] = ['constraint.' . $position . '.kind', $constraint->kind()->value];
            $claims[] = ['constraint.' . $position . '.value', (string) $constraint->value()];
        }
        foreach ($declaration->evidenceReferences() as $position => $evidence) {
            $claims[] = ['evidence.' . $position . '.kind', $evidence->kind()->value];
            $claims[] = ['evidence.' . $position . '.identity', $evidence->identity()->value()];
            $claims[] = ['evidence.' . $position . '.sha256', $evidence->sha256()];
        }

        $bytes = '';
        foreach ($claims as [$name, $value]) {
            $bytes .= strlen($name) . ':' . $name . strlen($value) . ':' . $value;
        }

        return $bytes;
    }

    private function __construct() {}
}
