<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for the memory adapter. */
#[Api(ApiStatus::Internal)]
enum MemoryCacheAdapterDiagnostic: string
{
    case InvalidCapacity = 'KGN-CACHE-ADAPTER-MEMORY-2021';
    case SequenceExhausted = 'KGN-CACHE-ADAPTER-MEMORY-2022';
    case ImpossibleState = 'KGN-CACHE-ADAPTER-MEMORY-2023';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
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
            self::InvalidCapacity => 'Memory cache adapter capacity is invalid',
            self::SequenceExhausted => 'Memory cache adapter sequence is exhausted',
            self::ImpossibleState => 'Memory cache adapter state is invalid',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidCapacity => 'Supply finite related capacity values within the documented bounds',
            self::SequenceExhausted => 'Retire this process-local adapter instance without treating the mutation as successful',
            self::ImpossibleState => 'Discard this adapter instance without exposing or repairing partial state',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidCapacity => 'Configure bounded memory cache adapter',
            self::SequenceExhausted => 'Create memory cache entry version',
            self::ImpossibleState => 'Replace memory cache adapter state',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidCapacity => 'Entry and exact opaque-value-byte capacities are positive, finite, bounded, and related',
            self::SequenceExhausted => 'Every successful store or replace consumes one positive instance-local sequence',
            self::ImpossibleState => 'Candidate state is complete and valid before one visible state replacement',
        };
    }
}
