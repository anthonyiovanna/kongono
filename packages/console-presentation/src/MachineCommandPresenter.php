<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandResult;
use Kongono\Console\CommandSpecification;
use Kongono\Console\CommandSpecificationSet;
use Kongono\ConsolePresentation\Internal\CanonicalJson;
use Kongono\ConsolePresentation\Internal\CommandHelpProjection;
use Kongono\ConsolePresentation\Internal\CommandResultProjection;

/** Renders canonical version-1 machine result and help documents without host state. */
#[Api(ApiStatus::Experimental)]
final class MachineCommandPresenter
{
    public function presentResult(CommandResult $result): CommandPresentation
    {
        $status = CommandExitStatus::forResult($result);
        $failure = $result->failure();
        $projection = CommandResultProjection::fromResult($result);
        $fields = [];
        foreach ($projection->safeFields() as $field) {
            $fields[] = [
                'name' => $field->name()->value(),
                'kind' => $field->value()->kind()->value,
                'value' => $field->value()->value(),
            ];
        }

        $document = CanonicalJson::encode([
            'schema' => 'kongono.command-result',
            'version' => 1,
            'outcome' => $result->outcome()->value,
            'exit_status' => $status->value,
            'failure' => null === $failure ? null : [
                'category' => $failure->category()->value,
                'diagnostic_id' => $failure->diagnosticIdentifier(),
            ],
            'data' => null === $result->data() ? null : [
                'fields' => $fields,
                'protected_field_count' => $projection->protectedFieldCount(),
            ],
        ]);

        return new CommandPresentation($document, '', CommandMediaType::MachineResultJson, $status);
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
        $document = CanonicalJson::encode([
            'schema' => 'kongono.command-help',
            'version' => 1,
            'commands' => $commands,
        ]);

        return new CommandPresentation($document, '', CommandMediaType::MachineHelpJson, CommandExitStatus::Succeeded);
    }
}
