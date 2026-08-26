<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Tests\Unit;

use Kongono\Console\CommandResult;
use Kongono\Console\CommandResultData;
use Kongono\ConsolePresentation\Exception\InvalidConsolePresentationException;
use Kongono\ConsolePresentation\HumanCommandPresenter;
use Kongono\ConsolePresentation\MachineCommandPresenter;
use PHPUnit\Framework\TestCase;

final class PresentationParityTest extends TestCase
{
    public function testRepeatedSequentialPresentationsAreByteIdenticalAndIsolated(): void
    {
        $human = new HumanCommandPresenter();
        $machine = new MachineCommandPresenter();
        self::assertSame($human->presentResult(CommandResult::succeeded())->stdout(), $human->presentResult(CommandResult::succeeded())->stdout());
        self::assertSame($machine->presentResult(CommandResult::succeeded())->stdout(), $machine->presentResult(CommandResult::succeeded())->stdout());
        self::assertSame(0, $human->presentResult(CommandResult::succeeded())->exitStatus()->value);
        self::assertSame(0, $machine->presentResult(CommandResult::succeeded())->exitStatus()->value);
    }

    public function testUnknownDataFailsWithoutTraversalOrStringificationInBothPresenters(): void
    {
        $data = new class implements CommandResultData {
            public int $calls = 0;
            public function __toString(): string
            {
                ++$this->calls;
                return 'unknown-data-sentinel';
            }
        };
        foreach ([new HumanCommandPresenter(), new MachineCommandPresenter()] as $presenter) {
            try {
                $presenter->presentResult(CommandResult::succeeded($data));
                self::fail('Expected unsupported result data.');
            } catch (InvalidConsolePresentationException $exception) {
                self::assertSame('KGN-CONSOLE-PRESENTATION-1002', $exception->diagnosticId());
                self::assertStringNotContainsString('unknown-data-sentinel', (string) $exception);
            }
        }
        self::assertSame(0, $data->calls);
    }
}
