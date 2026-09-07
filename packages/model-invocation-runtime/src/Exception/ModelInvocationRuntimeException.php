<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ModelInvocationRuntime\Diagnostic\ModelInvocationRuntimeDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Exposes only fixed package evidence while retaining exact throwable identity. */
#[Api(ApiStatus::Internal)]
abstract class ModelInvocationRuntimeException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    protected function __construct(
        private readonly ModelInvocationRuntimeDiagnostic $diagnostic,
        array $context,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidInvocationRuntimeException && !$this instanceof InvocationRejectedException && !$this instanceof InvocationOperationException && !$this instanceof InvocationCleanupException && !$this instanceof InvocationOperationAndCleanupException) {
            throw new LogicException('Model invocation runtime exception type is unsupported.');
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
