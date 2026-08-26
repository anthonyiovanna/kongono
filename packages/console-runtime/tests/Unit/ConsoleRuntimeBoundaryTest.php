<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime\Tests\Unit;

use Kongono\Console\CommandCancellation;
use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandHandler;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandInvocation;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandOperation;
use Kongono\Console\CommandOutcome;
use Kongono\Console\CommandResult;
use Kongono\Console\CommandSummary;
use Kongono\ConsoleRuntime\CommandDispatcher;
use Kongono\ConsoleRuntime\CommandHandlerBinding;
use Kongono\ConsoleRuntime\CommandHandlerSet;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class ConsoleRuntimeBoundaryTest extends TestCase
{
    public function testManifestDependenciesAndProductionInventoryAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/console' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);

        $files = glob($root . '/src/{,Diagnostic/,Exception/}*.php', GLOB_BRACE);
        self::assertIsArray($files);
        self::assertCount(9, $files);
        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            self::assertSame(0, preg_match('/Symfony|ReflectionClass|ServiceLocator|Container|Registry|Presenter|json_encode|STDIN|STDOUT|STDERR|php:\/\/|\$_(?:SERVER|ENV|REQUEST|GET|POST|COOKIE|FILES)|\b(?:getenv|putenv|getcwd|chdir|fopen|file_get_contents|fwrite|printf|print|echo|exit|die|eval|ob_[a-z_]+)\s*\(?/i', $source));
        }
    }

    public function testDispatcherHasNoStaticMutableInvocationState(): void
    {
        $reflection = new ReflectionClass(CommandDispatcher::class);
        self::assertTrue($reflection->isFinal());
        foreach ($reflection->getProperties() as $property) {
            self::assertFalse($property->isStatic());
            if ('handlers' === $property->getName()) {
                self::assertTrue($property->isReadOnly());
            }
        }
        self::assertSame(['dispatchActive', 'handlers'], array_map(
            static fn(ReflectionProperty $property): string => $property->getName(),
            $reflection->getProperties(),
        ));
    }

    public function testDispatchProducesNoOutput(): void
    {
        $handler = new class implements CommandHandler {
            public function execute(CommandOperation $operation): CommandResult
            {
                return CommandResult::succeeded();
            }
        };
        $cancellation = new class implements CommandCancellation {
            public function isRequested(): bool
            {
                return false;
            }
        };
        $identifier = new CommandIdentifier('graph:inspect');
        $invocation = new CommandInvocation(
            new CommandDefinition($identifier, new CommandSummary('Inspect graph.'), CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly),
            [],
            CommandExecutionIntent::Observe,
            CommandInteractionMode::Disabled,
        );
        $dispatcher = new CommandDispatcher(new CommandHandlerSet([new CommandHandlerBinding($identifier, $handler)]));

        ob_start();
        try {
            $result = $dispatcher->dispatch($invocation, $cancellation);
        } finally {
            $output = ob_get_clean();
        }

        self::assertSame(CommandOutcome::Succeeded, $result->outcome());
        self::assertSame('', $output);
    }
}
