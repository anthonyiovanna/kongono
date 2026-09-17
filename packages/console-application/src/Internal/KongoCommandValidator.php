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
use Kongono\ConsoleApplication\Handler\AssetBuildCommandHandler;
use Kongono\ConsoleApplication\Handler\AssetCheckCommandHandler;
use Kongono\ConsoleApplication\Handler\AssetResolveCommandHandler;
use Kongono\ConsoleApplication\Handler\CacheBuildCommandHandler;
use Kongono\ConsoleApplication\Handler\CacheCheckCommandHandler;
use Kongono\ConsoleApplication\Handler\CachePlanCommandHandler;
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

/** Fails closed unless definitions and handlers match the exact supported family. */
#[Api(ApiStatus::Internal)]
final class KongoCommandValidator
{
    public function validate(CommandSpecificationSet $specifications, CommandHandlerSet $handlers): void
    {
        $commands = $specifications->commands();
        $bindings = $handlers->bindings();
        if (16 !== count($commands) || 16 !== count($bindings)) {
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
            KongoCommand::AssetBuild,
            'Build exact current deterministic native assets',
            CommandMutationPolicy::MutationCapable,
            ['application-root', 'output-directory', 'source-file'],
        );
        $this->validateSpecification(
            $commands[3],
            KongoCommand::AssetCheck,
            'Check exact current deterministic native assets',
            CommandMutationPolicy::ReadOnly,
            ['application-root', 'output-directory', 'source-file'],
        );
        $this->validateSpecification(
            $commands[4],
            KongoCommand::AssetResolve,
            'Resolve one exact current asset under explicit browser policy',
            CommandMutationPolicy::ReadOnly,
            ['application-root', 'cache-policy', 'integrity-policy', 'logical-identity', 'output-directory', 'preload-policy', 'public-base-url', 'source-file'],
        );
        $this->validateSpecification(
            $commands[5],
            KongoCommand::CacheBuild,
            'Build exact current deterministic cache profiles',
            CommandMutationPolicy::MutationCapable,
            ['application-root', 'output-directory', 'source-file'],
        );
        $this->validateSpecification(
            $commands[6],
            KongoCommand::CacheCheck,
            'Check exact current deterministic cache profiles',
            CommandMutationPolicy::ReadOnly,
            ['application-root', 'output-directory', 'source-file'],
        );
        $this->validateSpecification(
            $commands[7],
            KongoCommand::CachePlan,
            'Plan deterministic cache profiles without mutation',
            CommandMutationPolicy::ReadOnly,
            ['application-root', 'output-directory', 'source-file'],
        );
        $this->validateSpecification(
            $commands[8],
            KongoCommand::ExtensionInspect,
            'Inspect detached extension platform evidence',
            CommandMutationPolicy::ReadOnly,
            ['declaration-artifact-path', 'generated-wiring-path'],
        );
        $this->validateSpecification(
            $commands[9],
            KongoCommand::ObservabilityCheck,
            'Check exact current observability configuration evidence',
            CommandMutationPolicy::ReadOnly,
        );
        $this->validateSpecification(
            $commands[10],
            KongoCommand::ObservabilityInspect,
            'Inspect planned observability configuration evidence',
            CommandMutationPolicy::ReadOnly,
        );
        $this->validateSpecification(
            $commands[11],
            KongoCommand::SecurityCheck,
            'Check exact current security policy evidence',
            CommandMutationPolicy::ReadOnly,
        );
        $this->validateSpecification(
            $commands[12],
            KongoCommand::SecurityInspect,
            'Inspect planned security policy evidence',
            CommandMutationPolicy::ReadOnly,
        );
        $this->validateSpecification($commands[13], KongoCommand::ViewBuild, 'Build exact current compiled-view application artifact', CommandMutationPolicy::MutationCapable, ['application-root', 'output-directory', 'source-file']);
        $this->validateSpecification($commands[14], KongoCommand::ViewCheck, 'Check exact current compiled-view application artifact', CommandMutationPolicy::ReadOnly, ['application-root', 'output-directory', 'source-file']);
        $this->validateSpecification($commands[15], KongoCommand::ViewInspect, 'Inspect planned compiled-view application evidence', CommandMutationPolicy::ReadOnly, ['application-root', 'output-directory', 'source-file']);
        if (
            KongoCommand::ApplicationGraphInspect->value !== $bindings[0]->identifier()->value()
            || !$bindings[0]->handler() instanceof ApplicationGraphInspectCommandHandler
            || KongoCommand::ApplicationBuild->value !== $bindings[1]->identifier()->value()
            || !$bindings[1]->handler() instanceof ApplicationBuildCommandHandler
            || KongoCommand::AssetBuild->value !== $bindings[2]->identifier()->value()
            || !$bindings[2]->handler() instanceof AssetBuildCommandHandler
            || KongoCommand::AssetCheck->value !== $bindings[3]->identifier()->value()
            || !$bindings[3]->handler() instanceof AssetCheckCommandHandler
            || KongoCommand::AssetResolve->value !== $bindings[4]->identifier()->value()
            || !$bindings[4]->handler() instanceof AssetResolveCommandHandler
            || KongoCommand::CacheBuild->value !== $bindings[5]->identifier()->value()
            || !$bindings[5]->handler() instanceof CacheBuildCommandHandler
            || KongoCommand::CacheCheck->value !== $bindings[6]->identifier()->value()
            || !$bindings[6]->handler() instanceof CacheCheckCommandHandler
            || KongoCommand::CachePlan->value !== $bindings[7]->identifier()->value()
            || !$bindings[7]->handler() instanceof CachePlanCommandHandler
            || KongoCommand::ExtensionInspect->value !== $bindings[8]->identifier()->value()
            || !$bindings[8]->handler() instanceof ExtensionInspectCommandHandler
            || KongoCommand::ObservabilityCheck->value !== $bindings[9]->identifier()->value()
            || !$bindings[9]->handler() instanceof ObservabilityCheckCommandHandler
            || KongoCommand::ObservabilityInspect->value !== $bindings[10]->identifier()->value()
            || !$bindings[10]->handler() instanceof ObservabilityInspectCommandHandler
            || KongoCommand::SecurityCheck->value !== $bindings[11]->identifier()->value()
            || !$bindings[11]->handler() instanceof SecurityCheckCommandHandler
            || KongoCommand::SecurityInspect->value !== $bindings[12]->identifier()->value()
            || !$bindings[12]->handler() instanceof SecurityInspectCommandHandler
            || KongoCommand::ViewBuild->value !== $bindings[13]->identifier()->value()
            || !$bindings[13]->handler() instanceof ViewBuildCommandHandler
            || KongoCommand::ViewCheck->value !== $bindings[14]->identifier()->value()
            || !$bindings[14]->handler() instanceof ViewCheckCommandHandler
            || KongoCommand::ViewInspect->value !== $bindings[15]->identifier()->value()
            || !$bindings[15]->handler() instanceof ViewInspectCommandHandler
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
