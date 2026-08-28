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
use Kongono\ConsoleApplication\Handler\ObservabilityCheckCommandHandler;
use Kongono\ConsoleApplication\Handler\ObservabilityInspectCommandHandler;
use Kongono\ConsoleApplication\Handler\SecurityCheckCommandHandler;
use Kongono\ConsoleApplication\Handler\SecurityInspectCommandHandler;
use Kongono\ConsoleApplication\Internal\KongoCommandValidator;
use Kongono\ConsolePresentation\CommandPresentationAdapter;
use Kongono\ConsolePresentation\HumanCommandPresenter;
use Kongono\ConsolePresentation\MachineCommandPresenter;
use Kongono\ConsoleRuntime\CommandDispatcher;
use Kongono\ConsoleRuntime\CommandHandlerBinding;
use Kongono\ConsoleRuntime\CommandHandlerSet;
use Kongono\ObservabilityBuild\Workflow\ObservabilityBuildWorkflow;
use Kongono\SecurityPolicyBuild\Workflow\SecurityPolicyBuildWorkflow;

/** Constructs the closed supported application without discovery or service location. */
#[Api(ApiStatus::Experimental)]
final class KongoApplicationFactory
{
    public function create(): KongoApplication
    {
        $applicationBuild = new CommandIdentifier(KongoCommand::ApplicationBuild->value);
        $observabilityCheck = new CommandIdentifier(KongoCommand::ObservabilityCheck->value);
        $observabilityInspect = new CommandIdentifier(KongoCommand::ObservabilityInspect->value);
        $securityCheck = new CommandIdentifier(KongoCommand::SecurityCheck->value);
        $securityInspect = new CommandIdentifier(KongoCommand::SecurityInspect->value);
        $specifications = new CommandSpecificationSet([
            new CommandSpecification(
                new CommandDefinition(
                    $applicationBuild,
                    new CommandSummary('Build exact current application composition outputs'),
                    CommandInteractionPolicy::None,
                    CommandMutationPolicy::MutationCapable,
                ),
                [
                    new CommandInputDefinition(new CommandInputName('application-root'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                    new CommandInputDefinition(new CommandInputName('output-directory'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                ],
            ),
            $this->readOnlySpecification($observabilityCheck, 'Check exact current observability configuration evidence'),
            $this->readOnlySpecification($observabilityInspect, 'Inspect planned observability configuration evidence'),
            $this->readOnlySpecification($securityCheck, 'Check exact current security policy evidence'),
            $this->readOnlySpecification($securityInspect, 'Inspect planned security policy evidence'),
        ]);
        $observabilityWorkflow = new ObservabilityBuildWorkflow();
        $policyWorkflow = new SecurityPolicyBuildWorkflow();
        $handlers = new CommandHandlerSet([
            new CommandHandlerBinding($applicationBuild, new ApplicationBuildCommandHandler(new ApplicationBuildWorkflow())),
            new CommandHandlerBinding($observabilityCheck, new ObservabilityCheckCommandHandler($observabilityWorkflow)),
            new CommandHandlerBinding($observabilityInspect, new ObservabilityInspectCommandHandler($observabilityWorkflow)),
            new CommandHandlerBinding($securityCheck, new SecurityCheckCommandHandler($policyWorkflow)),
            new CommandHandlerBinding($securityInspect, new SecurityInspectCommandHandler($policyWorkflow)),
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

    private function readOnlySpecification(CommandIdentifier $identifier, string $summary): CommandSpecification
    {
        return new CommandSpecification(
            new CommandDefinition(
                $identifier,
                new CommandSummary($summary),
                CommandInteractionPolicy::None,
                CommandMutationPolicy::ReadOnly,
            ),
            [
                new CommandInputDefinition(new CommandInputName('application-root'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                new CommandInputDefinition(new CommandInputName('output-directory'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
            ],
        );
    }
}
