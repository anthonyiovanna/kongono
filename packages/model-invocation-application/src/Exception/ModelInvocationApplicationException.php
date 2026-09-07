<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ModelInvocationApplication\Diagnostic\ModelInvocationApplicationDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Exposes bounded package evidence without rendering foreign failure messages. */
#[Api(ApiStatus::Internal)]
abstract class ModelInvocationApplicationException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    protected function __construct(
        private readonly ModelInvocationApplicationDiagnostic $diagnostic,
        array $context,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidModelInvocationApplicationProfileException
            && !$this instanceof InvalidGeneratedModelInvocationWiringException
            && !$this instanceof ModelInvocationWiringPublicationException
            && !$this instanceof ModelInvocationWiringLoadException
            && !$this instanceof ModelInvocationApplicationWorkflowException
            && !$this instanceof ModelInvocationApplicationLifecycleException
            && !$this instanceof ModelInvocationApplicationOperationException
        ) {
            throw new LogicException('Model Invocation Application exception type is unsupported.');
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
