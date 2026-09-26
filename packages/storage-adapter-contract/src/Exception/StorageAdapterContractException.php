<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\StorageAdapterContract\Diagnostic\StorageAdapterContractDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides deterministic nondisclosing storage-adapter failures. */
#[Api(ApiStatus::Internal)]
abstract class StorageAdapterContractException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $values */
    final protected function __construct(
        private readonly StorageAdapterContractDiagnostic $diagnostic,
        array $values,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidStorageAdapterContractException && !$this instanceof StorageAdapterExecutionException) {
            throw new LogicException('Storage adapter contract diagnostic and exception type do not match.');
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
