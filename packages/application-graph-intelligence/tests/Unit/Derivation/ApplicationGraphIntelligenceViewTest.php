<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Tests\Unit\Derivation;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Exception\InvalidApplicationGraphIntelligenceValueException;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ApplicationGraphIntelligenceViewTest extends TestCase
{
    public function testRoleVocabularyAndViewSerializationAreExactAndDetached(): void
    {
        self::assertSame(['chie', 'hantei', 'oshie'], array_column(DeveloperIntelligenceRole::cases(), 'value'));
        $view = self::view();
        $expected = [
            'schema' => 'kongono.application-graph-intelligence-view',
            'version' => 1,
            'role' => 'chie',
            'artifact_sha256' => str_repeat('a', 64),
            'graph_identity' => 'kgn:graph:v1:graph:' . str_repeat('b', 64),
            'graph_contract_version' => 1,
            'node_count' => 10,
            'relation_count' => 26,
            'node_type_counts' => [
                'composition.input' => 1,
                'composition.service' => 2,
                'composition.contract' => 3,
                'composition.root' => 4,
            ],
            'relation_type_counts' => [
                'composition.depends_on' => 5,
                'composition.consumes_input' => 6,
                'composition.implements_contract' => 7,
                'composition.exposes_root' => 8,
            ],
            'scope' => 'composition-v1',
            'visibility' => 'safe-aggregate-only',
            'currentness' => 'not-established',
            'authority' => 'evidence-only',
            'uncertainties' => [
                'individual-facts-omitted',
                'currentness-not-established',
                'authorization-not-granted',
            ],
        ];

        $first = $view->toArray();
        $first['node_type_counts'] = [];
        $first['uncertainties'] = [];
        $uncertainties = $view->uncertainties();
        array_pop($uncertainties);

        self::assertSame($expected, $view->toArray());
        self::assertSame($expected['uncertainties'], $view->uncertainties());
        self::assertTrue((new ReflectionClass($view))->isReadOnly());
        foreach (NodeType::cases() as $position => $type) {
            self::assertSame($position + 1, $view->nodeTypeCount($type));
        }
        foreach (RelationType::cases() as $position => $type) {
            self::assertSame($position + 5, $view->relationTypeCount($type));
        }
    }

    public function testEveryInvalidViewFamilyFailsWithoutDisclosingRejectedValues(): void
    {
        $raw = 'TOP-SECRET-/workspace/customer.php';
        $validNodes = self::nodeCounts();
        $validRelations = self::relationCounts();
        $cases = [
            fn() => new ApplicationGraphIntelligenceView(DeveloperIntelligenceRole::Chie, $raw, new GraphIdentity(str_repeat('b', 64)), ContractVersion::Version1, 10, 26, $validNodes, $validRelations),
            fn() => new ApplicationGraphIntelligenceView(DeveloperIntelligenceRole::Chie, str_repeat('a', 64), new GraphIdentity(str_repeat('b', 64)), ContractVersion::Version1, -1, 26, $validNodes, $validRelations),
            fn() => new ApplicationGraphIntelligenceView(DeveloperIntelligenceRole::Chie, str_repeat('a', 64), new GraphIdentity(str_repeat('b', 64)), ContractVersion::Version1, 10, 26, array_slice($validNodes, 1, null, true), $validRelations),
            fn() => new ApplicationGraphIntelligenceView(DeveloperIntelligenceRole::Chie, str_repeat('a', 64), new GraphIdentity(str_repeat('b', 64)), ContractVersion::Version1, 11, 26, $validNodes, $validRelations),
            fn() => new ApplicationGraphIntelligenceView(DeveloperIntelligenceRole::Chie, str_repeat('a', 64), new GraphIdentity(str_repeat('b', 64)), ContractVersion::Version1, 10, 26, [...$validNodes, 'composition.root' => -1], $validRelations),
            fn() => new ApplicationGraphIntelligenceView(DeveloperIntelligenceRole::Chie, str_repeat('a', 64), new GraphIdentity(str_repeat('b', 64)), ContractVersion::Version1, 10, 26, $validNodes, $validRelations, visibility: $raw),
            fn() => new ApplicationGraphIntelligenceView(DeveloperIntelligenceRole::Chie, str_repeat('a', 64), new GraphIdentity(str_repeat('b', 64)), ContractVersion::Version1, 10, 26, $validNodes, $validRelations, uncertainties: array_reverse(ApplicationGraphIntelligenceView::UNCERTAINTIES)),
            fn() => new ApplicationGraphIntelligenceView(DeveloperIntelligenceRole::Chie, str_repeat('a', 64), new GraphIdentity(str_repeat('b', 64)), ContractVersion::Version1, 10, 26, [...$validNodes, 'composition.root' => $raw], $validRelations),
        ];

        foreach ($cases as $case) {
            try {
                $case();
                self::fail('Invalid view was accepted.');
            } catch (InvalidApplicationGraphIntelligenceValueException $exception) {
                self::assertSame('KGN-APPLICATION-GRAPH-INTELLIGENCE-1001', $exception->diagnosticId());
                self::assertStringNotContainsString($raw, $exception->getMessage());
                self::assertStringNotContainsString($raw, (string) $exception);
            }
        }
    }

    private static function view(): ApplicationGraphIntelligenceView
    {
        return new ApplicationGraphIntelligenceView(
            DeveloperIntelligenceRole::Chie,
            str_repeat('a', 64),
            new GraphIdentity(str_repeat('b', 64)),
            ContractVersion::Version1,
            10,
            26,
            self::nodeCounts(),
            self::relationCounts(),
        );
    }

    /** @return array<string, int> */
    private static function nodeCounts(): array
    {
        return array_combine(
            array_map(static fn(NodeType $type): string => $type->value, NodeType::cases()),
            [1, 2, 3, 4],
        );
    }

    /** @return array<string, int> */
    private static function relationCounts(): array
    {
        return array_combine(
            array_map(static fn(RelationType $type): string => $type->value, RelationType::cases()),
            [5, 6, 7, 8],
        );
    }
}
