<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns closed Safe reasons for local adapter failures. */
#[Api(ApiStatus::Internal)]
enum LocalStorageAdapterDiagnostic: string
{
    case InvalidConfiguration = 'KGN-STORAGE-ADAPTER-LOCAL-2121';
    case InvalidRecord = 'KGN-STORAGE-ADAPTER-LOCAL-2122';
    case OperationFailure = 'KGN-STORAGE-ADAPTER-LOCAL-2123';
    case CleanupFailure = 'KGN-STORAGE-ADAPTER-LOCAL-2124';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema('Execute contained local storage operation', 'All paths and operation evidence remain contained and bounded', [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('operation', 'Operation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('count', 'Count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
        ]);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidConfiguration => 'Local storage adapter configuration is invalid',
            self::InvalidRecord => 'Local storage adapter record is invalid',
            self::OperationFailure => 'Local storage adapter operation failed',
            self::CleanupFailure => 'Local storage adapter cleanup failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidConfiguration => 'Supply one explicit absolute existing ordinary non-link directory',
            self::InvalidRecord => 'Reject the record without exposing or repairing its contents',
            self::OperationFailure => 'Treat the operation as unavailable or uncertain without retrying',
            self::CleanupFailure => 'Retire the operation and preserve cleanup failure truth',
        };
    }
}
