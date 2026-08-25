<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Tests\Fixture;

use Kongono\Database\DatabaseStatement;
use Kongono\DatabaseSql\SqlDialectCapabilities;
use Kongono\DatabaseSql\SqlDialectCompiler;
use Kongono\DatabaseSql\SqlPlan;
use Kongono\DatabaseSqlPgsql\PostgreSql17Compiler;

final class RecordingCompiler implements SqlDialectCompiler
{
    /** @var list<SqlPlan> */
    private array $plans = [];
    private readonly PostgreSql17Compiler $compiler;

    public function __construct()
    {
        $this->compiler = new PostgreSql17Compiler();
    }

    public function capabilities(): SqlDialectCapabilities
    {
        return $this->compiler->capabilities();
    }

    public function compile(SqlPlan $plan): DatabaseStatement
    {
        $this->plans[] = $plan;

        return $this->compiler->compile($plan);
    }

    /** @return list<SqlPlan> */
    public function plans(): array
    {
        return [...$this->plans];
    }
}
