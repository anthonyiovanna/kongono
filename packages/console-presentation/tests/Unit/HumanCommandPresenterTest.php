<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Tests\Unit;

use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandResult;
use Kongono\Console\CommandResultField;
use Kongono\Console\CommandResultFieldDisclosure;
use Kongono\Console\CommandResultFieldLabel;
use Kongono\Console\CommandResultFieldName;
use Kongono\Console\CommandResultRecord;
use Kongono\Console\CommandResultValue;
use Kongono\ConsolePresentation\HumanCommandPresenter;
use PHPUnit\Framework\TestCase;

final class HumanCommandPresenterTest extends TestCase
{
    public function testSuccessUsesStdoutOrdinalSafeScalarsAndProtectedCount(): void
    {
        $secret = 'protected-sentinel-never-render';
        $record = new CommandResultRecord([
            $this->field('zulu', 'Zulu', CommandResultValue::boolean(true)),
            $this->field('secret', 'Secret', CommandResultValue::text($secret), CommandResultFieldDisclosure::Protected),
            $this->field('alpha', 'Alpha', CommandResultValue::integer(-7)),
            $this->field('middle', 'Middle', CommandResultValue::text('café/ok')),
        ]);
        $presentation = (new HumanCommandPresenter())->presentResult(CommandResult::succeeded($record));

        self::assertSame("Succeeded.\nAlpha: -7\nMiddle: café/ok\nZulu: true\nProtected fields omitted: 1\n", $presentation->stdout());
        self::assertSame('', $presentation->stderr());
        self::assertSame(0, $presentation->exitStatus()->value);
        self::assertStringNotContainsString($secret, $presentation->stdout());
    }

    public function testEveryFailureIsOneSafeStderrLineWithExactExit(): void
    {
        $presenter = new HumanCommandPresenter();
        $cases = [
            [CommandFailureCategory::InvalidInput, 2],
            [CommandFailureCategory::Precondition, 3],
            [CommandFailureCategory::Denied, 4],
            [CommandFailureCategory::Capability, 5],
            [CommandFailureCategory::Infrastructure, 6],
            [CommandFailureCategory::Cancelled, 7],
        ];
        foreach ($cases as [$category, $exit]) {
            $failure = new CommandFailure($category, 'KGN-TEST-1001');
            $result = match ($category) {
                CommandFailureCategory::InvalidInput, CommandFailureCategory::Precondition, CommandFailureCategory::Denied => CommandResult::rejected($failure),
                CommandFailureCategory::Capability, CommandFailureCategory::Infrastructure => CommandResult::failed($failure),
                CommandFailureCategory::Cancelled => CommandResult::cancelled($failure),
            };
            $presentation = $presenter->presentResult($result);
            self::assertSame('', $presentation->stdout());
            self::assertSame("Rejected: {$category->value} [KGN-TEST-1001].\n", $presentation->stderr());
            self::assertSame($exit, $presentation->exitStatus()->value);
        }
    }

    private function field(string $name, string $label, CommandResultValue $value, CommandResultFieldDisclosure $disclosure = CommandResultFieldDisclosure::Safe): CommandResultField
    {
        return new CommandResultField(new CommandResultFieldName($name), new CommandResultFieldLabel($label), $value, $disclosure);
    }
}
