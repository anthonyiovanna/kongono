<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Tests\Unit\Internal;

use Kongono\ViewContract\Output\OutputContext;
use Kongono\ViewRuntime\Internal\ContextualEscaper;
use PHPUnit\Framework\TestCase;

final class ContextualEscaperTest extends TestCase
{
    public function testEscapesHtmlTextWithDoubleEncodingAndLeavesQuotesAsData(): void
    {
        self::assertSame('&lt;&gt;&amp;&amp;amp;"\'é', (new ContextualEscaper())->escape('<>&&amp;"\'é', OutputContext::HtmlText, 0));
    }

    public function testEscapesBothQuotesAndMetacharactersInQuotedAttribute(): void
    {
        self::assertSame('&lt;&gt;&amp;&quot;&apos;é', (new ContextualEscaper())->escape('<>&"\'é', OutputContext::HtmlQuotedAttribute, 1));
    }
}
