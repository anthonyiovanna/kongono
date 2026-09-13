<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewRuntime\Diagnostic\ViewRuntimeDiagnostic;

/** Reports a pre-addition render byte or step limit denial. */
#[Api(ApiStatus::Experimental)]
final class RenderBoundExceededException extends ViewRuntimeException
{
    public static function exceeded(string $violation, int $limit, int $observedCount, ?int $operationIndex = null): self
    {
        $context = [
            'violation' => in_array($violation, ['output_bytes', 'render_steps'], true) ? $violation : 'unsupported_violation',
            'limit' => max(0, $limit),
            'observed_count' => max(0, $observedCount),
        ];
        if (null !== $operationIndex) {
            $context['operation_index'] = max(0, $operationIndex);
        }

        return new self(ViewRuntimeDiagnostic::BoundExceeded, $context);
    }
}
