<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Stores an explicit immutable command set without discovery or registration. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandSpecificationSet
{
    /** @var list<CommandSpecification> */
    private array $commands;

    /** @param array<array-key, mixed> $commands */
    public function __construct(array $commands)
    {
        if ([] === $commands || count($commands) > 256) {
            throw InvalidConsoleValueException::specification();
        }

        $accepted = [];
        $identifiers = [];
        foreach ($commands as $command) {
            if (!$command instanceof CommandSpecification) {
                throw InvalidConsoleValueException::specification();
            }

            $identifier = $command->definition()->identifier()->value();
            if (isset($identifiers[$identifier])) {
                throw InvalidConsoleValueException::specification();
            }

            $identifiers[$identifier] = true;
            $accepted[] = $command;
        }
        usort($accepted, static fn(CommandSpecification $left, CommandSpecification $right): int => strcmp($left->definition()->identifier()->value(), $right->definition()->identifier()->value()));
        $this->commands = $accepted;
    }

    /** @return list<CommandSpecification> */
    public function commands(): array
    {
        return $this->commands;
    }

    public function command(CommandIdentifier $identifier): ?CommandSpecification
    {
        foreach ($this->commands as $command) {
            if ($command->definition()->identifier()->equals($identifier)) {
                return $command;
            }
        }

        return null;
    }

    public function hasCommand(CommandIdentifier $identifier): bool
    {
        return null !== $this->command($identifier);
    }
}
