<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactLoader;
use Kongono\AssetApplication\Workflow\AssetApplicationWorkflow;
use Kongono\CacheApplication\Workflow\CacheApplicationWorkflow;
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
use Kongono\ConsoleApplication\Internal\KongoCommandValidator;
use Kongono\ConsolePresentation\CommandPresentationAdapter;
use Kongono\ConsolePresentation\HumanCommandPresenter;
use Kongono\ConsolePresentation\MachineCommandPresenter;
use Kongono\ConsoleRuntime\CommandDispatcher;
use Kongono\ConsoleRuntime\CommandHandlerBinding;
use Kongono\ConsoleRuntime\CommandHandlerSet;
use Kongono\ExtensionInspection\Loading\ExtensionInspectionLoader;
use Kongono\ObservabilityBuild\Workflow\ObservabilityBuildWorkflow;
use Kongono\SecurityPolicyBuild\Workflow\SecurityPolicyBuildWorkflow;
use Kongono\ViewApplication\Workflow\ViewApplicationBuildWorkflow;

/** Constructs the closed supported application without discovery or service location. */
#[Api(ApiStatus::Experimental)]
final class KongoApplicationFactory
{
    public function create(): KongoApplication
    {
        $applicationGraphInspect = new CommandIdentifier(KongoCommand::ApplicationGraphInspect->value);
        $applicationBuild = new CommandIdentifier(KongoCommand::ApplicationBuild->value);
        $assetBuild = new CommandIdentifier(KongoCommand::AssetBuild->value);
        $assetCheck = new CommandIdentifier(KongoCommand::AssetCheck->value);
        $assetResolve = new CommandIdentifier(KongoCommand::AssetResolve->value);
        $cacheBuild = new CommandIdentifier(KongoCommand::CacheBuild->value);
        $cacheCheck = new CommandIdentifier(KongoCommand::CacheCheck->value);
        $cachePlan = new CommandIdentifier(KongoCommand::CachePlan->value);
        $extensionInspect = new CommandIdentifier(KongoCommand::ExtensionInspect->value);
        $observabilityCheck = new CommandIdentifier(KongoCommand::ObservabilityCheck->value);
        $observabilityInspect = new CommandIdentifier(KongoCommand::ObservabilityInspect->value);
        $securityCheck = new CommandIdentifier(KongoCommand::SecurityCheck->value);
        $securityInspect = new CommandIdentifier(KongoCommand::SecurityInspect->value);
        $viewBuild = new CommandIdentifier(KongoCommand::ViewBuild->value);
        $viewCheck = new CommandIdentifier(KongoCommand::ViewCheck->value);
        $viewInspect = new CommandIdentifier(KongoCommand::ViewInspect->value);
        $specifications = new CommandSpecificationSet([
            new CommandSpecification(
                new CommandDefinition(
                    $applicationGraphInspect,
                    new CommandSummary('Inspect one authoritative application graph artifact'),
                    CommandInteractionPolicy::None,
                    CommandMutationPolicy::ReadOnly,
                ),
                [
                    new CommandInputDefinition(new CommandInputName('artifact-path'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                ],
            ),
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
            $this->assetSpecification($assetBuild, 'Build exact current deterministic native assets', CommandMutationPolicy::MutationCapable),
            $this->assetSpecification($assetCheck, 'Check exact current deterministic native assets', CommandMutationPolicy::ReadOnly),
            $this->assetResolveSpecification($assetResolve),
            new CommandSpecification(
                new CommandDefinition(
                    $extensionInspect,
                    new CommandSummary('Inspect detached extension platform evidence'),
                    CommandInteractionPolicy::None,
                    CommandMutationPolicy::ReadOnly,
                ),
                [
                    new CommandInputDefinition(new CommandInputName('declaration-artifact-path'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                    new CommandInputDefinition(new CommandInputName('generated-wiring-path'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                ],
            ),
            $this->readOnlySpecification($observabilityCheck, 'Check exact current observability configuration evidence'),
            $this->readOnlySpecification($observabilityInspect, 'Inspect planned observability configuration evidence'),
            $this->readOnlySpecification($securityCheck, 'Check exact current security policy evidence'),
            $this->readOnlySpecification($securityInspect, 'Inspect planned security policy evidence'),
            $this->viewSpecification($viewBuild, 'Build exact current compiled-view application artifact', CommandMutationPolicy::MutationCapable),
            $this->viewSpecification($viewCheck, 'Check exact current compiled-view application artifact', CommandMutationPolicy::ReadOnly),
            $this->viewSpecification($viewInspect, 'Inspect planned compiled-view application evidence', CommandMutationPolicy::ReadOnly),
            $this->cacheSpecification($cacheBuild, 'Build exact current deterministic cache profiles', CommandMutationPolicy::MutationCapable),
            $this->cacheSpecification($cacheCheck, 'Check exact current deterministic cache profiles', CommandMutationPolicy::ReadOnly),
            $this->cacheSpecification($cachePlan, 'Plan deterministic cache profiles without mutation', CommandMutationPolicy::ReadOnly),
        ]);
        $observabilityWorkflow = new ObservabilityBuildWorkflow();
        $policyWorkflow = new SecurityPolicyBuildWorkflow();
        $assetWorkflow = new AssetApplicationWorkflow();
        $cacheWorkflow = new CacheApplicationWorkflow();
        $viewWorkflow = new ViewApplicationBuildWorkflow();
        $handlers = new CommandHandlerSet([
            new CommandHandlerBinding($applicationGraphInspect, new ApplicationGraphInspectCommandHandler(new ApplicationGraphArtifactLoader())),
            new CommandHandlerBinding($applicationBuild, new ApplicationBuildCommandHandler(new ApplicationBuildWorkflow())),
            new CommandHandlerBinding($assetBuild, new AssetBuildCommandHandler($assetWorkflow)),
            new CommandHandlerBinding($assetCheck, new AssetCheckCommandHandler($assetWorkflow)),
            new CommandHandlerBinding($assetResolve, new AssetResolveCommandHandler($assetWorkflow)),
            new CommandHandlerBinding($extensionInspect, new ExtensionInspectCommandHandler(new ExtensionInspectionLoader())),
            new CommandHandlerBinding($observabilityCheck, new ObservabilityCheckCommandHandler($observabilityWorkflow)),
            new CommandHandlerBinding($observabilityInspect, new ObservabilityInspectCommandHandler($observabilityWorkflow)),
            new CommandHandlerBinding($securityCheck, new SecurityCheckCommandHandler($policyWorkflow)),
            new CommandHandlerBinding($securityInspect, new SecurityInspectCommandHandler($policyWorkflow)),
            new CommandHandlerBinding($viewBuild, new ViewBuildCommandHandler($viewWorkflow)),
            new CommandHandlerBinding($viewCheck, new ViewCheckCommandHandler($viewWorkflow)),
            new CommandHandlerBinding($viewInspect, new ViewInspectCommandHandler($viewWorkflow)),
            new CommandHandlerBinding($cacheBuild, new CacheBuildCommandHandler($cacheWorkflow)),
            new CommandHandlerBinding($cacheCheck, new CacheCheckCommandHandler($cacheWorkflow)),
            new CommandHandlerBinding($cachePlan, new CachePlanCommandHandler($cacheWorkflow)),
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

    private function viewSpecification(CommandIdentifier $identifier, string $summary, CommandMutationPolicy $mutation): CommandSpecification
    {
        return new CommandSpecification(
            new CommandDefinition($identifier, new CommandSummary($summary), CommandInteractionPolicy::None, $mutation),
            [
                new CommandInputDefinition(new CommandInputName('application-root'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                new CommandInputDefinition(new CommandInputName('output-directory'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                new CommandInputDefinition(new CommandInputName('source-file'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
            ],
        );
    }

    private function assetSpecification(CommandIdentifier $identifier, string $summary, CommandMutationPolicy $mutation): CommandSpecification
    {
        return new CommandSpecification(
            new CommandDefinition($identifier, new CommandSummary($summary), CommandInteractionPolicy::None, $mutation),
            [
                new CommandInputDefinition(new CommandInputName('application-root'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                new CommandInputDefinition(new CommandInputName('output-directory'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                new CommandInputDefinition(new CommandInputName('source-file'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
            ],
        );
    }

    private function assetResolveSpecification(CommandIdentifier $identifier): CommandSpecification
    {
        $inputs = [];
        foreach (['application-root', 'output-directory', 'source-file', 'logical-identity', 'public-base-url', 'cache-policy', 'integrity-policy', 'preload-policy'] as $name) {
            $inputs[] = new CommandInputDefinition(new CommandInputName($name), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary);
        }

        return new CommandSpecification(
            new CommandDefinition($identifier, new CommandSummary('Resolve one exact current asset under explicit browser policy'), CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly),
            $inputs,
        );
    }

    private function cacheSpecification(CommandIdentifier $identifier, string $summary, CommandMutationPolicy $mutation): CommandSpecification
    {
        return new CommandSpecification(
            new CommandDefinition($identifier, new CommandSummary($summary), CommandInteractionPolicy::None, $mutation),
            [
                new CommandInputDefinition(new CommandInputName('application-root'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                new CommandInputDefinition(new CommandInputName('output-directory'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
                new CommandInputDefinition(new CommandInputName('source-file'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
            ],
        );
    }
}
