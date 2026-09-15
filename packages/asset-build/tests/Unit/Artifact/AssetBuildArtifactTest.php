<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Unit\Artifact;

use Kongono\AssetBuild\Build\NativeAssetBuilder;
use Kongono\AssetBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\TestCase;

final class AssetBuildArtifactTest extends TestCase
{
    public function testArtifactIsCanonicalAndOmitsRawAndSourcePaths(): void
    {
        $artifact = (new NativeAssetBuilder())->build(BuildProbe::sourceSet())->artifact();
        self::assertStringEndsWith("\n", $artifact->bytes());
        self::assertStringNotContainsString('assets/app.js', $artifact->bytes());
        self::assertStringNotContainsString("console.log('kongono')", $artifact->bytes());
        self::assertSame(hash('sha256', $artifact->bytes()), $artifact->buildIdentity()->value());
        self::assertFalse(str_starts_with($artifact->bytes(), "\xEF\xBB\xBF"));
        self::assertStringNotContainsString("\r", $artifact->bytes());
        self::assertStringNotContainsString("\0", $artifact->bytes());
        self::assertFalse(str_ends_with($artifact->bytes(), "\n\n"));
        $model = self::decodeModel($artifact->bytes());
        self::assertSame(['schema', 'artifact_version', 'contract_version', 'builder', 'source_set_sha256', 'graph_sha256', 'declarations', 'dependencies', 'entry_points', 'outputs'], array_keys($model));
        $builder = self::object($model['builder'] ?? null);
        $declarations = self::list($model['declarations'] ?? null);
        $dependencies = self::list($model['dependencies'] ?? null);
        $entryPoints = self::list($model['entry_points'] ?? null);
        $outputs = self::list($model['outputs'] ?? null);
        $declaration = self::object($declarations[0] ?? null);
        $dependency = self::object($dependencies[0] ?? null);
        $entryPoint = self::object($entryPoints[0] ?? null);
        $output = self::object($outputs[0] ?? null);
        $integrity = self::object($output['integrity'] ?? null);
        self::assertSame(['package', 'profile', 'version'], array_keys($builder));
        self::assertSame(['identity', 'kind', 'content_type'], array_keys($declaration));
        self::assertSame(['source', 'target', 'kind'], array_keys($dependency));
        self::assertSame(['identity'], array_keys($entryPoint));
        self::assertSame(['identity', 'kind', 'content_type', 'content_sha256', 'byte_count', 'integrity', 'preload_hint', 'path'], array_keys($output));
        self::assertSame(['algorithm', 'digest'], array_keys($integrity));
        foreach (['source_path', 'public_url', 'base_url', 'origin', 'cdn', 'csp', 'nonce', 'header', 'cookie', 'credential', 'environment', 'timestamp', 'hostname', 'process_id', 'current_pointer'] as $denied) {
            self::assertStringNotContainsString($denied, $artifact->bytes());
        }
    }

    /** @return array<string, mixed> */
    private static function decodeModel(string $bytes): array
    {
        return self::object(json_decode($bytes, true, 64, JSON_THROW_ON_ERROR));
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

    /** @return list<mixed> */
    private static function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new \LogicException('Expected artifact list.');
        }

        return $value;
    }
}
