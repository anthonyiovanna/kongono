<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Tests\Unit\Inspection;

use Kongono\CacheApplication\Workflow\CurrentCacheApplicationBuild;
use Kongono\CacheBuild\Artifact\CacheProfileArtifact;
use Kongono\CacheBuild\Artifact\CacheProfileArtifactIdentity;
use Kongono\CacheInspection\Exception\CacheInspectionBindingException;
use Kongono\CacheInspection\Inspection\CacheInspection;
use Kongono\CacheInspection\Inspection\CacheInspectionRequest;
use Kongono\CacheInspection\Inspection\CacheInspector;
use Kongono\CacheInspection\RuntimeEvidence\DetachedCacheRuntimeEvidence;
use Kongono\CacheInspection\Tests\Support\CacheInspectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CacheInspectorTest extends TestCase
{
    /** @return iterable<string, array{CacheInspectionRequest, string, int}> */
    public static function requests(): iterable
    {
        $current = CacheInspectionFactory::current();
        $empty = CacheInspectionFactory::current(true);
        yield 'without runtime' => [new CacheInspectionRequest($current), CacheInspection::RUNTIME_NOT_SUPPLIED, 2];
        yield 'with runtime' => [new CacheInspectionRequest($current, CacheInspectionFactory::runtime($current)), CacheInspection::RUNTIME_SUPPLIED, 2];
        yield 'empty with zero runtime' => [new CacheInspectionRequest($empty, CacheInspectionFactory::zeroRuntime($empty)), CacheInspection::RUNTIME_SUPPLIED, 0];
    }

    #[DataProvider('requests')]
    public function testExactCurrentEvidenceIsReconstructedDeterministically(CacheInspectionRequest $request, string $status, int $profiles): void
    {
        $inspector = new CacheInspector();
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
        $current = CacheInspectionFactory::current();
        $runtime = CacheInspectionFactory::runtime($current, 1);
        $inspection = (new CacheInspector())->inspect(new CacheInspectionRequest($current, $runtime));
        $counts = $inspection->counts();
        $runtimeCounts = $runtime->outcomeCounts();
        $counts['lookup_fresh_hit'] = 999;
        $runtimeCounts['lookup_fresh_hit'] = 999;

        self::assertSame(['profile_count', ...DetachedCacheRuntimeEvidence::OUTCOMES, 'outcome_total'], array_keys($inspection->counts()));
        self::assertSame(1, $inspection->count('lookup_fresh_hit'));
        self::assertSame(1, $runtime->outcomeCounts()['lookup_fresh_hit']);
        self::assertSame(array_sum($runtime->outcomeCounts()), $runtime->total());
        self::assertSame($runtime->total(), $inspection->count('outcome_total'));
    }

    public function testSafeAndProtectedFieldInventoriesAreExact(): void
    {
        $current = CacheInspectionFactory::current();
        $inspection = (new CacheInspector())->inspect(new CacheInspectionRequest($current, CacheInspectionFactory::runtime($current)));

        self::assertSame([
            'schema', 'version', 'scope', 'posture', 'disclosure', 'authority', 'inspection_identity',
            'runtime_evidence_status', 'protected_bindings', 'counts', 'flags', 'role_purposes', 'limitations',
        ], array_keys($inspection->toArray()));
        self::assertSame([
            'profile_set_sha256', 'build_sha256', 'artifact_sha256', 'detached_runtime_sha256',
        ], array_keys($inspection->protectedBindings()));
        self::assertSame(['profile_count', ...DetachedCacheRuntimeEvidence::OUTCOMES, 'outcome_total'], array_keys($inspection->counts()));
        self::assertSame(CacheInspection::FLAG_KEYS, array_keys($inspection->flags()));
        $rolePurposes = $inspection->toArray()['role_purposes'];
        self::assertIsArray($rolePurposes);
        self::assertSame(['chie', 'hantei', 'oshie'], array_keys($rolePurposes));
    }

    public function testMismatchedCurrentBuildAndRuntimeBindingsFailClosedWithoutRetainedState(): void
    {
        $valid = CacheInspectionFactory::current();
        $other = CacheInspectionFactory::current(suffix: '-other');
        $invalidCurrent = new CurrentCacheApplicationBuild($valid->profiles(), $other->artifact());
        $inspector = new CacheInspector();
        try {
            $inspector->inspect(new CacheInspectionRequest($invalidCurrent));
            self::fail('Mismatched current build must fail.');
        } catch (CacheInspectionBindingException $failure) {
            self::assertSame('KGN-CACHE-INSPECTION-2052', $failure->diagnosticId());
        }
        try {
            $inspector->inspect(new CacheInspectionRequest($valid, CacheInspectionFactory::runtime($other)));
            self::fail('Mismatched runtime binding must fail.');
        } catch (CacheInspectionBindingException $failure) {
            self::assertSame('KGN-CACHE-INSPECTION-2052', $failure->diagnosticId());
        }
        self::assertSame(2, $inspector->inspect(new CacheInspectionRequest($valid))->count('profile_count'));
    }

    public function testNoncanonicalArtifactBytesFailStructuralRevalidation(): void
    {
        $valid = CacheInspectionFactory::current();
        $artifact = $valid->artifact();
        $bytes = "{\"tampered\":true}\n";
        $tampered = new CacheProfileArtifact(
            $bytes,
            $artifact->sourceIdentity(),
            $artifact->buildIdentity(),
            CacheProfileArtifactIdentity::fromBytes($bytes),
            $valid->profiles(),
        );

        try {
            (new CacheInspector())->inspect(new CacheInspectionRequest(new CurrentCacheApplicationBuild($valid->profiles(), $tampered)));
            self::fail('Noncanonical artifact bytes must fail structural revalidation.');
        } catch (CacheInspectionBindingException $failure) {
            self::assertSame('KGN-CACHE-INSPECTION-2052', $failure->diagnosticId());
        }
    }

    public function testRuntimeEvidenceRejectsCountTotalFlagIdentityAndShapeTampering(): void
    {
        $current = CacheInspectionFactory::current();
        $runtime = CacheInspectionFactory::runtime($current);
        $bindings = $runtime->bindings();
        $counts = $runtime->outcomeCounts();
        $cases = [
            static fn() => new DetachedCacheRuntimeEvidence($bindings, [...$counts, 'extra' => 0]),
            static fn() => new DetachedCacheRuntimeEvidence($bindings, [...array_slice($counts, 0, 1, true), 'lookup_miss' => -1, ...array_slice($counts, 2, null, true)]),
            static fn() => new DetachedCacheRuntimeEvidence($bindings, $counts, DetachedCacheRuntimeEvidence::FLAGS, $runtime->total() + 1),
            static fn() => new DetachedCacheRuntimeEvidence($bindings, $counts, [...DetachedCacheRuntimeEvidence::FLAGS, 'runtime_observed' => true]),
            static fn() => new DetachedCacheRuntimeEvidence($bindings, $counts, DetachedCacheRuntimeEvidence::FLAGS, $runtime->total(), str_repeat('f', 64)),
        ];
        foreach ($cases as $case) {
            try {
                $case();
                self::fail('Tampered runtime evidence must fail.');
            } catch (\Kongono\CacheInspection\Exception\InvalidCacheIntelligenceEvidenceException $failure) {
                self::assertSame('KGN-CACHE-INSPECTION-2053', $failure->diagnosticId());
            }
        }
    }

    private function withoutIdentity(CacheInspection $inspection): string
    {
        $value = $inspection->toArray();
        unset($value['inspection_identity']);

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
