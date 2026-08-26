<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInput;
use Kongono\Console\CommandInputDefinition;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInputRequirement;
use Kongono\Console\CommandInputSensitivity;
use Kongono\Console\CommandInputValue;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandSpecification;
use Kongono\Console\CommandSpecificationSet;
use Kongono\Console\CommandSummary;
use Kongono\Console\CommandTokenParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CommandTokenParserTest extends TestCase
{
    public function testReadOnlyMutationAndInteractionPoliciesProduceExactInvocations(): void
    {
        $parser = new CommandTokenParser();
        $set = $this->specifications();

        $observe = $parser->parse($set, ['status', '--format=']);
        self::assertTrue($observe->isAccepted());
        $observeInvocation = $observe->invocation();
        self::assertNotNull($observeInvocation);
        self::assertSame(CommandExecutionIntent::Observe, $observeInvocation->executionIntent());
        self::assertSame(CommandInteractionMode::Disabled, $observeInvocation->interactionMode());
        $format = $observeInvocation->input(new CommandInputName('format'));
        self::assertNotNull($format);
        self::assertSame('', $format->value()->reveal());

        $plan = $parser->parse($set, ['records:update', '--zulu=last', '--plan', '--alpha=first']);
        $planInvocation = $plan->invocation();
        self::assertNotNull($planInvocation);
        self::assertSame(CommandExecutionIntent::Plan, $planInvocation->executionIntent());
        self::assertSame(CommandInteractionMode::Permitted, $planInvocation->interactionMode());
        self::assertSame(['alpha', 'zulu'], array_map(static fn(CommandInput $input): string => $input->name()->value(), $planInvocation->inputs()));

        $apply = $parser->parse($set, ['records:update', '--apply', '--no-interaction', '--alpha=first']);
        $applyInvocation = $apply->invocation();
        self::assertNotNull($applyInvocation);
        self::assertSame(CommandExecutionIntent::Apply, $applyInvocation->executionIntent());
        self::assertSame(CommandInteractionMode::Disabled, $applyInvocation->interactionMode());
    }

    public function testSensitiveValuesUseOnlyTheSeparateCarrierAndNeverLeakOnRejection(): void
    {
        $parser = new CommandTokenParser();
        $set = $this->specifications();
        $secret = 'accepted-sensitive-value';
        $secure = new CommandInput(new CommandInputName('credential'), new CommandInputValue($secret), CommandInputSensitivity::Sensitive);

        $accepted = $parser->parse($set, ['login', '--name=user'], [$secure]);
        self::assertTrue($accepted->isAccepted());
        $acceptedInvocation = $accepted->invocation();
        self::assertNotNull($acceptedInvocation);
        $credential = $acceptedInvocation->input(new CommandInputName('credential'));
        self::assertNotNull($credential);
        self::assertSame($secret, $credential->value()->reveal());

        foreach ([
            $parser->parse($set, ['login', '--name=user', '--credential=raw-sensitive-value']),
            $parser->parse($set, ['login', '--name=user'], [new CommandInput(new CommandInputName('credential'), new CommandInputValue('ordinary-carrier-value'), CommandInputSensitivity::Ordinary)]),
            $parser->parse($set, ['login', '--name=user'], [$secure, $secure]),
            $parser->parse($set, ['login', '--name=user'], [new CommandInput(new CommandInputName('unknown'), new CommandInputValue('unknown-carrier-value'), CommandInputSensitivity::Sensitive)]),
        ] as $rejected) {
            self::assertFalse($rejected->isAccepted());
            $failure = $rejected->failure();
            self::assertNotNull($failure);
            self::assertSame('KGN-CONSOLE-1010', $failure->diagnosticIdentifier());
            self::assertNull($rejected->invocation());
            $evidence = var_export([$failure->category()->value, $failure->diagnosticIdentifier()], true);
            foreach (['raw-sensitive-value', 'ordinary-carrier-value', 'unknown-carrier-value', $secret] as $value) {
                self::assertStringNotContainsString($value, $evidence);
            }
        }
    }

    /** @return iterable<string, array{array<array-key, mixed>, string}> */
    public static function rejectedTokens(): iterable
    {
        yield 'empty' => [[], 'KGN-CONSOLE-1006'];
        yield 'non-string' => [['status', 7], 'KGN-CONSOLE-1006'];
        yield 'empty token' => [['status', ''], 'KGN-CONSOLE-1006'];
        yield 'control' => [['status', "--format=x\nsecret"], 'KGN-CONSOLE-1006'];
        yield 'invalid utf8' => [['status', "\xC3\x28"], 'KGN-CONSOLE-1006'];
        yield 'per-token bound' => [['status', str_repeat('x', 8193)], 'KGN-CONSOLE-1006'];
        yield 'token count' => [array_fill(0, 129, 'status'), 'KGN-CONSOLE-1006'];
        yield 'total bytes' => [array_merge(['status'], array_fill(0, 8, str_repeat('x', 8192))), 'KGN-CONSOLE-1006'];
        yield 'unknown command' => [['missing'], 'KGN-CONSOLE-1007'];
        yield 'abbreviation' => [['sta'], 'KGN-CONSOLE-1007'];
        yield 'unknown input' => [['status', '--other=value'], 'KGN-CONSOLE-1008'];
        yield 'duplicate input' => [['status', '--format=a', '--format=b'], 'KGN-CONSOLE-1006'];
        yield 'positional' => [['status', 'value'], 'KGN-CONSOLE-1006'];
        yield 'short option' => [['status', '-f=value'], 'KGN-CONSOLE-1006'];
        yield 'delimiter' => [['status', '--'], 'KGN-CONSOLE-1006'];
        yield 'response file' => [['status', '@arguments'], 'KGN-CONSOLE-1006'];
        yield 'quoted token' => [['status', '"--format=value"'], 'KGN-CONSOLE-1006'];
        yield 'environment syntax' => [['status', '$FORMAT'], 'KGN-CONSOLE-1006'];
        yield 'read-only plan' => [['status', '--plan'], 'KGN-CONSOLE-1006'];
        yield 'missing mutation authority' => [['records:update', '--alpha=value'], 'KGN-CONSOLE-1006'];
        yield 'conflicting mutation authority' => [['records:update', '--plan', '--apply', '--alpha=value'], 'KGN-CONSOLE-1006'];
        yield 'duplicate policy' => [['records:update', '--plan', '--plan', '--alpha=value'], 'KGN-CONSOLE-1006'];
        yield 'missing required' => [['records:update', '--plan'], 'KGN-CONSOLE-1009'];
    }

    /** @param array<array-key, mixed> $tokens */
    #[DataProvider('rejectedTokens')]
    public function testInvalidGrammarAndBoundsReturnExactNeutralFailure(array $tokens, string $identifier): void
    {
        $result = (new CommandTokenParser())->parse($this->specifications(), $tokens);

        self::assertFalse($result->isAccepted());
        self::assertNull($result->invocation());
        $failure = $result->failure();
        self::assertNotNull($failure);
        self::assertSame($identifier, $failure->diagnosticIdentifier());
        self::assertSame('invalid_input', $failure->category()->value);
    }

    private function specifications(): CommandSpecificationSet
    {
        return new CommandSpecificationSet([
            new CommandSpecification(
                $this->definition('status', CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly),
                [$this->input('format', CommandInputRequirement::Optional)],
            ),
            new CommandSpecification(
                $this->definition('records:update', CommandInteractionPolicy::Declared, CommandMutationPolicy::MutationCapable),
                [$this->input('zulu', CommandInputRequirement::Optional), $this->input('alpha', CommandInputRequirement::Required)],
            ),
            new CommandSpecification(
                $this->definition('login', CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly),
                [
                    $this->input('name', CommandInputRequirement::Required),
                    $this->input('credential', CommandInputRequirement::Required, CommandInputSensitivity::Sensitive),
                ],
            ),
        ]);
    }

    private function definition(string $identifier, CommandInteractionPolicy $interaction, CommandMutationPolicy $mutation): CommandDefinition
    {
        return new CommandDefinition(new CommandIdentifier($identifier), new CommandSummary('Test command.'), $interaction, $mutation);
    }

    private function input(string $name, CommandInputRequirement $requirement, CommandInputSensitivity $sensitivity = CommandInputSensitivity::Ordinary): CommandInputDefinition
    {
        return new CommandInputDefinition(new CommandInputName($name), $requirement, $sensitivity);
    }
}
