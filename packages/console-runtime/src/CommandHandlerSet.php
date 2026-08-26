<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandIdentifier;
use Kongono\ConsoleRuntime\Exception\InvalidCommandRuntimeValueException;

/** Stores a bounded immutable set of explicit handler bindings in ordinal order. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandHandlerSet
{
    /** @var list<CommandHandlerBinding> */
    private array $bindings;

    /** @var array<string, CommandHandlerBinding> */
    private array $bindingsByIdentifier;

    /** @param array<array-key, mixed> $bindings */
    public function __construct(array $bindings)
    {
        if ([] === $bindings || count($bindings) > 256) {
            throw InvalidCommandRuntimeValueException::handlerSet();
        }

        $detached = [];
        $byIdentifier = [];
        foreach ($bindings as $binding) {
            if (!$binding instanceof CommandHandlerBinding) {
                throw InvalidCommandRuntimeValueException::handlerSet();
            }

            $identifier = $binding->identifier()->value();
            if (array_key_exists($identifier, $byIdentifier)) {
                throw InvalidCommandRuntimeValueException::handlerSet();
            }

            $detached[] = $binding;
            $byIdentifier[$identifier] = $binding;
        }

        usort(
            $detached,
            static fn(CommandHandlerBinding $left, CommandHandlerBinding $right): int => strcmp(
                $left->identifier()->value(),
                $right->identifier()->value(),
            ),
        );

        $this->bindings = $detached;
        $this->bindingsByIdentifier = $byIdentifier;
    }

    /** @return list<CommandHandlerBinding> */
    public function bindings(): array
    {
        return $this->bindings;
    }

    public function binding(CommandIdentifier $identifier): ?CommandHandlerBinding
    {
        return $this->bindingsByIdentifier[$identifier->value()] ?? null;
    }

    public function hasBinding(CommandIdentifier $identifier): bool
    {
        return array_key_exists($identifier->value(), $this->bindingsByIdentifier);
    }
}
