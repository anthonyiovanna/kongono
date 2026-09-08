<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\NetworkEgressRuntime\Diagnostic\NetworkEgressRuntimeDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Exposes deterministic package evidence while retaining exact throwable identity. */
#[Api(ApiStatus::Internal)]
abstract class NetworkEgressRuntimeException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    protected function __construct(
        private readonly NetworkEgressRuntimeDiagnostic $diagnostic,
        array $context,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidEgressRuntimeException && !$this instanceof EgressOperationException && !$this instanceof EgressCleanupException && !$this instanceof EgressOperationAndCleanupException) {
            throw new LogicException('Network egress runtime exception type is unsupported.');
        }
        $this->diagnosticContext = $diagnostic->context($context);
        parent::__construct($diagnostic->title() . '.', 0, $previous);
    }

    final public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
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

    final public function __toString(): string
    {
        return sprintf('%s: [%s] %s.', static::class, $this->diagnosticId(), $this->diagnosticTitle());
    }
}
