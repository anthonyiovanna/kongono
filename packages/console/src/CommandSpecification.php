<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Combines one command meaning with its closed deterministic input specification. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandSpecification
{
    /** @var list<CommandInputDefinition> */
    private array $inputs;

    /** @param array<array-key, mixed> $inputs */
    public function __construct(private CommandDefinition $definition, array $inputs)
    {
        if (count($inputs) > 64) {
            throw InvalidConsoleValueException::specification();
        }

        $accepted = [];
        $names = [];
        foreach ($inputs as $input) {
            if (!$input instanceof CommandInputDefinition) {
                throw InvalidConsoleValueException::specification();
            }

            $name = $input->name()->value();
            if (isset($names[$name])) {
                throw InvalidConsoleValueException::specification();
            }

            $names[$name] = true;
            $accepted[] = $input;
        }
        usort($accepted, static fn(CommandInputDefinition $left, CommandInputDefinition $right): int => strcmp($left->name()->value(), $right->name()->value()));
        $this->inputs = $accepted;
    }

    public function definition(): CommandDefinition
    {
        return $this->definition;
    }

    /** @return list<CommandInputDefinition> */
    public function inputs(): array
    {
        return $this->inputs;
    }

    public function input(CommandInputName $name): ?CommandInputDefinition
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
