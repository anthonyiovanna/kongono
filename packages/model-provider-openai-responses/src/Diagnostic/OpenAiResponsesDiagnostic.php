<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for the offline mapping boundary. */
#[Api(ApiStatus::Internal)]
enum OpenAiResponsesDiagnostic: string
{
    case InvalidProviderProfile = 'KGN-OPENAI-RESPONSES-1701';
    case UnsupportedMappingSurface = 'KGN-OPENAI-RESPONSES-1702';
    case RequestEncodingFailed = 'KGN-OPENAI-RESPONSES-1703';
    case RequestLimitExceeded = 'KGN-OPENAI-RESPONSES-1704';
    case ResponseSyntaxInvalid = 'KGN-OPENAI-RESPONSES-1705';
    case ResponseStructureInvalid = 'KGN-OPENAI-RESPONSES-1706';
    case ResponseLimitExceeded = 'KGN-OPENAI-RESPONSES-1707';
    case ResponseMappingUnsupported = 'KGN-OPENAI-RESPONSES-1708';
    case InvalidInvocationContext = 'KGN-OPENAI-RESPONSES-1709';
    case InvocationLifecycleViolation = 'KGN-OPENAI-RESPONSES-1710';
    case ExternalServiceOperationFailed = 'KGN-OPENAI-RESPONSES-1711';
    case HttpResponseRejected = 'KGN-OPENAI-RESPONSES-1712';
    case ResponseBodyFailed = 'KGN-OPENAI-RESPONSES-1713';
    case ResultNormalizationFailed = 'KGN-OPENAI-RESPONSES-1714';
    case StreamingResponseFramingRejected = 'KGN-OPENAI-RESPONSES-1715';
    case StreamingEventMappingRejected = 'KGN-OPENAI-RESPONSES-1716';
    case StreamingEventOrderRejected = 'KGN-OPENAI-RESPONSES-1717';
    case StreamingResponseCleanupFailed = 'KGN-OPENAI-RESPONSES-1718';
    case InvalidStrictJsonSchema = 'KGN-OPENAI-RESPONSES-1719';
    case InvalidApplicationProfile = 'KGN-OPENAI-RESPONSES-1720';
    case ApplicationWorkflowFailed = 'KGN-OPENAI-RESPONSES-1721';
    case ApplicationLifecycleViolation = 'KGN-OPENAI-RESPONSES-1722';
    case ApplicationOperationFailed = 'KGN-OPENAI-RESPONSES-1723';

