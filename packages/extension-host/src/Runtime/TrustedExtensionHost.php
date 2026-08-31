<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Runtime;

use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionComposition\Runtime\GeneratedExtensionApplicationBoundary;
use Kongono\ExtensionHost\Contract\ApplicationExtensionInterface;
use Kongono\ExtensionHost\Contract\OperationExtensionInterface;
use Kongono\ExtensionHost\Exception\ExtensionHostUnavailableException;
use Kongono\ExtensionHost\Exception\InvalidGeneratedExtensionHostException;
use Kongono\ExtensionHost\Internal\LifecycleFailureComposer;
use Throwable;

/** Owns one explicit trusted in-process application lifecycle and only a bounded operation lease count. */
#[Api(ApiStatus::Experimental)]
final class TrustedExtensionHost
{
    private ExtensionHostState $state = ExtensionHostState::Active;
    private int $activeOperations = 0;

    /**
     * @param list<ApplicationExtensionInterface> $applicationExtensions
     * @internal Constructed only after complete application startup.
     */
    private function __construct(
        private ?GeneratedExtensionApplicationBoundary $applicationAssembly,
        private array $applicationExtensions,
    ) {}

    public static function start(LoadedExtensionWiring $wiring, object ...$applicationProviders): self
    {
        $assembly = $wiring->start(...$applicationProviders);
        $extensions = $assembly->applicationExtensions();
        foreach ($extensions as $extension) {
            if (!$extension instanceof ApplicationExtensionInterface) {
                throw InvalidGeneratedExtensionHostException::invalid('lifecycle_mismatch');
            }
        }

        $started = [];
        try {
            foreach ($extensions as $extension) {
                $extension->start();
                $started[] = $extension;
            }
        } catch (Throwable $primaryFailure) {
            $cleanupFailures = self::stopReverse($started);
            $assembly = null;
            $extensions = [];
            $started = [];
            LifecycleFailureComposer::throwIfNeeded($primaryFailure, $cleanupFailures);
        }

        return new self($assembly, $started);
    }

    public function beginOperation(object ...$operationProviders): TrustedExtensionOperation
    {
        if (ExtensionHostState::Active !== $this->state) {
            throw ExtensionHostUnavailableException::unavailable('host_not_active', $this->state);
        }
        $assembly = $this->applicationAssembly;
        if (null === $assembly) {
            throw ExtensionHostUnavailableException::unavailable('host_not_active', $this->state);
        }

        ++$this->activeOperations;
        try {
            $operationAssembly = $assembly->beginHostedOperation(array_values($operationProviders));
        } catch (InvalidArgumentException $failure) {
            $this->releaseOperationLease();
            $violation = str_contains($failure->getMessage(), 'count') ? 'provider_count' : 'provider_type';
            throw InvalidGeneratedExtensionHostException::invalid($violation, $failure);
        } catch (Throwable $primaryFailure) {
            $this->releaseOperationLease();
            $this->retire();
            throw $primaryFailure;
        }

        $extensions = $operationAssembly->operationExtensions();
        foreach ($extensions as $extension) {
            if (!$extension instanceof OperationExtensionInterface) {
                $operationAssembly = null;
                $extensions = [];
                $this->releaseOperationLease();
                $this->retire();
                throw InvalidGeneratedExtensionHostException::invalid('lifecycle_mismatch');
            }
        }

        $begun = [];
        try {
            foreach ($extensions as $extension) {
                $extension->begin();
                $begun[] = $extension;
            }
        } catch (Throwable $primaryFailure) {
            $cleanupFailures = TrustedExtensionOperation::closeReverse($begun);
            $operationAssembly = null;
            $extensions = [];
            $begun = [];
            $this->releaseOperationLease();
            $this->retire();
            LifecycleFailureComposer::throwIfNeeded($primaryFailure, $cleanupFailures);
        }

        return new TrustedExtensionOperation($this, $operationAssembly, $begun);
    }

    public function shutdown(): void
    {
        if (ExtensionHostState::Shutdown === $this->state) {
            return;
        }
        if ($this->activeOperations > 0) {
            throw ExtensionHostUnavailableException::unavailable('active_operations', $this->state);
        }

        $this->state = ExtensionHostState::Shutdown;
        $extensions = $this->applicationExtensions;
        $this->applicationExtensions = [];
        $this->applicationAssembly = null;
        $cleanupFailures = self::stopReverse($extensions);
        $extensions = [];
        LifecycleFailureComposer::throwIfNeeded(null, $cleanupFailures);
    }

    public function state(): ExtensionHostState
    {
        return $this->state;
    }

    public function isRetired(): bool
    {
        return ExtensionHostState::Retired === $this->state;
    }

    public function isClosed(): bool
    {
        return ExtensionHostState::Shutdown === $this->state;
    }

    /** @internal Releases exactly one lease owned by one TrustedExtensionOperation. */
    public function releaseOperationLease(): void
    {
        if ($this->activeOperations < 1) {
            throw ExtensionHostUnavailableException::unavailable('operation_lease_missing', $this->state);
        }
        --$this->activeOperations;
    }

    /** @internal Marks uncertain operation cleanup as irreversibly unavailable for new work. */
    public function retire(): void
    {
        if (ExtensionHostState::Active === $this->state) {
            $this->state = ExtensionHostState::Retired;
        }
    }

    /**
     * @param list<ApplicationExtensionInterface> $extensions
     *
     * @return list<Throwable>
     */
    private static function stopReverse(array $extensions): array
    {
        $failures = [];
        for ($index = count($extensions) - 1; $index >= 0; --$index) {
            try {
                $extensions[$index]->stop();
            } catch (Throwable $failure) {
                $failures[] = $failure;
            }
        }

        return $failures;
    }
}
