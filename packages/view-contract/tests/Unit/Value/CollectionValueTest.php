<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Value;

use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Schema\PresentationValueKind;
use Kongono\ViewContract\Value\ListValue;
use Kongono\ViewContract\Value\PresentationValue;
use Kongono\ViewContract\Value\RecordValue;
use Kongono\ViewContract\Value\StringValue;
use PHPUnit\Framework\TestCase;

final class CollectionValueTest extends TestCase
{
    public function testListsRequirePhpListsPreserveOrderAndDetach(): void
    {
        $input = [new StringValue('a'), new StringValue('b')];
        $value = new ListValue($input);
        $input[] = new StringValue('c');
        $returned = $value->values();
        $returned[] = new StringValue('d');
        self::assertCount(2, $value->values());
        self::assertTrue($value->equals(new ListValue([new StringValue('a'), new StringValue('b')])));
        self::assertFalse($value->equals(new ListValue([new StringValue('b'), new StringValue('a')])));
    }

    public function testListsEnforceShapeAndDirectBound(): void
    {
        self::assertCount(256, (new ListValue(array_fill(0, 256, new StringValue('x'))))->values());
        foreach ([[1 => new StringValue('x')], array_fill(0, 257, new StringValue('x'))] as $invalid) {
            try {
                new ListValue($invalid);
                self::fail('Invalid list was accepted.');
            } catch (InvalidViewContractException $failure) {
                self::assertInstanceOf(InvalidViewContractException::class, $failure);
            }
        }
    }

    public function testRecordsCanonicalizeValidateDetachAndCompare(): void
    {
        $input = ['z' => new StringValue('last'), 'a' => new StringValue('first')];
        $record = new RecordValue($input);
        $input['later'] = new StringValue('detached');
        $returned = $record->fields();
        $returned['later'] = new StringValue('detached');
        self::assertSame(['a', 'z'], array_keys($record->fields()));
        self::assertTrue($record->equals(new RecordValue(['a' => new StringValue('first'), 'z' => new StringValue('last')])));
        self::assertFalse($record->equals(new RecordValue(['a' => new StringValue('different'), 'z' => new StringValue('last')])));
    }

    public function testRecordsRejectInvalidNamesAndBounds(): void
    {
        foreach ([['Bad' => new StringValue('x')], ['private-path' => new StringValue('x')], array_fill_keys(array_map(static fn(int $i): string => 'f' . $i, range(0, 128)), new StringValue('x'))] as $invalid) {
            try {
                new RecordValue($invalid);
                self::fail('Invalid record was accepted.');
            } catch (InvalidViewContractException $failure) {
                self::assertInstanceOf(InvalidViewContractException::class, $failure);
            }
        }
    }

    public function testForeignPresentationValueImplementationsAreRejected(): void
    {
        $foreign = new class implements PresentationValue {
            public function kind(): PresentationValueKind
            {
                return PresentationValueKind::String;
            }

            public function equals(PresentationValue $other): bool
            {
                return $this === $other;
            }

            public function depth(): int
            {
                return 1;
            }

            public function nodeCount(): int
            {
                return 1;
            }
        };

        foreach ([[0 => $foreign], ['foreign' => $foreign]] as $invalid) {
            try {
                array_is_list($invalid) ? new ListValue($invalid) : new RecordValue($invalid);
                self::fail('Foreign presentation value was accepted.');
            } catch (InvalidViewContractException $failure) {
                self::assertInstanceOf(InvalidViewContractException::class, $failure);
            }
        }
    }
}
