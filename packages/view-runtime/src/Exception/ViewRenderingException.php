<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewRuntime\Diagnostic\ViewRuntimeDiagnostic;
use Throwable;

/** Reports denied output contexts and generated rendering defects. */
#[Api(ApiStatus::Experimental)]
final class ViewRenderingException extends ViewRuntimeException
{
    public static function unsupportedContext(string $outputContext, ?int $operationIndex = null): self
    {
        $context = ['violation' => 'unsupported_output_context', 'output_context' => in_array($outputContext, ['html_text', 'html_quoted_attribute'], true) ? $outputContext : 'unsupported_output_context'];
        if (null !== $operationIndex) {
            $context['operation_index'] = max(0, $operationIndex);
        }

        return new self(ViewRuntimeDiagnostic::UnsupportedOutputContext, $context);
    }

    public static function failed(string $violation, ?int $operationIndex = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => in_array($violation, ['non_string_value', 'generated_throwable', 'protocol_violation', 'output_invalid', 'result_invalid'], true) ? $violation : 'unsupported_violation'];
        if (null !== $operationIndex) {
            $context['operation_index'] = max(0, $operationIndex);
        }

        return new self(ViewRuntimeDiagnostic::RenderingFailed, $context, $previous);
    }
}
