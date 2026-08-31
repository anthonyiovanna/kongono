<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Tests\Unit\Runtime;

use Kongono\ExtensionAdmission\Admission\ExtensionAdmissionBuilder;
use Kongono\ExtensionAdmission\Selection\ExtensionAdmissionPlan;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionComposition\Generated\ExtensionWiringGenerator;
use Kongono\ExtensionComposition\Plan\ExtensionGrantPlan;
use Kongono\ExtensionHost\Exception\InvalidGeneratedExtensionHostException;
use Kongono\ExtensionHost\Runtime\ExtensionHostLoader;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class LoadedExtensionWiringTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testLoadedStartupIsOneUseAndConstructsNoExtensionsForEmptyExactInput(): void
    {
        $declarations = [];
        $identity = (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity();
        $admission = (new ExtensionAdmissionBuilder())->build($declarations, new ExtensionAdmissionPlan($identity, [], []), []);
        $plan = new ExtensionGrantPlan($admission->admissionIdentity(), [], []);
        $artifact = (new ExtensionWiringGenerator())->generate($declarations, $admission, $plan);
        $directory = sys_get_temp_dir() . '/kgn12f-loaded-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($directory));
        $directory = str_replace('\\', '/', (string) realpath($directory));
        $path = $directory . '/' . $artifact->wiringSha256() . '.php';
        self::assertSame(strlen($artifact->canonicalBytes()), file_put_contents($path, $artifact->canonicalBytes()));
        try {
            $loaded = (new ExtensionHostLoader())->load($artifact, $plan, $path);
            $boundary = $loaded->start();
            self::assertSame([], $boundary->applicationExtensions());
            self::assertTrue($loaded->isStarted());
            $this->expectException(InvalidGeneratedExtensionHostException::class);
            $loaded->start();
        } finally {
            self::assertTrue(unlink($path));
            self::assertTrue(rmdir($directory));
        }
    }
}
