<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Render;

use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Identity\RenderOperationIdentity;
use Kongono\ViewContract\Lifecycle\RenderOperationState;
use Kongono\ViewContract\Render\RenderedOutput;
use Kongono\ViewContract\Render\RenderLimits;
use Kongono\ViewContract\Render\RenderMetadata;
use Kongono\ViewContract\Render\RenderResult;
use PHPUnit\Framework\TestCase;

final class RenderResultTest extends TestCase
{
    public function testCompletedResultPreservesExactEvidenceAndEquality(): void
    {
        $limits = new RenderLimits(64, 10);
        $output = new RenderedOutput('done', $limits);
        $metadata = RenderMetadata::from($output, $limits, 3, 2);
        $result = self::buildResult($output, $metadata);
        self::assertSame(ContractVersion::Version1, $result->contractVersion());
        self::assertSame(1, $result->contractVersion()->value);
        self::assertSame(RenderOperationState::Completed, $result->state());
        self::assertSame($output, $result->output());
        self::assertSame($metadata, $result->metadata());
        self::assertSame('render-result-1', $result->operationIdentity()->value());
        self::assertTrue($result->equals(self::buildResult(
            new RenderedOutput('done', $limits),
            RenderMetadata::from(new RenderedOutput('done', $limits), $limits, 3, 2),
        )));
    }

    public function testOutputAndMetadataRelationMustAgree(): void
    {
        $limits = new RenderLimits(64, 10);
        $other = new RenderedOutput('other', $limits);
        $this->expectException(InvalidViewContractException::class);
        self::buildResult(new RenderedOutput('done', $limits), RenderMetadata::from($other, $limits, 3, 2));
    }

    private static function buildResult(RenderedOutput $output, RenderMetadata $metadata): RenderResult
    {
        return new RenderResult(
            ContractVersion::Version1,
            new RenderOperationIdentity('render-result-1'),
            new LogicalViewIdentity('account.profile'),
            new CompiledViewIdentity(str_repeat('c', 64)),
            $output,
            $metadata,
        );
    }
}
