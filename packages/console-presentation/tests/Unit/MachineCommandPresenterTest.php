<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Tests\Unit;

use Kongono\Console\CommandResult;
use Kongono\Console\CommandResultField;
use Kongono\Console\CommandResultFieldDisclosure;
use Kongono\Console\CommandResultFieldLabel;
use Kongono\Console\CommandResultFieldName;
use Kongono\Console\CommandResultRecord;
use Kongono\Console\CommandResultValue;
use Kongono\ConsolePresentation\CommandMediaType;
use Kongono\ConsolePresentation\MachineCommandPresenter;
use PHPUnit\Framework\TestCase;

final class MachineCommandPresenterTest extends TestCase
{
    public function testCanonicalResultKeyOrderTypesUnicodeSlashAndDisclosureAreExact(): void
    {
        $secret = 'machine-protected-sentinel';
        $record = new CommandResultRecord([
            $this->field('text', CommandResultValue::text('café/path')),
            $this->field('integer', CommandResultValue::integer(12)),
            $this->field('boolean', CommandResultValue::boolean(false)),
            $this->field('secret', CommandResultValue::text($secret), CommandResultFieldDisclosure::Protected),
        ]);
        $presentation = (new MachineCommandPresenter())->presentResult(CommandResult::succeeded($record));
        $expected = '{"schema":"kongono.command-result","version":1,"outcome":"succeeded","exit_status":0,"failure":null,"data":{"fields":[{"name":"boolean","kind":"boolean","value":false},{"name":"integer","kind":"integer","value":12},{"name":"text","kind":"text","value":"café/path"}],"protected_field_count":1}}' . "\n";

        self::assertSame($expected, $presentation->stdout());
        self::assertSame('', $presentation->stderr());
        self::assertSame(CommandMediaType::MachineResultJson, $presentation->mediaType());
        self::assertStringNotContainsString($secret, $presentation->stdout());
    }

    public function testNullDataIsRepresentedAsNull(): void
    {
        self::assertSame(
            '{"schema":"kongono.command-result","version":1,"outcome":"succeeded","exit_status":0,"failure":null,"data":null}' . "\n",
            (new MachineCommandPresenter())->presentResult(CommandResult::succeeded())->stdout(),
        );
    }

    private function field(string $name, CommandResultValue $value, CommandResultFieldDisclosure $disclosure = CommandResultFieldDisclosure::Safe): CommandResultField
    {
        return new CommandResultField(new CommandResultFieldName($name), new CommandResultFieldLabel(ucfirst($name)), $value, $disclosure);
    }
}
