<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Tests\Unit\Internal;

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
use Kongono\ConsolePresentation\HumanCommandPresenter;
use Kongono\ConsolePresentation\Internal\CommandHelpProjection;
use Kongono\ConsolePresentation\MachineCommandPresenter;
use PHPUnit\Framework\TestCase;

final class CommandHelpProjectionTest extends TestCase
{
    public function testFullDefinitionProjectionAndHumanMachineHelpParityAreExact(): void
    {
        $specification = new CommandSpecification(
            new CommandDefinition(new CommandIdentifier('records:update'), new CommandSummary('Update records.'), CommandInteractionPolicy::Declared, CommandMutationPolicy::MutationCapable),
            [
                new CommandInputDefinition(new CommandInputName('zulu'), CommandInputRequirement::Optional, CommandInputSensitivity::Sensitive),
                new CommandInputDefinition(new CommandInputName('alpha'), CommandInputRequirement::Required, CommandInputSensitivity::Ordinary),
            ],
        );
        $projection = CommandHelpProjection::forCommand($specification);
        self::assertSame(['identifier', 'summary', 'interaction', 'mutation', 'inputs'], array_keys($projection[0]));
        self::assertSame(['alpha', 'zulu'], array_column($projection[0]['inputs'], 'name'));

        $set = new CommandSpecificationSet([$specification]);
        $human = (new HumanCommandPresenter())->presentHelp($set)->stdout();
        $machine = (new MachineCommandPresenter())->presentHelp($set)->stdout();
        foreach (['records:update', 'Update records.', 'alpha', 'required', 'ordinary', 'zulu', 'optional', 'sensitive'] as $fact) {
            self::assertStringContainsString($fact, $human);
            self::assertStringContainsString($fact, $machine);
        }
        self::assertSame((new MachineCommandPresenter())->presentCommandHelp($specification)->stdout(), $machine);
    }
}
