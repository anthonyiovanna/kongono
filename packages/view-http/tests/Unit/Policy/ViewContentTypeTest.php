<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Tests\Unit\Policy;

use Kongono\ViewHttp\Policy\ViewContentType;
use PHPUnit\Framework\TestCase;

final class ViewContentTypeTest extends TestCase
{
    public function testVocabularyIsClosedToUtf8Html(): void
    {
        self::assertSame([ViewContentType::HtmlUtf8], ViewContentType::cases());
        self::assertSame('text/html; charset=utf-8', ViewContentType::HtmlUtf8->value);
    }

    public function testEnumRoundTripsItsOnlyValue(): void
    {
        self::assertSame(ViewContentType::HtmlUtf8, ViewContentType::from('text/html; charset=utf-8'));
        self::assertSame('HtmlUtf8', ViewContentType::HtmlUtf8->name);
    }

    public function testUnknownContentTypeIsRejected(): void
    {
        $this->expectException(\ValueError::class);
        ViewContentType::from('application/json');
    }
}
