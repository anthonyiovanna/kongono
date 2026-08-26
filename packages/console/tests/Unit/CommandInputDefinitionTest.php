<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

use Kongono\Console\CommandInputDefinition;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInputRequirement;
use Kongono\Console\CommandInputSensitivity;
use PHPUnit\Framework\TestCase;

final class CommandInputDefinitionTest extends TestCase
{
    public function testRequirementVocabularyAndAccessorsAreExact(): void
    {
        $name = new CommandInputName('credential');
        $definition = new CommandInputDefinition($name, CommandInputRequirement::Required, CommandInputSensitivity::Sensitive);

        self::assertSame(['optional', 'required'], array_column(CommandInputRequirement::cases(), 'value'));
        self::assertSame($name, $definition->name());
        self::assertSame(CommandInputRequirement::Required, $definition->requirement());
        self::assertSame(CommandInputSensitivity::Sensitive, $definition->sensitivity());
    }
}
