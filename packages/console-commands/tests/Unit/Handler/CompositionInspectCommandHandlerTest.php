<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Tests\Unit\Handler;

use Kongono\Composition\Build\CompositionArtifact;
use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandInvocation;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandOperation;
use Kongono\Console\CommandOutcome;
use Kongono\Console\CommandResultRecord;
use Kongono\Console\CommandSummary;
use Kongono\ConsoleCommands\Handler\CompositionInspectCommandHandler;
use PHPUnit\Framework\TestCase;

final class CompositionInspectCommandHandlerTest extends TestCase
{
    public function testOnlyFiveExactArtifactIdentitiesAreReturned(): void
    {
        $artifact = new CompositionArtifact(
            'protected-canonical-service-secret-path-exception',
            str_repeat('a', 64),
            str_repeat('b', 64),
            str_repeat('c', 64),
            str_repeat('d', 64),
            str_repeat('e', 64),
        );
        $operation = self::createStub(CommandOperation::class);
        $operation->method('invocation')->willReturn(self::invocation());
        $result = (new CompositionInspectCommandHandler($artifact))->execute($operation);
        self::assertSame(CommandOutcome::Succeeded, $result->outcome());
        self::assertInstanceOf(CommandResultRecord::class, $result->data());

        $values = [];
        foreach ($result->data()->fields() as $field) {
            self::assertSame('safe', $field->disclosure()->value);
            self::assertSame('text', $field->value()->kind()->value);
            $values[$field->name()->value()] = $field->value()->value();
        }
        self::assertSame([
            'artifact_sha256' => str_repeat('a', 64),
            'builder_sha256' => str_repeat('e', 64),
            'model_sha256' => str_repeat('c', 64),
            'source_set_sha256' => str_repeat('d', 64),
            'source_sha256' => str_repeat('b', 64),
        ], $values);
        $serialized = json_encode($values, JSON_THROW_ON_ERROR);
        foreach (['canonical', 'service-secret', 'path', 'exception'] as $sentinel) {
            self::assertStringNotContainsString($sentinel, $serialized);
        }
    }

    private static function invocation(): CommandInvocation
    {
        return new CommandInvocation(
            new CommandDefinition(new CommandIdentifier('framework:composition'), new CommandSummary('Inspect'), CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly),
            [],
            CommandExecutionIntent::Observe,
            CommandInteractionMode::Disabled,
        );
    }
}
