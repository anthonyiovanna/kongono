<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines immutable neutral command meaning without parsing or behavior. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandDefinition
{
    public function __construct(
        private CommandIdentifier $identifier,
        private CommandSummary $summary,
        private CommandInteractionPolicy $interactionPolicy,
        private CommandMutationPolicy $mutationPolicy,
    ) {}

    public function identifier(): CommandIdentifier
    {
        return $this->identifier;
    }

    public function summary(): CommandSummary
    {
        return $this->summary;
    }

    public function interactionPolicy(): CommandInteractionPolicy
    {
        return $this->interactionPolicy;
    }

    public function mutationPolicy(): CommandMutationPolicy
    {
        return $this->mutationPolicy;
    }
}
