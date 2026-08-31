<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Tests\Unit;

use Kongono\ExtensionAdmission\Admission\ExtensionAdmissionBuilder;
use Kongono\ExtensionAdmission\Selection\ExtensionAdmissionPlan;
use Kongono\ExtensionAdmission\Selection\ExtensionSelection;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionComposition\Exception\InvalidExtensionGrantPlanException;
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
use PHPUnit\Framework\TestCase;
use RuntimeException;

interface AdversarialProvider {}
final class RequiredExtension
{
    public function __construct(AdversarialProvider $provider)
    {
        unset($provider);
    }
}
final class ScalarExtension
{
    public function __construct(string $provider)
    {
        unset($provider);
    }
}
abstract class AbstractExtension
{
    public function __construct(AdversarialProvider $provider)
    {
        unset($provider);
    }
}
abstract class AbstractProviderContract {}
final class AbstractProviderExtension
{
    public function __construct(AbstractProviderContract $provider)
    {
        unset($provider);
    }
}

final class TrustedExtensionWiringAdversarialTest extends TestCase
{
    public function testRequiredDenialOperationProviderIntoApplicationAndInvalidReflectionShapesFailClosed(): void
    {
        [$declaration, $admission] = self::scenario(ExtensionCategory::TrustedInProcess);
        $request = $declaration->capabilityRequests()[0];
        $deny = CapabilityDecision::deny($declaration->identity(), $declaration->version(), $request->identity(), $request->purpose(), $request->requirement(), 'provider', AdversarialProvider::class);
        $operationGrant = CapabilityDecision::grant($declaration->identity(), $declaration->version(), $request->identity(), $request->purpose(), $request->requirement(), 'provider', AdversarialProvider::class, CapabilityProviderLifetime::Operation);
        $applicationGrant = CapabilityDecision::grant($declaration->identity(), $declaration->version(), $request->identity(), $request->purpose(), $request->requirement(), 'provider', AdversarialProvider::class, CapabilityProviderLifetime::Application);
        $cases = [
            new ExtensionGrantPlan($admission->admissionIdentity(), [new TrustedExtensionImplementation($declaration->identity(), $declaration->version(), RequiredExtension::class)], [$deny]),
            new ExtensionGrantPlan($admission->admissionIdentity(), [new TrustedExtensionImplementation($declaration->identity(), $declaration->version(), RequiredExtension::class)], [$operationGrant]),
            new ExtensionGrantPlan($admission->admissionIdentity(), [new TrustedExtensionImplementation($declaration->identity(), $declaration->version(), ScalarExtension::class)], [$applicationGrant]),
            new ExtensionGrantPlan($admission->admissionIdentity(), [new TrustedExtensionImplementation($declaration->identity(), $declaration->version(), AbstractExtension::class)], [$applicationGrant]),
        ];
        foreach ($cases as $plan) {
            try {
                (new ExtensionWiringGenerator())->generate([$declaration], $admission, $plan);
                self::fail('Invalid trusted wiring was accepted.');
            } catch (InvalidExtensionGrantPlanException $exception) {
                self::assertSame('KGN-EXTENSION-COMPOSITION-1201', $exception->diagnosticId());
            }
        }
    }

    public function testNonTrustedCategoryFailsEvenWithExactSelectionAndImplementation(): void
    {
        [$declaration, $admission] = self::scenario(ExtensionCategory::External);
        $request = $declaration->capabilityRequests()[0];
        $decision = CapabilityDecision::grant($declaration->identity(), $declaration->version(), $request->identity(), $request->purpose(), $request->requirement(), 'provider', AdversarialProvider::class, CapabilityProviderLifetime::Application);
        $plan = new ExtensionGrantPlan($admission->admissionIdentity(), [new TrustedExtensionImplementation($declaration->identity(), $declaration->version(), RequiredExtension::class)], [$decision]);
        $this->expectException(InvalidExtensionGrantPlanException::class);
        (new ExtensionWiringGenerator())->generate([$declaration], $admission, $plan);
    }

    public function testAbstractUserlandProviderContractIsAcceptedWithoutInstantiation(): void
    {
        [$declaration, $admission] = self::scenario(ExtensionCategory::TrustedInProcess);
        $request = $declaration->capabilityRequests()[0];
        $decision = CapabilityDecision::grant($declaration->identity(), $declaration->version(), $request->identity(), $request->purpose(), $request->requirement(), 'provider', AbstractProviderContract::class, CapabilityProviderLifetime::Application);
        $plan = new ExtensionGrantPlan($admission->admissionIdentity(), [new TrustedExtensionImplementation($declaration->identity(), $declaration->version(), AbstractProviderExtension::class)], [$decision]);
        $bytes = (new ExtensionWiringGenerator())->generate([$declaration], $admission, $plan)->canonicalBytes();
        self::assertStringContainsString('\\' . AbstractProviderContract::class . ' $applicationProvider1', $bytes);
    }

