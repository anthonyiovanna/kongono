<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

use Kongono\Console\CommandResultField;
use Kongono\Console\CommandResultFieldDisclosure;
use Kongono\Console\CommandResultFieldLabel;
use Kongono\Console\CommandResultFieldName;
use Kongono\Console\CommandResultRecord;
use Kongono\Console\CommandResultValue;
use Kongono\Console\Exception\InvalidConsoleValueException;
use PHPUnit\Framework\TestCase;

final class CommandResultRecordTest extends TestCase
{
    public function testRecordDetachesSortsAndPerformsExactLookup(): void
    {
        $alpha = $this->field('alpha');
        $zulu = $this->field('zulu', CommandResultFieldDisclosure::Protected);
        $source = [9 => $zulu, 2 => $alpha];
        $record = new CommandResultRecord($source);
        $source[] = $this->field('later');

        self::assertSame([$alpha, $zulu], $record->fields());
        self::assertSame($alpha, $record->field(new CommandResultFieldName('alpha')));
        self::assertTrue($record->hasField(new CommandResultFieldName('zulu')));
        self::assertNull($record->field(new CommandResultFieldName('missing')));
    }

    public function testOneAndSixtyFourFieldBoundsAreAccepted(): void
    {
        self::assertCount(1, (new CommandResultRecord([$this->field('one')]))->fields());
        $fields = [];
        for ($index = 0; $index < 64; ++$index) {
            $fields[] = $this->field('field_' . $index);
        }
        self::assertCount(64, (new CommandResultRecord($fields))->fields());
    }

    public function testEmptyOversizedInvalidAndDuplicateFieldsAreRejected(): void
    {
        $valid = $this->field('same');
        foreach ([[], [new \stdClass()], [$valid, $valid], array_fill(0, 65, $valid)] as $fields) {
            try {
                new CommandResultRecord($fields);
                self::fail('Expected invalid result record.');
            } catch (InvalidConsoleValueException $exception) {
                self::assertSame('KGN-CONSOLE-1004', $exception->diagnosticId());
            }
        }
    }

    private function field(string $name, CommandResultFieldDisclosure $disclosure = CommandResultFieldDisclosure::Safe): CommandResultField
    {
        return new CommandResultField(new CommandResultFieldName($name), new CommandResultFieldLabel(ucfirst($name)), CommandResultValue::text($name), $disclosure);
    }
}
