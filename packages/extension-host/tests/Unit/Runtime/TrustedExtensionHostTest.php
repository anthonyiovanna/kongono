<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Tests\Unit\Runtime;

use Kongono\ExtensionAdmission\Admission\ExtensionAdmissionBuilder;
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
use Kongono\ExtensionHost\Runtime\ExtensionHostLoader;
use Kongono\ExtensionHost\Runtime\ExtensionHostState;
use Kongono\ExtensionHost\Runtime\TrustedExtensionHost;
use PHPUnit\Framework\TestCase;

interface HostRecorderPort
{
    public function add(string $event): void;
}
final class HostRecorder implements HostRecorderPort
{
    /** @var list<string> */
    public array $events = [];
    public function add(string $event): void
    {
        $this->events[] = $event;
    }
}
final class HostApplicationAlpha implements ApplicationExtensionInterface
{
    private HostRecorderPort $recorder;
    public function __construct(HostRecorderPort $recorder)
    {
        $this->recorder = $recorder;
    }
    public function start(): void
    {
        $this->recorder->add('start-alpha');
    }
    public function stop(): void
    {
        $this->recorder->add('stop-alpha');
    }
}
final class HostApplicationBeta implements ApplicationExtensionInterface
{
    private HostRecorderPort $recorder;
    public function __construct(HostRecorderPort $recorder)
    {
        $this->recorder = $recorder;
    }
    public function start(): void
    {
        $this->recorder->add('start-beta');
    }
    public function stop(): void
    {
        $this->recorder->add('stop-beta');
    }
}

final class TrustedExtensionHostTest extends TestCase
{
    public function testApplicationExtensionsStartCanonicallyAndShutdownOnceInReverseOrder(): void
    {
        $recorder = new HostRecorder();
        [$loaded, $directory] = self::loaded();
        try {
            $host = TrustedExtensionHost::start($loaded, $recorder, $recorder);
            self::assertSame(['start-alpha', 'start-beta'], $recorder->events);
            self::assertSame(ExtensionHostState::Active, $host->state());
            self::assertFalse($host->isRetired());
            self::assertFalse($host->isClosed());
            $host->shutdown();
            $host->shutdown();
            self::assertSame(['start-alpha', 'start-beta', 'stop-beta', 'stop-alpha'], $recorder->events);
            self::assertTrue($host->isClosed());
        } finally {
            self::remove($directory);
        }
    }

    /** @return array{\Kongono\ExtensionHost\Runtime\LoadedExtensionWiring, string} */
    private static function loaded(): array
    {
        $implementations = [HostApplicationAlpha::class, HostApplicationBeta::class];
        $declarations = [];
        $trusted = [];
        $decisions = [];
        foreach ($implementations as $index => $class) {
            $identity = new ExtensionIdentity('example/host-' . ($index + 1));
            $version = new ExtensionVersion('v1');
            $request = new CapabilityRequest(new CapabilityIdentity('recorder', 1), new CapabilityPurpose('lifecycle'), CapabilityRequirement::Required);
            $declaration = new ExtensionDeclaration(ContractVersion::Version1, $identity, $version, ExtensionCategory::TrustedInProcess, new ExtensionProvenance(new ExtensionSourceIdentity(str_repeat('a', 64)), str_repeat('b', 64)), ExtensionLifecycle::Application, [$request], []);
            $declarations[] = $declaration;
            $trusted[] = new TrustedExtensionImplementation($identity, $version, $class);
            $decisions[] = CapabilityDecision::grant($identity, $version, $request->identity(), $request->purpose(), $request->requirement(), 'recorder', HostRecorderPort::class, CapabilityProviderLifetime::Application);
        }
        $identity = (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity();
        $selections = array_map(static fn(ExtensionDeclaration $declaration): ExtensionSelection => new ExtensionSelection($declaration->identity(), $declaration->version()), $declarations);
        $admission = (new ExtensionAdmissionBuilder())->build($declarations, new ExtensionAdmissionPlan($identity, $selections, []), []);
        $plan = new ExtensionGrantPlan($admission->admissionIdentity(), $trusted, $decisions);
        $artifact = (new ExtensionWiringGenerator())->generate($declarations, $admission, $plan);
        $directory = sys_get_temp_dir() . '/kgn12f-host-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($directory));
        $directory = str_replace('\\', '/', (string) realpath($directory));
        $path = $directory . '/' . $artifact->wiringSha256() . '.php';
        self::assertSame(strlen($artifact->canonicalBytes()), file_put_contents($path, $artifact->canonicalBytes()));

        return [(new ExtensionHostLoader())->load($artifact, $plan, $path), $directory];
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
