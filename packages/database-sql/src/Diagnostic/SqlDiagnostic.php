<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\SqlDialectCapability;
use Kongono\DatabaseSql\SqlPlanKind;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing version-1 SQL-plan failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum SqlDiagnostic: string
{
    case InvalidPlan = 'KGN-SQL-1001';
    case UnsupportedDialectCapability = 'KGN-SQL-1002';
    case Compilation = 'KGN-SQL-1003';

    public function category(): string
    {
        return match ($this) {
            self::InvalidPlan => 'invalid_plan',
            self::UnsupportedDialectCapability => 'unsupported_dialect_capability',
            self::Compilation => 'compilation',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidPlan => 'Invalid SQL plan',
            self::UnsupportedDialectCapability => 'Unsupported SQL dialect capability',
            self::Compilation => 'Unable to compile SQL plan',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidPlan => 'Construct the plan only from the documented bounded version-1 SQL vocabulary.',
            self::UnsupportedDialectCapability => 'Use a dialect that declares every capability required by the plan.',
            self::Compilation => 'Correct the plan for the selected dialect profile before compiling again.',
        };
    }

    public function context(
        ?SqlPlanKind $planKind = null,
        ?string $dialectProfile = null,
        ?SqlDialectCapability $requiredCapability = null,
        ?int $parameterCount = null,
    ): DiagnosticContext {
        $schema = new DiagnosticContextSchema(
            'Compile immutable SQL plan',
            'SQL plans must remain bounded, capability-compatible, deterministic, and non-disclosing',
            [
                new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('plan_kind', 'Plan kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('dialect_profile', 'Dialect profile', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('required_capability', 'Required capability', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('parameter_count', 'Parameter count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            ],
        );
        $values = [
            'package' => 'database-sql',
            'failure_category' => $this->category(),
        ];
        if (null !== $planKind) {
            $values['plan_kind'] = $planKind->value;
        }
        if (null !== $dialectProfile) {
            $values['dialect_profile'] = $dialectProfile;
        }
        if (null !== $requiredCapability) {
            $values['required_capability'] = strtolower($requiredCapability->name);
        }
        if (null !== $parameterCount) {
            $values['parameter_count'] = $parameterCount;
        }

        return new DiagnosticContext($schema, $values);
    }
}
