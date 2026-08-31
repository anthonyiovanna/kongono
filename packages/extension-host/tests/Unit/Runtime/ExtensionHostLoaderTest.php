<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Tests\Unit\Runtime;

use Kongono\ExtensionAdmission\Admission\ExtensionAdmissionBuilder;
use Kongono\ExtensionAdmission\Identity\AdmissionIdentity;
use Kongono\ExtensionAdmission\Selection\ExtensionAdmissionPlan;
use Kongono\ExtensionAdmission\Selection\ExtensionSelection;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionComposition\Generated\ExtensionWiringGenerator;
use Kongono\ExtensionComposition\Grant\CapabilityDecision;
use Kongono\ExtensionComposition\Grant\CapabilityProviderLifetime;
use Kongono\ExtensionComposition\Implementation\TrustedExtensionImplementation;
use Kongono\ExtensionComposition\Plan\ExtensionGrantPlan;
use Kongono\ExtensionContract\Capability\CapabilityIdentity;
use Kongono\ExtensionContract\Capability\CapabilityPurpose;
use Kongono\ExtensionContract\Capability\CapabilityRequest;
use Kongono\ExtensionContract\Capability\CapabilityRequirement;
use Kongono\ExtensionContract\ContractVersion;
use Kongono\ExtensionContract\Declaration\ExtensionDeclaration;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionContract\Provenance\ExtensionProvenance;
use Kongono\ExtensionContract\Provenance\ExtensionSourceIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use Kongono\ExtensionHost\Contract\ApplicationExtensionInterface;
use Kongono\ExtensionHost\Contract\OperationExtensionInterface;
use Kongono\ExtensionHost\Exception\InvalidGeneratedExtensionHostException;
use Kongono\ExtensionHost\Runtime\ExtensionHostLoader;
use PHPUnit\Framework\TestCase;

interface LoaderProvider {}
final class LoaderProviderValue implements LoaderProvider {}
final class LoaderApplicationExtension implements ApplicationExtensionInterface
{
    public function __construct(LoaderProvider $provider)
    {
        unset($provider);
    }
    public function start(): void {}
    public function stop(): void {}
}
final class LoaderOperationExtension implements OperationExtensionInterface
{
    public function __construct(LoaderProvider $provider)
    {
        unset($provider);
    }
    public function begin(): void {}
    public function close(): void {}
}
final class LoaderInvalidApplicationExtension
{
    public function __construct(LoaderProvider $provider)
    {
        unset($provider);
    }
}

final class ExtensionHostLoaderTest extends TestCase
{
    public function testExactArtifactPlanPathBytesClassesAndLifecycleShapeAreBoundBeforeStartup(): void
    {
        [$artifact, $plan] = self::scenario('valid', LoaderApplicationExtension::class, ExtensionLifecycle::Application);
        $directory = self::directory('valid');
        $path = $directory . '/' . $artifact->wiringSha256() . '.php';
        self::assertSame(strlen($artifact->canonicalBytes()), file_put_contents($path, $artifact->canonicalBytes()));
        try {
            $wrongPlan = new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('f', 64)), [], []);
            $this->expectLoadFailure(fn() => (new ExtensionHostLoader())->load($artifact, $wrongPlan, $path));

            $wrongName = $directory . '/wrong.php';
            self::assertSame(strlen($artifact->canonicalBytes()), file_put_contents($wrongName, $artifact->canonicalBytes()));
            $this->expectLoadFailure(fn() => (new ExtensionHostLoader())->load($artifact, $plan, $wrongName));
            self::assertTrue(unlink($wrongName));

            $loaded = (new ExtensionHostLoader())->load($artifact, $plan, $path);
            self::assertSame($artifact->wiringSha256(), $loaded->wiringSha256());
            self::assertFalse($loaded->isStarted());
            self::assertTrue(class_exists($loaded->applicationClass(), false));
            self::assertTrue(class_exists($loaded->operationClass(), false));
        } finally {
            self::remove($directory);
        }
    }

    public function testIrreversiblePostRequireLifecycleMismatchFailsVisiblyWithoutFallback(): void
    {
        [$artifact, $plan] = self::scenario('invalid', LoaderInvalidApplicationExtension::class, ExtensionLifecycle::Application);
        $directory = self::directory('invalid');
        $path = $directory . '/' . $artifact->wiringSha256() . '.php';
        self::assertSame(strlen($artifact->canonicalBytes()), file_put_contents($path, $artifact->canonicalBytes()));
        try {
            $this->expectLoadFailure(fn() => (new ExtensionHostLoader())->load($artifact, $plan, $path));
            self::assertTrue(class_exists($artifact->applicationClass(), false));
            self::assertTrue(class_exists($artifact->operationClass(), false));
        } finally {
            self::remove($directory);
        }
    }

    /** @return array{\Kongono\ExtensionComposition\Artifact\GeneratedExtensionWiringArtifact, ExtensionGrantPlan} */
    private static function scenario(string $seed, string $implementationClass, ExtensionLifecycle $lifecycle): array
    {
        $identity = new ExtensionIdentity('example/' . $seed);
        $version = new ExtensionVersion('v1');
        $request = new CapabilityRequest(new CapabilityIdentity('provider', 1), new CapabilityPurpose('lifecycle'), CapabilityRequirement::Required);
        $declaration = new ExtensionDeclaration(ContractVersion::Version1, $identity, $version, ExtensionCategory::TrustedInProcess, new ExtensionProvenance(new ExtensionSourceIdentity(str_repeat('a', 64)), str_repeat('b', 64)), $lifecycle, [$request], []);
        $declarations = [$declaration];
        $declarationIdentity = (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity();
        $admission = (new ExtensionAdmissionBuilder())->build($declarations, new ExtensionAdmissionPlan($declarationIdentity, [new ExtensionSelection($identity, $version)], []), []);
        $decision = CapabilityDecision::grant($identity, $version, $request->identity(), $request->purpose(), $request->requirement(), 'provider', LoaderProvider::class, CapabilityProviderLifetime::Application);
        $plan = new ExtensionGrantPlan($admission->admissionIdentity(), [new TrustedExtensionImplementation($identity, $version, $implementationClass)], [$decision]);

        return [(new ExtensionWiringGenerator())->generate($declarations, $admission, $plan), $plan];
    }

    /** @param callable(): mixed $operation */
    private function expectLoadFailure(callable $operation): void
    {
        try {
            $operation();
            self::fail('Invalid generated host input was accepted.');
        } catch (InvalidGeneratedExtensionHostException $failure) {
            self::assertSame('KGN-EXTENSION-HOST-1201', $failure->diagnosticId());
        }
    }

    private static function directory(string $seed): string
    {
        $directory = sys_get_temp_dir() . '/kgn12f-loader-' . $seed . '-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($directory));

        return str_replace('\\', '/', (string) realpath($directory));
    }

    private static function remove(string $directory): void
    {
        $paths = glob($directory . '/*');
        self::assertIsArray($paths);
        foreach ($paths as $path) {
            self::assertTrue(unlink($path));
        }
        self::assertTrue(rmdir($directory));
    }
}
