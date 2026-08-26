<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandInputDefinition;
use Kongono\Console\CommandSpecification;
use Kongono\Console\CommandSpecificationSet;

/** Projects immutable command definitions into one shared help model. */
#[Api(ApiStatus::Internal)]
final class CommandHelpProjection
{
    /** @return list<array{identifier: string, summary: string, interaction: string, mutation: string, inputs: list<array{name: string, requirement: string, sensitivity: string}>}> */
    public static function forSet(CommandSpecificationSet $specifications): array
    {
        return array_map(self::project(...), $specifications->commands());
    }

    /** @return list<array{identifier: string, summary: string, interaction: string, mutation: string, inputs: list<array{name: string, requirement: string, sensitivity: string}>}> */
    public static function forCommand(CommandSpecification $specification): array
    {
        return [self::project($specification)];
    }

    /** @return array{identifier: string, summary: string, interaction: string, mutation: string, inputs: list<array{name: string, requirement: string, sensitivity: string}>} */
    private static function project(CommandSpecification $specification): array
    {
        $definition = $specification->definition();

        return [
            'identifier' => $definition->identifier()->value(),
            'summary' => $definition->summary()->value(),
            'interaction' => $definition->interactionPolicy()->value,
            'mutation' => $definition->mutationPolicy()->value,
            'inputs' => array_map(
                static fn(CommandInputDefinition $input): array => [
                    'name' => $input->name()->value(),
                    'requirement' => $input->requirement()->value,
                    'sensitivity' => $input->sensitivity()->value,
                ],
                $specification->inputs(),
            ),
        ];
    }

    private function __construct() {}
}
