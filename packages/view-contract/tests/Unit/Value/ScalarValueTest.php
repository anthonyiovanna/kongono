<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Value;

use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Schema\PresentationValueKind;
use Kongono\ViewContract\Value\BooleanValue;
use Kongono\ViewContract\Value\IntegerValue;
use Kongono\ViewContract\Value\NullValue;
use Kongono\ViewContract\Value\StringValue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ScalarValueTest extends TestCase
{
    public function testScalarValuesPreserveExactTypesKindsAndEquality(): void
    {
        self::assertSame('', (new StringValue(''))->value());
        self::assertSame("tab\tline\n", (new StringValue("tab\tline\n"))->value());
        self::assertSame(PresentationValueKind::String, (new StringValue('0'))->kind());
        self::assertSame(42, (new IntegerValue(42))->value());
        self::assertSame(PresentationValueKind::Integer, (new IntegerValue(42))->kind());
        self::assertTrue((new BooleanValue(true))->value());
        self::assertSame(PresentationValueKind::Null, (new NullValue())->kind());
        self::assertTrue((new NullValue())->equals(new NullValue()));
        self::assertFalse((new IntegerValue(1))->equals(new StringValue('1')));

        $integerParameter = (new ReflectionMethod(IntegerValue::class, '__construct'))->getParameters()[0];
        $booleanParameter = (new ReflectionMethod(BooleanValue::class, '__construct'))->getParameters()[0];
        self::assertSame('int', (string) $integerParameter->getType());
        self::assertSame('bool', (string) $booleanParameter->getType());
    }

    public function testStringByteBoundaryIsExact(): void
    {
        self::assertSame(65536, strlen((new StringValue(str_repeat('a', 65536)))->value()));
        $this->expectException(InvalidViewContractException::class);
        new StringValue(str_repeat('a', 65537));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidStrings(): iterable
    {
        yield 'invalid utf8' => ["\xC3\x28"];
        yield 'bom' => ["\xEF\xBB\xBFprivate"];
        yield 'nul' => ["private\0value"];
        yield 'carriage return' => ["private\rvalue"];
        yield 'other c0' => ["private\x1Fvalue"];
        yield 'c1' => ["private\xC2\x80value"];
    }

    #[DataProvider('invalidStrings')]
    public function testStringsRejectInvalidUtf8BomAndControls(string $value): void
    {
        $this->expectException(InvalidViewContractException::class);
        new StringValue($value);
    }
}
