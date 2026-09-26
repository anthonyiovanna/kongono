<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Tests\Security;

use Kongono\Observability\ObservabilityContext;
use Kongono\StorageInspection\Derivation\StorageIntelligenceDeriver;
use Kongono\StorageInspection\Exception\ExceptionInterface;
use Kongono\StorageInspection\Exception\InvalidStorageInspectionException;
use Kongono\StorageInspection\Exception\InvalidStorageIntelligenceEvidenceException;
use Kongono\StorageInspection\Exception\StorageInspectionBindingException;
use Kongono\StorageInspection\Exception\StorageInspectionProjectionException;
use Kongono\StorageInspection\Exception\StorageInspectionVerificationException;
use Kongono\StorageInspection\Inspection\StorageInspectionRequest;
use Kongono\StorageInspection\Inspection\StorageInspector;
use Kongono\StorageInspection\Projection\StorageObservationProjector;
use Kongono\StorageInspection\Projection\StorageSecurityAuditProjector;
use Kongono\StorageInspection\Role\StorageInspectionDeveloperRole;
use Kongono\StorageInspection\Tests\Support\StorageInspectionFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StorageInspectionDisclosureTest extends TestCase
{
    public function testDiagnosticFamiliesRetainPreviousCausesWithoutDisclosingThem(): void
    {
        $previous = new RuntimeException(StorageInspectionFactory::EXCEPTION_SECRET);
        $failures = [
            InvalidStorageInspectionException::invalid('request', $previous),
            StorageInspectionBindingException::mismatch('artifact_structure', $previous),
            InvalidStorageIntelligenceEvidenceException::invalid('runtime_evidence', $previous),
            StorageInspectionProjectionException::failed('observation', $previous),
            StorageInspectionVerificationException::failed('verification', $previous),
        ];
        self::assertSame(
            array_map(static fn(int $number): string => 'KGN-STORAGE-INSPECTION-' . $number, range(2151, 2155)),
            array_map(static fn(ExceptionInterface $failure): string => $failure->diagnosticId(), $failures),
        );
        foreach ($failures as $failure) {
            self::assertSame($previous, $failure->getPrevious());
            self::assertStringNotContainsString(StorageInspectionFactory::EXCEPTION_SECRET, (string) $failure);
            self::assertStringNotContainsString('RuntimeException', (string) $failure);
        }
    }

    public function testAllReturnedProjectedAndFailureSurfacesOmitForbiddenValuesByteForByte(): void
    {
        $current = StorageInspectionFactory::current();
        $request = new StorageInspectionRequest($current, StorageInspectionFactory::runtime($current));
        $inspection = (new StorageInspector())->inspect($request);
        $view = (new StorageIntelligenceDeriver())->derive($inspection, StorageInspectionDeveloperRole::Oshie);
        $context = ObservabilityContext::uncorrelated();
        $observation = (new StorageObservationProjector())->project($inspection, $context);
        $audit = (new StorageSecurityAuditProjector())->project($inspection, $context);
        $failure = StorageInspectionBindingException::mismatch('artifact_structure', new RuntimeException(StorageInspectionFactory::EXCEPTION_SECRET));
        $surface = $inspection->canonicalBytes() . $view->canonicalBytes() . (string) $failure
            . $failure->getMessage() . $failure->diagnosticTitle() . $failure->diagnosticRemediation();
        foreach ([$observation->attributes(), $audit->attributes()] as $attributes) {
            foreach ($attributes->attributes() as $attribute) {
                $surface .= $attribute->key()->value() . '=' . (string) $attribute->value()->value();
            }
        }
        foreach ([
            StorageInspectionFactory::PROFILE_SECRET,
            StorageInspectionFactory::NAMESPACE_SECRET,
            StorageInspectionFactory::OBJECT_SECRET,
            StorageInspectionFactory::CONTENT_SECRET,
            StorageInspectionFactory::METADATA_SECRET,
            StorageInspectionFactory::PATH_SECRET,
            StorageInspectionFactory::PROVIDER_SECRET,
            StorageInspectionFactory::CREDENTIAL_SECRET,
            StorageInspectionFactory::EXCEPTION_SECRET,
            $current->artifact()->bytes(),
        ] as $value) {
            self::assertStringNotContainsString($value, $surface);
        }
        foreach (['RuntimeException', 'http://', 'https://', 'object_bytes', 'provider_locator'] as $value) {
            self::assertStringNotContainsString($value, $surface);
        }
    }

    public function testDisclosureMatrixInBothProvenExceptionArgumentModes(): void
    {
        $body = <<<'PHP'
$factory = \Kongono\StorageInspection\Tests\Support\StorageInspectionFactory::class;
$sentinels = [
    $factory::PROFILE_SECRET, $factory::NAMESPACE_SECRET, $factory::OBJECT_SECRET,
    $factory::CONTENT_SECRET, $factory::METADATA_SECRET, $factory::PATH_SECRET,
    $factory::PROVIDER_SECRET, $factory::CREDENTIAL_SECRET, $factory::EXCEPTION_SECRET,
];
$foreign = new \RuntimeException(implode('|', $sentinels));
$surface = '';
$families = [];
$render = static function (\Kongono\StorageInspection\Exception\ExceptionInterface $failure) use (&$surface, &$families): void {
    $families[] = $failure->diagnosticId();
    $surface .= $failure->getMessage() . $failure->diagnosticTitle() . $failure->diagnosticRemediation() . (string) $failure;
    foreach ($failure->diagnosticContext()->fields() as $field) {
        $surface .= $field->key() . '=' . $field->value();
    }
};
$thrower = static function (array $private) use ($foreign): never {
    throw \Kongono\StorageInspection\Exception\InvalidStorageInspectionException::invalid($private[5], $foreign);
};
try {
    $thrower($sentinels);
} catch (\Kongono\StorageInspection\Exception\InvalidStorageInspectionException $failure) {
    $traceHasArguments = str_contains(json_encode($failure->getTrace(), JSON_THROW_ON_ERROR), $factory::PATH_SECRET);
    $render($failure);
}
$render(\Kongono\StorageInspection\Exception\StorageInspectionBindingException::mismatch($factory::PROVIDER_SECRET, $foreign));
$render(\Kongono\StorageInspection\Exception\InvalidStorageIntelligenceEvidenceException::invalid($factory::CREDENTIAL_SECRET, $foreign));
$render(\Kongono\StorageInspection\Exception\StorageInspectionProjectionException::failed($factory::OBJECT_SECRET, $foreign));
$render(\Kongono\StorageInspection\Exception\StorageInspectionVerificationException::failed($factory::METADATA_SECRET, $foreign));
$current = $factory::current();
$runtime = $factory::runtime($current);
$request = new \Kongono\StorageInspection\Inspection\StorageInspectionRequest($current, $runtime);
$inspection = (new \Kongono\StorageInspection\Inspection\StorageInspector())->inspect($request);
$view = (new \Kongono\StorageInspection\Derivation\StorageIntelligenceDeriver())->derive(
    $inspection, \Kongono\StorageInspection\Role\StorageInspectionDeveloperRole::Chie,
);
$surface .= $inspection->canonicalBytes() . $view->canonicalBytes();
$context = \Kongono\Observability\ObservabilityContext::uncorrelated();
$observation = (new \Kongono\StorageInspection\Projection\StorageObservationProjector())->project($inspection, $context);
$audit = (new \Kongono\StorageInspection\Projection\StorageSecurityAuditProjector())->project($inspection, $context);
foreach ([$observation->attributes(), $audit->attributes()] as $attributes) {
    foreach ($attributes->attributes() as $attribute) {
        $surface .= $attribute->key()->value() . '=' . (string) $attribute->value()->value();
    }
}
$other = $factory::current(true, '-different');
$otherInspection = (new \Kongono\StorageInspection\Inspection\StorageInspector())->inspect(
    new \Kongono\StorageInspection\Inspection\StorageInspectionRequest($other),
);
$verification = (new \Kongono\StorageInspection\Verification\StorageIntelligenceVerifier())->verify(
    $request, $inspection, $view,
    (new \Kongono\StorageInspection\Projection\StorageObservationProjector())->project($otherInspection, $context),
    (new \Kongono\StorageInspection\Projection\StorageSecurityAuditProjector())->project($otherInspection, $context),
);
$surface .= json_encode($verification->toArray(), JSON_THROW_ON_ERROR);
$cases = [
    static function () use ($inspection, $factory): void {
        $bindings = $inspection->protectedBindings();
        $bindings['artifact_sha256'] = $factory::OBJECT_SECRET;
        new \Kongono\StorageInspection\Inspection\StorageInspection($inspection->runtimeEvidenceStatus(), $bindings, $inspection->counts(), $inspection->flags());
    },
    static function () use ($inspection, $factory): void {
        $counts = $inspection->counts();
        $counts['read_completed'] = $factory::CONTENT_SECRET;
        new \Kongono\StorageInspection\Inspection\StorageInspection($inspection->runtimeEvidenceStatus(), $inspection->protectedBindings(), $counts, $inspection->flags());
    },
    static function () use ($runtime, $factory): void {
        $bindings = $runtime->bindings();
        $bindings['artifact_sha256'] = $factory::PROVIDER_SECRET;
        new \Kongono\StorageInspection\RuntimeEvidence\DetachedStorageRuntimeEvidence($bindings, $runtime->outcomeCounts());
    },
];
foreach ($cases as $case) {
    try {
        $case();
        throw new \LogicException('Malformed direct input unexpectedly accepted.');
    } catch (\Kongono\StorageInspection\Exception\ExceptionInterface $failure) {
        $render($failure);
    }
}
echo json_encode([
    'mode' => (string) ini_get('zend.exception_ignore_args'),
    'trace_has_arguments' => $traceHasArguments,
    'families' => array_values(array_unique($families)),
    'disclosed' => array_values(array_filter($sentinels, static fn(string $value): bool => str_contains($surface, $value))),
    'verification' => $verification->verdict()->value,
    'public_bytes' => strlen($surface),
], JSON_THROW_ON_ERROR);
PHP;
        foreach (['0', '1'] as $mode) {
            $result = StorageInspectionFactory::isolatedPhp($body, $mode);
            StorageInspectionFactory::requireSuccessfulChild($result);
            self::assertSame('', $result['stderr']);
            $proof = json_decode($result['stdout'], true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($proof);
            self::assertSame($mode, $proof['mode']);
            self::assertSame('0' === $mode, $proof['trace_has_arguments']);
            self::assertSame(array_map(static fn(int $number): string => 'KGN-STORAGE-INSPECTION-' . $number, range(2151, 2155)), $proof['families']);
            self::assertSame([], $proof['disclosed']);
            self::assertSame('fail', $proof['verification']);
            self::assertGreaterThan(100, $proof['public_bytes']);
            foreach ([StorageInspectionFactory::PATH_SECRET, StorageInspectionFactory::CREDENTIAL_SECRET] as $value) {
                self::assertStringNotContainsString($value, $result['stdout']);
            }
            StorageInspectionFactory::acknowledgeChild($result);
        }
    }
}
