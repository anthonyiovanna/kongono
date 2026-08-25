<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Exception\InvalidDatabaseValueException;

/** Stores a canonical duplicate-free detached capability set. */
#[Api(ApiStatus::Experimental)]
final readonly class DatabaseCapabilities
{
    /** @var list<DatabaseCapability> */
    private array $capabilities;

    /** @param array<array-key, mixed> $capabilities */
    public function __construct(array $capabilities)
    {
        if (!array_is_list($capabilities)) {
            throw InvalidDatabaseValueException::validation();
        }
        $seen = [];
        foreach ($capabilities as $capability) {
            if (!$capability instanceof DatabaseCapability || isset($seen[$capability->name])) {
                throw InvalidDatabaseValueException::validation();
            }
            $seen[$capability->name] = $capability;
        }
        $canonical = [];
        foreach (DatabaseCapability::cases() as $capability) {
            if (isset($seen[$capability->name])) {
                $canonical[] = $capability;
            }
        }
        $this->capabilities = $canonical;
    }

    public function has(DatabaseCapability $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    /** @return list<DatabaseCapability> */
    public function all(): array
    {
        return [...$this->capabilities];
    }
}
