<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Tests\Unit;

use Kongono\ConsolePresentation\CommandExitStatus;
use Kongono\ConsolePresentation\CommandMediaType;
use Kongono\ConsolePresentation\CommandPresentation;
use Kongono\ConsolePresentation\Exception\InvalidConsolePresentationException;
use PHPUnit\Framework\TestCase;

final class CommandPresentationTest extends TestCase
{
    public function testExactStdoutAndStderrDocumentsArePreserved(): void
    {
        $stdout = new CommandPresentation("Succeeded.\n", '', CommandMediaType::HumanText, CommandExitStatus::Succeeded);
        self::assertSame("Succeeded.\n", $stdout->stdout());
        self::assertSame('', $stdout->stderr());
        self::assertSame(CommandMediaType::HumanText, $stdout->mediaType());
        self::assertSame(CommandExitStatus::Succeeded, $stdout->exitStatus());

        $stderr = new CommandPresentation('', "Rejected.\n", CommandMediaType::HumanText, CommandExitStatus::Denied);
        self::assertSame("Rejected.\n", $stderr->stderr());
    }

    public function testInvalidDocumentsAreRejected(): void
    {
        foreach ([['', ''], ["a\n", "b\n"], ["no-lf", ''], ["two\n\n", ''], ["cr\r\n", ''], ["nul\0\n", ''], ["\xEF\xBB\xBFbom\n", ''], [str_repeat('x', 1_048_576) . "\n", '']] as [$stdout, $stderr]) {
            try {
                new CommandPresentation($stdout, $stderr, CommandMediaType::HumanText, CommandExitStatus::Succeeded);
                self::fail('Expected invalid presentation.');
            } catch (InvalidConsolePresentationException $exception) {
                self::assertSame('KGN-CONSOLE-PRESENTATION-1001', $exception->diagnosticId());
            }
        }
    }
}
