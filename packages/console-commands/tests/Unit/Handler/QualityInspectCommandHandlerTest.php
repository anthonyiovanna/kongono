<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Tests\Unit\Handler;

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
use Kongono\ConsoleCommands\Handler\QualityInspectCommandHandler;
use Kongono\Hantei\HanteiCheckEvidence;
use Kongono\Hantei\HanteiCheckOutcome;
use Kongono\Hantei\HanteiReport;
use Kongono\Hantei\HanteiVerdict;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class QualityInspectCommandHandlerTest extends TestCase
{
    /**
     * @param list<HanteiCheckOutcome> $outcomes
     * @param array<string, int|string> $expected
     */
    #[DataProvider('verdicts')]
    public function testEveryVerdictRemainsSuccessfulInspectionData(HanteiVerdict $verdict, array $outcomes, array $expected): void
    {
        $report = self::report($verdict, $outcomes);
        $operation = self::createStub(CommandOperation::class);
        $operation->method('invocation')->willReturn(self::invocation());
        $result = (new QualityInspectCommandHandler($report))->execute($operation);

        self::assertSame(CommandOutcome::Succeeded, $result->outcome());
        self::assertNull($result->failure());
        self::assertInstanceOf(CommandResultRecord::class, $result->data());
        $values = [];
        foreach ($result->data()->fields() as $field) {
            self::assertSame('safe', $field->disclosure()->value);
            $values[$field->name()->value()] = $field->value()->value();
        }
        self::assertSame($expected, $values);
        $serialized = json_encode($values, JSON_THROW_ON_ERROR);
        foreach (['check-reason-secret', 'C:\\secret', '/secret/path', 'exception-secret'] as $sentinel) {
            self::assertStringNotContainsString($sentinel, $serialized);
        }
    }

    /** @return iterable<string, array{HanteiVerdict, list<HanteiCheckOutcome>, array<string, int|string>}> */
    public static function verdicts(): iterable
    {
        yield 'pass' => [HanteiVerdict::Pass, array_fill(0, 8, HanteiCheckOutcome::Pass), ['blocked_count' => 0, 'check_count' => 8, 'fail_count' => 0, 'pass_count' => 8, 'verdict' => 'PASS']];
        yield 'fail' => [HanteiVerdict::Fail, [HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Fail], ['blocked_count' => 0, 'check_count' => 8, 'fail_count' => 1, 'pass_count' => 7, 'verdict' => 'FAIL']];
        yield 'blocked mixed' => [HanteiVerdict::Blocked, [HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Blocked, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass], ['blocked_count' => 1, 'check_count' => 8, 'fail_count' => 0, 'pass_count' => 7, 'verdict' => 'BLOCKED']];
        yield 'fail and blocked mixed' => [HanteiVerdict::Fail, [HanteiCheckOutcome::Pass, HanteiCheckOutcome::Fail, HanteiCheckOutcome::Blocked, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass, HanteiCheckOutcome::Pass], ['blocked_count' => 1, 'check_count' => 8, 'fail_count' => 1, 'pass_count' => 6, 'verdict' => 'FAIL']];
    }

    /** @param list<HanteiCheckOutcome> $outcomes */
    private static function report(HanteiVerdict $verdict, array $outcomes): HanteiReport
    {
        $checks = [];
        $uncertainties = [];
        foreach (array_keys(HanteiCheckEvidence::CHECKS) as $index => $checkId) {
            $outcome = $outcomes[$index];
            $script = HanteiCheckEvidence::CHECKS[$checkId];
            $reason = HanteiCheckOutcome::Blocked === $outcome ? 'tool-unavailable' : null;
            if (null !== $reason && !in_array($reason, $uncertainties, true)) {
                $uncertainties[] = $reason;
            }
            $checks[] = HanteiCheckOutcome::Blocked === $outcome
                ? new HanteiCheckEvidence($checkId, $script, $outcome, null, 0, null, 0, null, $reason)
                : new HanteiCheckEvidence($checkId, $script, $outcome, HanteiCheckOutcome::Pass === $outcome ? 0 : 1, 0, str_repeat('a', 64), 0, str_repeat('b', 64), null);
        }

        return new HanteiReport(
            $verdict,
            HanteiVerdict::Pass === $verdict ? str_repeat('c', 64) : null,
            HanteiVerdict::Pass === $verdict ? str_repeat('d', 64) : null,
            HanteiVerdict::Pass === $verdict ? str_repeat('e', 64) : null,
            HanteiVerdict::Pass === $verdict ? str_repeat('f', 64) : null,
            '8.5.9',
            HanteiVerdict::Pass === $verdict ? str_repeat('1', 64) : null,
            HanteiVerdict::Pass === $verdict ? 'git version' : null,
            $checks,
            $uncertainties,
        );
    }

    private static function invocation(): CommandInvocation
    {
        return new CommandInvocation(
            new CommandDefinition(new CommandIdentifier('framework:quality'), new CommandSummary('Inspect'), CommandInteractionPolicy::None, CommandMutationPolicy::ReadOnly),
            [],
            CommandExecutionIntent::Observe,
            CommandInteractionMode::Disabled,
        );
    }
}
