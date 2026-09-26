<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageRuntime\Diagnostic\StorageRuntimeDiagnostic;
use Throwable;

/** Reports an invalid binding, operation, stream relation, or adapter result. */
#[Api(ApiStatus::Experimental)]
final class InvalidStorageRuntimeException extends StorageRuntimeException
{
    /** @param array<string, string|int|bool> $context */
    public static function binding(string $violation, ?Throwable $previous = null, array $context = []): self
    {
        return new self(StorageRuntimeDiagnostic::InvalidBinding, ['violation' => self::safe($violation)] + $context, $previous);
    }

    /** @param array<string, string|int|bool> $context */
    public static function operation(string $violation, string $operation, ?Throwable $previous = null, array $context = []): self
    {
        return new self(StorageRuntimeDiagnostic::InvalidOperation, ['violation' => self::safe($violation), 'operation' => self::op($operation)] + $context, $previous);
    }

    /** @param array<string, string|int|bool> $context */
    public static function evidence(string $violation, string $operation, ?string $outcome = null, ?Throwable $previous = null, array $context = []): self
    {
        $values = ['violation' => self::safe($violation), 'operation' => self::op($operation)] + $context;
        if (null !== $outcome) {
            $values['outcome'] = in_array($outcome, ['completed', 'absent', 'already_exists', 'conflict', 'rejected', 'unsupported', 'unavailable', 'uncertain'], true) ? $outcome : 'unsupported_outcome';
        }

        return new self(StorageRuntimeDiagnostic::InvalidAdapterEvidence, $values, $previous);
    }

    private static function safe(string $value): string
    {
        return 1 === preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $value) ? $value : 'unsupported_violation';
    }

    private static function op(string $operation): string
    {
        return in_array($operation, ['read', 'create', 'replace', 'delete', 'list'], true) ? $operation : 'unsupported_operation';
    }
}
