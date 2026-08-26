<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandSpecificationSet;
use Kongono\ConsoleCommands\Handler\CompositionInspectCommandHandler;
use Kongono\ConsoleCommands\Handler\GraphInspectCommandHandler;
use Kongono\ConsoleCommands\Handler\QualityInspectCommandHandler;
use Kongono\ConsoleCommands\Internal\ReadOnlyCommandFamilyValidator;
use Kongono\ConsoleRuntime\CommandHandlerBinding;
use Kongono\ConsoleRuntime\CommandHandlerSet;

/** Owns the exact validated definitions and typed bindings for Phase 8F. */
#[Api(ApiStatus::Experimental)]
final readonly class ReadOnlyFrameworkCommandFamily
{
    private CommandHandlerSet $handlers;

    public function __construct(
        private CommandSpecificationSet $specifications,
        CompositionInspectCommandHandler $composition,
        GraphInspectCommandHandler $graph,
        QualityInspectCommandHandler $quality,
    ) {
        (new ReadOnlyCommandFamilyValidator())->validate($specifications);

        $this->handlers = new CommandHandlerSet([
            new CommandHandlerBinding(new CommandIdentifier(ReadOnlyFrameworkCommand::Composition->value), $composition),
            new CommandHandlerBinding(new CommandIdentifier(ReadOnlyFrameworkCommand::Graph->value), $graph),
            new CommandHandlerBinding(new CommandIdentifier(ReadOnlyFrameworkCommand::Quality->value), $quality),
        ]);
    }

    public function specifications(): CommandSpecificationSet
    {
        return $this->specifications;
    }

    public function handlers(): CommandHandlerSet
    {
        return $this->handlers;
    }
}
