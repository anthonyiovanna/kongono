<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Tests\Unit\Inspection;

use Kongono\AssetApplication\Workflow\CurrentAssetApplicationBuild;
use Kongono\AssetInspection\Exception\AssetInspectionBindingException;
use Kongono\AssetInspection\Inspection\AssetInspection;
use Kongono\AssetInspection\Inspection\AssetInspectionRequest;
use Kongono\AssetInspection\Inspection\AssetInspector;
use Kongono\AssetInspection\Tests\Support\AssetInspectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetInspectorTest extends TestCase
{
    /** @return iterable<string, array{CurrentAssetApplicationBuild}> */
    public static function currentResults(): iterable
    {
        yield 'native' => [AssetInspectionFactory::current()];
        yield 'adapter' => [AssetInspectionFactory::current('adapter')];
        yield 'empty' => [AssetInspectionFactory::empty()];
    }

    #[DataProvider('currentResults')]
    public function testNativeAdapterAndEmptyEvidenceIsDeterministic(CurrentAssetApplicationBuild $current): void
    {
        $inspector = new AssetInspector();
        $first = $inspector->inspect(new AssetInspectionRequest($current));
        $second = $inspector->inspect(new AssetInspectionRequest($current));

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame(hash('sha256', $this->withoutIdentity($first)), $first->identity());
        self::assertSame($current->resolutionArtifact()->producerKind(), $first->producerKind());
        self::assertSame($current->declarationCount(), $first->count('declaration_count'));
        self::assertSame($current->resolutionArtifact()->entryCount(), $first->count('resolution_entry_count'));
        self::assertTrue($first->flag('structurally_validated'));
        self::assertTrue($first->flag('supplied_current_evidence'));
        self::assertFalse($first->flag('filesystem_read'));
    }

    public function testReturnedArraysAreDetachedAndExact(): void
    {
        $inspection = (new AssetInspector())->inspect(new AssetInspectionRequest(AssetInspectionFactory::current()));
        $bindings = $inspection->protectedBindings();
        $counts = $inspection->counts();
        $flags = $inspection->flags();
        $bindings['build_sha256'] = str_repeat('f', 64);
        $counts['output_count'] = 99;
        $flags['filesystem_read'] = true;

        self::assertSame(str_repeat('1', 64), $inspection->protectedBindings()['build_sha256']);
        self::assertSame(2, $inspection->count('output_count'));
        self::assertFalse($inspection->flag('filesystem_read'));
        self::assertSame([
            'resolution_sha256', 'build_sha256', 'source_set_sha256', 'graph_sha256', 'content_set_sha256',
        ], array_keys($inspection->protectedBindings()));
        self::assertSame([
            'declaration_count', 'dependency_count', 'entry_point_count', 'output_count', 'resolution_entry_count',
            'reachable_object_count', 'integrity_present_count', 'preload_present_count',
        ], array_keys($inspection->counts()));
    }

    public function testIncoherentCurrentCountsFailClosedWithoutRetainedState(): void
    {
        $valid = AssetInspectionFactory::current();
        $invalid = new CurrentAssetApplicationBuild($valid->resolutionArtifact(), AssetInspectionFactory::BASENAME_SECRET, 2, 1, 1, 1);
        $inspector = new AssetInspector();
        try {
            $inspector->inspect(new AssetInspectionRequest($invalid));
            self::fail('Mismatched output count must fail.');
        } catch (AssetInspectionBindingException $failure) {
            self::assertSame('KGN-ASSET-INSPECTION-1951', $failure->diagnosticId());
        }
        self::assertSame(2, $inspector->inspect(new AssetInspectionRequest($valid))->count('output_count'));
    }

    private function withoutIdentity(AssetInspection $inspection): string
    {
        $value = $inspection->toArray();
        unset($value['inspection_identity']);

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
