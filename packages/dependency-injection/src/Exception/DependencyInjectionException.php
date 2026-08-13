<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Exception;

use Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic;
use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;
use RuntimeException;
use Throwable;

/**
 * Provides the Internal base for deliberate dependency-resolution failures.
 *
 * The base defines no transport, logging, retry, serialization, or rendering
 * policy. Consumers should catch the package's Public marker when appropriate.
 */
#[Api(ApiStatus::Internal)]
abstract class DependencyInjectionException extends RuntimeException implements ExceptionInterface
{
    final public function __construct(
        private readonly DependencyInjectionDiagnostic $diagnostic,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    final public function diagnosticId(): string
    {
        return $this->diagnostic->value;
    }

    final public function diagnosticTitle(): string
    {
        return $this->diagnostic->title();
    }

    final public function diagnosticRemediation(): string
    {
        return $this->diagnostic->remediation();
    }
}
