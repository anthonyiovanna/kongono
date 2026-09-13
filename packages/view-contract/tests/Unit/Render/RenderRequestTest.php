<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Render;

use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use Kongono\ViewContract\Lifecycle\RenderOperationState;
use Kongono\ViewContract\Render\RenderLimits;
use Kongono\ViewContract\Render\RenderRequest;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Schema\PresentationValueSchema;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewContract\Value\ViewContext;
use PHPUnit\Framework\TestCase;

final class RenderRequestTest extends TestCase
{
    public function testConstructionValidatesAndRetainsOnlyExactTypedEvidence(): void
    {
        $schema = self::schema();
        $context = new ViewContext(['title' => new StringValue('safe')]);
        $request = new RenderRequest(
            ContractVersion::Version1,
            new RenderOperationIdentity('render-1'),
            new LogicalViewIdentity('account.profile'),
            new CompiledViewIdentity(str_repeat('a', 64)),
            $schema,
            $context,
            new RenderLimits(1024, 100),
        );

        self::assertSame(ContractVersion::Version1, $request->contractVersion());
        self::assertSame(1, $request->contractVersion()->value);
        self::assertSame(RenderOperationState::Requested, $request->state());
        self::assertSame($schema, $request->contextSchema());
        self::assertSame($context, $request->context());
        self::assertTrue($request->equals(new RenderRequest(
            ContractVersion::Version1,
            new RenderOperationIdentity('render-1'),
            new LogicalViewIdentity('account.profile'),
            new CompiledViewIdentity(str_repeat('a', 64)),
            self::schema(),
            new ViewContext(['title' => new StringValue('safe')]),
            new RenderLimits(1024, 100),
        )));
    }

    public function testMismatchedContextFailsDuringConstruction(): void
    {
        $this->expectException(InvalidViewContractException::class);
        new RenderRequest(
            ContractVersion::Version1,
            new RenderOperationIdentity('render-2'),
            new LogicalViewIdentity('account.profile'),
            new CompiledViewIdentity(str_repeat('b', 64)),
            self::schema(),
            new ViewContext(['title' => new StringValue('too-long')]),
            new RenderLimits(1024, 100),
        );
    }

    private static function schema(): ViewContextSchema
    {
        return new ViewContextSchema(PresentationValueSchema::record([
            new PresentationFieldSchema('title', PresentationValueSchema::string(4), true),
        ]));
    }
}
