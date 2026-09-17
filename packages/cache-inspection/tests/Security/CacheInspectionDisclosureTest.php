<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Tests\Security;

use Kongono\CacheInspection\Derivation\CacheIntelligenceDeriver;
use Kongono\CacheInspection\Exception\CacheInspectionBindingException;
use Kongono\CacheInspection\Exception\CacheInspectionProjectionException;
use Kongono\CacheInspection\Exception\CacheInspectionVerificationException;
use Kongono\CacheInspection\Exception\ExceptionInterface;
use Kongono\CacheInspection\Exception\InvalidCacheInspectionException;
use Kongono\CacheInspection\Exception\InvalidCacheIntelligenceEvidenceException;
use Kongono\CacheInspection\Inspection\CacheInspectionRequest;
use Kongono\CacheInspection\Inspection\CacheInspector;
use Kongono\CacheInspection\Projection\CacheObservationProjector;
use Kongono\CacheInspection\Projection\CacheSecurityAuditProjector;
use Kongono\CacheInspection\Role\CacheInspectionDeveloperRole;
use Kongono\CacheInspection\Tests\Support\CacheInspectionFactory;
use Kongono\Observability\ObservabilityContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CacheInspectionDisclosureTest extends TestCase
{
    public function testDiagnosticFamiliesRetainPreviousCausesWithoutDisclosingThem(): void
    {
        $previous = new RuntimeException(CacheInspectionFactory::EXCEPTION_SECRET);
        $failures = [
            InvalidCacheInspectionException::invalid('request', $previous),
            CacheInspectionBindingException::mismatch('artifact_structure', $previous),
            InvalidCacheIntelligenceEvidenceException::invalid('runtime_evidence', $previous),
            CacheInspectionProjectionException::failed('observation', $previous),
            CacheInspectionVerificationException::failed('verification', $previous),
        ];
        self::assertSame(
            array_map(static fn(int $number): string => 'KGN-CACHE-INSPECTION-' . $number, range(2051, 2055)),
            array_map(static fn(ExceptionInterface $failure): string => $failure->diagnosticId(), $failures),
        );
        foreach ($failures as $failure) {
            self::assertSame($previous, $failure->getPrevious());
            self::assertStringNotContainsString(CacheInspectionFactory::EXCEPTION_SECRET, (string) $failure);
            self::assertStringNotContainsString('RuntimeException', (string) $failure);
        }
    }

    public function testAllReturnedProjectedAndFailureSurfacesOmitForbiddenValuesByteForByte(): void
    {
        $current = CacheInspectionFactory::current();
        $request = new CacheInspectionRequest($current, CacheInspectionFactory::runtime($current));
        $inspection = (new CacheInspector())->inspect($request);
        $view = (new CacheIntelligenceDeriver())->derive($inspection, CacheInspectionDeveloperRole::Oshie);
        $context = ObservabilityContext::uncorrelated();
        $observation = (new CacheObservationProjector())->project($inspection, $context);
        $audit = (new CacheSecurityAuditProjector())->project($inspection, $context);
        $failure = CacheInspectionBindingException::mismatch('artifact_structure', new RuntimeException(CacheInspectionFactory::EXCEPTION_SECRET));
        $surface = $inspection->canonicalBytes() . $view->canonicalBytes() . (string) $failure
            . $failure->getMessage() . $failure->diagnosticTitle() . $failure->diagnosticRemediation();
        foreach ([$observation->attributes(), $audit->attributes()] as $attributes) {
            foreach ($attributes->attributes() as $attribute) {
                $surface .= $attribute->key()->value() . '=' . (string) $attribute->value()->value();
            }
        }
        foreach ([
            CacheInspectionFactory::PROFILE_SECRET,
            CacheInspectionFactory::NAMESPACE_SECRET,
            CacheInspectionFactory::CODEC_SECRET,
            CacheInspectionFactory::KEY_SECRET,
            CacheInspectionFactory::VALUE_SECRET,
            CacheInspectionFactory::PROVIDER_SECRET,
            CacheInspectionFactory::EXCEPTION_SECRET,
            $current->artifact()->bytes(),
        ] as $value) {
            self::assertStringNotContainsString($value, $surface);
        }
        foreach (['RuntimeException', 'http://', 'https://', 'lifetime_microseconds', 'max_value_bytes'] as $value) {
            self::assertStringNotContainsString($value, $surface);
        }
    }
}
