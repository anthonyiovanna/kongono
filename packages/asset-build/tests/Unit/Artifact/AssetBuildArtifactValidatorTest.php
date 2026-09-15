<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Unit\Artifact;

use Kongono\AssetBuild\Artifact\AssetBuildArtifactValidator;
use Kongono\AssetBuild\Build\NativeAssetBuilder;
use Kongono\AssetBuild\Exception\InvalidAssetBuildArtifactException;
use Kongono\AssetBuild\Exception\StaleAssetBuildArtifactException;
use Kongono\AssetBuild\Source\AssetSource;
use Kongono\AssetBuild\Source\AssetSourcePath;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetBuild\Tests\Fixture\BuildProbe;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Dependency\AssetDependency;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use Kongono\AssetContract\EntryPoint\AssetEntryPoint;
use PHPUnit\Framework\TestCase;

final class AssetBuildArtifactValidatorTest extends TestCase
{
    public function testStructureAndCurrentnessRoundTrip(): void
    {
        $result = (new NativeAssetBuilder())->build(BuildProbe::sourceSet());
        $validator = new AssetBuildArtifactValidator();
        self::assertTrue($result->artifact()->equals($validator->validateStructure($result->artifact()->bytes(), $result->outputBytesByPath())));
        self::assertTrue($result->artifact()->equals($validator->validateCurrent(BuildProbe::sourceSet(true), $result->artifact()->bytes(), $result->outputBytesByPath())));
    }

    public function testDuplicateMemberAndStaleSourceFailClosed(): void
    {
        $result = (new NativeAssetBuilder())->build(BuildProbe::sourceSet());
        $duplicate = preg_replace('/\A\{/', '{"schema":"duplicate",', $result->artifact()->bytes());
        self::assertIsString($duplicate);
        try {
            (new AssetBuildArtifactValidator())->validateStructure($duplicate, $result->outputBytesByPath());
            self::fail('Expected duplicate failure.');
        } catch (InvalidAssetBuildArtifactException $exception) {
            self::assertSame('KGN-ASSET-BUILD-1914', $exception->diagnosticId());
        }
        $this->expectException(StaleAssetBuildArtifactException::class);
        (new AssetBuildArtifactValidator())->validateCurrent(BuildProbe::sourceSet(scriptBytes: 'changed'), $result->artifact()->bytes(), $result->outputBytesByPath());
    }

    public function testFramingDepthSchemaMemberOrderAndNestedDuplicatesFailClosed(): void
    {
        $result = (new NativeAssetBuilder())->build(BuildProbe::sourceSet());
        $bytes = $result->artifact()->bytes();
        $model = self::object(json_decode($bytes, true, 64, JSON_THROW_ON_ERROR));
        $missing = $model;
        unset($missing['schema']);
        $extra = $model;
        $extra['extra'] = 'denied';
        $reorderedBuilder = $model;
        $reorderedBuilder['builder'] = ['profile' => 'native-copy/1', 'package' => 'kongono/asset-build', 'version' => 1];
        $invalid = [
            substr($bytes, 0, -1),
            $bytes . "\n",
            "\xEF\xBB\xBF" . $bytes,
            str_replace("\n", "\r\n", $bytes),
            str_replace('"artifact_version":1', '"artifact_version":"1"', $bytes),
            str_replace('"artifact_version":1', '"artifact_version":2', $bytes),
            str_replace('"profile":"native-copy/1"', '"profile":"adapter/1"', $bytes),
            str_replace('{"schema"', '{ "schema"', $bytes),
            preg_replace('/\A\{/', '{"schema":"duplicate",', $bytes),
            str_replace('"builder":{"package":', '"builder":{"package":"duplicate","package":', $bytes),
            str_replace('{"schema":"kongono.asset-build-artifact","artifact_version":1', '{"artifact_version":1,"schema":"kongono.asset-build-artifact"', $bytes),
            self::encode($missing),
            self::encode($extra),
            self::encode($reorderedBuilder),
            str_repeat(' ', 8_388_608) . "\n",
            '{"x":' . str_repeat('[', 64) . '0' . str_repeat(']', 64) . "}\n",
        ];
        foreach ($invalid as $candidate) {
            self::assertIsString($candidate);
            try {
                (new AssetBuildArtifactValidator())->validateStructure($candidate, $result->outputBytesByPath());
                self::fail('Expected invalid artifact failure.');
            } catch (InvalidAssetBuildArtifactException $exception) {
                self::assertSame('KGN-ASSET-BUILD-1914', $exception->diagnosticId());
            }
        }
    }

