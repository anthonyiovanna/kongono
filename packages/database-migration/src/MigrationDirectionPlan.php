<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;

/** Retains one non-empty ordered operation sequence and its exact derived risk. */
#[Api(ApiStatus::Experimental)]
final readonly class MigrationDirectionPlan
{
    /** @var list<MigrationOperation> */
    private array $operations;

    /** @param array<array-key, mixed> $operations */
    public function __construct(array $operations, private MigrationRisk $risk)
    {
        if (!array_is_list($operations) || [] === $operations || count($operations) > 4_096) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
        $detached = [];
        $derived = MigrationRisk::Safe;
        foreach ($operations as $operation) {
            if (!$operation instanceof MigrationOperation) {
                throw InvalidMigrationDefinitionException::invalidDefinition();
            }
            if (MigrationRisk::Destructive === $operation->risk()) {
                $derived = MigrationRisk::Destructive;
            }
            $detached[] = $operation;
        }
        if ($risk !== $derived) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
        $this->operations = $detached;
    }

    /** @return list<MigrationOperation> */
    public function operations(): array
    {
        return [...$this->operations];
    }

    public function risk(): MigrationRisk
    {
        return $this->risk;
    }
}
