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
use Kongono\ConsoleApplication\Handler\ApplicationGraphInspectCommandHandler;
use Kongono\ConsoleApplication\Handler\ExtensionInspectCommandHandler;
use Kongono\ConsoleApplication\Handler\ObservabilityCheckCommandHandler;
use Kongono\ConsoleApplication\Handler\ObservabilityInspectCommandHandler;
use Kongono\ConsoleApplication\Handler\SecurityCheckCommandHandler;
use Kongono\ConsoleApplication\Handler\SecurityInspectCommandHandler;
use Kongono\ConsoleApplication\Handler\ViewBuildCommandHandler;
use Kongono\ConsoleApplication\Handler\ViewCheckCommandHandler;
use Kongono\ConsoleApplication\Handler\ViewInspectCommandHandler;
use Kongono\ConsoleApplication\KongoCommand;
use Kongono\ConsoleRuntime\CommandHandlerSet;

/** Fails closed unless definitions and handlers match the exact Phase 11F family. */
#[Api(ApiStatus::Internal)]
final class KongoCommandValidator
{
    public function validate(CommandSpecificationSet $specifications, CommandHandlerSet $handlers): void
    {
        $commands = $specifications->commands();
        $bindings = $handlers->bindings();
        if (10 !== count($commands) || 10 !== count($bindings)) {
            throw InvalidConsoleApplicationValueException::invalidCommandFamily();
        }

        $this->validateSpecification(
            $commands[0],
            KongoCommand::ApplicationGraphInspect,
            'Inspect one authoritative application graph artifact',
            CommandMutationPolicy::ReadOnly,
            ['artifact-path'],
        );
        $this->validateSpecification(
            $commands[1],
            KongoCommand::ApplicationBuild,
            'Build exact current application composition outputs',
            CommandMutationPolicy::MutationCapable,
        );
        $this->validateSpecification(
            $commands[2],
            KongoCommand::ExtensionInspect,
            'Inspect detached extension platform evidence',
            CommandMutationPolicy::ReadOnly,
            ['declaration-artifact-path', 'generated-wiring-path'],
        );
        $this->validateSpecification(
            $commands[3],
            KongoCommand::ObservabilityCheck,
            'Check exact current observability configuration evidence',
            CommandMutationPolicy::ReadOnly,
        );
        $this->validateSpecification(
            $commands[4],
            KongoCommand::ObservabilityInspect,
            'Inspect planned observability configuration evidence',
            CommandMutationPolicy::ReadOnly,
        );
        $this->validateSpecification(
            $commands[5],
            KongoCommand::SecurityCheck,
            'Check exact current security policy evidence',
            CommandMutationPolicy::ReadOnly,
        );
        $this->validateSpecification(
            $commands[6],
            KongoCommand::SecurityInspect,
            'Inspect planned security policy evidence',
            CommandMutationPolicy::ReadOnly,
        );
        $this->validateSpecification($commands[7], KongoCommand::ViewBuild, 'Build exact current compiled-view application artifact', CommandMutationPolicy::MutationCapable, ['application-root', 'output-directory', 'source-file']);
        $this->validateSpecification($commands[8], KongoCommand::ViewCheck, 'Check exact current compiled-view application artifact', CommandMutationPolicy::ReadOnly, ['application-root', 'output-directory', 'source-file']);
        $this->validateSpecification($commands[9], KongoCommand::ViewInspect, 'Inspect planned compiled-view application evidence', CommandMutationPolicy::ReadOnly, ['application-root', 'output-directory', 'source-file']);
        if (
            KongoCommand::ApplicationGraphInspect->value !== $bindings[0]->identifier()->value()
            || !$bindings[0]->handler() instanceof ApplicationGraphInspectCommandHandler
            || KongoCommand::ApplicationBuild->value !== $bindings[1]->identifier()->value()
            || !$bindings[1]->handler() instanceof ApplicationBuildCommandHandler
            || KongoCommand::ExtensionInspect->value !== $bindings[2]->identifier()->value()
            || !$bindings[2]->handler() instanceof ExtensionInspectCommandHandler
            || KongoCommand::ObservabilityCheck->value !== $bindings[3]->identifier()->value()
            || !$bindings[3]->handler() instanceof ObservabilityCheckCommandHandler
            || KongoCommand::ObservabilityInspect->value !== $bindings[4]->identifier()->value()
            || !$bindings[4]->handler() instanceof ObservabilityInspectCommandHandler
            || KongoCommand::SecurityCheck->value !== $bindings[5]->identifier()->value()
            || !$bindings[5]->handler() instanceof SecurityCheckCommandHandler
            || KongoCommand::SecurityInspect->value !== $bindings[6]->identifier()->value()
            || !$bindings[6]->handler() instanceof SecurityInspectCommandHandler
            || KongoCommand::ViewBuild->value !== $bindings[7]->identifier()->value()
            || !$bindings[7]->handler() instanceof ViewBuildCommandHandler
            || KongoCommand::ViewCheck->value !== $bindings[8]->identifier()->value()
            || !$bindings[8]->handler() instanceof ViewCheckCommandHandler
            || KongoCommand::ViewInspect->value !== $bindings[9]->identifier()->value()
            || !$bindings[9]->handler() instanceof ViewInspectCommandHandler
        ) {
            throw InvalidConsoleApplicationValueException::invalidCommandFamily();
        }
    }

    /** @param list<string> $inputNames */
    private function validateSpecification(
        \Kongono\Console\CommandSpecification $specification,
        KongoCommand $command,
        string $summary,
        CommandMutationPolicy $mutationPolicy,
        array $inputNames = ['application-root', 'output-directory'],
    ): void {
        $definition = $specification->definition();
        $inputs = $specification->inputs();
        if (
            $command->value !== $definition->identifier()->value()
            || $summary !== $definition->summary()->value()
            || CommandInteractionPolicy::None !== $definition->interactionPolicy()
            || $mutationPolicy !== $definition->mutationPolicy()
            || count($inputNames) !== count($inputs)
        ) {
            throw InvalidConsoleApplicationValueException::invalidCommandFamily();
        }
        foreach ($inputNames as $position => $inputName) {
            if (
                $inputName !== $inputs[$position]->name()->value()
                || CommandInputRequirement::Required !== $inputs[$position]->requirement()
                || CommandInputSensitivity::Ordinary !== $inputs[$position]->sensitivity()
            ) {
                throw InvalidConsoleApplicationValueException::invalidCommandFamily();
            }
        }
    }
}
