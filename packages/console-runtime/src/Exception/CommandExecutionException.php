<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleRuntime\Diagnostic\ConsoleRuntimeDiagnostic;
use Throwable;

/** Retains exact primary and ordered cleanup failure identities behind safe presentation. */
#[Api(ApiStatus::Public)]
final class CommandExecutionException extends ConsoleRuntimeException
{
    /** @var list<Throwable> */
    private readonly array $cleanupFailures;

    /** @param list<Throwable> $cleanupFailures */
    private function __construct(
        private readonly ?Throwable $primaryFailure,
        array $cleanupFailures,
        ConsoleRuntimeDiagnostic $diagnostic,
    ) {
        $this->cleanupFailures = $cleanupFailures;
        parent::__construct(
            $diagnostic,
            null !== $primaryFailure,
            count($cleanupFailures),
            $primaryFailure ?? $cleanupFailures[0],
        );
    }

    /** @param array<array-key, mixed> $cleanupFailures */
    public static function forFailures(?Throwable $primaryFailure, array $cleanupFailures): self
    {
        if (count($cleanupFailures) > 64) {
            throw InvalidCommandRuntimeValueException::operationLifecycle();
        }

        $detached = [];
        foreach ($cleanupFailures as $failure) {
            if (!$failure instanceof Throwable) {
                throw InvalidCommandRuntimeValueException::operationLifecycle();
            }
            $detached[] = $failure;
        }

        if (null === $primaryFailure && [] === $detached) {
            throw InvalidCommandRuntimeValueException::operationLifecycle();
        }

        $diagnostic = match (true) {
            null !== $primaryFailure && [] !== $detached => ConsoleRuntimeDiagnostic::ExecutionAndCleanupFailed,
            null !== $primaryFailure => ConsoleRuntimeDiagnostic::ExecutionFailed,
            default => ConsoleRuntimeDiagnostic::CleanupFailed,
        };

        return new self($primaryFailure, $detached, $diagnostic);
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