    public function contextSchema(): DiagnosticContextSchema
    {
        $fields = match ($this) {
            self::InvalidProviderProfile,
            self::UnsupportedMappingSurface,
            self::RequestEncodingFailed,
            self::RequestLimitExceeded,
            self::ResponseSyntaxInvalid,
            self::ResponseStructureInvalid,
            self::ResponseLimitExceeded,
            self::ResponseMappingUnsupported => [
                new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('field', 'Field', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('status', 'Status', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('mapping_version', 'Mapping version', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            ],
            self::InvalidStrictJsonSchema => [
                new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('field', 'Field', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('mapping_version', 'Mapping version', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            ],
            self::InvalidApplicationProfile => [
                new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
            self::ApplicationWorkflowFailed,
            self::ApplicationOperationFailed => [
                new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
            self::ApplicationLifecycleViolation => [
                new DiagnosticFieldSchema('state', 'State', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
            self::InvalidInvocationContext,
            self::InvocationLifecycleViolation,
            self::ExternalServiceOperationFailed,
            self::ResultNormalizationFailed => [
                new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
            self::HttpResponseRejected => [
                new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('http_status', 'HTTP status', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            ],
            self::ResponseBodyFailed => [
                new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            ],
            self::StreamingResponseFramingRejected,
            self::StreamingEventMappingRejected,
            self::StreamingEventOrderRejected,
            self::StreamingResponseCleanupFailed => [
                new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('observed_count', 'Observed count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('event_kind', 'Event kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            ],
        };

        return new DiagnosticContextSchema($this->operation(), $this->invariant(), $fields);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidProviderProfile => 'OpenAI Responses provider profile is invalid',
            self::UnsupportedMappingSurface => 'OpenAI Responses mapping surface is unsupported',
            self::RequestEncodingFailed => 'OpenAI Responses request encoding failed',
            self::RequestLimitExceeded => 'OpenAI Responses request limit is exceeded',
            self::ResponseSyntaxInvalid => 'OpenAI Responses response syntax is invalid',
            self::ResponseStructureInvalid => 'OpenAI Responses response structure is invalid',
            self::ResponseLimitExceeded => 'OpenAI Responses response limit is exceeded',
            self::ResponseMappingUnsupported => 'OpenAI Responses response mapping is unsupported',
            self::InvalidInvocationContext => 'OpenAI Responses invocation context is invalid',
            self::InvocationLifecycleViolation => 'OpenAI Responses invocation lifecycle is invalid',
            self::ExternalServiceOperationFailed => 'OpenAI Responses external-service operation failed',
            self::HttpResponseRejected => 'OpenAI Responses HTTP response is rejected',
            self::ResponseBodyFailed => 'OpenAI Responses response body handling failed',
            self::ResultNormalizationFailed => 'OpenAI Responses result normalization failed',
            self::StreamingResponseFramingRejected => 'OpenAI Responses streaming response framing is rejected',
            self::StreamingEventMappingRejected => 'OpenAI Responses streaming event mapping is rejected',
            self::StreamingEventOrderRejected => 'OpenAI Responses streaming event order or completeness is rejected',
            self::StreamingResponseCleanupFailed => 'OpenAI Responses streaming response cleanup failed',
            self::InvalidStrictJsonSchema => 'OpenAI Responses strict JSON schema is invalid',
            self::InvalidApplicationProfile => 'OpenAI Responses application profile is invalid',
            self::ApplicationWorkflowFailed => 'OpenAI Responses application workflow failed',
            self::ApplicationLifecycleViolation => 'OpenAI Responses application lifecycle is invalid',
            self::ApplicationOperationFailed => 'OpenAI Responses application operation failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidProviderProfile => 'Supply the exact version-1 OpenAI text and usage profile',
            self::UnsupportedMappingSurface => 'Use only the non-streaming text request surface supported by mapping version 1',
            self::RequestEncodingFailed => 'Supply a valid bounded typed request that can be encoded deterministically',
            self::RequestLimitExceeded => 'Reduce the encoded request to the documented byte bound',
            self::ResponseSyntaxInvalid => 'Supply one complete valid UTF-8 JSON object without duplicate members',
            self::ResponseStructureInvalid => 'Supply all required response fields with exact bounded types and relations',
            self::ResponseLimitExceeded => 'Reduce the response body, output, list, or integer to the documented bound',
            self::ResponseMappingUnsupported => 'Use only the terminal single-message single-text response surface',
            self::InvalidInvocationContext => 'Supply the exact plan, profile, binding, and finite operation context',
            self::InvocationLifecycleViolation => 'Use one adapter open and one pull-driven session lifecycle only',
            self::ExternalServiceOperationFailed => 'Inspect the bounded external-service outcome without replaying the operation',
            self::HttpResponseRejected => 'Supply one successful JSON HTTP response with the required media type',
            self::ResponseBodyFailed => 'Supply one readable bounded response body whose stream can be closed',
            self::ResultNormalizationFailed => 'Supply decoded response evidence that maps to the neutral result contract',
            self::StreamingResponseFramingRejected => 'Supply one bounded well-formed SSE response using the documented framing profile',
            self::StreamingEventMappingRejected => 'Supply only the closed version-1 provider event shapes',
            self::StreamingEventOrderRejected => 'Supply the complete contiguous single-message provider event sequence',
            self::StreamingResponseCleanupFailed => 'Retire the failed operation and inspect the retained cleanup cause without replay',
            self::InvalidStrictJsonSchema => 'Supply one bounded duplicate-free schema using only the closed version-2 subset',
            self::InvalidApplicationProfile => 'Supply exact-current matching provider, invocation, and external-service profiles',
            self::ApplicationWorkflowFailed => 'Inspect the bounded workflow stage and correct the explicit current inputs',
            self::ApplicationLifecycleViolation => 'Use one Ready application for at most one terminal synchronous run',
            self::ApplicationOperationFailed => 'Inspect the retained lower failure without replaying the operation',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidProviderProfile => 'Create OpenAI Responses mapping profile',
            self::UnsupportedMappingSurface => 'Map OpenAI Responses request surface',
            self::RequestEncodingFailed => 'Encode OpenAI Responses request body',
            self::RequestLimitExceeded => 'Validate OpenAI Responses request bound',
            self::ResponseSyntaxInvalid => 'Parse OpenAI Responses response body',
            self::ResponseStructureInvalid => 'Validate OpenAI Responses response structure',
            self::ResponseLimitExceeded => 'Validate OpenAI Responses response bound',
            self::ResponseMappingUnsupported => 'Map OpenAI Responses response surface',
            self::InvalidInvocationContext => 'Validate OpenAI Responses invocation context',
            self::InvocationLifecycleViolation => 'Advance OpenAI Responses invocation lifecycle',
            self::ExternalServiceOperationFailed => 'Run OpenAI Responses external-service operation',
            self::HttpResponseRejected => 'Validate OpenAI Responses HTTP response',
            self::ResponseBodyFailed => 'Consume and close OpenAI Responses response body',
            self::ResultNormalizationFailed => 'Normalize OpenAI Responses result evidence',
            self::StreamingResponseFramingRejected => 'Parse OpenAI Responses streaming response framing',
            self::StreamingEventMappingRejected => 'Map OpenAI Responses streaming event',
            self::StreamingEventOrderRejected => 'Validate OpenAI Responses streaming sequence',
            self::StreamingResponseCleanupFailed => 'Close OpenAI Responses streaming response body',
            self::InvalidStrictJsonSchema => 'Validate OpenAI Responses strict JSON schema',
            self::InvalidApplicationProfile => 'Validate OpenAI Responses application profile',
            self::ApplicationWorkflowFailed => 'Build or check OpenAI Responses application wiring',
            self::ApplicationLifecycleViolation => 'Advance OpenAI Responses application lifecycle',
            self::ApplicationOperationFailed => 'Run OpenAI Responses application operation',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidProviderProfile => 'The provider, contract, model capabilities, mapping, and storage preference are exact',
            self::UnsupportedMappingSurface => 'Mapping version 1 accepts only its closed non-streaming text request shape',
            self::RequestEncodingFailed => 'Supported typed input must produce one deterministic compact JSON body',
            self::RequestLimitExceeded => 'Encoded request bytes must not exceed 524288',
            self::ResponseSyntaxInvalid => 'Response bytes must be valid UTF-8 JSON without duplicate object members',
            self::ResponseStructureInvalid => 'Required response members must preserve exact types, values, and relations',
            self::ResponseLimitExceeded => 'Every response byte, text, list, depth, and integer bound is fail-closed',
            self::ResponseMappingUnsupported => 'Only one terminal assistant message with one output text part is supported',
            self::InvalidInvocationContext => 'One exact plan, profile, HTTPS binding, and operation context must agree',
            self::InvocationLifecycleViolation => 'The adapter and session are single-use and retain no completed operation data',
            self::ExternalServiceOperationFailed => 'Exactly one terminal successful external-service operation is required',
            self::HttpResponseRejected => 'Only HTTP 200 with one application/json media type is accepted',
            self::ResponseBodyFailed => 'The body is read in bounded chunks and receives exactly one close attempt',
            self::ResultNormalizationFailed => 'Decoded provider claims map deterministically to one neutral result',
            self::StreamingResponseFramingRejected => 'The response uses one bounded supported SSE frame grammar',
            self::StreamingEventMappingRejected => 'Every provider event has one exact supported bounded version-1 shape',
            self::StreamingEventOrderRejected => 'Provider and neutral sequences are contiguous, complete, and snapshot-consistent',
            self::StreamingResponseCleanupFailed => 'Every owned response body receives exactly one close attempt',
            self::InvalidStrictJsonSchema => 'The schema is explicit, bounded, duplicate-free, closed, and deterministic',
            self::InvalidApplicationProfile => 'All exact-current provider, invocation, binding, and endpoint relations must agree',
            self::ApplicationWorkflowFailed => 'The accepted lower workflow and provider composition must remain exact-current',
            self::ApplicationLifecycleViolation => 'One application instance may perform at most one terminal operation',
            self::ApplicationOperationFailed => 'Lower failure identity and uncertainty remain available without replay',
        };
    }
}