    public function testOutputMapAndArtifactOutputRelationsAreExact(): void
    {
        $result = (new NativeAssetBuilder())->build(BuildProbe::sourceSet());
        $bytes = $result->artifact()->bytes();
        $map = $result->outputBytesByPath();
        $firstPath = array_key_first($map);
        self::assertIsString($firstPath);
        $missing = $map;
        unset($missing[$firstPath]);
        $extra = $map;
        $extra['objects/sha256/' . str_repeat('0', 2) . '/' . str_repeat('0', 64)] = '';
        $changed = $map;
        $changed[$firstPath] .= 'changed';
        foreach ([$missing, $extra, $changed, array_values($map), ['unsafe' => 1]] as $invalidMap) {
            try {
                (new AssetBuildArtifactValidator())->validateStructure($bytes, $invalidMap);
                self::fail('Expected output-map failure.');
            } catch (InvalidAssetBuildArtifactException $exception) {
                self::assertSame('KGN-ASSET-BUILD-1914', $exception->diagnosticId());
            }
        }

        $model = self::object(json_decode($bytes, true, 64, JSON_THROW_ON_ERROR));
        $outputs = self::outputRows($model);
        $firstOutput = $outputs[0];
        $mutations = [];
        $wrongGraph = $model;
        $wrongGraph['graph_sha256'] = str_repeat('0', 64);
        $mutations[] = $wrongGraph;
        $wrongCount = $model;
        $wrongCountRows = $outputs;
        $wrongCountValue = $firstOutput['byte_count'] ?? null;
        self::assertIsInt($wrongCountValue);
        $wrongCountRows[0]['byte_count'] = $wrongCountValue + 1;
        $wrongCount['outputs'] = $wrongCountRows;
        $mutations[] = $wrongCount;
        $wrongIntegrity = $model;
        $wrongIntegrityRows = $outputs;
        $integrity = self::object($firstOutput['integrity'] ?? null);
        $integrity['digest'] = str_repeat('0', 96);
        $wrongIntegrityRows[0]['integrity'] = $integrity;
        $wrongIntegrity['outputs'] = $wrongIntegrityRows;
        $mutations[] = $wrongIntegrity;
        $wrongPath = $model;
        $wrongPathRows = $outputs;
        $wrongPathRows[0]['path'] = 'objects/sha256/00/' . str_repeat('0', 64);
        $wrongPath['outputs'] = $wrongPathRows;
        $mutations[] = $wrongPath;
        $wrongDigest = $model;
        $wrongDigestRows = $outputs;
        $wrongDigestRows[0]['content_sha256'] = str_repeat('0', 64);
        $wrongDigest['outputs'] = $wrongDigestRows;
        $mutations[] = $wrongDigest;
        $reordered = $model;
        $reordered['outputs'] = array_reverse($outputs);
        $mutations[] = $reordered;
        $duplicate = $model;
        $duplicate['outputs'] = [...$outputs, $firstOutput];
        $mutations[] = $duplicate;
        $missingOutput = $model;
        $missingRows = $outputs;
        array_pop($missingRows);
        $missingOutput['outputs'] = $missingRows;
        $mutations[] = $missingOutput;
        foreach ($mutations as $mutation) {
            try {
                (new AssetBuildArtifactValidator())->validateStructure(self::encode($mutation), $map);
                self::fail('Expected artifact/output relation failure.');
            } catch (InvalidAssetBuildArtifactException $exception) {
                self::assertSame('KGN-ASSET-BUILD-1914', $exception->diagnosticId());
            }
        }
    }

    public function testEveryCurrentInputDimensionIsCompared(): void
    {
        $base = BuildProbe::sourceSet();
        $result = (new NativeAssetBuilder())->build($base);
        $sources = $base->sources();
        $pathChanged = $sources;
        $pathChanged[0] = new AssetSource($sources[0]->identity(), new AssetSourcePath('assets/renamed.js'), $sources[0]->bytes());

        $declarations = $base->declarationSet()->declarations();
        $declarationChanged = $declarations;
        $declarationChanged[0] = new AssetDeclaration(ContractVersion::Version1, $declarations[0]->identity(), AssetKind::Data, AssetContentType::ApplicationJson);
        $dependency = $base->declarationSet()->dependencies()[0];
        $dependencyChanged = new AssetDeclarationSet(ContractVersion::Version1, $declarations, [new AssetDependency(ContractVersion::Version1, $dependency->source(), $dependency->target(), AssetDependencyKind::Dynamic)], $base->declarationSet()->entryPoints());
        $entryChanged = new AssetDeclarationSet(ContractVersion::Version1, $declarations, $base->declarationSet()->dependencies(), [
            ...$base->declarationSet()->entryPoints(),
            new AssetEntryPoint(ContractVersion::Version1, $declarations[1]->identity()),
        ]);
        $candidates = [
            new AssetSourceSet($base->declarationSet(), $pathChanged),
            BuildProbe::sourceSet(scriptBytes: 'changed'),
            new AssetSourceSet(new AssetDeclarationSet(ContractVersion::Version1, $declarationChanged, $base->declarationSet()->dependencies(), $base->declarationSet()->entryPoints()), $sources),
            new AssetSourceSet($dependencyChanged, $sources),
            new AssetSourceSet($entryChanged, $sources),
        ];
        foreach ($candidates as $candidate) {
            try {
                (new AssetBuildArtifactValidator())->validateCurrent($candidate, $result->artifact()->bytes(), $result->outputBytesByPath());
                self::fail('Expected stale artifact failure.');
            } catch (StaleAssetBuildArtifactException $exception) {
                self::assertSame('KGN-ASSET-BUILD-1915', $exception->diagnosticId());
            }
        }
    }

    /** @param array<array-key, mixed> $model */
    private static function encode(array $model): string
    {
        return json_encode($model, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /** @return array<string, mixed> */
    private static function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new \LogicException('Expected artifact object.');
        }
        $copy = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                throw new \LogicException('Expected artifact object key.');
            }
            $copy[$key] = $entry;
        }

        return $copy;
    }

    /**
     * @param array<string, mixed> $model
     * @return list<array<string, mixed>>
     */
    private static function outputRows(array $model): array
    {
        $value = $model['outputs'] ?? null;
        if (!is_array($value) || !array_is_list($value)) {
            throw new \LogicException('Expected artifact outputs.');
        }
        $rows = [];
        foreach ($value as $row) {
            $rows[] = self::object($row);
        }

        return $rows;
    }
}
