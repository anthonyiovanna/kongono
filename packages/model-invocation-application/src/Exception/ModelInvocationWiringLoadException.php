<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Diagnostic\ModelInvocationApplicationDiagnostic;
use Throwable;

/** Preserves trusted generated-PHP load failure without disclosing a path. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationWiringLoadException extends ModelInvocationApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['path', 'snapshot', 'validation', 'identity', 'collision', 'require', 'shape', 'start'];

        return new self(ModelInvocationApplicationDiagnostic::LoadFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'], $previous);
    }
}
