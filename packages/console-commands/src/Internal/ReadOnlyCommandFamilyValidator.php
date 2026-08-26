<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandSpecificationSet;
use Kongono\ConsoleCommands\Exception\InvalidConsoleCommandsValueException;
use Kongono\ConsoleCommands\ReadOnlyFrameworkCommand;

/** Fails closed unless one specification set matches the exact Phase 8F family. */
#[Api(ApiStatus::Internal)]
final class ReadOnlyCommandFamilyValidator
{
    private const DEFINITIONS = [
        ReadOnlyFrameworkCommand::Composition->value => 'Inspect supplied composition artifact identities',
        ReadOnlyFrameworkCommand::Graph->value => 'Inspect supplied application graph summary',
        ReadOnlyFrameworkCommand::Quality->value => 'Inspect supplied Hantei quality verdict',
    ];

    public function validate(CommandSpecificationSet $specifications): void
    {
        $commands = $specifications->commands();
        if (count(self::DEFINITIONS) !== count($commands)) {
            throw InvalidConsoleCommandsValueException::invalidFamily();
        }

        foreach ($commands as $index => $command) {
            $expectedIdentifier = array_keys(self::DEFINITIONS)[$index];
            $definition = $command->definition();
            if (
                $expectedIdentifier !== $definition->identifier()->value()
                || self::DEFINITIONS[$expectedIdentifier] !== $definition->summary()->value()
                || CommandInteractionPolicy::None !== $definition->interactionPolicy()
                || CommandMutationPolicy::ReadOnly !== $definition->mutationPolicy()
                || [] !== $command->inputs()
            ) {
                throw InvalidConsoleCommandsValueException::invalidFamily();
            }
        }
    }
}
