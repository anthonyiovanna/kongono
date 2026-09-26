<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Tests\Unit\Derivation;

use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\StorageInspection\Derivation\StorageIntelligenceDeriver;
use Kongono\StorageInspection\Derivation\StorageIntelligenceView;
use Kongono\StorageInspection\Exception\InvalidStorageIntelligenceEvidenceException;
use Kongono\StorageInspection\Graph\StorageGraphEvidence;
use Kongono\StorageInspection\Graph\StorageGraphEvidenceProjector;
use Kongono\StorageInspection\Inspection\StorageInspection;
use Kongono\StorageInspection\Inspection\StorageInspectionRequest;
use Kongono\StorageInspection\Inspection\StorageInspector;
use Kongono\StorageInspection\Role\StorageInspectionDeveloperRole;
use Kongono\StorageInspection\Tests\Support\StorageInspectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StorageIntelligenceTest extends TestCase
{
    /** @return iterable<string, array{StorageInspectionDeveloperRole, string}> */
    public static function roles(): iterable
    {
        yield 'chie' => [StorageInspectionDeveloperRole::Chie, 'explain'];
        yield 'hantei' => [StorageInspectionDeveloperRole::Hantei, 'verify'];
        yield 'oshie' => [StorageInspectionDeveloperRole::Oshie, 'teach'];
    }

    #[DataProvider('roles')]
    public function testRolesSelectExactPurposesWithoutExecution(StorageInspectionDeveloperRole $role, string $purpose): void
    {
        $view = (new StorageIntelligenceDeriver())->derive($this->inspection(), $role);

        self::assertSame($purpose, $view->purpose());
        self::assertSame(StorageIntelligenceView::STATEMENTS, $view->statements());
        self::assertSame(StorageIntelligenceView::LIMITATIONS, $view->limitations());
        self::assertStringContainsString('"role_execution":"not-performed"', $view->canonicalBytes());
        self::assertStringContainsString('"provider_access":"not-performed"', $view->canonicalBytes());
    }

    public function testGraphIsOptionalAggregateOnlyAndAlwaysUnestablished(): void
    {
        $inspection = $this->inspection();
        $projector = new StorageGraphEvidenceProjector();
        $absent = $projector->project($inspection, StorageInspectionDeveloperRole::Chie);
        $supplied = $projector->project($inspection, StorageInspectionDeveloperRole::Chie, StorageInspectionFactory::graph());
        $bytes = json_encode($supplied->toArray(), JSON_THROW_ON_ERROR);

        self::assertFalse($absent->supplied());
        self::assertTrue($supplied->supplied());
        self::assertSame('not-established', $absent->relationship());
        self::assertSame('not-established', $supplied->relationship());
        self::assertStringContainsString('safe-aggregate-only', $bytes);
        self::assertStringNotContainsString('artifact_sha256', $bytes);
        self::assertStringNotContainsString('graph_identity', $bytes);
    }

    public function testGraphRoleMismatchAndTamperedViewFailClosed(): void
    {
        $this->expectException(InvalidStorageIntelligenceEvidenceException::class);
        (new StorageGraphEvidenceProjector())->project(
            $this->inspection(),
            StorageInspectionDeveloperRole::Chie,
            StorageInspectionFactory::graph(GraphRole::Hantei),
        );
    }

    public function testPurposeStatementsSerializationAndGraphRelationshipTamperingFailClosed(): void
    {
        $inspection = $this->inspection();
        $role = StorageInspectionDeveloperRole::Chie;
        $graph = (new StorageGraphEvidenceProjector())->project($inspection, $role);
        $cases = [
            static fn() => new StorageIntelligenceView($role, 'teach', $inspection, $graph),
            static fn() => new StorageIntelligenceView($role, $role->purpose(), $inspection, $graph, ['tampered']),
            static fn() => new StorageIntelligenceView($role, $role->purpose(), $inspection, $graph, serializationSha256: str_repeat('f', 64)),
            static fn() => new StorageGraphEvidence($inspection->identity(), $role, false, relationship: 'established'),
        ];

        foreach ($cases as $case) {
            try {
                $case();
                self::fail('Tampered derived evidence must fail.');
            } catch (InvalidStorageIntelligenceEvidenceException $failure) {
                self::assertSame('KGN-STORAGE-INSPECTION-2153', $failure->diagnosticId());
            }
        }
    }

    public function testGraphAggregateAndPerFieldBoundaries(): void
    {
        $inspection = $this->inspection();
        $role = StorageInspectionDeveloperRole::Chie;
        foreach ([4095, 4096, 4097] as $nodes) {
            $nodeTypes = ['composition.input' => $nodes, 'composition.service' => 0, 'composition.contract' => 0, 'composition.root' => 0];
            $relationTypes = ['composition.depends_on' => 0, 'composition.consumes_input' => 0, 'composition.implements_contract' => 0, 'composition.exposes_root' => 0];
            if ($nodes <= 4096) {
                $evidence = $this->graphEvidence($inspection, $role, $nodes, 0, $nodeTypes, $relationTypes);
                $graph = $evidence->toArray()['graph'];
                self::assertIsArray($graph);
                self::assertSame($nodes, $graph['node_count']);
                self::assertSame($nodeTypes, $graph['node_type_counts']);
            } else {
                $this->assertInvalidGraph(fn() => $this->graphEvidence($inspection, $role, $nodes, 0, $nodeTypes, $relationTypes));
            }
        }
        foreach ([16383, 16384, 16385] as $relations) {
            $nodeTypes = ['composition.input' => 0, 'composition.service' => 0, 'composition.contract' => 0, 'composition.root' => 0];
            $relationTypes = ['composition.depends_on' => $relations, 'composition.consumes_input' => 0, 'composition.implements_contract' => 0, 'composition.exposes_root' => 0];
            if ($relations <= 16384) {
                $evidence = $this->graphEvidence($inspection, $role, 0, $relations, $nodeTypes, $relationTypes);
                $graph = $evidence->toArray()['graph'];
                self::assertIsArray($graph);
                self::assertSame($relations, $graph['relation_count']);
                self::assertSame($relationTypes, $graph['relation_type_counts']);
            } else {
                $this->assertInvalidGraph(fn() => $this->graphEvidence($inspection, $role, 0, $relations, $nodeTypes, $relationTypes));
            }
        }
        $nodeTypes = ['composition.input' => 1, 'composition.service' => 0, 'composition.contract' => 0, 'composition.root' => 0];
        $relationTypes = ['composition.depends_on' => 1, 'composition.consumes_input' => 0, 'composition.implements_contract' => 0, 'composition.exposes_root' => 0];
        $this->assertInvalidGraph(fn() => $this->graphEvidence($inspection, $role, 0, 1, $nodeTypes, $relationTypes));
        $this->assertInvalidGraph(fn() => $this->graphEvidence($inspection, $role, 1, 0, $nodeTypes, $relationTypes));
        $this->assertInvalidGraph(fn() => $this->graphEvidence($inspection, $role, 1, 1, array_reverse($nodeTypes, true), $relationTypes));
        $this->assertInvalidGraph(fn() => $this->graphEvidence($inspection, $role, 1, 1, $nodeTypes, array_reverse($relationTypes, true)));
        $nodeTypes['composition.input'] = 4097;
        $this->assertInvalidGraph(fn() => $this->graphEvidence($inspection, $role, 4096, 1, $nodeTypes, $relationTypes));
        $relationTypes['composition.depends_on'] = 16385;
        $this->assertInvalidGraph(fn() => $this->graphEvidence($inspection, $role, 1, 16384, ['composition.input' => 1, 'composition.service' => 0, 'composition.contract' => 0, 'composition.root' => 0], $relationTypes));
    }

    public function testInterleavedIndependentInputsAndRolesRemainDeterministic(): void
    {
        $inspector = new StorageInspector();
        $deriver = new StorageIntelligenceDeriver();
        $aCurrent = StorageInspectionFactory::current();
        $bCurrent = StorageInspectionFactory::current(true, '-different');
        $a = new StorageInspectionRequest($aCurrent, StorageInspectionFactory::runtime($aCurrent));
        $b = new StorageInspectionRequest($bCurrent);
        $aInspection = $inspector->inspect($a);
        $aView = $deriver->derive($aInspection, StorageInspectionDeveloperRole::Chie, StorageInspectionFactory::graph());
        $bInspection = $inspector->inspect($b);
        $bView = $deriver->derive($bInspection, StorageInspectionDeveloperRole::Oshie);
        $againInspection = $inspector->inspect($a);
        $againView = $deriver->derive($againInspection, StorageInspectionDeveloperRole::Chie, StorageInspectionFactory::graph());

        self::assertSame($aInspection->canonicalBytes(), $againInspection->canonicalBytes());
        self::assertSame($aInspection->identity(), $againInspection->identity());
        self::assertSame($aView->canonicalBytes(), $againView->canonicalBytes());
        self::assertSame($aView->serializationSha256(), $againView->serializationSha256());
        self::assertNotSame($aInspection->identity(), $bInspection->identity());
        self::assertNotSame($aView->serializationSha256(), $bView->serializationSha256());
        self::assertSame(StorageInspection::RUNTIME_NOT_SUPPLIED, $bInspection->runtimeEvidenceStatus());
        self::assertTrue($aView->graphEvidence()->supplied());
        self::assertFalse($bView->graphEvidence()->supplied());
        foreach ([$aInspection->canonicalBytes(), $aView->canonicalBytes(), $bInspection->canonicalBytes(), $bView->canonicalBytes()] as $bytes) {
            self::assertSame("\n", substr($bytes, -1));
            self::assertNotSame("\n\n", substr($bytes, -2));
        }
    }

    public function testTwoFreshProcessesRecomputeExactInspectionAndViewBytes(): void
    {
        $current = StorageInspectionFactory::current();
        $inspection = (new StorageInspector())->inspect(new StorageInspectionRequest($current, StorageInspectionFactory::runtime($current)));
        $view = (new StorageIntelligenceDeriver())->derive($inspection, StorageInspectionDeveloperRole::Chie, StorageInspectionFactory::graph());
        $expected = [
            'inspection_bytes' => $inspection->canonicalBytes(),
            'inspection_identity' => $inspection->identity(),
            'view_bytes' => $view->canonicalBytes(),
            'view_identity' => $view->serializationSha256(),
        ];
        $body = <<<'PHP'
$current = \Kongono\StorageInspection\Tests\Support\StorageInspectionFactory::current();
$inspection = (new \Kongono\StorageInspection\Inspection\StorageInspector())->inspect(
    new \Kongono\StorageInspection\Inspection\StorageInspectionRequest(
        $current,
        \Kongono\StorageInspection\Tests\Support\StorageInspectionFactory::runtime($current),
    ),
);
$view = (new \Kongono\StorageInspection\Derivation\StorageIntelligenceDeriver())->derive(
    $inspection,
    \Kongono\StorageInspection\Role\StorageInspectionDeveloperRole::Chie,
    \Kongono\StorageInspection\Tests\Support\StorageInspectionFactory::graph(),
);
echo json_encode([
    'inspection_bytes' => $inspection->canonicalBytes(),
    'inspection_identity' => $inspection->identity(),
    'view_bytes' => $view->canonicalBytes(),
    'view_identity' => $view->serializationSha256(),
], JSON_THROW_ON_ERROR);
PHP;
        $first = StorageInspectionFactory::isolatedPhp($body);
        StorageInspectionFactory::requireSuccessfulChild($first);
        try {
            $second = StorageInspectionFactory::isolatedPhp($body);
            StorageInspectionFactory::requireSuccessfulChild($second);
        } catch (\Throwable $failure) {
            throw new RuntimeException('First fresh-process capture also retained: ' . $first['stdout_path'] . ', ' . $first['stderr_path'], 0, $failure);
        }
        self::assertSame('', $first['stderr']);
        self::assertSame('', $second['stderr']);
        self::assertSame($first['stdout'], $second['stdout']);
        self::assertSame($expected, json_decode($first['stdout'], true, flags: JSON_THROW_ON_ERROR));
        self::assertSame($expected, json_decode($second['stdout'], true, flags: JSON_THROW_ON_ERROR));
        StorageInspectionFactory::acknowledgeChild($first);
        StorageInspectionFactory::acknowledgeChild($second);
    }

    public function testDeliberateCallerFailureRetainsBothStreamsUntilAcknowledged(): void
    {
        $capture = StorageInspectionFactory::isolatedPhp('fwrite(STDOUT, "stdout-marker-a"); fwrite(STDERR, "stderr-marker-a");');
        StorageInspectionFactory::requireSuccessfulChild($capture);
        $caught = false;
        try {
            self::assertSame('deliberately-wrong', $capture['stdout']);
        } catch (ExpectationFailedException) {
            $caught = true;
        }
        self::assertTrue($caught);
        self::assertSame('stdout-marker-a', $capture['stdout']);
        self::assertSame('stderr-marker-a', $capture['stderr']);
        self::assertSame($capture['stdout'], file_get_contents($capture['stdout_path']));
        self::assertSame($capture['stderr'], file_get_contents($capture['stderr_path']));
        self::assertDirectoryExists($capture['directory']);
        StorageInspectionFactory::acknowledgeChild($capture);
    }

    public function testLaterSuccessfulAcknowledgementDoesNotEraseEarlierCapture(): void
    {
        $retained = StorageInspectionFactory::isolatedPhp('fwrite(STDOUT, "earlier-marker");');
        $later = StorageInspectionFactory::isolatedPhp('fwrite(STDOUT, "later-marker");');
        StorageInspectionFactory::requireSuccessfulChild($retained);
        StorageInspectionFactory::requireSuccessfulChild($later);
        self::assertNotSame($retained['directory'], $later['directory']);
        self::assertSame('earlier-marker', file_get_contents($retained['stdout_path']));
        self::assertSame('later-marker', file_get_contents($later['stdout_path']));
        StorageInspectionFactory::acknowledgeChild($later);
        self::assertSame('earlier-marker', file_get_contents($retained['stdout_path']));
        StorageInspectionFactory::acknowledgeChild($retained);
    }

    public function testNonzeroChildPreservesBothStreamsAndActualExit(): void
    {
        $capture = StorageInspectionFactory::isolatedPhp('fwrite(STDOUT, "nonzero-out"); fwrite(STDERR, "nonzero-err"); exit(7);');
        self::assertTrue($capture['retired']);
        self::assertFalse($capture['timed_out']);
        self::assertSame(7, $capture['exit']);
        self::assertSame('nonzero-out', file_get_contents($capture['stdout_path']));
        self::assertSame('nonzero-err', file_get_contents($capture['stderr_path']));
        try {
            StorageInspectionFactory::requireSuccessfulChild($capture);
            self::fail('Nonzero child cannot be successful evidence.');
        } catch (RuntimeException $failure) {
            self::assertStringContainsString('exit=7', $failure->getMessage());
            self::assertStringContainsString($capture['stdout_path'], $failure->getMessage());
            self::assertStringContainsString($capture['stderr_path'], $failure->getMessage());
        }
        StorageInspectionFactory::discardVerifiedSyntheticCapture($capture);
    }

    public function testTimedOutChildRetiresWithinBoundAndRetainsBothStreams(): void
    {
        $capture = StorageInspectionFactory::isolatedPhp(
            'fwrite(STDOUT, "timeout-out"); fflush(STDOUT); fwrite(STDERR, "timeout-err"); fflush(STDERR); sleep(3);',
            deadlineMilliseconds: 150,
            retirementMilliseconds: 2_000,
        );
        self::assertTrue($capture['timed_out']);
        self::assertTrue($capture['retired']);
        self::assertSame('timeout-out', file_get_contents($capture['stdout_path']));
        self::assertSame('timeout-err', file_get_contents($capture['stderr_path']));
        try {
            StorageInspectionFactory::requireSuccessfulChild($capture);
            self::fail('Timed-out child cannot be successful evidence.');
        } catch (RuntimeException $failure) {
            self::assertStringContainsString('timed_out=yes', $failure->getMessage());
            self::assertStringContainsString($capture['stdout_path'], $failure->getMessage());
        }
        StorageInspectionFactory::discardVerifiedSyntheticCapture($capture);
    }

    public function testCleanupFailureSurfacesAndRestoresBothRawStreams(): void
    {
        $capture = StorageInspectionFactory::isolatedPhp('fwrite(STDOUT, "cleanup-out"); fwrite(STDERR, "cleanup-err");');
        StorageInspectionFactory::requireSuccessfulChild($capture);
        $blocker = $capture['directory'] . DIRECTORY_SEPARATOR . 'blocker.bin';
        self::assertSame(7, file_put_contents($blocker, 'blocker'));
        try {
            StorageInspectionFactory::acknowledgeChild($capture);
            self::fail('Nonempty owned capture directory must reject cleanup.');
        } catch (RuntimeException $failure) {
            self::assertStringContainsString($capture['stdout_path'], $failure->getMessage());
            self::assertStringContainsString($capture['stderr_path'], $failure->getMessage());
        }
        self::assertSame('cleanup-out', file_get_contents($capture['stdout_path']));
        self::assertSame('cleanup-err', file_get_contents($capture['stderr_path']));
        self::assertSame('blocker', file_get_contents($blocker));
        self::assertTrue(unlink($blocker));
        StorageInspectionFactory::discardVerifiedSyntheticCapture($capture);
    }

    public function testEveryClosedViewEffectMarkerRejectsTampering(): void
    {
        $inspection = $this->inspection();
        $role = StorageInspectionDeveloperRole::Chie;
        $graph = (new StorageGraphEvidenceProjector())->project($inspection, $role);
        foreach ([
            'roleExecution', 'repair', 'apply', 'hiddenDataAccess', 'filesystemAccess',
            'providerAccess', 'adapterAccess', 'credentialValidation', 'authorizationExecution',
            'runtimeAccess', 'clockRead', 'mutation', 'networkAccess', 'recording',
            'exporting', 'productionObservation', 'certification', 'authority',
        ] as $field) {
            try {
                new StorageIntelligenceView($role, $role->purpose(), $inspection, $graph, ...[$field => 'performed']);
                self::fail('Changed effect marker was accepted: ' . $field);
            } catch (InvalidStorageIntelligenceEvidenceException $failure) {
                self::assertSame('KGN-STORAGE-INSPECTION-2153', $failure->diagnosticId());
            }
        }
        foreach ([
            static fn() => new StorageIntelligenceView($role, $role->purpose(), $inspection, $graph, limitations: ['tampered']),
            static fn() => new StorageIntelligenceView($role, $role->purpose(), $inspection, $graph, statements: ['tampered']),
        ] as $case) {
            $this->assertInvalidGraph($case);
        }
    }

    /**
     * @param array<array-key, mixed> $nodes
     * @param array<array-key, mixed> $relations
     */
    private function graphEvidence(
        \Kongono\StorageInspection\Inspection\StorageInspection $inspection,
        StorageInspectionDeveloperRole $role,
        int $nodeCount,
        int $relationCount,
        array $nodes,
        array $relations,
    ): StorageGraphEvidence {
        return new StorageGraphEvidence(
            $inspection->identity(),
            $role,
            true,
            1,
            $nodeCount,
            $relationCount,
            $nodes,
            $relations,
            'composition-v1',
            'safe-aggregate-only',
            'not-established',
            'evidence-only',
            ['individual-facts-omitted', 'currentness-not-established', 'authorization-not-granted'],
        );
    }

    private function assertInvalidGraph(\Closure $case): void
    {
        try {
            $case();
            self::fail('Malformed graph aggregate was accepted.');
        } catch (InvalidStorageIntelligenceEvidenceException $failure) {
            self::assertSame('KGN-STORAGE-INSPECTION-2153', $failure->diagnosticId());
        }
    }

    private function inspection(): \Kongono\StorageInspection\Inspection\StorageInspection
    {
        $current = StorageInspectionFactory::current();

        return (new StorageInspector())->inspect(new StorageInspectionRequest($current, StorageInspectionFactory::runtime($current)));
    }
}
