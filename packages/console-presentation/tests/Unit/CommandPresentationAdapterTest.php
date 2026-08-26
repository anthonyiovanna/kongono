<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Tests\Unit;

use Kongono\ConsolePresentation\CommandExitStatus;
use Kongono\ConsolePresentation\CommandMediaType;
use Kongono\ConsolePresentation\CommandOutputPort;
use Kongono\ConsolePresentation\CommandPresentation;
use Kongono\ConsolePresentation\CommandPresentationAdapter;
use Kongono\ConsolePresentation\Exception\CommandOutputException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CommandPresentationAdapterTest extends TestCase
{
    public function testExactlyOneSelectedPortReceivesExactBytesBeforeExitReturns(): void
    {
        $stdout = new RecordingOutputPort();
        $stderr = new RecordingOutputPort();
        $presentation = new CommandPresentation("exact/stdout\n", '', CommandMediaType::HumanText, CommandExitStatus::Succeeded);

        self::assertSame(0, (new CommandPresentationAdapter())->deliver($presentation, $stdout, $stderr));
        self::assertSame(["exact/stdout\n"], $stdout->writes);
        self::assertSame([], $stderr->writes);

        $stderrPresentation = new CommandPresentation('', "exact/stderr\n", CommandMediaType::HumanText, CommandExitStatus::Denied);
        self::assertSame(4, (new CommandPresentationAdapter())->deliver($stderrPresentation, $stdout, $stderr));
        self::assertSame(["exact/stderr\n"], $stderr->writes);
    }

    public function testPortFailureIsWrappedOnceWithoutFallback(): void
    {
        $failure = new RuntimeException('port-secret-path');
        $stdout = new RecordingOutputPort($failure);
        $stderr = new RecordingOutputPort();

        try {
            (new CommandPresentationAdapter())->deliver(new CommandPresentation("document-secret\n", '', CommandMediaType::HumanText, CommandExitStatus::Succeeded), $stdout, $stderr);
            self::fail('Expected output failure.');
        } catch (CommandOutputException $exception) {
            self::assertSame($failure, $exception->getPrevious());
            self::assertSame('KGN-CONSOLE-PRESENTATION-1004', $exception->diagnosticId());
            self::assertStringNotContainsString('secret', (string) $exception);
        }
        self::assertCount(1, $stdout->writes);
        self::assertSame([], $stderr->writes);
    }
}

final class RecordingOutputPort implements CommandOutputPort
{
    /** @var list<string> */
    public array $writes = [];

    public function __construct(private readonly ?RuntimeException $failure = null) {}

    public function write(string $bytes): void
    {
        $this->writes[] = $bytes;
        if (null !== $this->failure) {
            throw $this->failure;
        }
    }
}
