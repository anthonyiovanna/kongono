<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Tests\Unit\Plan;

use Kongono\ExtensionAdmission\Identity\AdmissionIdentity;
use Kongono\ExtensionComposition\Exception\InvalidExtensionGrantPlanException;
use Kongono\ExtensionComposition\Grant\CapabilityDecision;
use Kongono\ExtensionComposition\Grant\CapabilityProviderLifetime;
use Kongono\ExtensionComposition\Implementation\TrustedExtensionImplementation;
use Kongono\ExtensionComposition\Plan\ExtensionGrantPlan;
use Kongono\ExtensionContract\Capability\CapabilityIdentity;
use Kongono\ExtensionContract\Capability\CapabilityPurpose;
use Kongono\ExtensionContract\Capability\CapabilityRequirement;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use PHPUnit\Framework\TestCase;

final class ExtensionGrantPlanTest extends TestCase
{
    public function testCanonicalPlanIsDetachedBoundedAndIdentitySensitive(): void
    {
        $implementation = new TrustedExtensionImplementation(new ExtensionIdentity('a/a'), new ExtensionVersion('v1'), 'Example\\A');
        $decision = CapabilityDecision::grant($implementation->identity(), $implementation->version(), new CapabilityIdentity('read', 1), new CapabilityPurpose('render'), CapabilityRequirement::Required, 'reader', 'Example\\Reader', CapabilityProviderLifetime::Application);
        $implementations = [$implementation];
        $decisions = [$decision];
        $plan = new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('a', 64)), $implementations, $decisions);
        $implementations = [];
        $decisions = [];
        self::assertSame($implementation, $plan->implementations()[0]);
        self::assertSame($decision, $plan->decisions()[0]);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $plan->identity()->value());
        $changed = new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('b', 64)), [$implementation], [$decision]);
        self::assertNotSame($plan->identity()->value(), $changed->identity()->value());
    }

    public function testNonListsDuplicatesOrderAndDuplicateParametersFailClosed(): void
    {
        $a = new TrustedExtensionImplementation(new ExtensionIdentity('a/a'), new ExtensionVersion('v1'), 'Example\\A');
        $b = new TrustedExtensionImplementation(new ExtensionIdentity('b/b'), new ExtensionVersion('v1'), 'Example\\B');
        $decision = static fn(TrustedExtensionImplementation $implementation, string $capability): CapabilityDecision => CapabilityDecision::grant($implementation->identity(), $implementation->version(), new CapabilityIdentity($capability, 1), new CapabilityPurpose('purpose'), CapabilityRequirement::Required, 'provider', 'Example\\Provider', CapabilityProviderLifetime::Application);
        $cases = [
            fn() => new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('a', 64)), ['named' => $a], []),
            fn() => new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('a', 64)), [$a, $a], []),
            fn() => new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('a', 64)), [$b, $a], []),
            fn() => new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('a', 64)), [$a], ['named' => $decision($a, 'a')]),
            fn() => new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('a', 64)), [$a], [$decision($a, 'a'), $decision($a, 'b')]),
            fn() => new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('a', 64)), [new \stdClass()], []),
            fn() => new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('a', 64)), [$a], [new \stdClass()]),
        ];
        foreach ($cases as $case) {
            $this->expectFailure($case);
        }
    }

    public function testEveryPlanSemanticChangesItsCanonicalIdentity(): void
    {
        $admission = new AdmissionIdentity(str_repeat('a', 64));
        $extensionIdentity = new ExtensionIdentity('a/a');
        $extensionVersion = new ExtensionVersion('v1');
        $implementation = new TrustedExtensionImplementation($extensionIdentity, $extensionVersion, 'Example\\A');
        $decision = static fn(
            ?ExtensionVersion $version = null,
            string $capability = 'read',
            int $capabilityVersion = 1,
            string $purpose = 'render',
            CapabilityRequirement $requirement = CapabilityRequirement::Required,
            string $parameter = 'reader',
            string $provider = 'Example\\Reader',
            CapabilityProviderLifetime $lifetime = CapabilityProviderLifetime::Application,
            bool $denied = false,
        ): CapabilityDecision => $denied
            ? CapabilityDecision::deny($extensionIdentity, $version ?? $extensionVersion, new CapabilityIdentity($capability, $capabilityVersion), new CapabilityPurpose($purpose), $requirement, $parameter, $provider)
            : CapabilityDecision::grant($extensionIdentity, $version ?? $extensionVersion, new CapabilityIdentity($capability, $capabilityVersion), new CapabilityPurpose($purpose), $requirement, $parameter, $provider, $lifetime);

        $plans = [
            new ExtensionGrantPlan($admission, [$implementation], [$decision()]),
            new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('b', 64)), [$implementation], [$decision()]),
            new ExtensionGrantPlan($admission, [new TrustedExtensionImplementation($extensionIdentity, $extensionVersion, 'Example\\B')], [$decision()]),
            new ExtensionGrantPlan($admission, [new TrustedExtensionImplementation($extensionIdentity, new ExtensionVersion('v2'), 'Example\\A')], [$decision()]),
            new ExtensionGrantPlan($admission, [$implementation], [$decision(version: new ExtensionVersion('v2'))]),
            new ExtensionGrantPlan($admission, [$implementation], [$decision(capability: 'write')]),
            new ExtensionGrantPlan($admission, [$implementation], [$decision(capabilityVersion: 2)]),
            new ExtensionGrantPlan($admission, [$implementation], [$decision(purpose: 'export')]),
            new ExtensionGrantPlan($admission, [$implementation], [$decision(requirement: CapabilityRequirement::Optional)]),
            new ExtensionGrantPlan($admission, [$implementation], [$decision(parameter: 'writer')]),
            new ExtensionGrantPlan($admission, [$implementation], [$decision(provider: 'Example\\Writer')]),
            new ExtensionGrantPlan($admission, [$implementation], [$decision(lifetime: CapabilityProviderLifetime::Operation)]),
            new ExtensionGrantPlan($admission, [$implementation], [$decision(requirement: CapabilityRequirement::Optional, denied: true)]),
        ];
        $identities = array_map(static fn(ExtensionGrantPlan $plan): string => $plan->identity()->value(), $plans);
        self::assertCount(count($identities), array_unique($identities));
    }

    public function testImplementationAndDecisionBoundsFailClosed(): void
    {
        $implementations = [];
        for ($index = 0; $index < 65; ++$index) {
            $implementations[] = new TrustedExtensionImplementation(new ExtensionIdentity(sprintf('v%02d/x', $index)), new ExtensionVersion('v1'), sprintf('Example\\Extension%02d', $index));
        }
        $implementation = new TrustedExtensionImplementation(new ExtensionIdentity('a/a'), new ExtensionVersion('v1'), 'Example\\A');
        $decision = CapabilityDecision::grant($implementation->identity(), $implementation->version(), new CapabilityIdentity('read', 1), new CapabilityPurpose('render'), CapabilityRequirement::Required, 'reader', 'Example\\Reader', CapabilityProviderLifetime::Application);
        $this->expectFailure(fn() => new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('a', 64)), $implementations, []));
        $this->expectFailure(fn() => new ExtensionGrantPlan(new AdmissionIdentity(str_repeat('a', 64)), [$implementation], array_fill(0, 4097, $decision)));
    }

    /** @param callable(): mixed $case */
    private function expectFailure(callable $case): void
    {
        try {
            $case();
            self::fail('Malformed plan was accepted.');
        } catch (InvalidExtensionGrantPlanException $exception) {
            self::assertSame('KGN-EXTENSION-COMPOSITION-1201', $exception->diagnosticId());
        }
    }
}
