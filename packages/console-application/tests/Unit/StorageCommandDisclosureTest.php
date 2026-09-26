<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit;

use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInputDefinition;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInputRequirement;
use Kongono\Console\CommandInputSensitivity;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandSpecification;
use Kongono\Console\CommandSpecificationSet;
use Kongono\Console\CommandSummary;
use Kongono\ConsoleApplication\Exception\InvalidConsoleApplicationValueException;
use Kongono\ConsoleApplication\Internal\KongoCommandValidator;
use Kongono\ConsoleApplication\KongoApplication;
use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleRuntime\CommandDispatcher;
use Kongono\ConsoleRuntime\CommandHandlerBinding;
use Kongono\ConsoleRuntime\CommandHandlerSet;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class StorageCommandDisclosureTest extends TestCase
{
    public function testClosedValidatorRejectsChangedStorageSpecificationsAndBindings(): void
    {
        $application = (new KongoApplicationFactory())->create();
        $specifications = (new ReflectionProperty(KongoApplication::class, 'specifications'))->getValue($application);
        $dispatcher = (new ReflectionProperty(KongoApplication::class, 'dispatcher'))->getValue($application);
        self::assertInstanceOf(CommandSpecificationSet::class, $specifications);
        self::assertInstanceOf(CommandDispatcher::class, $dispatcher);
        $handlers = (new ReflectionProperty(CommandDispatcher::class, 'handlers'))->getValue($dispatcher);
        self::assertInstanceOf(CommandHandlerSet::class, $handlers);
        $commands = $specifications->commands();
        $bindings = $handlers->bindings();
        self::assertCount(19, $commands);
        self::assertCount(19, $bindings);
        (new KongoCommandValidator())->validate($specifications, $handlers);

        foreach (['summary', 'mutation', 'input', 'requirement', 'sensitivity'] as $change) {
            $changed = $commands;
            $original = $commands[13];
            $definition = $original->definition();
            $inputs = $original->inputs();
            if ('input' === $change || 'requirement' === $change || 'sensitivity' === $change) {
                $input = $inputs[0];
                $inputs[0] = new CommandInputDefinition(
                    new CommandInputName('input' === $change ? 'wrong-root' : $input->name()->value()),
                    'requirement' === $change ? CommandInputRequirement::Optional : $input->requirement(),
                    'sensitivity' === $change ? CommandInputSensitivity::Sensitive : $input->sensitivity(),
                );
            }
            $changed[13] = new CommandSpecification(new CommandDefinition(
                $definition->identifier(),
                new CommandSummary('summary' === $change ? 'Wrong summary' : $definition->summary()->value()),
                $definition->interactionPolicy(),
                'mutation' === $change ? CommandMutationPolicy::ReadOnly : $definition->mutationPolicy(),
            ), $inputs);
            self::rejects(new CommandSpecificationSet($changed), $handlers);
        }

        $missing = $commands;
        array_splice($missing, 13, 1);
        self::rejects(new CommandSpecificationSet($missing), $handlers);
        $extra = $commands;
        $original = $commands[13];
        $definition = $original->definition();
        $extra[] = new CommandSpecification(new CommandDefinition(
            new CommandIdentifier('storage:other'),
            $definition->summary(),
            $definition->interactionPolicy(),
            $definition->mutationPolicy(),
        ), $original->inputs());
        self::rejects(new CommandSpecificationSet($extra), $handlers);

        $wrongType = $bindings;
        $wrongType[13] = new CommandHandlerBinding($bindings[13]->identifier(), $bindings[14]->handler());
        self::rejects($specifications, new CommandHandlerSet($wrongType));
        $wrongIdentifier = $bindings;
        $wrongIdentifier[13] = new CommandHandlerBinding(new CommandIdentifier('storage:other'), $bindings[13]->handler());
        self::rejects($specifications, new CommandHandlerSet($wrongIdentifier));
        $missingBinding = $bindings;
        array_splice($missingBinding, 13, 1);
        self::rejects($specifications, new CommandHandlerSet($missingBinding));
        $extraBinding = $bindings;
        $extraBinding[] = new CommandHandlerBinding(new CommandIdentifier('storage:other'), $bindings[13]->handler());
        self::rejects($specifications, new CommandHandlerSet($extraBinding));
    }

    private static function rejects(CommandSpecificationSet $specifications, CommandHandlerSet $handlers): void
    {
        try {
            (new KongoCommandValidator())->validate($specifications, $handlers);
            self::fail('Changed command family was accepted.');
        } catch (InvalidConsoleApplicationValueException) {
        }
    }
}
