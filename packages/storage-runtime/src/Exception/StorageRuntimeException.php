<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\StorageRuntime\Diagnostic\StorageRuntimeDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides deterministic nondisclosing storage-runtime failure presentation. */
#[Api(ApiStatus::Internal)]
abstract class StorageRuntimeException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $values */
    protected function __construct(
        private readonly StorageRuntimeDiagnostic $diagnostic,
        array $values,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidStorageRuntimeException
            && !$this instanceof StorageRuntimeOperationException
            && !$this instanceof StorageRuntimeCleanupException
            && !$this instanceof StorageRuntimeOperationAndCleanupException) {
            throw new LogicException('Storage runtime diagnostic and exception type do not match.');
        }
        $this->diagnosticContext = $diagnostic->context($values);
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
        $context = [];
        foreach ($this->diagnosticContext->fields() as $field) {
            $context[] = $field->key() . '=' . $field->value();
        }

        return sprintf('%s: [%s] %s. Remediation: %s. Context: %s', static::class, $this->diagnosticId(), $this->diagnosticTitle(), $this->diagnosticRemediation(), implode(', ', $context));
    }
}
