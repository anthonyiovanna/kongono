<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Artifact;

use JsonException;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentDeclarationBuild\Exception\InvalidAgentDeclarationArtifactException;
use Kongono\AgentDeclarationBuild\Internal\CanonicalJson;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Builds canonical declaration evidence from one explicit typed value. */
#[Api(ApiStatus::Experimental)]
final class AgentDeclarationArtifactBuilder
{
    private const int MAX_ARTIFACT_BYTES = 131_072;

    public function build(AgentDeclaration $declaration): AgentDeclarationArtifact
    {
        try {
            $data = self::declarationData($declaration);
            $declarationSha256 = hash('sha256', CanonicalJson::encode($data));
            $artifactBytes = CanonicalJson::encode([
                'schema' => 'kongono.agent-declaration-artifact',
                'artifact_version' => 1,
                'contract_version' => 1,
                'builder' => [
                    'package' => 'kongono/agent-declaration-build',
                    'version' => 1,
                ],
                'declaration' => $data,
                'declaration_sha256' => $declarationSha256,
            ]);
        } catch (JsonException $failure) {
            throw InvalidAgentDeclarationArtifactException::invalid('encoding_failed', previous: $failure);
        }
        if (strlen($artifactBytes) > self::MAX_ARTIFACT_BYTES) {
            throw InvalidAgentDeclarationArtifactException::limit('artifact_bytes', strlen($artifactBytes));
        }

        return new AgentDeclarationArtifact(
            $artifactBytes,
            hash('sha256', $artifactBytes),
            $declaration->identity(),
            $declarationSha256,
        );
    }

    /** @return array<string, mixed> */
    private static function declarationData(AgentDeclaration $declaration): array
    {
        return [
            'declaration_id' => $declaration->identity()->value(),
            'principal' => [
                'kind' => 'agent',
                'id' => $declaration->principal()->reference()->id()->value(),
            ],
            'capability_requests' => array_map(self::capability(...), $declaration->capabilityRequests()),
            'constraints' => array_map(self::constraint(...), $declaration->constraints()),
            'evidence_references' => array_map(self::evidence(...), $declaration->evidenceReferences()),
            'provenance' => [
                'source' => $declaration->provenance()->source()->value,
                'source_identity' => $declaration->provenance()->sourceIdentity(),
                'sha256' => $declaration->provenance()->sha256(),
            ],
            'visibility' => $declaration->visibility()->value,
        ];
    }

    /** @return array{identity:string,purpose:string,requirement:string} */
    private static function capability(CapabilityRequest $request): array
    {
        return [
            'identity' => $request->identity()->value(),
            'purpose' => $request->purpose()->value(),
            'requirement' => $request->requirement()->value,
        ];
    }

    /** @return array{kind:string,value:int} */
    private static function constraint(AgentConstraint $constraint): array
    {
        return ['kind' => $constraint->kind()->value, 'value' => $constraint->value()];
    }

    /** @return array{kind:string,identity:string,sha256:string} */
    private static function evidence(EvidenceReference $reference): array
    {
        return [
            'kind' => $reference->kind()->value,
            'identity' => $reference->identity()->value(),
            'sha256' => $reference->sha256(),
        ];
    }
}
