<?php

declare(strict_types=1);

namespace Kongono\Database\Exception;

use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Diagnostic\DatabaseDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;

/** Reports an invalid neutral value without copying rejected input. */
#[Api(ApiStatus::Experimental)]
final class InvalidDatabaseValueException extends InvalidArgumentException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    private function __construct()
    {
        $this->diagnosticContext = DatabaseDiagnostic::Validation->context();
        parent::__construct(DatabaseDiagnostic::Validation->title());
    }

    public static function validation(): self
    {
        return new self();
    }

    public function diagnosticId(): string
    {
        return DatabaseDiagnostic::Validation->value;
    }
    public function diagnosticTitle(): string
    {
        return DatabaseDiagnostic::Validation->title();
    }
    public function diagnosticRemediation(): string
    {
        return DatabaseDiagnostic::Validation->remediation();
    }
    public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
    }
    public function __toString(): string
    {
        return sprintf('%s: %s', $this->diagnosticId(), $this->diagnosticTitle());
    }
}
