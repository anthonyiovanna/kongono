<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionHost\Diagnostic\ExtensionHostDiagnostic;
use Throwable;

/** Retains the nullable exact primary plus every exact cleanup failure in observed order. */
#[Api(ApiStatus::Experimental)]
final class ExtensionHostLifecycleException extends ExtensionHostException
{
    /**
     * @param list<Throwable> $cleanupFailures
     * @internal Constructed only by the package lifecycle composer.
     */
    public function __construct(
        private readonly ?Throwable $primaryFailure,
        private readonly array $cleanupFailures,
    ) {
        parent::__construct(
            ExtensionHostDiagnostic::LifecycleFailuresCombined,
            ['primary_present' => null !== $primaryFailure, 'cleanup_count' => count($cleanupFailures)],
            $primaryFailure ?? $cleanupFailures[0],
        );
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
