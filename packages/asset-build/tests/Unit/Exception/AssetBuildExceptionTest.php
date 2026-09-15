<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Unit\Exception;

use Kongono\AssetBuild\Exception\AssetPublicationException;
use Kongono\AssetBuild\Exception\InvalidAssetBuildArtifactException;
use Kongono\AssetBuild\Exception\InvalidAssetGraphException;
use Kongono\AssetBuild\Exception\InvalidAssetSourceException;
use Kongono\AssetBuild\Exception\StaleAssetBuildArtifactException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AssetBuildExceptionTest extends TestCase
{
    public function testPreviousIdentityAndStringificationAreDeterministic(): void
    {
        $previous = new RuntimeException('private-value');
        $exception = InvalidAssetSourceException::invalid('source_path_grammar', previous: $previous);
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame('KGN-ASSET-BUILD-1911', $exception->diagnosticId());
        self::assertStringNotContainsString('private-value', (string) $exception);
        self::assertStringContainsString($exception->diagnosticRemediation(), (string) $exception);
    }

    public function testAllFailureFamiliesUseFixedDiagnosticsAndClosedFactories(): void
    {
        $previous = new RuntimeException('private-cause');
        $exceptions = [
            InvalidAssetSourceException::invalid('source_path_grammar', entryCount: 1, previous: $previous),
            InvalidAssetGraphException::invalid('dependency_depth', depth: 257, limit: 256, previous: $previous),
            InvalidAssetBuildArtifactException::output('output_metadata_relation', previous: $previous),
            InvalidAssetBuildArtifactException::invalid('artifact_schema', previous: $previous),
            StaleAssetBuildArtifactException::stale($previous),
            AssetPublicationException::failed('temporary_cleanup_failed', $previous, 'failed'),
        ];
        foreach ($exceptions as $index => $exception) {
            self::assertSame(sprintf('KGN-ASSET-BUILD-%d', 1911 + $index), $exception->diagnosticId());
            self::assertSame($previous, $exception->getPrevious());
            self::assertStringNotContainsString('private-cause', $exception->getMessage());
            self::assertStringNotContainsString('private-cause', (string) $exception);
            self::assertNotSame('', $exception->diagnosticTitle());
            self::assertNotSame('', $exception->diagnosticRemediation());
        }
        foreach ([
            [InvalidAssetSourceException::class, 'invalid'],
            [InvalidAssetGraphException::class, 'invalid'],
            [InvalidAssetBuildArtifactException::class, 'output'],
            [InvalidAssetBuildArtifactException::class, 'invalid'],
            [StaleAssetBuildArtifactException::class, 'stale'],
            [AssetPublicationException::class, 'failed'],
        ] as [$class, $method]) {
            foreach ((new \ReflectionMethod($class, $method))->getParameters() as $parameter) {
                self::assertNotSame('array', (string) $parameter->getType());
            }
        }
    }
}
