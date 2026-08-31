<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Handler;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandHandler;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandOperation;
use Kongono\Console\CommandResult;
use Kongono\Console\CommandResultField;
use Kongono\Console\CommandResultFieldDisclosure;
use Kongono\Console\CommandResultFieldLabel;
use Kongono\Console\CommandResultFieldName;
use Kongono\Console\CommandResultRecord;
use Kongono\Console\CommandResultValue;
use Kongono\ConsoleApplication\Diagnostic\ConsoleApplicationDiagnostic;
use Kongono\ConsoleApplication\KongoCommand;
use Kongono\ExtensionContract\Contribution\ContributionVisibility;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionInspection\Exception\ExceptionInterface as ExtensionInspectionException;
use Kongono\ExtensionInspection\Loading\ExtensionInspectionLoader;
use Throwable;

/** Projects one explicit detached extension inspection without loading generated PHP. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionInspectCommandHandler implements CommandHandler
{
    private const string DECLARATION_PATH = 'declaration-artifact-path';
    private const string WIRING_PATH = 'generated-wiring-path';

    public function __construct(private ExtensionInspectionLoader $loader) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        $invocation = $operation->invocation();
        if (KongoCommand::ExtensionInspect->value !== $invocation->definition()->identifier()->value() || CommandMutationPolicy::ReadOnly !== $invocation->definition()->mutationPolicy() || CommandInteractionMode::Disabled !== $invocation->interactionMode() || CommandExecutionIntent::Observe !== $invocation->executionIntent() || 2 !== count($invocation->inputs())) {
            return $this->invalid();
        }
        $declarationPath = $invocation->input(new CommandInputName(self::DECLARATION_PATH));
        $wiringPath = $invocation->input(new CommandInputName(self::WIRING_PATH));
        if (null === $declarationPath || null === $wiringPath) {
            return $this->invalid();
        }
        try {
            $inspection = $this->loader->load($declarationPath->value()->reveal(), $wiringPath->value()->reveal());
        } catch (ExtensionInspectionException) {
            return $this->precondition();
        } catch (\Error $failure) {
            throw $failure;
        } catch (Throwable) {
            return $this->infrastructure();
        }

        $fields = [
            $this->text('schema', 'Schema', 'kongono.extension-platform-inspection'),
            $this->integer('version', 'Version', 1),
            $this->text('declaration_artifact_sha256', 'Declaration artifact SHA-256', $inspection->declarationArtifactSha256()),
            $this->text('declaration_set_identity', 'Declaration-set identity', $inspection->declarationSetIdentity()->value()),
            $this->text('generated_wiring_sha256', 'Generated wiring SHA-256', $inspection->generatedWiringSha256()),
            $this->text('admission_identity', 'Admission identity', $inspection->admissionIdentity()),
            $this->text('grant_plan_identity', 'Grant-plan identity', $inspection->grantPlanIdentity()),
            $this->integer('extension_contract_version', 'Extension contract version', $inspection->extensionContractVersion()->value),
            $this->integer('declaration_count', 'Declaration count', $inspection->declarationCount()),
            $this->integer('implementation_count', 'Implementation count', $inspection->implementationCount()),
            $this->integer('decision_count', 'Decision count', $inspection->decisionCount()),
            $this->integer('application_provider_count', 'Application provider count', $inspection->applicationProviderCount()),
            $this->integer('operation_provider_count', 'Operation provider count', $inspection->operationProviderCount()),
        ];
        foreach (ExtensionCategory::cases() as $case) {
            $fields[] = $this->integer('category_' . $case->value . '_count', 'Category ' . $case->value . ' count', $inspection->categoryCount($case));
        }
        foreach (ExtensionLifecycle::cases() as $case) {
            $fields[] = $this->integer('lifecycle_' . $case->value . '_count', 'Lifecycle ' . $case->value . ' count', $inspection->lifecycleCount($case));
        }
        $fields[] = $this->integer('capability_request_count', 'Capability request count', $inspection->capabilityRequestCount());
        $fields[] = $this->integer('required_capability_request_count', 'Required capability request count', $inspection->requiredCapabilityRequestCount());
        $fields[] = $this->integer('optional_capability_request_count', 'Optional capability request count', $inspection->optionalCapabilityRequestCount());
        $fields[] = $this->integer('contribution_count', 'Contribution count', $inspection->contributionCount());
        foreach (ContributionVisibility::cases() as $case) {
            $fields[] = $this->integer('contribution_visibility_' . $case->value . '_count', 'Contribution visibility ' . $case->value . ' count', $inspection->contributionVisibilityCount($case));
        }
        $fields[] = $this->text('scope', 'Scope', $inspection->scope());
        $fields[] = $this->text('visibility', 'Visibility', $inspection->visibility());
        $fields[] = $this->text('currentness', 'Currentness', $inspection->currentness());
        $fields[] = $this->text('authority', 'Authority', $inspection->authority());
        $fields[] = $this->text('host_state', 'Host state', $inspection->hostState());
        $fields[] = $this->text('execution', 'Execution', $inspection->execution());
        $fields[] = $this->text('trusted_code_isolation', 'Trusted-code isolation', $inspection->trustedCodeIsolation());
        return CommandResult::succeeded(new CommandResultRecord($fields));
    }

    private function invalid(): CommandResult
    {
        return CommandResult::rejected(new CommandFailure(CommandFailureCategory::InvalidInput, ConsoleApplicationDiagnostic::InvalidProcessRequest->value));
    }
    private function precondition(): CommandResult
    {
        return CommandResult::rejected(new CommandFailure(CommandFailureCategory::Precondition, ConsoleApplicationDiagnostic::ExtensionInspectionPrecondition->value));
    }
    private function infrastructure(): CommandResult
    {
        return CommandResult::failed(new CommandFailure(CommandFailureCategory::Infrastructure, ConsoleApplicationDiagnostic::ApplicationFailure->value));
    }
    private function text(string $name, string $label, string $value): CommandResultField
    {
        return $this->field($name, $label, CommandResultValue::text($value));
    }
    private function integer(string $name, string $label, int $value): CommandResultField
    {
        return $this->field($name, $label, CommandResultValue::integer($value));
    }
    private function field(string $name, string $label, CommandResultValue $value): CommandResultField
    {
        return new CommandResultField(new CommandResultFieldName($name), new CommandResultFieldLabel($label), $value, CommandResultFieldDisclosure::Safe);
    }
}
