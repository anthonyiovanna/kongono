<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Binds one immutable definition to the exact direction selected for execution. */
#[Api(ApiStatus::Experimental)]
final readonly class PlannedMigration
{
    public function __construct(private MigrationDefinition $definition, private MigrationDirection $direction) {}

    public function definition(): MigrationDefinition
    {
        return $this->definition;
    }

    public function direction(): MigrationDirection
    {
        return $this->direction;
    }

    public function plan(): MigrationDirectionPlan
    {
        if (MigrationDirection::Apply === $this->direction) {
            return $this->definition->apply();
        }

        return $this->definition->revert() ?? throw Exception\MigrationNotReversibleException::absent($this->definition->ordinal());
    }
}
