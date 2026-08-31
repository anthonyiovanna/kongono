<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Tests\Unit\Generated;

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
use PHPUnit\Framework\TestCase;

interface CatalogProvider {}
interface OperationProvider {}
interface MetricsProvider {}
final class NullMetricsProvider implements MetricsProvider {}
final class ApplicationExtension
{
    public CatalogProvider $catalog;
    public function __construct(CatalogProvider $catalog)
    {
        $this->catalog = $catalog;
    }
}
final class OperationExtension
{
    public CatalogProvider $catalog;
    public OperationProvider $operation;
    public MetricsProvider $metrics;
    public function __construct(
        CatalogProvider $catalog,
        OperationProvider $operation,
        MetricsProvider $metrics = new NullMetricsProvider(),
    ) {
        $this->catalog = $catalog;
        $this->operation = $operation;
        $this->metrics = $metrics;
    }
}

final class ExtensionWiringGeneratorTest extends TestCase
{
    public function testEmptyInputIsDeterministicAndProducesTwoInertClasses(): void
    {
        [$declarations, $admission, $plan] = self::emptyScenario();
        $generator = new ExtensionWiringGenerator();
        $first = $generator->generate($declarations, $admission, $plan);
        $second = $generator->generate($declarations, $admission, $plan);
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->wiringSha256(), $second->wiringSha256());
        self::assertSame(0, $first->implementationCount());
        self::assertSame(0, $first->decisionCount());
        self::assertSame(2, substr_count($first->canonicalBytes(), 'final class '));
        self::assertStringNotContainsString('Reflection', $first->canonicalBytes());
    }

    public function testTypedApplicationAndOperationEdgesAreReadableExplicitAndDefaultDenialIsOmitted(): void
    {
        [$declarations, $admission] = self::admittedScenario();
        $application = $declarations[0];
        $operation = $declarations[1];
        $plan = new ExtensionGrantPlan($admission->admissionIdentity(), [
            new TrustedExtensionImplementation($application->identity(), $application->version(), ApplicationExtension::class),
            new TrustedExtensionImplementation($operation->identity(), $operation->version(), OperationExtension::class),
        ], [
            self::grant($application, $application->capabilityRequests()[0], 'catalog', CatalogProvider::class, CapabilityProviderLifetime::Application),
            self::grant($operation, $operation->capabilityRequests()[0], 'catalog', CatalogProvider::class, CapabilityProviderLifetime::Application),
            CapabilityDecision::deny($operation->identity(), $operation->version(), $operation->capabilityRequests()[1]->identity(), $operation->capabilityRequests()[1]->purpose(), $operation->capabilityRequests()[1]->requirement(), 'metrics', MetricsProvider::class),
            self::grant($operation, $operation->capabilityRequests()[2], 'operation', OperationProvider::class, CapabilityProviderLifetime::Operation),
        ]);
        $artifact = (new ExtensionWiringGenerator())->generate($declarations, $admission, $plan);
        $bytes = $artifact->canonicalBytes();
        self::assertStringContainsString('\\' . CatalogProvider::class . ' $applicationProvider1', $bytes);
        self::assertStringContainsString('\\' . OperationProvider::class . ' $operationProvider1', $bytes);
        self::assertStringContainsString('catalog: $applicationProvider1', $bytes);
        self::assertStringContainsString('operation: $operationProvider1', $bytes);
        self::assertStringNotContainsString('metrics:', $bytes);
        self::assertStringContainsString('applicationExtension1(): \\' . ApplicationExtension::class, $bytes);
        self::assertStringContainsString('operationExtension1(): \\' . OperationExtension::class, $bytes);
        self::assertSame(2, $artifact->applicationProviderCount());
        self::assertSame(1, $artifact->operationProviderCount());
        self::assertSame(4, $artifact->decisionCount());
        self::assertNotEmpty(token_get_all($bytes, TOKEN_PARSE));
    }

    public function testGeneratedBytesPassPhpLintWithoutLoadingOrExecutingThem(): void
    {
        [$declarations, $admission, $plan] = self::emptyScenario();
        $artifact = (new ExtensionWiringGenerator())->generate($declarations, $admission, $plan);
        self::assertFalse(class_exists($artifact->applicationClass(), false));
        self::assertFalse(class_exists($artifact->operationClass(), false));

        $path = tempnam(sys_get_temp_dir(), 'kgn12e-lint-');
        self::assertIsString($path);
        try {
            self::assertSame(strlen($artifact->canonicalBytes()), file_put_contents($path, $artifact->canonicalBytes()));
            $pipes = [];
            $process = proc_open([PHP_BINARY, '-l', $path], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($process)) {
                self::fail('PHP lint process could not start.');
            }
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), (string) $stderr . (string) $stdout);
        } finally {
            if (is_file($path)) {
                self::assertTrue(unlink($path));
            }
        }

        self::assertFalse(class_exists($artifact->applicationClass(), false));
        self::assertFalse(class_exists($artifact->operationClass(), false));
    }

    /** @return array{list<ExtensionDeclaration>, \Kongono\ExtensionAdmission\Admission\AdmittedExtensionGraphInput, ExtensionGrantPlan} */
    private static function emptyScenario(): array
    {
        $declarations = [];
        $identity = (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity();
        $admission = (new ExtensionAdmissionBuilder())->build($declarations, new ExtensionAdmissionPlan($identity, [], []), []);
        return [$declarations, $admission, new ExtensionGrantPlan($admission->admissionIdentity(), [], [])];
    }

    /** @return array{list<ExtensionDeclaration>, \Kongono\ExtensionAdmission\Admission\AdmittedExtensionGraphInput} */
    private static function admittedScenario(): array
    {
        $declarations = [
            self::declaration('example/application', ExtensionLifecycle::Application, [self::request('catalog', CapabilityRequirement::Required)]),
            self::declaration('example/operation', ExtensionLifecycle::Operation, [self::request('catalog', CapabilityRequirement::Required), self::request('metrics', CapabilityRequirement::Optional), self::request('operation', CapabilityRequirement::Required)]),
        ];
        $identity = (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity();
        $selections = array_map(static fn(ExtensionDeclaration $declaration): ExtensionSelection => new ExtensionSelection($declaration->identity(), $declaration->version()), $declarations);
        $admission = (new ExtensionAdmissionBuilder())->build($declarations, new ExtensionAdmissionPlan($identity, $selections, []), []);
        return [$declarations, $admission];
    }

    /** @param list<CapabilityRequest> $requests */
    private static function declaration(string $identity, ExtensionLifecycle $lifecycle, array $requests): ExtensionDeclaration
    {
        return new ExtensionDeclaration(ContractVersion::Version1, new ExtensionIdentity($identity), new ExtensionVersion('v1'), ExtensionCategory::TrustedInProcess, new ExtensionProvenance(new ExtensionSourceIdentity(str_repeat('a', 64)), str_repeat('b', 64)), $lifecycle, $requests, []);
    }

    private static function request(string $identity, CapabilityRequirement $requirement): CapabilityRequest
    {
        return new CapabilityRequest(new CapabilityIdentity($identity, 1), new CapabilityPurpose('purpose_' . $identity), $requirement);
    }

    private static function grant(ExtensionDeclaration $declaration, CapabilityRequest $request, string $parameter, string $provider, CapabilityProviderLifetime $lifetime): CapabilityDecision
    {
        return CapabilityDecision::grant($declaration->identity(), $declaration->version(), $request->identity(), $request->purpose(), $request->requirement(), $parameter, $provider, $lifetime);
    }
}
