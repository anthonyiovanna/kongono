<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Unit\Graph;

use Kongono\AssetBuild\Exception\InvalidAssetGraphException;
use Kongono\AssetBuild\Graph\AssetGraphBuilder;
use Kongono\AssetBuild\Tests\Fixture\BuildProbe;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetGraphBuilderTest extends TestCase
{
    public function testDependenciesPrecedeDependentsAndInputOrderIsIrrelevant(): void
    {
        $left = (new AssetGraphBuilder())->build(BuildProbe::sourceSet());
        $right = (new AssetGraphBuilder())->build(BuildProbe::sourceSet(true));
        self::assertSame(['app.style', 'app.script'], $left->topologicalOrder());
        self::assertSame(2, $left->maximumDepth());
        self::assertTrue($left->identity()->equals($right->identity()));
        self::assertNotSame(BuildProbe::sourceSet()->identity()->value(), $left->identity()->value());
    }

    public function testEmptyGraphAndOrdinalTieBreakingAreExact(): void
    {
        $empty = (new AssetGraphBuilder())->build(BuildProbe::graphSourceSet(0, [], []));
        self::assertSame([], $empty->nodes());
        self::assertSame([], $empty->topologicalOrder());
        self::assertSame(0, $empty->maximumDepth());

        $tied = (new AssetGraphBuilder())->build(BuildProbe::graphSourceSet(3, [
            [2, 1, AssetDependencyKind::Static],
            [2, 0, AssetDependencyKind::Static],
        ], [2], true));
        self::assertSame(['asset.0000', 'asset.0001', 'asset.0002'], $tied->topologicalOrder());
        self::assertSame(['asset.0000', 'asset.0001', 'asset.0002'], array_map(static fn($node): string => $node->declaration()->identity()->value(), $tied->nodes()));
    }

    #[DataProvider('dependencyKinds')]
    public function testEveryDependencyKindParticipatesInCycleValidation(AssetDependencyKind $kind): void
    {
        $this->expectException(InvalidAssetGraphException::class);
        (new AssetGraphBuilder())->build(BuildProbe::graphSourceSet(3, [
            [0, 1, $kind],
            [1, 2, $kind],
            [2, 0, $kind],
        ], [0]));
    }

    /** @return iterable<string, array{AssetDependencyKind}> */
    public static function dependencyKinds(): iterable
    {
        foreach (AssetDependencyKind::cases() as $kind) {
            yield $kind->value => [$kind];
        }
    }

    public function testMissingEntryPointAndUnreachableDeclarationFailClosed(): void
    {
        foreach ([
            BuildProbe::graphSourceSet(1, [], []),
            BuildProbe::graphSourceSet(2, [], [0]),
        ] as $sourceSet) {
            try {
                (new AssetGraphBuilder())->build($sourceSet);
                self::fail('Expected invalid graph failure.');
            } catch (InvalidAssetGraphException $exception) {
                self::assertSame('KGN-ASSET-BUILD-1912', $exception->diagnosticId());
            }
        }
    }

    public function testDepthBoundaryAccepts256AndRejects257(): void
    {
        $accepted = [];
        for ($index = 1; $index < 256; ++$index) {
            $accepted[] = [$index, $index - 1, AssetDependencyKind::Dynamic];
        }
        self::assertSame(256, (new AssetGraphBuilder())->build(BuildProbe::graphSourceSet(256, $accepted, [255]))->maximumDepth());

        $rejected = [...$accepted, [256, 255, AssetDependencyKind::Resource]];
        try {
            (new AssetGraphBuilder())->build(BuildProbe::graphSourceSet(257, $rejected, [256]));
            self::fail('Expected dependency-depth failure.');
        } catch (InvalidAssetGraphException $exception) {
            $fields = $exception->diagnosticContext()->fields();
            self::assertSame('dependency_depth', $fields[0]->value());
            self::assertSame(257, $fields[1]->value());
            self::assertSame(256, $fields[2]->value());
        }
    }
}
