<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionComposition\Runtime\GeneratedExtensionOperationBoundary;
use Kongono\ExtensionHost\Contract\OperationExtensionInterface;
use Kongono\ExtensionHost\Internal\LifecycleFailureComposer;
use Throwable;

/** Owns one fresh operation assembly, reverse cleanup, exact lease release, and complete detachment. */
#[Api(ApiStatus::Experimental)]
final class TrustedExtensionOperation
{
    private bool $closed = false;

    /**
     * @param list<OperationExtensionInterface> $operationExtensions
     * @internal Constructed only after complete operation activation.
     */
    public function __construct(
        private ?TrustedExtensionHost $host,
        private ?GeneratedExtensionOperationBoundary $operationAssembly,
        private array $operationExtensions,
    ) {}

    private function __clone(): void {}

    public function close(?Throwable $primaryFailure = null): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $host = $this->host;
        $operationAssembly = $this->operationAssembly;
        $extensions = $this->operationExtensions;
        $this->host = null;
        $this->operationAssembly = null;
        $this->operationExtensions = [];
        $operationAssembly = null;

        $cleanupFailures = self::closeReverse($extensions);
        $extensions = [];
        if (null !== $host) {
            $host->releaseOperationLease();
            if ([] !== $cleanupFailures) {
                $host->retire();
            }
        }
        $host = null;

        LifecycleFailureComposer::throwIfNeeded($primaryFailure, $cleanupFailures);
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * @param list<OperationExtensionInterface> $extensions
     *
     * @return list<Throwable>
     */
    public static function closeReverse(array $extensions): array
    {
        $failures = [];
        for ($index = count($extensions) - 1; $index >= 0; --$index) {
            try {
                $extensions[$index]->close();
            } catch (Throwable $failure) {
                $failures[] = $failure;
            }
        }

        return $failures;
    }
}
