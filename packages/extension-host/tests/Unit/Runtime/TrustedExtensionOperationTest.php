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
use Kongono\ExtensionHost\Contract\OperationExtensionInterface;
use Kongono\ExtensionHost\Exception\ExtensionHostUnavailableException;
use Kongono\ExtensionHost\Runtime\ExtensionHostLoader;
use Kongono\ExtensionHost\Runtime\ExtensionHostState;
use Kongono\ExtensionHost\Runtime\TrustedExtensionHost;
use PHPUnit\Framework\TestCase;
use RuntimeException;

interface OperationRecorderPort
{
    public function add(string $event): void;
}
final class OperationRecorder implements OperationRecorderPort
{
    /** @var list<string> */
    public array $events = [];
    public function add(string $event): void
    {
        $this->events[] = $event;
    }
}
final class OperationAlpha implements OperationExtensionInterface
{
    private OperationRecorderPort $recorder;
    public function __construct(OperationRecorderPort $recorder)
    {
        $this->recorder = $recorder;
    }
    public function begin(): void
    {
        $this->recorder->add('begin-alpha');
    }
    public function close(): void
    {
        $this->recorder->add('close-alpha');
    }
}
final class OperationBeta implements OperationExtensionInterface
{
    private OperationRecorderPort $recorder;
    public function __construct(OperationRecorderPort $recorder)
    {
        $this->recorder = $recorder;
    }
    public function begin(): void
    {
        $this->recorder->add('begin-beta');
    }
    public function close(): void
    {
        $this->recorder->add('close-beta');
    }
}

final class TrustedExtensionOperationTest extends TestCase
{
    public function testOperationsAreFreshReverseClosedIdempotentAndBlockShutdownWhileLeased(): void
    {
        $recorder = new OperationRecorder();
        [$loaded, $directory] = self::loaded();
        try {
            $host = TrustedExtensionHost::start($loaded, $recorder, $recorder);
            $first = $host->beginOperation();
            self::assertSame(['begin-alpha', 'begin-beta'], $recorder->events);
            $primary = new RuntimeException('work-primary');
            try {
                $first->close($primary);
                self::fail('Primary failure was not preserved.');
            } catch (RuntimeException $failure) {
                self::assertSame($primary, $failure);
            }
            $first->close();
            self::assertSame(['begin-alpha', 'begin-beta', 'close-beta', 'close-alpha'], $recorder->events);
            self::assertSame(ExtensionHostState::Active, $host->state());

            $second = $host->beginOperation();
            try {
                $host->shutdown();
                self::fail('Shutdown accepted an outstanding operation.');
            } catch (ExtensionHostUnavailableException $failure) {
                self::assertSame(ExtensionHostState::Active, $host->state());
            }
            $second->close();
            $host->shutdown();
            self::assertTrue($host->isClosed());
        } finally {
            self::remove($directory);
        }
    }

    /** @return array{\Kongono\ExtensionHost\Runtime\LoadedExtensionWiring, string} */
    private static function loaded(): array
    {
        $implementations = [OperationAlpha::class, OperationBeta::class];
        $declarations = [];
        $trusted = [];
        $decisions = [];
        foreach ($implementations as $index => $class) {
            $identity = new ExtensionIdentity('example/operation-' . ($index + 1));
            $version = new ExtensionVersion('v1');
            $request = new CapabilityRequest(new CapabilityIdentity('recorder', 1), new CapabilityPurpose('operation'), CapabilityRequirement::Required);
            $declaration = new ExtensionDeclaration(ContractVersion::Version1, $identity, $version, ExtensionCategory::TrustedInProcess, new ExtensionProvenance(new ExtensionSourceIdentity(str_repeat('c', 64)), str_repeat('d', 64)), ExtensionLifecycle::Operation, [$request], []);
            $declarations[] = $declaration;
            $trusted[] = new TrustedExtensionImplementation($identity, $version, $class);
            $decisions[] = CapabilityDecision::grant($identity, $version, $request->identity(), $request->purpose(), $request->requirement(), 'recorder', OperationRecorderPort::class, CapabilityProviderLifetime::Application);
        }
        $identity = (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity();
        $selections = array_map(static fn(ExtensionDeclaration $declaration): ExtensionSelection => new ExtensionSelection($declaration->identity(), $declaration->version()), $declarations);
        $admission = (new ExtensionAdmissionBuilder())->build($declarations, new ExtensionAdmissionPlan($identity, $selections, []), []);
        $plan = new ExtensionGrantPlan($admission->admissionIdentity(), $trusted, $decisions);
        $artifact = (new ExtensionWiringGenerator())->generate($declarations, $admission, $plan);
        $directory = sys_get_temp_dir() . '/kgn12f-operation-' . bin2hex(random_bytes(4));
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
