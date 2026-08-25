<?php

declare(strict_types=1);

namespace Kongono\DatabaseSqlPgsql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseStatement;
use Kongono\DatabaseSql\DeletePlan;
use Kongono\DatabaseSql\Exception\SqlCompilationException;
use Kongono\DatabaseSql\Exception\UnsupportedDialectCapabilityException;
use Kongono\DatabaseSql\InsertPlan;
use Kongono\DatabaseSql\SelectPlan;
use Kongono\DatabaseSql\SqlDialectCapabilities;
use Kongono\DatabaseSql\SqlDialectCapability;
use Kongono\DatabaseSql\SqlDialectCompiler;
use Kongono\DatabaseSql\SqlPlan;
use Kongono\DatabaseSql\UpdatePlan;
use Kongono\DatabaseSqlPgsql\Internal\PostgreSqlCompilation;

/** Compiles the exact immutable version-1 grammar for PostgreSQL 17 without I/O. */
#[Api(ApiStatus::Experimental)]
final readonly class PostgreSql17Compiler implements SqlDialectCompiler
{
    public const PROFILE = 'postgresql-17';

    public function capabilities(): SqlDialectCapabilities
    {
        return new SqlDialectCapabilities(SqlDialectCapability::cases());
    }

    public function compile(SqlPlan $plan): DatabaseStatement
    {
        $acceptedPlan = $this->acceptedPlan($plan);
        foreach ($acceptedPlan->requiredCapabilities()->all() as $required) {
            if (!$this->capabilities()->has($required)) {
                throw UnsupportedDialectCapabilityException::capability($acceptedPlan->kind(), $required, self::PROFILE);
            }
        }

        $this->validateIdentifiers($acceptedPlan);
        $parameterCount = PostgreSqlCompilation::parameterCount($acceptedPlan);
        if ($parameterCount > 65_535) {
            throw SqlCompilationException::parameterOverflow($acceptedPlan->kind(), self::PROFILE, $parameterCount);
        }

        return (new PostgreSqlCompilation())->compile($acceptedPlan);
    }

    private function acceptedPlan(SqlPlan $plan): SelectPlan|InsertPlan|UpdatePlan|DeletePlan
    {
        if ($plan instanceof SelectPlan || $plan instanceof InsertPlan || $plan instanceof UpdatePlan || $plan instanceof DeletePlan) {
            return $plan;
        }

        throw UnsupportedDialectCapabilityException::foreignPlan(self::PROFILE);
    }

    private function validateIdentifiers(SelectPlan|InsertPlan|UpdatePlan|DeletePlan $plan): void
    {
        foreach (PostgreSqlCompilation::identifiers($plan) as $identifier) {
            if (strlen($identifier->value()) > 63) {
                throw SqlCompilationException::rejected($plan->kind(), self::PROFILE);
            }
        }
    }
}
