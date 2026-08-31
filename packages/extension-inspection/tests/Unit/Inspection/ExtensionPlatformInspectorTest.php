<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Tests\Unit\Inspection;

use Kongono\ExtensionAdmission\Identity\AdmissionIdentity;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionComposition\Artifact\GeneratedExtensionWiringArtifact;
use Kongono\ExtensionComposition\Identity\GrantPlanIdentity;
use Kongono\ExtensionInspection\Exception\ExtensionInspectionLoadException;
use Kongono\ExtensionInspection\Inspection\ExtensionPlatformInspector;
use PHPUnit\Framework\TestCase;

final class ExtensionPlatformInspectorTest extends TestCase
{
    public function testValidatedEmptyEvidenceProducesOneInspection(): void
    {
        $declaration = (new ExtensionDeclarationArtifactBuilder())->build([]);
        $wiring = new GeneratedExtensionWiringArtifact("<?php\n", str_repeat('1', 64), $declaration->declarationSetIdentity(), new AdmissionIdentity(str_repeat('2', 64)), new GrantPlanIdentity(str_repeat('3', 64)), str_repeat('4', 64), 'Generated', 'Generated\\Application', 'Generated\\Operation', 0, 0, 0, 0);
        self::assertSame(0, (new ExtensionPlatformInspector())->inspect($declaration, $wiring)->declarationCount());
    }

    public function testIdentityMismatchFails(): void
    {
        $declaration = (new ExtensionDeclarationArtifactBuilder())->build([]);
        $wiring = new GeneratedExtensionWiringArtifact("<?php\n", str_repeat('1', 64), new \Kongono\ExtensionBuild\Identity\DeclarationSetIdentity(str_repeat('9', 64)), new AdmissionIdentity(str_repeat('2', 64)), new GrantPlanIdentity(str_repeat('3', 64)), str_repeat('4', 64), 'Generated', 'Generated\\Application', 'Generated\\Operation', 0, 0, 0, 0);
        $this->expectException(ExtensionInspectionLoadException::class);
        (new ExtensionPlatformInspector())->inspect($declaration, $wiring);
    }
}
