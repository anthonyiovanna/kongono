<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Exception;

use Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic;
use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;
use RuntimeException;
use Throwable;

/**
 * Provides the Internal base for deliberate Application Graph failures.
 *
 * Consumers should catch {@see ExceptionInterface}. This class defines no
 * logging, transport, localization, telemetry, or retry policy.
 */
#[Api(ApiStatus::Internal)]
abstract class GraphException extends RuntimeException implements ExceptionInterface
{
    final public function __construct(
        private readonly GraphDiagnostic $diagnostic,
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
