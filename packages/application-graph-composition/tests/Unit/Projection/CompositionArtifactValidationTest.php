<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Tests\Unit\Projection;

use Kongono\ApplicationGraphComposition\Exception\InvalidCompositionGraphProjectionException;
use Kongono\ApplicationGraphComposition\Projection\CompositionIrProjector;
use Kongono\Composition\Build\CompositionArtifact;
use PHPUnit\Framework\TestCase;

final class CompositionArtifactValidationTest extends TestCase
{
    public function testMalformedArtifactFailsClosedWithoutRawValueDisclosure(): void
    {
        $artifact = new CompositionArtifact('private', str_repeat('0', 64), str_repeat('0', 64), str_repeat('0', 64), str_repeat('0', 64), str_repeat('0', 64));
        try {
            (new CompositionIrProjector())->project($artifact);
            self::fail('Expected rejection.');
        } catch (InvalidCompositionGraphProjectionException $exception) {
            self::assertSame('KGN-GRAPH-1111', $exception->diagnosticId());
            self::assertStringNotContainsString('private', (string) $exception);
        }
    }

    public function testVersionOneModelIsVisiblyUnsupported(): void
    {
        $model = ['schema' => 'kongono.composition', 'version' => 1, 'inputs' => [], 'services' => [], 'bindings' => [], 'roots' => []];
        $artifact = self::artifact($model);
        $this->expectException(InvalidCompositionGraphProjectionException::class);
        try {
            (new CompositionIrProjector())->project($artifact);
        } catch (InvalidCompositionGraphProjectionException $exception) {
            self::assertSame('KGN-GRAPH-1112', $exception->diagnosticId());
            throw $exception;
        }
    }

    /** @param array<string, mixed> $model */
    private static function artifact(array $model): CompositionArtifact
    {
        $zero = str_repeat('0', 64);
        $empty = hash('sha256', '');
        $modelBytes = json_encode($model, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        $modelHash = hash('sha256', $modelBytes);
        $data = ['schema' => 'kongono.composition-artifact', 'version' => 1, 'source' => ['path' => 'kongono.composition.json', 'bytes' => 1, 'sha256' => $zero], 'project' => ['composer_json_sha256' => $zero, 'composer_lock_sha256' => $zero], 'builder' => ['package' => 'kongono/composition', 'contract_version' => 1, 'implementation_sha256' => $zero], 'symbol_state' => ['source_set_sha256' => $empty, 'source_count' => 0, 'source_bytes' => 0, 'sources' => []], 'model_sha256' => $modelHash, 'model' => $model];
        $bytes = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        return new CompositionArtifact($bytes, hash('sha256', $bytes), $zero, $modelHash, $empty, $zero);
    }
}
