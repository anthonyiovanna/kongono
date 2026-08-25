<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;
use LogicException;
use Throwable;

/** Retains one exact primary failure and ordered detached stream-cleanup failures. */
#[Api(ApiStatus::Public)]
final class RepositoryCleanupException extends MappingException
{
    /** @var list<Throwable> */
    private readonly array $cleanupFailures;

    /** @param array<array-key, mixed> $cleanupFailures */
    private function __construct(
        private readonly ?Throwable $primaryFailure,
        array $cleanupFailures,
        string $operationKind,
    ) {
        if (!array_is_list($cleanupFailures) || [] === $cleanupFailures) {
            throw new LogicException('Repository cleanup evidence is invalid.');
        }
        $detached = [];
        foreach ($cleanupFailures as $failure) {
            if (!$failure instanceof Throwable) {
                throw new LogicException('Repository cleanup evidence is invalid.');
            }
            $detached[] = $failure;
        }
        $this->cleanupFailures = $detached;
        parent::__construct(
            MappingDiagnostic::RepositoryCleanupFailed,
            MappingFailureCategory::Cleanup,
            count: count($detached),
            artifactCurrent: true,
            operationKind: $operationKind,
            cleanupEvidence: true,
            previous: $primaryFailure ?? $detached[0],
        );
    }

    /** @param array<array-key, mixed> $cleanupFailures */
    public static function forFind(?Throwable $primaryFailure, array $cleanupFailures): self
    {
        return new self($primaryFailure, $cleanupFailures, 'find');
    }

    public function primaryFailure(): ?Throwable
    {
        return $this->primaryFailure;
    }

    /** @return list<Throwable> */
    public function cleanupFailures(): array
    {
        return [...$this->cleanupFailures];
    }
}
