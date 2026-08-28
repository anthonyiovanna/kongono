<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ObservabilityApplication\Diagnostic\ObservabilityApplicationDiagnostic;
use Throwable;

/** Preserves one exact primary and the deterministic ordered cleanup failures. */
#[Api(ApiStatus::Experimental)]
final class ApplicationObservabilityCleanupException extends ObservabilityApplicationException
{
    /** @var list<Throwable> */
    private readonly array $cleanupFailures;

    /** @param list<Throwable> $cleanupFailures */
    private function __construct(private readonly ?Throwable $primaryFailure, array $cleanupFailures)
    {
        $this->cleanupFailures = $cleanupFailures;
        $diagnostic = ObservabilityApplicationDiagnostic::CombinedFailure;
        parent::__construct(
            $diagnostic,
            $diagnostic->combinedContext(null !== $primaryFailure, count($cleanupFailures)),
            $primaryFailure ?? $cleanupFailures[0],
        );
    }

    /** @param array<array-key, mixed> $cleanupFailures */
    public static function forFailures(?Throwable $primaryFailure, array $cleanupFailures): self
    {
        if ([] === $cleanupFailures || count($cleanupFailures) > 3) {
            throw ApplicationObservabilityLifecycleException::operationState();
        }

        $detached = [];
        foreach ($cleanupFailures as $failure) {
            if (!$failure instanceof Throwable) {
                throw ApplicationObservabilityLifecycleException::operationState();
            }
            $detached[] = $failure;
        }

        return new self($primaryFailure, $detached);
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
