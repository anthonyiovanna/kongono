<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterMemory\Diagnostic\MemoryCacheAdapterDiagnostic;
use Kongono\CacheAdapterMemory\Exception\InvalidMemoryCacheAdapterException;

/** Centralizes private memory-adapter invariants. */
#[Api(ApiStatus::Internal)]
final class MemoryAdapterGuard
{
    public static function capacity(int $entries, int $aggregateBytes, int $valueBytes): void
    {
        if ($entries < 1 || $entries > 4_096) {
            throw self::invalid('entry_count_range', $entries, 4_096);
        }
        if ($aggregateBytes < 1 || $aggregateBytes > 67_108_864) {
            throw self::invalid('aggregate_value_bytes_range', $aggregateBytes, 67_108_864);
        }
        if ($valueBytes < 1 || $valueBytes > 1_048_576) {
            throw self::invalid('single_value_bytes_range', $valueBytes, 1_048_576);
        }
        if ($valueBytes > $aggregateBytes) {
            throw self::invalid('single_exceeds_aggregate');
        }
    }

    public static function sequence(int $sequence): void
    {
        if ($sequence < 1 || PHP_INT_MAX === $sequence) {
            throw InvalidMemoryCacheAdapterException::forDiagnostic(MemoryCacheAdapterDiagnostic::SequenceExhausted, ['violation' => 'mutation_sequence_exhausted']);
        }
    }

    public static function impossibleState(): InvalidMemoryCacheAdapterException
    {
        return InvalidMemoryCacheAdapterException::forDiagnostic(MemoryCacheAdapterDiagnostic::ImpossibleState, ['violation' => 'candidate_state_inconsistent']);
    }

    private static function invalid(string $violation, ?int $count = null, ?int $limit = null): InvalidMemoryCacheAdapterException
    {
        $context = ['violation' => $violation];
        if (null !== $count) {
            $context['count'] = max(0, $count);
        }
        if (null !== $limit) {
            $context['limit'] = $limit;
        }

        return InvalidMemoryCacheAdapterException::forDiagnostic(MemoryCacheAdapterDiagnostic::InvalidCapacity, $context);
    }
}
