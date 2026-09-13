<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Output;

use Kongono\ViewContract\Output\OutputContext;
use PHPUnit\Framework\TestCase;

final class OutputContextTest extends TestCase
{
    public function testOnlyInitialHtmlContextsExist(): void
    {
        self::assertSame(['html_text', 'html_quoted_attribute'], array_column(OutputContext::cases(), 'value'));
        foreach (['url', 'javascript', 'css', 'raw_markup', 'event_handler', 'html_unquoted_attribute'] as $unsupported) {
            self::assertNotContains($unsupported, array_column(OutputContext::cases(), 'value'));
        }
    }
}
