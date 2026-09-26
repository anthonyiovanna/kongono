<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Tests\Unit\Inspection;

use Kongono\StorageApplication\Workflow\CurrentStorageApplicationBuild;
use Kongono\StorageBuild\Artifact\StorageProfileArtifact;
use Kongono\StorageBuild\Artifact\StorageProfileArtifactIdentity;
use Kongono\StorageInspection\Exception\InvalidStorageInspectionException;
use Kongono\StorageInspection\Exception\InvalidStorageIntelligenceEvidenceException;
use Kongono\StorageInspection\Exception\StorageInspectionBindingException;
use Kongono\StorageInspection\Inspection\StorageInspection;
use Kongono\StorageInspection\Inspection\StorageInspectionRequest;
use Kongono\StorageInspection\Inspection\StorageInspector;
use Kongono\StorageInspection\RuntimeEvidence\DetachedStorageRuntimeEvidence;
use Kongono\StorageInspection\Tests\Support\StorageInspectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StorageInspectorTest extends TestCase
{
    /** @return iterable<string, array{StorageInspectionRequest, string, int}> */
    public static function requests(): iterable
    {
        $current = StorageInspectionFactory::current();
        $single = StorageInspectionFactory::current(true);
        yield 'without runtime' => [new StorageInspectionRequest($current), StorageInspection::RUNTIME_NOT_SUPPLIED, 2];
        yield 'with runtime' => [new StorageInspectionRequest($current, StorageInspectionFactory::runtime($current)), StorageInspection::RUNTIME_SUPPLIED, 2];
        yield 'single with zero runtime' => [new StorageInspectionRequest($single, StorageInspectionFactory::zeroRuntime($single)), StorageInspection::RUNTIME_SUPPLIED, 1];
    }

    #[DataProvider('requests')]
    public function testExactCurrentEvidenceIsReconstructedDeterministically(StorageInspectionRequest $request, string $status, int $profiles): void
    {
        $inspector = new StorageInspector();
        $first = $inspector->inspect($request);
        $second = $inspector->inspect($request);

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame(hash('sha256', $this->withoutIdentity($first)), $first->identity());
        self::assertSame($status, $first->runtimeEvidenceStatus());
        self::assertSame($profiles, $first->count('profile_count'));
        self::assertTrue($first->flag('structurally_validated'));
        self::assertTrue($first->flag('supplied_exact_current_evidence'));
        self::assertFalse($first->flag('runtime_observed'));
    }

    public function testAllClosedOutcomesAreOrderedBoundedAndDetached(): void
    {
        $current = StorageInspectionFactory::current();
        $runtime = StorageInspectionFactory::runtime($current, 1);
        $inspection = (new StorageInspector())->inspect(new StorageInspectionRequest($current, $runtime));
        $counts = $inspection->counts();
        $runtimeCounts = $runtime->outcomeCounts();
        $counts['read_completed'] = 999;
        $runtimeCounts['read_completed'] = 999;

        self::assertSame(['profile_count', ...DetachedStorageRuntimeEvidence::OUTCOMES, 'outcome_total'], array_keys($inspection->counts()));
        self::assertSame(1, $inspection->count('read_completed'));
        self::assertSame(1, $runtime->outcomeCounts()['read_completed']);
        self::assertSame(13, $runtime->outcomeCounts()['mutation_uncertain']);
        self::assertSame(array_sum($runtime->outcomeCounts()), $runtime->total());
        self::assertSame($runtime->total(), $inspection->count('outcome_total'));
    }

    public function testSafeAndProtectedFieldInventoriesAreExact(): void
    {
        $current = StorageInspectionFactory::current();
        $inspection = (new StorageInspector())->inspect(new StorageInspectionRequest($current, StorageInspectionFactory::runtime($current)));

        self::assertSame([
            'schema', 'version', 'scope', 'posture', 'disclosure', 'authority', 'inspection_identity',
            'runtime_evidence_status', 'protected_bindings', 'counts', 'flags', 'role_purposes', 'limitations',
        ], array_keys($inspection->toArray()));
        self::assertSame([
            'profile_set_sha256', 'build_sha256', 'artifact_sha256', 'detached_runtime_sha256',
        ], array_keys($inspection->protectedBindings()));
        self::assertSame(['profile_count', ...DetachedStorageRuntimeEvidence::OUTCOMES, 'outcome_total'], array_keys($inspection->counts()));
        self::assertSame(StorageInspection::FLAG_KEYS, array_keys($inspection->flags()));
        $rolePurposes = $inspection->toArray()['role_purposes'];
        self::assertIsArray($rolePurposes);
        self::assertSame(['chie', 'hantei', 'oshie'], array_keys($rolePurposes));
    }

    public function testMismatchedCurrentBuildAndRuntimeBindingsFailClosedWithoutRetainedState(): void
    {
        $valid = StorageInspectionFactory::current();
        $other = StorageInspectionFactory::current(suffix: '-other');
        $inspector = new StorageInspector();
        try {
            new CurrentStorageApplicationBuild($valid->profiles(), $other->artifact());
            self::fail('Mismatched current build must fail.');
        } catch (\Kongono\StorageApplication\Exception\StorageApplicationWorkflowException $failure) {
            self::assertSame('KGN-STORAGE-APPLICATION-2143', $failure->diagnosticId());
        }
        try {
            $inspector->inspect(new StorageInspectionRequest($valid, StorageInspectionFactory::runtime($other)));
            self::fail('Mismatched runtime binding must fail.');
        } catch (StorageInspectionBindingException $failure) {
            self::assertSame('KGN-STORAGE-INSPECTION-2152', $failure->diagnosticId());
        }
        self::assertSame(2, $inspector->inspect(new StorageInspectionRequest($valid))->count('profile_count'));
    }

    public function testNoncanonicalArtifactBytesFailStructuralRevalidation(): void
    {
        $valid = StorageInspectionFactory::current();
        $artifact = $valid->artifact();
        $bytes = "{\"tampered\":true}\n";
        $tampered = new StorageProfileArtifact(
            $bytes,
            $artifact->sourceIdentity(),
            $artifact->buildIdentity(),
            StorageProfileArtifactIdentity::fromBytes($bytes),
            $valid->profiles(),
        );

        try {
            new CurrentStorageApplicationBuild($valid->profiles(), $tampered);
            self::fail('Noncanonical artifact bytes must fail structural revalidation.');
        } catch (\Kongono\StorageApplication\Exception\StorageApplicationWorkflowException $failure) {
            self::assertSame('KGN-STORAGE-APPLICATION-2143', $failure->diagnosticId());
        }
    }

    public function testRuntimeEvidenceRejectsCountTotalFlagIdentityAndShapeTampering(): void
    {
        $current = StorageInspectionFactory::current();
        $runtime = StorageInspectionFactory::runtime($current);
        $bindings = $runtime->bindings();
        $counts = $runtime->outcomeCounts();
        $cases = [
            static fn() => new DetachedStorageRuntimeEvidence($bindings, [...$counts, 'extra' => 0]),
            static fn() => new DetachedStorageRuntimeEvidence($bindings, [...array_slice($counts, 0, 1, true), 'read_absent' => -1, ...array_slice($counts, 2, null, true)]),
            static fn() => new DetachedStorageRuntimeEvidence($bindings, $counts, DetachedStorageRuntimeEvidence::FLAGS, $runtime->total() + 1),
            static fn() => new DetachedStorageRuntimeEvidence($bindings, $counts, [...DetachedStorageRuntimeEvidence::FLAGS, 'runtime_observed' => true]),
            static fn() => new DetachedStorageRuntimeEvidence($bindings, $counts, DetachedStorageRuntimeEvidence::FLAGS, $runtime->total(), str_repeat('f', 64)),
        ];
        foreach ($cases as $case) {
            try {
                $case();
                self::fail('Tampered runtime evidence must fail.');
            } catch (\Kongono\StorageInspection\Exception\InvalidStorageIntelligenceEvidenceException $failure) {
                self::assertSame('KGN-STORAGE-INSPECTION-2153', $failure->diagnosticId());
            }
        }
    }

    public function testEveryDetachedOutcomeAndInspectionCountBoundary(): void
    {
        $current = StorageInspectionFactory::current();
        $bindings = StorageInspectionFactory::zeroRuntime($current)->bindings();
        $base = array_fill_keys(DetachedStorageRuntimeEvidence::OUTCOMES, 0);
        $inspector = new StorageInspector();
        foreach (DetachedStorageRuntimeEvidence::OUTCOMES as $outcome) {
            foreach ([999_999, 1_000_000] as $value) {
                $counts = $base;
                $counts[$outcome] = $value;
                $runtime = new DetachedStorageRuntimeEvidence($bindings, $counts, total: $value);
                $inspection = $inspector->inspect(new StorageInspectionRequest($current, $runtime));
                self::assertSame($value, $runtime->outcomeCounts()[$outcome]);
                self::assertSame($value, $runtime->total());
                self::assertSame($value, $inspection->count($outcome));
                self::assertSame($value, $inspection->count('outcome_total'));
                self::assertSame($counts, array_intersect_key($inspection->counts(), $base));

                $direct = new StorageInspection(
                    StorageInspection::RUNTIME_SUPPLIED,
                    $inspection->protectedBindings(),
                    $inspection->counts(),
                    $inspection->flags(),
                );
                self::assertSame($inspection->canonicalBytes(), $direct->canonicalBytes());
            }
            $counts = $base;
            $counts[$outcome] = 1_000_001;
            try {
                new DetachedStorageRuntimeEvidence($bindings, $counts);
                self::fail('Detached outcome above its bound was accepted: ' . $outcome);
            } catch (InvalidStorageIntelligenceEvidenceException $failure) {
                self::assertSame('KGN-STORAGE-INSPECTION-2153', $failure->diagnosticId());
            }
            $valid = $inspector->inspect(new StorageInspectionRequest($current, StorageInspectionFactory::zeroRuntime($current)));
            $directCounts = $valid->counts();
            $directCounts[$outcome] = 1_000_001;
            $directCounts['outcome_total'] = 1_000_001;
            $this->assertInvalidInspection($valid, $directCounts, 'count');
        }
    }

    public function testIndependentSumAndRawCountTypesFailClosed(): void
    {
        $current = StorageInspectionFactory::current();
        $zero = StorageInspectionFactory::zeroRuntime($current);
        $base = $zero->outcomeCounts();
        foreach ([[499_999, 500_000, true], [500_000, 500_000, true], [500_000, 500_001, false]] as [$a, $b, $accepted]) {
            $counts = $base;
            $counts['mutation_completed'] = $a;
            $counts['mutation_uncertain'] = $b;
            if ($accepted) {
                $runtime = new DetachedStorageRuntimeEvidence($zero->bindings(), $counts);
                self::assertSame($a + $b, $runtime->total());
                $inspection = (new StorageInspector())->inspect(new StorageInspectionRequest($current, $runtime));
                self::assertSame($a, $inspection->count('mutation_completed'));
                self::assertSame($b, $inspection->count('mutation_uncertain'));
                self::assertSame(0, $inspection->count('mutation_rejected'));
            } else {
                $this->assertInvalidRuntime($zero->bindings(), $counts);
            }
        }
        $this->assertInvalidRuntime($zero->bindings(), $base, 1);
        $valid = (new StorageInspector())->inspect(new StorageInspectionRequest($current, $zero));
        $mismatch = $valid->counts();
        $mismatch['outcome_total'] = 1;
        $this->assertInvalidInspection($valid, $mismatch, 'total');

        foreach ([0, -1, '1', 1.0, true, false] as $raw) {
            $counts = $base;
            $counts['read_completed'] = $raw;
            if (0 === $raw) {
                self::assertSame(0, (new DetachedStorageRuntimeEvidence($zero->bindings(), $counts))->total());
            } else {
                $this->assertInvalidRuntime($zero->bindings(), $counts);
            }
            $directCounts = $valid->counts();
            $directCounts['read_completed'] = $raw;
            if (0 === $raw) {
                self::assertSame($valid->canonicalBytes(), (new StorageInspection(
                    $valid->runtimeEvidenceStatus(),
                    $valid->protectedBindings(),
                    $directCounts,
                    $valid->flags(),
                ))->canonicalBytes());
            } else {
                $this->assertInvalidInspection($valid, $directCounts, 'count');
            }
        }
    }

    public function testOwningProfileCountBoundariesAndAbsentRuntimeMeaning(): void
    {
        $current = StorageInspectionFactory::current();
        $absent = (new StorageInspector())->inspect(new StorageInspectionRequest($current));
        self::assertSame(StorageInspection::RUNTIME_NOT_SUPPLIED, $absent->runtimeEvidenceStatus());
        self::assertFalse($absent->flag('detached_runtime_evidence_supplied'));
        self::assertFalse($absent->flag('runtime_observed'));
        self::assertSame(0, $absent->count('outcome_total'));
        self::assertNotSame($absent->canonicalBytes(), (new StorageInspector())->inspect(
            new StorageInspectionRequest($current, StorageInspectionFactory::zeroRuntime($current)),
        )->canonicalBytes());
        foreach ([1, 255, 256] as $value) {
            $counts = $absent->counts();
            $counts['profile_count'] = $value;
            self::assertSame($value, (new StorageInspection(
                StorageInspection::RUNTIME_NOT_SUPPLIED,
                $absent->protectedBindings(),
                $counts,
                $absent->flags(),
            ))->count('profile_count'));
        }
        foreach ([0, 257] as $value) {
            $counts = $absent->counts();
            $counts['profile_count'] = $value;
            $this->assertInvalidInspection($absent, $counts, 'count');
        }
    }

    public function testDirectShapeOrderingFlagsAndForgedIdentitiesFail(): void
    {
        $current = StorageInspectionFactory::current();
        $runtime = StorageInspectionFactory::zeroRuntime($current);
        $valid = (new StorageInspector())->inspect(new StorageInspectionRequest($current, $runtime));
        $counts = $valid->counts();
        $this->assertInvalidInspection($valid, array_reverse($counts, true), 'shape');
        $missing = $counts;
        unset($missing['read_completed']);
        $this->assertInvalidInspection($valid, $missing, 'shape');
        $extra = $counts;
        $extra['unexpected'] = 0;
        $this->assertInvalidInspection($valid, $extra, 'shape');
        foreach ([['read_completed' => '0'], ['read_completed' => false], ['read_completed' => 0.0]] as $replacement) {
            $this->assertInvalidInspection($valid, array_replace($counts, $replacement), 'count');
        }
        foreach ([
            fn() => new StorageInspection($valid->runtimeEvidenceStatus(), array_reverse($valid->protectedBindings(), true), $counts, $valid->flags()),
            fn() => new StorageInspection($valid->runtimeEvidenceStatus(), $valid->protectedBindings(), $counts, array_reverse($valid->flags(), true)),
            fn() => new StorageInspection($valid->runtimeEvidenceStatus(), $valid->protectedBindings(), $counts, $valid->flags(), str_repeat('f', 64)),
        ] as $case) {
            try {
                $case();
                self::fail('Forged or wrongly ordered inspection evidence was accepted.');
            } catch (InvalidStorageInspectionException $failure) {
                self::assertSame('KGN-STORAGE-INSPECTION-2151', $failure->diagnosticId());
            }
        }
        $runtimeCounts = $runtime->outcomeCounts();
        $this->assertInvalidRuntime($runtime->bindings(), array_reverse($runtimeCounts, true));
        $this->assertInvalidRuntime($runtime->bindings(), [...$runtimeCounts, 'extra' => 0]);
        $this->assertInvalidRuntime(array_reverse($runtime->bindings(), true), $runtimeCounts);
    }

    public function testEveryClosedInspectionAndDetachedFlagRejectsTampering(): void
    {
        $current = StorageInspectionFactory::current();
        $runtime = StorageInspectionFactory::zeroRuntime($current);
        $valid = (new StorageInspector())->inspect(new StorageInspectionRequest($current, $runtime));
        foreach (StorageInspection::FLAG_KEYS as $key) {
            $flags = $valid->flags();
            $flags[$key] = !$flags[$key];
            try {
                new StorageInspection($valid->runtimeEvidenceStatus(), $valid->protectedBindings(), $valid->counts(), $flags);
                self::fail('Changed inspection flag was accepted: ' . $key);
            } catch (InvalidStorageInspectionException $failure) {
                self::assertSame('KGN-STORAGE-INSPECTION-2151', $failure->diagnosticId());
            }
        }
        foreach (array_keys(DetachedStorageRuntimeEvidence::FLAGS) as $key) {
            $flags = $runtime->flags();
            $flags[$key] = !$flags[$key];
            try {
                new DetachedStorageRuntimeEvidence($runtime->bindings(), $runtime->outcomeCounts(), $flags);
                self::fail('Changed detached flag was accepted: ' . $key);
            } catch (InvalidStorageIntelligenceEvidenceException $failure) {
                self::assertSame('KGN-STORAGE-INSPECTION-2153', $failure->diagnosticId());
            }
        }
    }

    /**
     * @param array<array-key, mixed> $bindings
     * @param array<array-key, mixed> $counts
     */
    private function assertInvalidRuntime(array $bindings, array $counts, ?int $total = null): void
    {
        try {
            new DetachedStorageRuntimeEvidence($bindings, $counts, total: $total);
            self::fail('Malformed detached runtime evidence was accepted.');
        } catch (InvalidStorageIntelligenceEvidenceException $failure) {
            self::assertSame('KGN-STORAGE-INSPECTION-2153', $failure->diagnosticId());
        }
    }

    /** @param array<array-key, mixed> $counts */
    private function assertInvalidInspection(StorageInspection $valid, array $counts, string $reason): void
    {
        try {
            new StorageInspection($valid->runtimeEvidenceStatus(), $valid->protectedBindings(), $counts, $valid->flags());
            self::fail('Malformed inspection count was accepted.');
        } catch (InvalidStorageInspectionException $failure) {
            self::assertSame('KGN-STORAGE-INSPECTION-2151', $failure->diagnosticId());
            self::assertNotSame('', $failure->getMessage());
        }
    }

    private function withoutIdentity(StorageInspection $inspection): string
    {
        $value = $inspection->toArray();
        unset($value['inspection_identity']);

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
