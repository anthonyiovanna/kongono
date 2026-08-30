<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Tests\Unit\Projection;

use Kongono\ApplicationGraphComposition\Projection\CompositionIrProjector;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use Kongono\Composition\Build\CompositionArtifact;
use PHPUnit\Framework\TestCase;

final class CompositionProjectionMappingTest extends TestCase
{
    public function testExactNodesAndDeduplicatedRelationsAreProjected(): void
    {
        $projection = (new CompositionIrProjector())->project(self::artifact());
        self::assertCount(6, $projection->nodeRecords());
        self::assertSame(
            [NodeType::CompositionContract->value, NodeType::CompositionInput->value, NodeType::CompositionRoot->value, NodeType::CompositionService->value],
            array_values(array_unique(array_map(static fn($record): string => $record->type()->value, $projection->nodeRecords()))),
        );
        self::assertCount(5, $projection->relationRecords());
        self::assertEqualsCanonicalizing(
            array_column(RelationType::cases(), 'value'),
            array_values(array_unique(array_map(static fn($record): string => $record->type()->value, $projection->relationRecords()))),
        );
    }

    public static function artifact(): CompositionArtifact
    {
        $model = ['schema' => 'kongono.composition', 'version' => 2, 'inputs' => [['contract' => 'App\\Input', 'lifetime' => 'application']], 'services' => [['class' => 'App\\Child', 'lifetime' => 'application', 'arguments' => [], 'cleanup' => null], ['class' => 'App\\Root', 'lifetime' => 'application', 'arguments' => [['parameter' => 'input', 'input' => 'App\\Input'], ['parameter' => 'child', 'service' => 'App\\Contract'], ['parameter' => 'optional', 'default' => true]], 'cleanup' => null]], 'bindings' => [['contract' => 'App\\Contract', 'service' => 'App\\Child']], 'roots' => [['kind' => 'application', 'contract' => 'App\\Root']]];
        $zero = str_repeat('0', 64);
        $empty = hash('sha256', '');
        $modelHash = hash('sha256', json_encode($model, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
        $data = ['schema' => 'kongono.composition-artifact', 'version' => 1, 'source' => ['path' => 'kongono.composition.json', 'bytes' => 1, 'sha256' => $zero], 'project' => ['composer_json_sha256' => $zero, 'composer_lock_sha256' => $zero], 'builder' => ['package' => 'kongono/composition', 'contract_version' => 1, 'implementation_sha256' => $zero], 'symbol_state' => ['source_set_sha256' => $empty, 'source_count' => 0, 'source_bytes' => 0, 'sources' => []], 'model_sha256' => $modelHash, 'model' => $model];
        $bytes = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        return new CompositionArtifact($bytes, hash('sha256', $bytes), $zero, $modelHash, $empty, $zero);
    }
}
