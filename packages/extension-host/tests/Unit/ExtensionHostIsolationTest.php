<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Tests\Unit;

use Kongono\ExtensionAdmission\Admission\ExtensionAdmissionBuilder;
use Kongono\ExtensionAdmission\Selection\ExtensionAdmissionPlan;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionComposition\Generated\ExtensionWiringGenerator;
use Kongono\ExtensionComposition\Plan\ExtensionGrantPlan;
use Kongono\ExtensionHost\Runtime\ExtensionHostLoader;
use Kongono\ExtensionHost\Runtime\ExtensionHostState;
use Kongono\ExtensionHost\Runtime\TrustedExtensionHost;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class ExtensionHostIsolationTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testSequentialAndOverlappingOperationsOwnIndependentLeasesAndMayCloseInAnyOrder(): void
    {
        $declarations = [];
        $identity = (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity();
        $admission = (new ExtensionAdmissionBuilder())->build($declarations, new ExtensionAdmissionPlan($identity, [], []), []);
        $plan = new ExtensionGrantPlan($admission->admissionIdentity(), [], []);
        $artifact = (new ExtensionWiringGenerator())->generate($declarations, $admission, $plan);
        $directory = sys_get_temp_dir() . '/kgn12f-isolation-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($directory));
        $directory = str_replace('\\', '/', (string) realpath($directory));
        $path = $directory . '/' . $artifact->wiringSha256() . '.php';
        self::assertSame(strlen($artifact->canonicalBytes()), file_put_contents($path, $artifact->canonicalBytes()));
        try {
            $host = TrustedExtensionHost::start((new ExtensionHostLoader())->load($artifact, $plan, $path));
            $first = $host->beginOperation();
            $second = $host->beginOperation();
            self::assertNotSame($first, $second);
            $second->close();
            $first->close();
            $third = $host->beginOperation();
            $third->close();
            self::assertSame(ExtensionHostState::Active, $host->state());
            $host->shutdown();
        } finally {
            self::assertTrue(unlink($path));
            self::assertTrue(rmdir($directory));
        }
    }

    public function testApplicationHostSourceRetainsNoOperationObjectsProvidersExtensionsOrFailures(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Runtime/TrustedExtensionHost.php');
        self::assertStringContainsString('private int $activeOperations = 0;', $source);
        self::assertStringNotContainsString('private array $operations', $source);
        self::assertStringNotContainsString('private array $operationProviders', $source);
        self::assertStringNotContainsString('private array $operationExtensions', $source);
        self::assertStringNotContainsString('private array $failures', $source);
    }
}
