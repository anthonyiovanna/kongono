<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandInputRequirement;
use Kongono\Console\CommandInputSensitivity;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandSpecificationSet;
use Kongono\ConsoleApplication\Exception\InvalidConsoleApplicationValueException;
use Kongono\ConsoleApplication\Handler\ApplicationBuildCommandHandler;
use Kongono\ConsoleApplication\KongoCommand;
use Kongono\ConsoleRuntime\CommandHandlerSet;

/** Fails closed unless definitions and handlers match the exact Phase 8G family. */
#[Api(ApiStatus::Internal)]
final class KongoCommandValidator
{
    public function validate(CommandSpecificationSet $specifications, CommandHandlerSet $handlers): void
    {
        $commands = $specifications->commands();
        $bindings = $handlers->bindings();
        if (1 !== count($commands) || 1 !== count($bindings)) {
            throw InvalidConsoleApplicationValueException::invalidCommandFamily();
        }

        $specification = $commands[0];
        $definition = $specification->definition();
        $inputs = $specification->inputs();
        if (
            KongoCommand::ApplicationBuild->value !== $definition->identifier()->value()
            || 'Build exact current application composition outputs' !== $definition->summary()->value()
            || CommandInteractionPolicy::None !== $definition->interactionPolicy()
            || CommandMutationPolicy::MutationCapable !== $definition->mutationPolicy()
            || 2 !== count($inputs)
            || 'application-root' !== $inputs[0]->name()->value()
            || CommandInputRequirement::Required !== $inputs[0]->requirement()
            || CommandInputSensitivity::Ordinary !== $inputs[0]->sensitivity()
            || 'output-directory' !== $inputs[1]->name()->value()
            || CommandInputRequirement::Required !== $inputs[1]->requirement()
            || CommandInputSensitivity::Ordinary !== $inputs[1]->sensitivity()
            || KongoCommand::ApplicationBuild->value !== $bindings[0]->identifier()->value()
            || !$bindings[0]->handler() instanceof ApplicationBuildCommandHandler
        ) {
            throw InvalidConsoleApplicationValueException::invalidCommandFamily();
        }
    }
}
