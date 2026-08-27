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
use Kongono\ConsoleApplication\Handler\SecurityCheckCommandHandler;
use Kongono\ConsoleApplication\Handler\SecurityInspectCommandHandler;
use Kongono\ConsoleApplication\KongoCommand;
use Kongono\ConsoleRuntime\CommandHandlerSet;

/** Fails closed unless definitions and handlers match the exact Phase 9G family. */
#[Api(ApiStatus::Internal)]
final class KongoCommandValidator
{
    public function validate(CommandSpecificationSet $specifications, CommandHandlerSet $handlers): void
    {
        $commands = $specifications->commands();
        $bindings = $handlers->bindings();
        if (3 !== count($commands) || 3 !== count($bindings)) {
            throw InvalidConsoleApplicationValueException::invalidCommandFamily();
        }

        $this->validateSpecification(
            $commands[0],
            KongoCommand::ApplicationBuild,
            'Build exact current application composition outputs',
            CommandMutationPolicy::MutationCapable,
        );
        $this->validateSpecification(
            $commands[1],
            KongoCommand::SecurityCheck,
            'Check exact current security policy evidence',
            CommandMutationPolicy::ReadOnly,
        );
        $this->validateSpecification(
            $commands[2],
            KongoCommand::SecurityInspect,
            'Inspect planned security policy evidence',
            CommandMutationPolicy::ReadOnly,
        );
        if (
            KongoCommand::ApplicationBuild->value !== $bindings[0]->identifier()->value()
            || !$bindings[0]->handler() instanceof ApplicationBuildCommandHandler
            || KongoCommand::SecurityCheck->value !== $bindings[1]->identifier()->value()
            || !$bindings[1]->handler() instanceof SecurityCheckCommandHandler
            || KongoCommand::SecurityInspect->value !== $bindings[2]->identifier()->value()
            || !$bindings[2]->handler() instanceof SecurityInspectCommandHandler
        ) {
            throw InvalidConsoleApplicationValueException::invalidCommandFamily();
        }
    }

    private function validateSpecification(
        \Kongono\Console\CommandSpecification $specification,
        KongoCommand $command,
        string $summary,
        CommandMutationPolicy $mutationPolicy,
    ): void {
        $definition = $specification->definition();
        $inputs = $specification->inputs();
        if (
            $command->value !== $definition->identifier()->value()
            || $summary !== $definition->summary()->value()
            || CommandInteractionPolicy::None !== $definition->interactionPolicy()
            || $mutationPolicy !== $definition->mutationPolicy()
            || 2 !== count($inputs)
            || 'application-root' !== $inputs[0]->name()->value()
            || CommandInputRequirement::Required !== $inputs[0]->requirement()
            || CommandInputSensitivity::Ordinary !== $inputs[0]->sensitivity()
            || 'output-directory' !== $inputs[1]->name()->value()
            || CommandInputRequirement::Required !== $inputs[1]->requirement()
            || CommandInputSensitivity::Ordinary !== $inputs[1]->sensitivity()
        ) {
            throw InvalidConsoleApplicationValueException::invalidCommandFamily();
        }
    }
}
