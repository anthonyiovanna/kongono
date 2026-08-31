<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Contribution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Internal\ContractGuard;

/** Identifies a contribution schema without carrying a contribution payload. */
#[Api(ApiStatus::Experimental)]
final readonly class ContributionSchema
{
    private string $name;
    private int $version;

    public function __construct(string $name, int $version)
    {
        $this->name = ContractGuard::contributionSchemaName($name);
        $this->version = ContractGuard::contributionSchemaVersion($version);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->version === $other->version;
    }
}
