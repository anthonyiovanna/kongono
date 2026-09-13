<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Unit\Grammar;

use Kongono\ViewBuild\Grammar\ViewNode;
use Kongono\ViewBuild\Grammar\ViewNodeKind;
use Kongono\ViewBuild\Source\SourcePointer;
use PHPUnit\Framework\TestCase;

final class ViewNodeTest extends TestCase
{
    public function testClosedFactoriesDetachAndCompareEveryNodeKind(): void
    {
        $pointer = new SourcePointer('/views/0/body/0');
        $children = [ViewNode::text('x', $pointer)];
        $element = ViewNode::element('div', [], $children, $pointer);
        $children[] = ViewNode::value('$.name', $pointer);
        self::assertCount(1, $element->children());
        self::assertSame(ViewNodeKind::Element, $element->kind());
        self::assertTrue($element->equals(ViewNode::element('div', [], [ViewNode::text('x', $pointer)], $pointer)));
        self::assertSame(ViewNodeKind::Each, ViewNode::each('$.items', 'item', 2, [], $pointer)->kind());
    }
}
