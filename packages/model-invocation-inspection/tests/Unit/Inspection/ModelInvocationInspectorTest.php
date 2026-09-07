<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Tests\Unit\Inspection;

use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationInspection\Exception\ModelInvocationInspectionBindingException;
use Kongono\ModelInvocationInspection\Exception\ModelInvocationRuntimeEvidenceException;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspection;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspectionRequest;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspector;
use Kongono\ModelInvocationInspection\Tests\Support\ModelInvocationInspectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ModelInvocationInspectorTest extends TestCase
{
    public function testEqualExplicitInputsProduceCanonicalByteIdenticalEvidence(): void
    {
        $profile = ModelInvocationInspectionFactory::profile();
        $wiring = ModelInvocationInspectionFactory::wiring($profile);
        $runtime = ModelInvocationInspectionFactory::runtime($profile->source());
        $inspector = new ModelInvocationInspector();
        $first = $inspector->inspect(new ModelInvocationInspectionRequest($profile, $wiring, $runtime));
        $second = $inspector->inspect(new ModelInvocationInspectionRequest($profile, $wiring, $runtime));

        self::assertSame($first->identity(), $second->identity());
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertStringNotContainsString("\r", $first->canonicalBytes());
        self::assertSame(ModelInvocationInspection::SCHEMA, $first->toArray()['schema']);
        $bindings = $first->toArray()['protected_bindings'];
        self::assertIsArray($bindings);
        self::assertSame('protected', $bindings['disclosure']);
    }

    public function testAbsentRuntimeIsExplicitAndContainsNoRuntimeClaim(): void
    {
        $profile = ModelInvocationInspectionFactory::profile();
        $inspection = (new ModelInvocationInspector())->inspect(new ModelInvocationInspectionRequest($profile, ModelInvocationInspectionFactory::wiring($profile)));

        self::assertFalse($inspection->runtimeEvidenceSupplied());
        self::assertSame([], $inspection->toArray()['runtime_summary']);
        self::assertFalse($inspection->resultPresent());
        self::assertFalse($inspection->cleanupComplete());
    }

    /** @return iterable<string, array{InvocationOutcome}> */
    public static function terminalOutcomes(): iterable
    {
        yield 'succeeded' => [InvocationOutcome::Succeeded];
        yield 'failed' => [InvocationOutcome::Failed];
        yield 'cancelled' => [InvocationOutcome::Cancelled];
        yield 'rejected' => [InvocationOutcome::Rejected];
    }

    #[DataProvider('terminalOutcomes')]
    public function testCompletedTerminalEvidenceIsBoundedAndRelationshipChecked(InvocationOutcome $outcome): void
    {
        $profile = ModelInvocationInspectionFactory::profile();
        $inspection = (new ModelInvocationInspector())->inspect(new ModelInvocationInspectionRequest(
            $profile,
            ModelInvocationInspectionFactory::wiring($profile),
            ModelInvocationInspectionFactory::runtime($profile->source(), $outcome),
        ));

        self::assertTrue($inspection->runtimeEvidenceSupplied());
        self::assertSame($outcome, $inspection->runtimeOutcome());
        self::assertTrue($inspection->cleanupComplete());
        self::assertSame(InvocationOutcome::Succeeded === $outcome, $inspection->resultPresent());
        self::assertSame(InvocationOutcome::Succeeded === $outcome, $inspection->costPresent());
        self::assertSame(InvocationOutcome::Rejected === $outcome ? 0 : 1, $inspection->attemptCount());
    }

    public function testSubstitutedProfileWiringFailsClosedWithoutLoadingGeneratedPhp(): void
    {
        $profile = ModelInvocationInspectionFactory::profile(ModelInvocationInspectionFactory::source('first'));
        $other = ModelInvocationInspectionFactory::profile(ModelInvocationInspectionFactory::source('second'));
        $this->expectException(ModelInvocationInspectionBindingException::class);
        (new ModelInvocationInspector())->inspect(new ModelInvocationInspectionRequest($profile, ModelInvocationInspectionFactory::wiring($other)));
    }

    public function testMismatchedCompletedRuntimeFailsClosed(): void
    {
        $profile = ModelInvocationInspectionFactory::profile();
        $this->expectException(ModelInvocationRuntimeEvidenceException::class);
        (new ModelInvocationInspector())->inspect(new ModelInvocationInspectionRequest(
            $profile,
            ModelInvocationInspectionFactory::wiring($profile),
            ModelInvocationInspectionFactory::mismatchedRuntime($profile->source()),
        ));
    }

    public function testRawModelAndProviderContentIsAbsentFromSerializationAndErrors(): void
    {
        $profile = ModelInvocationInspectionFactory::profile();
        $inspection = (new ModelInvocationInspector())->inspect(new ModelInvocationInspectionRequest(
            $profile,
            ModelInvocationInspectionFactory::wiring($profile),
            ModelInvocationInspectionFactory::runtime($profile->source()),
        ));
        $serialized = $inspection->canonicalBytes();
        foreach ([ModelInvocationInspectionFactory::SECRET, ModelInvocationInspectionFactory::RESULT_SECRET, 'provider.inspection', 'model.inspection', 'adapter.inspection', 'plan.inspection', 'invocation.inspection', 'receipt.inspection', 'tokens', 'USD'] as $secret) {
            self::assertStringNotContainsString($secret, $serialized);
        }

        try {
            (new ModelInvocationInspector())->inspect(new ModelInvocationInspectionRequest(
                $profile,
                ModelInvocationInspectionFactory::wiring(ModelInvocationInspectionFactory::profile(ModelInvocationInspectionFactory::source('foreign-secret'))),
            ));
            self::fail('Substitution must fail.');
        } catch (ModelInvocationInspectionBindingException $failure) {
            self::assertStringNotContainsString('foreign-secret', (string) $failure);
            $fields = $failure->diagnosticContext()->fields();
            self::assertCount(1, $fields);
            self::assertSame('component', $fields[0]->key());
            self::assertSame('profile_wiring', $fields[0]->value());
        }
    }

    public function testRepeatedNestedAndInterleavedCallsRetainNoState(): void
    {
        $inspector = new ModelInvocationInspector();
        $first = ModelInvocationInspectionFactory::profile(ModelInvocationInspectionFactory::source('first'));
        $second = ModelInvocationInspectionFactory::profile(ModelInvocationInspectionFactory::source('second'));
        $firstBytes = $inspector->inspect(new ModelInvocationInspectionRequest($first, ModelInvocationInspectionFactory::wiring($first)))->canonicalBytes();
        $secondBytes = $inspector->inspect(new ModelInvocationInspectionRequest($second, ModelInvocationInspectionFactory::wiring($second)))->canonicalBytes();

        self::assertNotSame($firstBytes, $secondBytes);
        self::assertSame($firstBytes, $inspector->inspect(new ModelInvocationInspectionRequest($first, ModelInvocationInspectionFactory::wiring($first)))->canonicalBytes());
    }
}
