<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Diagnostic\MigrationDiagnostic;
use Kongono\DatabaseMigration\MigrationDirection;
use Kongono\DatabaseMigration\MigrationRisk;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/** Supplies deterministic non-disclosing presentation for migration failures. */
#[Api(ApiStatus::Internal)]
abstract class MigrationException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $context;

    final protected function __construct(
        private readonly MigrationDiagnostic $diagnostic,
        ?MigrationDirection $direction = null,
        ?int $ordinal = null,
        ?string $operationKind = null,
        ?MigrationRisk $risk = null,
        ?string $lifecyclePhase = null,
        ?int $previousCount = null,
        ?int $resultingCount = null,
        ?string $profile = null,
        ?Throwable $previous = null,
    ) {
        $this->context = $diagnostic->context($direction, $ordinal, $operationKind, $risk, $lifecyclePhase, $previousCount, $resultingCount, $profile);
        parent::__construct($diagnostic->title(), 0, $previous);
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
