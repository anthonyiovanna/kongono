<?php

declare(strict_types=1);

namespace Kongono\Composition\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Composition\Exception\RetiredCompositionBoundaryException;

/** Owns the lease counter used by generated application and operation assemblies. */
#[Api(ApiStatus::Internal)]
abstract class GeneratedApplicationState
{
    private bool $retired = false;
    private int $activeOperations = 0;

    final protected function acquireOperationLease(): void
    {
        $this->assertNotRetired('application_retired');
        ++$this->activeOperations;
    }

    /** @internal Called only by the generated operation assembly that owns a lease. */
    final public function releaseOperationLease(): void
    {
        if ($this->activeOperations < 1) {
            throw new RetiredCompositionBoundaryException(CompositionDiagnostic::CompositionBoundaryUnavailable, 'operation_lease_missing');
        }
        --$this->activeOperations;
    }

    final protected function assertNotRetired(string $violation): void
    {
        if ($this->retired) {
            throw new RetiredCompositionBoundaryException(CompositionDiagnostic::CompositionBoundaryUnavailable, $violation);
        }
    }

    final protected function beginShutdown(): void
    {
        if ($this->activeOperations > 0) {
            throw new RetiredCompositionBoundaryException(CompositionDiagnostic::CompositionBoundaryUnavailable, 'application_has_active_operations');
        }
        $this->retired = true;
    }

    final protected function retireAfterConstructionFailure(): void
    {
        $this->retired = true;
    }

    final protected function retired(): bool
    {
        return $this->retired;
    }
}
