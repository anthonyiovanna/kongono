<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Tests\Unit\Model;

use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandSpecification;
use Kongono\Console\CommandSpecificationSet;
use Kongono\Console\CommandSummary;
use Kongono\ConsoleBuild\Model\ConsoleModel;
use PHPUnit\Framework\TestCase;

final class ConsoleModelTest extends TestCase
{
    public function testModelRetainsTheImmutableSpecificationSet(): void
    {
        $set = new CommandSpecificationSet([new CommandSpecification(new CommandDefinition(
            new CommandIdentifier('status'),
            new CommandSummary('Show status.'),
            CommandInteractionPolicy::None,
            CommandMutationPolicy::ReadOnly,
        ), [])]);

        self::assertSame($set, (new ConsoleModel($set))->specifications());
    }
}
