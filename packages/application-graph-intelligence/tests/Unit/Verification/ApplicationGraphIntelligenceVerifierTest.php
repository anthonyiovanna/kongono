<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Tests\Unit\Verification;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphArtifact\Inspection\ApplicationGraphArtifactInspection;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceDeriver;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole;
use Kongono\ApplicationGraphIntelligence\Verification\ApplicationGraphIntelligenceVerdict;
use Kongono\ApplicationGraphIntelligence\Verification\ApplicationGraphIntelligenceVerifier;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ApplicationGraphIntelligenceVerifierTest extends TestCase
{
    public function testExactViewPassesRepeatedNestedAndInterleavedVerification(): void
    {
        $inspection = self::inspection();
        $view = (new ApplicationGraphIntelligenceDeriver())->derive($inspection, DeveloperIntelligenceRole::Hantei);
        $verifier = new ApplicationGraphIntelligenceVerifier();
        $expected = $verifier->verify($inspection, $view)->toArray();

        self::assertSame('pass', $expected['verdict']);
        self::assertSame([], $expected['failures']);
        $checks = $expected['checks'];
        self::assertIsArray($checks);
        self::assertSame(array_fill_keys(array_keys($checks), true), $checks);
        $verifier->verify(self::alternativeInspection(), (new ApplicationGraphIntelligenceDeriver())->derive(self::alternativeInspection(), DeveloperIntelligenceRole::Chie));
        self::assertSame($expected, $verifier->verify($inspection, $view)->toArray());
    }

    public function testEveryRepresentableMismatchFamilyReturnsOnlyOrderedFixedTokens(): void
    {
        $inspection = self::inspection();
        $verifier = new ApplicationGraphIntelligenceVerifier();
        $identityMismatch = self::view(str_repeat('c', 64), str_repeat('d', 64));
        $countMismatch = self::view(str_repeat('a', 64), str_repeat('b', 64), [2, 1, 3, 4], [6, 5, 7, 8]);
        $semanticMismatch = self::corruptVisibility(self::view(str_repeat('a', 64), str_repeat('b', 64)));

        $identity = $verifier->verify($inspection, $identityMismatch);
        self::assertSame(ApplicationGraphIntelligenceVerdict::Fail, $identity->verdict());
        self::assertSame(['identity-match-failed'], $identity->failureTokens());

        $counts = $verifier->verify($inspection, $countMismatch);
        self::assertSame([
            'node-vocabulary-match-failed',
            'relation-vocabulary-match-failed',
        ], $counts->failureTokens());

        $semantic = $verifier->verify($inspection, $semanticMismatch);
        self::assertSame(['visibility-semantics-match-failed'], $semantic->failureTokens());

        $multiple = $verifier->verify($inspection, self::corruptVisibility($identityMismatch));
        self::assertSame([
            'identity-match-failed',
            'visibility-semantics-match-failed',
        ], $multiple->failureTokens());
        $serialized = json_encode($multiple->toArray(), JSON_THROW_ON_ERROR);
        foreach (['TOP-SECRET', '/workspace', 'customer.php', str_repeat('c', 64), str_repeat('d', 64)] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $serialized);
        }
    }

    private static function inspection(): ApplicationGraphArtifactInspection
    {
        return new ApplicationGraphArtifactInspection(str_repeat('a', 64), new GraphIdentity(str_repeat('b', 64)), ContractVersion::Version1, 10, 26, 1, 2, 3, 4, 5, 6, 7, 8);
    }

    private static function alternativeInspection(): ApplicationGraphArtifactInspection
    {
        return new ApplicationGraphArtifactInspection(str_repeat('c', 64), new GraphIdentity(str_repeat('d', 64)), ContractVersion::Version1, 10, 26, 2, 1, 3, 4, 6, 5, 7, 8);
    }

    /**
     * @param list<int> $nodes
     * @param list<int> $relations
     */
    private static function view(string $artifact, string $graph, array $nodes = [1, 2, 3, 4], array $relations = [5, 6, 7, 8]): ApplicationGraphIntelligenceView
    {
        return new ApplicationGraphIntelligenceView(
            DeveloperIntelligenceRole::Hantei,
            $artifact,
            new GraphIdentity($graph),
            ContractVersion::Version1,
            array_sum($nodes),
            array_sum($relations),
            array_combine(array_column(NodeType::cases(), 'value'), $nodes),
            array_combine(array_column(RelationType::cases(), 'value'), $relations),
        );
    }

    private static function corruptVisibility(ApplicationGraphIntelligenceView $valid): ApplicationGraphIntelligenceView
    {
        $reflection = new ReflectionClass(ApplicationGraphIntelligenceView::class);
        /** @var ApplicationGraphIntelligenceView $corrupt */
        $corrupt = $reflection->newInstanceWithoutConstructor();
        $values = [
            'role' => $valid->role(),
            'artifactSha256' => $valid->artifactSha256(),
            'graphIdentity' => $valid->graphIdentity(),
            'contractVersion' => $valid->contractVersion(),
            'nodeCount' => $valid->nodeCount(),
            'relationCount' => $valid->relationCount(),
            'nodeTypeCounts' => $valid->toArray()['node_type_counts'],
            'relationTypeCounts' => $valid->toArray()['relation_type_counts'],
            'scope' => $valid->scope(),
            'visibility' => 'corrupt-safe-token',
            'currentness' => $valid->currentness(),
            'authority' => $valid->authority(),
            'uncertainties' => $valid->uncertainties(),
        ];
        foreach ($values as $property => $value) {
            $reflection->getProperty($property)->setValue($corrupt, $value);
        }

        return $corrupt;
    }
}