    public function testAutoloadFailureIsPreservedButItsNativeMessageAndClassAreNotDisclosed(): void
    {
        [$declaration, $admission] = self::scenario(ExtensionCategory::TrustedInProcess);
        $request = $declaration->capabilityRequests()[0];
        $class = 'PrivateFixture\\AutoloadFailure';
        $sentinel = 'C:\\private\\autoload.php password=hunter2';
        $cause = new RuntimeException($sentinel);
        $loader = static function (string $candidate) use ($class, $cause): void {
            if ($candidate === $class) {
                throw $cause;
            }
        };
        spl_autoload_register($loader, true, true);
        try {
            $plan = new ExtensionGrantPlan($admission->admissionIdentity(), [new TrustedExtensionImplementation($declaration->identity(), $declaration->version(), $class)], [
                CapabilityDecision::grant($declaration->identity(), $declaration->version(), $request->identity(), $request->purpose(), $request->requirement(), 'provider', AdversarialProvider::class, CapabilityProviderLifetime::Application),
            ]);
            try {
                (new ExtensionWiringGenerator())->generate([$declaration], $admission, $plan);
                self::fail('Autoload failure was not preserved.');
            } catch (InvalidExtensionGrantPlanException $exception) {
                self::assertSame($cause, $exception->getPrevious());
                self::assertStringNotContainsString($sentinel, (string) $exception);
                self::assertStringNotContainsString($class, (string) $exception);
            }
        } finally {
            spl_autoload_unregister($loader);
        }
    }

    public function testEveryIncompleteExtraStaleOrMixedBindingFailsClosed(): void
    {
        [$declaration, $admission] = self::scenario(ExtensionCategory::TrustedInProcess);
        $request = $declaration->capabilityRequests()[0];
        $implementation = new TrustedExtensionImplementation($declaration->identity(), $declaration->version(), RequiredExtension::class);
        $decision = CapabilityDecision::grant($declaration->identity(), $declaration->version(), $request->identity(), $request->purpose(), $request->requirement(), 'provider', AdversarialProvider::class, CapabilityProviderLifetime::Application);
        $extraImplementation = new TrustedExtensionImplementation(new ExtensionIdentity('z/extra'), new ExtensionVersion('v1'), RequiredExtension::class);
        $extraDecision = CapabilityDecision::grant(new ExtensionIdentity('z/extra'), new ExtensionVersion('v1'), new CapabilityIdentity('provider', 1), new CapabilityPurpose('bounded_use'), CapabilityRequirement::Required, 'provider', AdversarialProvider::class, CapabilityProviderLifetime::Application);
        $wrongPurpose = CapabilityDecision::grant($declaration->identity(), $declaration->version(), $request->identity(), new CapabilityPurpose('other_use'), $request->requirement(), 'provider', AdversarialProvider::class, CapabilityProviderLifetime::Application);
        $cases = [
            [[], new ExtensionGrantPlan($admission->admissionIdentity(), [$implementation], [$decision])],
            [[$declaration], new ExtensionGrantPlan(new \Kongono\ExtensionAdmission\Identity\AdmissionIdentity(str_repeat('f', 64)), [$implementation], [$decision])],
            [[$declaration], new ExtensionGrantPlan($admission->admissionIdentity(), [], [$decision])],
            [[$declaration], new ExtensionGrantPlan($admission->admissionIdentity(), [$implementation, $extraImplementation], [$decision])],
            [[$declaration], new ExtensionGrantPlan($admission->admissionIdentity(), [$implementation], [])],
            [[$declaration], new ExtensionGrantPlan($admission->admissionIdentity(), [$implementation], [$decision, $extraDecision])],
            [[$declaration], new ExtensionGrantPlan($admission->admissionIdentity(), [new TrustedExtensionImplementation($declaration->identity(), new ExtensionVersion('v2'), RequiredExtension::class)], [$decision])],
            [[$declaration], new ExtensionGrantPlan($admission->admissionIdentity(), [$implementation], [$wrongPurpose])],
        ];
        foreach ($cases as [$declarations, $plan]) {
            try {
                (new ExtensionWiringGenerator())->generate($declarations, $admission, $plan);
                self::fail('Incomplete, extra, stale, or mixed input was accepted.');
            } catch (InvalidExtensionGrantPlanException $exception) {
                self::assertSame('KGN-EXTENSION-COMPOSITION-1201', $exception->diagnosticId());
            }
        }
    }

    /** @return array{ExtensionDeclaration, \Kongono\ExtensionAdmission\Admission\AdmittedExtensionGraphInput} */
    private static function scenario(ExtensionCategory $category): array
    {
        $request = new CapabilityRequest(new CapabilityIdentity('provider', 1), new CapabilityPurpose('bounded_use'), CapabilityRequirement::Required);
        $declaration = new ExtensionDeclaration(ContractVersion::Version1, new ExtensionIdentity('example/adversarial'), new ExtensionVersion('v1'), $category, new ExtensionProvenance(new ExtensionSourceIdentity(str_repeat('a', 64)), str_repeat('b', 64)), ExtensionLifecycle::Application, [$request], []);
        $identity = (new ExtensionDeclarationArtifactBuilder())->build([$declaration])->declarationSetIdentity();
        $admission = (new ExtensionAdmissionBuilder())->build([$declaration], new ExtensionAdmissionPlan($identity, [new ExtensionSelection($declaration->identity(), $declaration->version())], []), []);
        return [$declaration, $admission];
    }
}
