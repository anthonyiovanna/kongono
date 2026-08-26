<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandResult;
use Kongono\Console\CommandSpecification;
use Kongono\Console\CommandSpecificationSet;
use Kongono\ConsolePresentation\Internal\CommandHelpProjection;
use Kongono\ConsolePresentation\Internal\CommandResultProjection;
use Kongono\ConsolePresentation\Internal\HumanValueFormatter;

/** Renders plain deterministic human result and help documents without host state. */
#[Api(ApiStatus::Experimental)]
final class HumanCommandPresenter
{
    public function presentResult(CommandResult $result): CommandPresentation
    {
        $status = CommandExitStatus::forResult($result);
        $failure = $result->failure();
        if (null !== $failure) {
            return new CommandPresentation(
                '',
                sprintf('Rejected: %s [%s].', $failure->category()->value, $failure->diagnosticIdentifier()) . "\n",
                CommandMediaType::HumanText,
                $status,
            );
        }

        $projection = CommandResultProjection::fromResult($result);
        $lines = ['Succeeded.'];
        foreach ($projection->safeFields() as $field) {
            $lines[] = sprintf('%s: %s', $field->label()->value(), HumanValueFormatter::format($field->value()));
        }
        if (0 !== $projection->protectedFieldCount()) {
            $lines[] = sprintf('Protected fields omitted: %d', $projection->protectedFieldCount());
        }

        return new CommandPresentation(
            implode("\n", $lines) . "\n",
            '',
            CommandMediaType::HumanText,
            $status,
        );
    }

    public function presentHelp(CommandSpecificationSet $specifications): CommandPresentation
    {
        return $this->presentProjectedHelp(CommandHelpProjection::forSet($specifications));
    }

    public function presentCommandHelp(CommandSpecification $specification): CommandPresentation
    {
        return $this->presentProjectedHelp(CommandHelpProjection::forCommand($specification));
    }

    /** @param list<array{identifier: string, summary: string, interaction: string, mutation: string, inputs: list<array{name: string, requirement: string, sensitivity: string}>}> $commands */
    private function presentProjectedHelp(array $commands): CommandPresentation
    {
        $sections = [];
        foreach ($commands as $command) {
            $lines = [
                'Command: ' . $command['identifier'],
                'Summary: ' . $command['summary'],
                'Usage: kongo ' . $command['identifier'] . $this->usageInputs($command['inputs']),
                'Interaction: ' . $command['interaction'],
                'Mutation: ' . $command['mutation'],
            ];
            if ([] !== $command['inputs']) {
                $lines[] = 'Inputs:';
                foreach ($command['inputs'] as $input) {
                    $lines[] = sprintf('  --%s=<value> (%s, %s)', $input['name'], $input['requirement'], $input['sensitivity']);
                }
            }
            $sections[] = implode("\n", $lines);
        }

        return new CommandPresentation(
            implode("\n\n", $sections) . "\n",
            '',
            CommandMediaType::HumanText,
            CommandExitStatus::Succeeded,
        );
    }

    /** @param list<array{name: string, requirement: string, sensitivity: string}> $inputs */
    private function usageInputs(array $inputs): string
    {
        $usage = '';
        foreach ($inputs as $input) {
            $token = '--' . $input['name'] . '=<value>';
            $usage .= 'required' === $input['requirement'] ? ' ' . $token : ' [' . $token . ']';
        }

        return $usage;
    }
}
