<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Internal;

use JsonException;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationAgentBridge\Exception\InvalidModelInvocationAgentBridgeException;
use Kongono\ModelInvocationAgentBridge\Exception\ProposalBridgeRejectedException;
use Kongono\ModelInvocationAgentBridge\Mapping\ActionProposalMapping;
use Kongono\ModelInvocationContract\Result\ModelResult;

/** Builds the fixed canonical evidence projection without exposing action arguments. */
#[Api(ApiStatus::Internal)]
final class CanonicalEvidence
{
    public static function bytes(
        ModelResult $result,
        ActionProposalMapping $mapping,
        int $tokenUsage,
        int $outputByteUsage,
        AgentVisibility $visibility,
    ): string {
        $suggestion = $result->actionSuggestion();
        if (null === $suggestion) {
            throw InvalidModelInvocationAgentBridgeException::invalid('action_suggestion_required', 'evidence');
        }

        $projection = [
            'schema' => 'kongono.model-agent-proposal-evidence',
            'version' => 1,
            'model_result_identity' => $result->identity()->value(),
            'invocation_identity' => $result->invocationIdentity()->value(),
            'model_profile_identity' => $result->profile()->identity()->value(),
            'action_name' => $suggestion->name()->value(),
            'action_arguments_sha256' => hash('sha256', $suggestion->arguments()->value()),
            'mapped_capability_identity' => $mapping->capabilityRequest()->identity()->value(),
            'usage_metric' => $mapping->usageSelector()->metric()->value(),
            'usage_unit' => $mapping->usageSelector()->unit()->value(),
            'projected_token_usage' => $tokenUsage,
            'derived_output_byte_usage' => $outputByteUsage,
            'projected_visibility' => $visibility->value,
        ];

        try {
            return json_encode($projection, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw ProposalBridgeRejectedException::projection('canonical_json_encoding', 'evidence', 0, $exception);
        }
    }

    public static function digest(
        ModelResult $result,
        ActionProposalMapping $mapping,
        int $tokenUsage,
        int $outputByteUsage,
        AgentVisibility $visibility,
    ): string {
        return hash('sha256', self::bytes($result, $mapping, $tokenUsage, $outputByteUsage, $visibility));
    }

    private function __construct() {}
}
