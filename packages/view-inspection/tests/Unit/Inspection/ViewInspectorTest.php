<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Tests\Unit\Inspection;

use Fiber;
use Kongono\ViewApplication\Profile\ViewApplicationProfileIdentity;
use Kongono\ViewInspection\Exception\ViewArtifactEvidenceException;
use Kongono\ViewInspection\Exception\ViewInspectionBindingException;
use Kongono\ViewInspection\Inspection\ViewInspection;
use Kongono\ViewInspection\Inspection\ViewInspectionRequest;
use Kongono\ViewInspection\Inspection\ViewInspector;
use Kongono\ViewInspection\Tests\Support\ViewInspectionFactory;
use PHPUnit\Framework\TestCase;

final class ViewInspectorTest extends TestCase
{
    public function testExactProfileProducesDeterministicCanonicalEvidence(): void
    {
        $profile = ViewInspectionFactory::profile();
        $first = (new ViewInspector())->inspect(new ViewInspectionRequest($profile));
        $second = (new ViewInspector())->inspect(new ViewInspectionRequest($profile));
        $independent = (new ViewInspector())->inspect(new ViewInspectionRequest($profile));

        self::assertSame($first->identity(), $second->identity());
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->canonicalBytes(), $independent->canonicalBytes());
        self::assertSame([
            'schema', 'version', 'scope', 'disclosure', 'authority', 'inspection_identity',
            'protected_bindings', 'format_versions', 'counts', 'flags', 'limitations',
        ], array_keys($first->toArray()));
        self::assertSame(ViewInspection::LIMITATIONS, $first->toArray()['limitations']);
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertStringNotContainsString("\r", $first->canonicalBytes());
    }

    public function testPermittedArtifactEvidenceChangeChangesIdentity(): void
    {
        $first = $this->inspect(ViewInspectionFactory::profile('first-template'));
        $second = $this->inspect(ViewInspectionFactory::profile('second-template', true));

        self::assertNotSame($first->identity(), $second->identity());
        self::assertSame(1, $first->viewCount());
        self::assertSame(2, $second->viewCount());
    }

    public function testGeneratedPhpIsStructurallyValidatedWithoutClassLoadingOrExecution(): void
    {
        $profile = ViewInspectionFactory::profile();
        $class = $profile->currentBuild()->artifact()->generatedClass();
        self::assertFalse(class_exists($class, false));

        $inspection = $this->inspect($profile);

        self::assertTrue($inspection->structurallyValidated());
        self::assertTrue($inspection->suppliedCurrentEvidence());
        self::assertSame([
            'structurally_validated' => true,
            'supplied_current_evidence' => true,
            'generated_php_executed' => false,
            'rendering_performed' => false,
            'production_observed' => false,
        ], $inspection->flags());
        self::assertFalse(class_exists($class, false));
    }

    public function testCorruptArtifactBytesFailWithExactNondisclosingDiagnostic(): void
    {
        try {
            $this->inspect(ViewInspectionFactory::structurallyCorruptProfile());
            self::fail('Corrupt artifact evidence must fail.');
        } catch (ViewArtifactEvidenceException $failure) {
            self::assertSame('KGN-VIEW-INSPECTION-1861', $failure->diagnosticId());
            self::assertSame('artifact_structure', $failure->diagnosticContext()->fields()[0]->value());
            self::assertStringNotContainsString(ViewInspectionFactory::CONTEXT_SECRET, (string) $failure);
        }
    }

    public function testReconstructedArtifactMismatchFailsBeforeEvidenceDerivation(): void
    {
        try {
            $this->inspect(ViewInspectionFactory::reconstructedMismatchProfile());
            self::fail('Mismatched reconstructed artifact evidence must fail.');
        } catch (ViewArtifactEvidenceException $failure) {
            self::assertSame('KGN-VIEW-INSPECTION-1861', $failure->diagnosticId());
            self::assertSame('artifact_comparison', $failure->diagnosticContext()->fields()[0]->value());
        }
    }

    public function testProfileIdentityMismatchFailsWithExactDiagnostic(): void
    {
        $artifact = ViewInspectionFactory::profile()->currentBuild()->artifact();
        $profile = ViewInspectionFactory::profileFromArtifact($artifact, new ViewApplicationProfileIdentity(str_repeat('0', 64)));
        try {
            $this->inspect($profile);
            self::fail('Mismatched profile identity must fail.');
        } catch (ViewInspectionBindingException $failure) {
            self::assertSame('KGN-VIEW-INSPECTION-1862', $failure->diagnosticId());
            self::assertSame('profile_identity', $failure->diagnosticContext()->fields()[0]->value());
        }
    }

    public function testSequentialInterleavedIndependentAndFiberCallsRemainIsolated(): void
    {
        $firstProfile = ViewInspectionFactory::profile('first-private-template');
        $secondProfile = ViewInspectionFactory::profile('second-private-template', true);
        $inspector = new ViewInspector();
        $first = $inspector->inspect(new ViewInspectionRequest($firstProfile));
        $second = $inspector->inspect(new ViewInspectionRequest($secondProfile));
        $again = $inspector->inspect(new ViewInspectionRequest($firstProfile));
        self::assertSame($first->canonicalBytes(), $again->canonicalBytes());
        self::assertNotSame($first->identity(), $second->identity());

        $fiberA = new Fiber(static fn(): string => (new ViewInspector())->inspect(new ViewInspectionRequest($firstProfile))->identity());
        $fiberB = new Fiber(static fn(): string => (new ViewInspector())->inspect(new ViewInspectionRequest($secondProfile))->identity());
        $fiberA->start();
        $fiberB->start();
        self::assertSame($first->identity(), $fiberA->getReturn());
        self::assertSame($second->identity(), $fiberB->getReturn());
    }

    private function inspect(\Kongono\ViewApplication\Profile\ViewApplicationProfile $profile): ViewInspection
    {
        return (new ViewInspector())->inspect(new ViewInspectionRequest($profile));
    }
}
