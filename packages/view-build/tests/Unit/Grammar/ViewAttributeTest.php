<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Unit\Grammar;

use Kongono\ViewBuild\Grammar\ViewAttribute;
use Kongono\ViewBuild\Grammar\ViewAttributeValue;
use Kongono\ViewBuild\Grammar\ViewAttributeValueKind;
use Kongono\ViewBuild\Source\SourcePointer;
use PHPUnit\Framework\TestCase;

final class ViewAttributeTest extends TestCase
{
    public function testAttributeAndClosedValueVariantsAreExact(): void
    {
        $pointer = new SourcePointer('/views/0/body/0/attributes/0');
        $literal = new ViewAttribute('class', ViewAttributeValue::literal('box'), $pointer);
        self::assertSame('class', $literal->name());
        self::assertSame(ViewAttributeValueKind::Literal, $literal->value()->kind());
        self::assertNull($literal->value()->path());
        self::assertTrue($literal->equals(new ViewAttribute('class', ViewAttributeValue::literal('box'), $pointer)));
        self::assertSame('$.title', ViewAttributeValue::value('$.title')->path());
    }
}
