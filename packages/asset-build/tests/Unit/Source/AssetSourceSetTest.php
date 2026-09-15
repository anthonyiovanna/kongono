<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Unit\Source;

use Kongono\AssetBuild\Exception\InvalidAssetSourceException;
use Kongono\AssetBuild\Source\AssetSource;
use Kongono\AssetBuild\Source\AssetSourcePath;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetBuild\Tests\Fixture\BuildProbe;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use PHPUnit\Framework\TestCase;

final class AssetSourceSetTest extends TestCase
{
    public function testOrderIsDetachedCanonicalAndIdentityIncludesPathAndBytes(): void
    {
        $left = BuildProbe::sourceSet();
        $right = BuildProbe::sourceSet(true);
        self::assertTrue($left->equals($right));
        self::assertTrue($left->identity()->equals($right->identity()));
        self::assertSame(['app.script', 'app.style'], array_map(static fn($source): string => $source->identity()->value(), $left->sources()));
    }

    public function testEmptySetIsFirstClassAndMissingSourceFails(): void
    {
        $empty = new AssetSourceSet(new AssetDeclarationSet(ContractVersion::Version1, [], [], []), []);
        self::assertSame(0, $empty->sourceCount());
        $this->expectException(InvalidAssetSourceException::class);
        new AssetSourceSet(BuildProbe::sourceSet()->declarationSet(), []);
    }

    public function testCollectionShapeDuplicatesCoverageAndUnknownSourcesFailClosed(): void
    {
        $fixture = BuildProbe::sourceSet();
        $sources = $fixture->sources();
        $unknown = new AssetSource(new LogicalAssetIdentity('unknown.asset'), new AssetSourcePath('assets/unknown.bin'), 'unknown');
        $samePath = new AssetSource($sources[1]->identity(), $sources[0]->path(), $sources[1]->bytes());
        foreach ([
            ['not-a-list' => $sources[0]],
            [$sources[0], $sources[0]],
            [$sources[0], $samePath],
            [$sources[0], $sources[1], $unknown],
            [$sources[0], 'not-a-source'],
        ] as $invalid) {
            try {
                new AssetSourceSet($fixture->declarationSet(), $invalid);
                self::fail('Expected invalid source-set failure.');
            } catch (InvalidAssetSourceException $exception) {
                self::assertSame('KGN-ASSET-BUILD-1911', $exception->diagnosticId());
            }
        }
    }

    public function testSourceCountBoundaryAccepts1024AndRejects1025WithoutLargePayloads(): void
    {
        $accepted = BuildProbe::graphSourceSet(1024, [], []);
        self::assertSame(1024, $accepted->sourceCount());
        self::assertLessThan(20_000, $accepted->aggregateByteCount());

        $source = $accepted->sources()[0];
        $this->expectException(InvalidAssetSourceException::class);
        new AssetSourceSet(new AssetDeclarationSet(ContractVersion::Version1, [], [], []), array_fill(0, 1025, $source));
    }

    public function testAggregateBoundaryUsesOneShared64MiBRepresentativePayload(): void
    {
        $bytes = str_repeat('x', 67_108_864);
        $accepted = self::largeSet(2, $bytes);
        self::assertSame(134_217_728, $accepted->aggregateByteCount());

        try {
            self::largeSet(3, $bytes);
            self::fail('Expected aggregate byte bound failure.');
        } catch (InvalidAssetSourceException $exception) {
            self::assertSame('aggregate_bytes', $exception->diagnosticContext()->fields()[0]->value());
            self::assertSame(134_217_728, $exception->diagnosticContext()->fields()[2]->value());
        }
    }

    private static function largeSet(int $count, string $bytes): AssetSourceSet
    {
        $declarations = [];
        $sources = [];
        for ($index = 0; $index < $count; ++$index) {
            $identity = new LogicalAssetIdentity(sprintf('large.%d', $index));
            $declarations[] = new AssetDeclaration(ContractVersion::Version1, $identity, AssetKind::Data, AssetContentType::ApplicationOctetStream);
            $sources[] = new AssetSource($identity, new AssetSourcePath(sprintf('assets/large-%d.bin', $index)), $bytes);
        }

        return new AssetSourceSet(new AssetDeclarationSet(ContractVersion::Version1, $declarations, [], []), $sources);
    }
}
