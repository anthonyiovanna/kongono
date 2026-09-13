<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Render;

use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Render\RenderedOutput;
use Kongono\ViewContract\Render\RenderLimits;
use Kongono\ViewContract\Render\RenderMetadata;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RenderMetadataTest extends TestCase
{
    public function testMetadataIsDerivedDeterministicallyFromExactOutput(): void
    {
        $limits = new RenderLimits(64, 10);
        $metadata = RenderMetadata::from(new RenderedOutput('exact', $limits), $limits, 10, 4);
        self::assertSame(5, $metadata->outputByteCount());
        self::assertSame(hash('sha256', 'exact'), $metadata->outputSha256());
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $metadata->outputSha256());
        self::assertSame(10, $metadata->renderStepCount());
        self::assertSame(4, $metadata->escapedValueCount());
        self::assertTrue($metadata->equals(RenderMetadata::from(new RenderedOutput('exact', $limits), $limits, 10, 4)));
    }

    /** @return iterable<string, array{int, int}> */
    public static function invalidCounts(): iterable
    {
        yield 'negative steps' => [-1, 0];
        yield 'steps above limit' => [11, 0];
        yield 'negative escaped' => [1, -1];
        yield 'escaped above steps' => [1, 2];
    }

    #[DataProvider('invalidCounts')]
    public function testInvalidCountRelationsFailClosed(int $steps, int $escaped): void
    {
        $limits = new RenderLimits(64, 10);
        $this->expectException(InvalidViewContractException::class);
        RenderMetadata::from(new RenderedOutput('exact', $limits), $limits, $steps, $escaped);
    }
}
