<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Tests\Unit\Asset;

use Fiber;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Dependency\AssetDependency;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use Kongono\AssetContract\EntryPoint\AssetEntryPoint;
use Kongono\AssetContract\Exception\InvalidAssetContractException;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetDeclarationSetTest extends TestCase
{
    public function testEmptySetIsFirstClassAndListsAreDetached(): void
    {
        $set = new AssetDeclarationSet(ContractVersion::Version1, [], [], []);
        self::assertSame(0, $set->declarationCount());
        self::assertSame([], $set->declarations());
        self::assertNull($set->declaration(new LogicalAssetIdentity('missing')));

        $declarations = [$this->declaration('b'), $this->declaration('a')];
        $set = new AssetDeclarationSet(ContractVersion::Version1, $declarations, [], []);
        $declarations[] = $this->declaration('c');
        $returned = $set->declarations();
        array_pop($returned);
        self::assertSame(['a', 'b'], array_map(static fn(AssetDeclaration $value): string => $value->identity()->value(), $set->declarations()));
    }

    public function testRelationsAndEntryPointsAreCanonicalAndCyclesAreDeliberatelyAccepted(): void
    {
        $a = $this->declaration('a');
        $b = $this->declaration('b');
        $dependencies = [
            new AssetDependency(ContractVersion::Version1, $b->identity(), $a->identity(), AssetDependencyKind::Dynamic),
            new AssetDependency(ContractVersion::Version1, $a->identity(), $b->identity(), AssetDependencyKind::Static),
        ];
        $set = new AssetDeclarationSet(ContractVersion::Version1, [$b, $a], $dependencies, [new AssetEntryPoint(ContractVersion::Version1, $b->identity()), new AssetEntryPoint(ContractVersion::Version1, $a->identity())]);
        self::assertSame(['a', 'b'], array_map(static fn(AssetDeclaration $value): string => $value->identity()->value(), $set->declarations()));
        self::assertSame(['a', 'b'], array_map(static fn(AssetEntryPoint $value): string => $value->identity()->value(), $set->entryPoints()));
        self::assertSame(["a\0b\0static", "b\0a\0dynamic"], array_map(static fn(AssetDependency $value): string => $value->orderingKey(), $set->dependencies()));
        self::assertTrue($set->equals(new AssetDeclarationSet(ContractVersion::Version1, [$a, $b], array_reverse($dependencies), array_reverse($set->entryPoints()))));
    }

    #[DataProvider('invalidSetProvider')]
    public function testInvalidListsDuplicatesAndMissingMembershipAreRejected(callable $factory): void
    {
        $this->expectException(InvalidAssetContractException::class);
        $factory();
    }

    /** @return iterable<string, array{callable(): object}> */
    public static function invalidSetProvider(): iterable
    {
        $declaration = static fn(string $id): AssetDeclaration => new AssetDeclaration(ContractVersion::Version1, new LogicalAssetIdentity($id), AssetKind::Script, AssetContentType::TextJavascript);
        yield 'declarations not list' => [static fn() => new AssetDeclarationSet(ContractVersion::Version1, ['x' => $declaration('a')], [], [])];
        yield 'foreign declaration' => [static fn() => new AssetDeclarationSet(ContractVersion::Version1, [new \stdClass()], [], [])];
        yield 'duplicate declaration' => [static fn() => new AssetDeclarationSet(ContractVersion::Version1, [$declaration('a'), $declaration('a')], [], [])];
        yield 'dependencies not list' => [static fn() => new AssetDeclarationSet(ContractVersion::Version1, [$declaration('a')], ['x' => new \stdClass()], [])];
        yield 'foreign dependency' => [static fn() => new AssetDeclarationSet(ContractVersion::Version1, [$declaration('a')], [new \stdClass()], [])];
        yield 'missing dependency endpoint' => [static fn() => new AssetDeclarationSet(ContractVersion::Version1, [$declaration('a')], [new AssetDependency(ContractVersion::Version1, new LogicalAssetIdentity('a'), new LogicalAssetIdentity('b'), AssetDependencyKind::Static)], [])];
        yield 'entry points not list' => [static fn() => new AssetDeclarationSet(ContractVersion::Version1, [$declaration('a')], [], ['x' => new \stdClass()])];
        yield 'foreign entry point' => [static fn() => new AssetDeclarationSet(ContractVersion::Version1, [$declaration('a')], [], [new \stdClass()])];
        yield 'missing entry point declaration' => [static fn() => new AssetDeclarationSet(ContractVersion::Version1, [$declaration('a')], [], [new AssetEntryPoint(ContractVersion::Version1, new LogicalAssetIdentity('b'))])];
        yield 'duplicate entry point' => [static fn() => new AssetDeclarationSet(ContractVersion::Version1, [$declaration('a')], [], [new AssetEntryPoint(ContractVersion::Version1, new LogicalAssetIdentity('a')), new AssetEntryPoint(ContractVersion::Version1, new LogicalAssetIdentity('a'))])];
    }

    public function testCollectionBoundsAreFailClosed(): void
    {
        $this->expectException(InvalidAssetContractException::class);
        new AssetDeclarationSet(ContractVersion::Version1, array_fill(0, 1025, $this->declaration('a')), [], []);
    }

    public function testSequentialAndOverlappingFiberConstructionIsIsolated(): void
    {
        $first = new Fiber(fn(): AssetDeclarationSet => new AssetDeclarationSet(ContractVersion::Version1, [$this->declaration('z')], [], []));
        $second = new Fiber(fn(): AssetDeclarationSet => new AssetDeclarationSet(ContractVersion::Version1, [$this->declaration('a')], [], []));
        $first->start();
        $second->start();
        $firstSet = $first->getReturn();
        $secondSet = $second->getReturn();
        self::assertInstanceOf(AssetDeclarationSet::class, $firstSet);
        self::assertInstanceOf(AssetDeclarationSet::class, $secondSet);
        self::assertSame('z', $firstSet->declarations()[0]->identity()->value());
        self::assertSame('a', $secondSet->declarations()[0]->identity()->value());
        self::assertFalse($firstSet->equals($secondSet));
    }

    private function declaration(string $identity): AssetDeclaration
    {
        return new AssetDeclaration(ContractVersion::Version1, new LogicalAssetIdentity($identity), AssetKind::Script, AssetContentType::TextJavascript);
    }
}
