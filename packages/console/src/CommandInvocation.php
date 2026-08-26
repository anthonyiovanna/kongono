<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Stores one detached invocation after parsing and normalization have occurred elsewhere. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandInvocation
{
    /** @var list<CommandInput> */
    private array $inputs;

    /** @param array<array-key, mixed> $inputs */
    public function __construct(
        private CommandDefinition $definition,
        array $inputs,
        private CommandExecutionIntent $executionIntent,
        private CommandInteractionMode $interactionMode,
    ) {
        if (count($inputs) > 64) {
            throw InvalidConsoleValueException::invocation();
        }

        $detachedInputs = [];
        $names = [];
        foreach ($inputs as $input) {
            if (!$input instanceof CommandInput) {
                throw InvalidConsoleValueException::invocation();
            }

            $name = $input->name()->value();
            if (array_key_exists($name, $names)) {
                throw InvalidConsoleValueException::invocation();
            }

            $names[$name] = true;
            $detachedInputs[] = $input;
        }

        if (
            (CommandMutationPolicy::ReadOnly === $definition->mutationPolicy()
                && CommandExecutionIntent::Observe !== $executionIntent)
            || (CommandMutationPolicy::MutationCapable === $definition->mutationPolicy()
                && CommandExecutionIntent::Observe === $executionIntent)
            || (CommandInteractionPolicy::None === $definition->interactionPolicy()
                && CommandInteractionMode::Disabled !== $interactionMode)
        ) {
            throw InvalidConsoleValueException::invocation();
        }

        $this->inputs = $detachedInputs;
    }

    public function definition(): CommandDefinition
    {
        return $this->definition;
    }

    /** @return list<CommandInput> */
    public function inputs(): array
    {
        return $this->inputs;
    }

    public function executionIntent(): CommandExecutionIntent
    {
        return $this->executionIntent;
    }

    public function interactionMode(): CommandInteractionMode
    {
        return $this->interactionMode;
    }

    public function input(CommandInputName $name): ?CommandInput
    {
        foreach ($this->inputs as $input) {
            if ($input->name()->value() === $name->value()) {
                return $input;
            }
        }

        return null;
    }

    public function hasInput(CommandInputName $name): bool
    {
        return null !== $this->input($name);
    }
}
