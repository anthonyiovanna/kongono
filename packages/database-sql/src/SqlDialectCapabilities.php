<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Exception\InvalidSqlPlanException;

/** Retains one duplicate-free capability set in canonical enum order. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlDialectCapabilities
{
    /** @var list<SqlDialectCapability> */
    private array $capabilities;

    /** @param array<array-key, mixed> $capabilities */
    public function __construct(array $capabilities)
    {
        if (!array_is_list($capabilities)) {
            throw InvalidSqlPlanException::validation();
        }

        $present = [];
        foreach ($capabilities as $capability) {
            if (!$capability instanceof SqlDialectCapability || isset($present[$capability->name])) {
                throw InvalidSqlPlanException::validation();
            }
            $present[$capability->name] = true;
        }

        $canonical = [];
        foreach (SqlDialectCapability::cases() as $capability) {
            if (isset($present[$capability->name])) {
                $canonical[] = $capability;
            }
        }
        $this->capabilities = $canonical;
    }

    /** @return list<SqlDialectCapability> */
    public function all(): array
    {
        return [...$this->capabilities];
    }

    public function has(SqlDialectCapability $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }
}
