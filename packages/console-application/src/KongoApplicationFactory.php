<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInputDefinition;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInputRequirement;
use Kongono\Console\CommandInputSensitivity;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandSpecification;
use Kongono\Console\CommandSpecificationSet;
use Kongono\Console\CommandSummary;
use Kongono\Console\CommandTokenParser;
use Kongono\ConsoleApplication\Handler\ApplicationBuildCommandHandler;
use Kongono\ConsoleApplication\Internal\KongoCommandValidator;
use Kongono\ConsolePresentation\CommandPresentationAdapter;
use Kongono\ConsolePresentation\HumanCommandPresenter;
use Kongono\ConsolePresentation\MachineCommandPresenter;
use Kongono\ConsoleRuntime\CommandDispatcher;
use Kongono\ConsoleRuntime\CommandHandlerBinding;
use Kongono\ConsoleRuntime\CommandHandlerSet;

/** Constructs the closed supported application without discovery or service location. */
#[Api(ApiStatus::Experimental)]
final class KongoApplicationFactory
{
    public function create(): KongoApplication
    {
        $identifier = new CommandIdentifier(KongoCommand::ApplicationBuild->value);
        $specifications = new CommandSpecificationSet([
            new CommandSpecification(
                new CommandDefinition(
                    $identifier,
                    new CommandSummary('Build exact current application composition outputs'),
                    CommandInteractionPolicy::None,
                    CommandMutationPolicy::MutationCapable,
                ),
                [
                    new CommandInputDefinition(new CommandInputName('application-root'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                    new CommandInputDefinition(new CommandInputName('output-directory'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                ],
            ),
        ]);
        $handlers = new CommandHandlerSet([
            new CommandHandlerBinding($identifier, new ApplicationBuildCommandHandler(new ApplicationBuildWorkflow())),
        ]);
        (new KongoCommandValidator())->validate($specifications, $handlers);

        return new KongoApplication(
            $specifications,
            new CommandTokenParser(),
            new CommandDispatcher($handlers),
            new HumanCommandPresenter(),
            new MachineCommandPresenter(),
            new CommandPresentationAdapter(),
        );
    }
}
