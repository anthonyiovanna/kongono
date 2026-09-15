<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Tests\Security;

use Kongono\AssetInspection\Derivation\AssetIntelligenceDeriver;
use Kongono\AssetInspection\Exception\AssetInspectionBindingException;
use Kongono\AssetInspection\Exception\AssetInspectionProjectionException;
use Kongono\AssetInspection\Exception\AssetInspectionVerificationException;
use Kongono\AssetInspection\Exception\ExceptionInterface;
use Kongono\AssetInspection\Exception\InvalidAssetInspectionException;
use Kongono\AssetInspection\Exception\InvalidAssetIntelligenceEvidenceException;
use Kongono\AssetInspection\Inspection\AssetInspectionRequest;
use Kongono\AssetInspection\Inspection\AssetInspector;
use Kongono\AssetInspection\Projection\AssetObservationProjector;
use Kongono\AssetInspection\Projection\AssetSecurityAuditProjector;
use Kongono\AssetInspection\Role\AssetInspectionDeveloperRole;
use Kongono\AssetInspection\Tests\Support\AssetInspectionFactory;
use Kongono\Observability\ObservabilityContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AssetInspectionDisclosureTest extends TestCase
{
    public function testDiagnosticFamiliesRetainPreviousCausesWithoutDisclosingThem(): void
    {
        $previous = new RuntimeException(AssetInspectionFactory::EXCEPTION_SECRET);
        $failures = [
            InvalidAssetInspectionException::invalid('request', $previous),
            AssetInspectionBindingException::mismatch('artifact_structure', $previous),
            InvalidAssetIntelligenceEvidenceException::invalid('role_evidence', $previous),
            AssetInspectionProjectionException::failed('observation', $previous),
            AssetInspectionVerificationException::failed('verification', $previous),
        ];
        self::assertSame(
            array_map(static fn(int $number): string => 'KGN-ASSET-INSPECTION-' . $number, range(1950, 1954)),
            array_map(static fn(ExceptionInterface $failure): string => $failure->diagnosticId(), $failures),
        );
        foreach ($failures as $failure) {
            self::assertSame($previous, $failure->getPrevious());
            self::assertStringNotContainsString(AssetInspectionFactory::EXCEPTION_SECRET, (string) $failure);
            self::assertStringNotContainsString('RuntimeException', (string) $failure);
        }
    }

    public function testReturnedProjectedAndFailureSurfacesOmitForbiddenValues(): void
    {
        $current = AssetInspectionFactory::current();
        $artifact = $current->resolutionArtifact();
        $inspection = (new AssetInspector())->inspect(new AssetInspectionRequest($current));
        $view = (new AssetIntelligenceDeriver())->derive($inspection, AssetInspectionDeveloperRole::Oshie);
        $context = ObservabilityContext::uncorrelated();
        $observation = (new AssetObservationProjector())->project($inspection, $context);
        $audit = (new AssetSecurityAuditProjector())->project($inspection, $context);
        $failure = AssetInspectionBindingException::mismatch('artifact_structure', new RuntimeException(AssetInspectionFactory::EXCEPTION_SECRET));
        $surface = $inspection->canonicalBytes() . $view->canonicalBytes() . (string) $failure
            . $failure->getMessage() . $failure->diagnosticTitle() . $failure->diagnosticRemediation();
        foreach ([$observation->attributes(), $audit->attributes()] as $attributes) {
            foreach ($attributes->attributes() as $attribute) {
                $surface .= $attribute->key()->value() . '=' . (string) $attribute->value()->value();
            }
        }
        $forbidden = [
            AssetInspectionFactory::LOGICAL_SECRET,
            AssetInspectionFactory::BASENAME_SECRET,
            AssetInspectionFactory::EXCEPTION_SECRET,
            $artifact->entries()[0]->primary()->objectKey(),
            $artifact->entries()[0]->primary()->contentIdentity()->value(),
            $artifact->entries()[0]->primary()->integrity()->digest(),
            $artifact->entries()[0]->primary()->contentType()->value,
        ];
        foreach ($forbidden as $value) {
            self::assertStringNotContainsString($value, $surface);
        }
        foreach (['private script bytes', 'private style bytes', 'RuntimeException', 'http://', 'https://'] as $value) {
            self::assertStringNotContainsString($value, $surface);
        }
    }
}
