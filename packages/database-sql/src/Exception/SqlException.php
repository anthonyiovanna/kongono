<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Diagnostic\SqlDiagnostic;
use Kongono\DatabaseSql\SqlDialectCapability;
use Kongono\DatabaseSql\SqlPlanKind;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;

/** Supplies deterministic non-disclosing presentation for SQL-plan failures. */
#[Api(ApiStatus::Internal)]
abstract class SqlException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $context;

    final protected function __construct(
        private readonly SqlDiagnostic $diagnostic,
        ?SqlPlanKind $planKind = null,
        ?string $dialectProfile = null,
        ?SqlDialectCapability $requiredCapability = null,
        ?int $parameterCount = null,
    ) {
        $this->context = $diagnostic->context($planKind, $dialectProfile, $requiredCapability, $parameterCount);
        parent::__construct($diagnostic->title());
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

    final public function diagnosticContext(): DiagnosticContext
    {
        return $this->context;
    }

    final public function __toString(): string
    {
        return sprintf('%s: %s', $this->diagnostic->value, $this->diagnostic->title());
    }
}
