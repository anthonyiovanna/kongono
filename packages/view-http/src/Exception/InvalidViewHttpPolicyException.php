<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewHttp\Diagnostic\ViewHttpDiagnostic;

/** Reports invalid policy or completed-result preconditions. */
#[Api(ApiStatus::Experimental)]
final class InvalidViewHttpPolicyException extends ViewHttpException
{
    private const array POLICY_VIOLATIONS = ['status_code_range', 'reason_phrase_length', 'reason_phrase_bytes', 'headers_not_list', 'header_entry_type', 'header_count', 'header_name_length', 'header_name_grammar', 'header_name_reserved', 'header_values_not_list', 'header_value_count', 'header_value_type', 'header_value_length', 'header_value_bytes', 'header_name_duplicate', 'header_bytes'];

    public static function policy(string $violation, ?int $limit = null, ?int $observedCount = null, ?int $statusCode = null, ?int $headerCount = null): self
    {
        $context = ['violation' => in_array($violation, self::POLICY_VIOLATIONS, true) ? $violation : 'unsupported_violation'];
        foreach (['limit' => $limit, 'observed_count' => $observedCount, 'status_code' => $statusCode, 'header_count' => $headerCount] as $key => $value) {
            if (null !== $value) {
                $context[$key] = max(0, $value);
            }
        }

        return new self(ViewHttpDiagnostic::InvalidPolicy, $context);
    }

    public static function renderResult(string $violation, ?int $outputByteCount = null): self
    {
        $context = ['violation' => in_array($violation, ['operation_state', 'output_byte_count', 'output_sha256'], true) ? $violation : 'unsupported_violation'];
        if (null !== $outputByteCount) {
            $context['output_byte_count'] = max(0, $outputByteCount);
        }

        return new self(ViewHttpDiagnostic::InvalidRenderResult, $context);
    }
}
