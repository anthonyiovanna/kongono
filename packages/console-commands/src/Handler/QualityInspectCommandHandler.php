<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Handler;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandHandler;
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
use Kongono\ConsoleCommands\Diagnostic\ConsoleCommandsDiagnostic;
use Kongono\ConsoleCommands\ReadOnlyFrameworkCommand;
use Kongono\Hantei\HanteiCheckOutcome;
use Kongono\Hantei\HanteiReport;

/** Projects only verdict and outcome counts from one injected immutable report. */
#[Api(ApiStatus::Experimental)]
final readonly class QualityInspectCommandHandler implements CommandHandler
{
    public function __construct(private HanteiReport $report) {}

    public function execute(CommandOperation $operation): CommandResult
    {
        if (!$this->accepts($operation)) {
            return CommandResult::rejected(new CommandFailure(
                CommandFailureCategory::InvalidInput,
                ConsoleCommandsDiagnostic::InvalidInvocation->value,
            ));
        }

        $counts = [
            HanteiCheckOutcome::Blocked->value => 0,
            HanteiCheckOutcome::Fail->value => 0,
            HanteiCheckOutcome::Pass->value => 0,
        ];
        foreach ($this->report->checks() as $check) {
            ++$counts[$check->outcome()->value];
        }

        return CommandResult::succeeded(new CommandResultRecord([
            $this->integerField('blocked_count', 'Blocked checks', $counts[HanteiCheckOutcome::Blocked->value]),
            $this->integerField('check_count', 'Checks', count($this->report->checks())),
            $this->integerField('fail_count', 'Failed checks', $counts[HanteiCheckOutcome::Fail->value]),
            $this->integerField('pass_count', 'Passed checks', $counts[HanteiCheckOutcome::Pass->value]),
            $this->textField('verdict', 'Verdict', $this->report->verdict()->value),
        ]));
    }

    private function accepts(CommandOperation $operation): bool
    {
        $invocation = $operation->invocation();

        return ReadOnlyFrameworkCommand::Quality->value === $invocation->definition()->identifier()->value()
            && CommandExecutionIntent::Observe === $invocation->executionIntent()
            && CommandInteractionMode::Disabled === $invocation->interactionMode()
            && CommandMutationPolicy::ReadOnly === $invocation->definition()->mutationPolicy()
            && [] === $invocation->inputs();
    }

    private function integerField(string $name, string $label, int $value): CommandResultField
    {
        return new CommandResultField(
            new CommandResultFieldName($name),
            new CommandResultFieldLabel($label),
            CommandResultValue::integer($value),
            CommandResultFieldDisclosure::Safe,
        );
    }

    private function textField(string $name, string $label, string $value): CommandResultField
    {
        return new CommandResultField(
            new CommandResultFieldName($name),
            new CommandResultFieldLabel($label),
            CommandResultValue::text($value),
            CommandResultFieldDisclosure::Safe,
        );
    }
}
