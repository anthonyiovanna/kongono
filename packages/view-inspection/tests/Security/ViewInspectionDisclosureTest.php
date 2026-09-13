<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Tests\Security;

use Kongono\Observability\ObservabilityContext;
use Kongono\ViewInspection\Derivation\ViewIntelligenceDeriver;
use Kongono\ViewInspection\Exception\ViewArtifactEvidenceException;
use Kongono\ViewInspection\Inspection\ViewInspectionRequest;
use Kongono\ViewInspection\Inspection\ViewInspector;
use Kongono\ViewInspection\Projection\ViewObservationProjector;
use Kongono\ViewInspection\Projection\ViewSecurityAuditProjector;
use Kongono\ViewInspection\Role\ViewInspectionDeveloperRole;
use Kongono\ViewInspection\Tests\Support\ViewInspectionFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViewInspectionDisclosureTest extends TestCase
{
    public function testEveryReturnedProjectedAndFailureSurfaceOmitsHostileContent(): void
    {
        $profile = ViewInspectionFactory::profile();
        $artifact = $profile->currentBuild()->artifact();
        $inspection = (new ViewInspector())->inspect(new ViewInspectionRequest($profile));
        $view = (new ViewIntelligenceDeriver())->derive($inspection, ViewInspectionDeveloperRole::Oshie);
        $context = ObservabilityContext::uncorrelated();
        $observation = (new ViewObservationProjector())->project($inspection, $context);
        $audit = (new ViewSecurityAuditProjector())->project($inspection, $context);
        $failure = ViewArtifactEvidenceException::mismatch('artifact_structure', new RuntimeException(ViewInspectionFactory::EXCEPTION_SECRET));
        $surface = $inspection->canonicalBytes() . $view->canonicalBytes() . (string) $failure
            . $failure->getMessage() . $failure->diagnosticTitle() . $failure->diagnosticRemediation();
        foreach ([$observation->attributes(), $audit->attributes()] as $attributes) {
            foreach ($attributes->attributes() as $attribute) {
                $surface .= $attribute->key()->value() . '=' . (string) $attribute->value()->value();
            }
        }
        foreach ([
            ViewInspectionFactory::SOURCE_SECRET,
            ViewInspectionFactory::PATH_SECRET,
            ViewInspectionFactory::CONTEXT_SECRET,
            ViewInspectionFactory::RENDERED_SECRET,
            ViewInspectionFactory::HEADER_SECRET,
            ViewInspectionFactory::EXCEPTION_SECRET,
            $artifact->metadataBytes(),
            $artifact->generatedPhpBytes(),
            $artifact->generatedClass(),
        ] as $secret) {
            self::assertStringNotContainsString($secret, $surface);
        }
        self::assertStringNotContainsString('RuntimeException', $surface);
    }
}
