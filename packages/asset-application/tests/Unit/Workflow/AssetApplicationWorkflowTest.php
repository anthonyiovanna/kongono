<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Tests\Unit\Workflow;

use Kongono\AssetAdapterContract\Adapter\AdapterBuildLimits;
use Kongono\AssetAdapterContract\Adapter\AdapterBuildRequest;
use Kongono\AssetAdapterContract\Adapter\AdapterBuildResult;
use Kongono\AssetAdapterContract\Adapter\AssetBuildAdapter;
use Kongono\AssetAdapterContract\AdapterContractVersion;
use Kongono\AssetAdapterContract\Artifact\AdapterBuildArtifactValidator;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use Kongono\AssetAdapterContract\Identity\ToolchainIdentity;
use Kongono\AssetAdapterContract\Output\AdapterOutputSet;
use Kongono\AssetApplication\Definition\AssetApplicationDefinition;
use Kongono\AssetApplication\Exception\AssetAdapterOperationException;
use Kongono\AssetApplication\Exception\AssetApplicationWorkflowException;
use Kongono\AssetApplication\Exception\AssetResolutionOperationException;
use Kongono\AssetApplication\Exception\ExceptionInterface;
use Kongono\AssetApplication\Internal\AssetApplicationPathGuard;
use Kongono\AssetApplication\Tests\Fixture\AssetApplicationProbe;
use Kongono\AssetApplication\Workflow\AssetApplicationWorkflow;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Browser\PublicAssetBaseUrl;
use Kongono\AssetResolution\Policy\AssetCachePolicy;
use Kongono\AssetResolution\Policy\AssetIntegrityPolicy;
use Kongono\AssetResolution\Policy\AssetPreloadPolicy;
use Kongono\AssetResolution\Policy\AssetResolutionPolicy;
use PHPUnit\Framework\TestCase;

final class AssetApplicationWorkflowTest extends TestCase
{
    public function testCallerSuppliedNeutralAdapterIsInvokedOnceAndRevalidated(): void
    {
        $source = new AssetSourceSet(new AssetDeclarationSet(ContractVersion::Version1, [], [], []), []);
        $profile = new AdapterProfileIdentity('vite/client.1');
        $toolchain = new ToolchainIdentity(str_repeat('a', 64));
        $limits = new AdapterBuildLimits(1, 0, 0, 1024, 0, 0, 0, 0);
        $request = new AdapterBuildRequest(AdapterContractVersion::Version1, $source, $profile, $toolchain, $limits);
        $output = new AdapterOutputSet($source, [], [], []);
        $artifact = (new AdapterBuildArtifactValidator())->create($source, $profile, $toolchain, 'kongono/asset-adapter-vite', $output);
        $adapter = new class (new AdapterBuildResult($artifact, $output, [])) implements AssetBuildAdapter {
            public int $calls = 0;
            public function __construct(private AdapterBuildResult $result) {}
            public function build(AdapterBuildRequest $request): AdapterBuildResult
            {
                ++$this->calls;
                return $this->result;
            }
        };

        $result = (new AssetApplicationWorkflow())->buildWithAdapter($adapter, $request);
        self::assertSame(1, $adapter->calls);
        self::assertSame(0, $result->outputCount());
        self::assertSame($profile->value(), $result->adapterProfile());
    }

    public function testAdapterMismatchAndForeignFailureFailClosedWithoutRetainedState(): void
    {
        $source = new AssetSourceSet(new AssetDeclarationSet(ContractVersion::Version1, [], [], []), []);
        $profile = new AdapterProfileIdentity('vite/client.1');
        $otherProfile = new AdapterProfileIdentity('vite/client.2');
        $toolchain = new ToolchainIdentity(str_repeat('a', 64));
        $limits = new AdapterBuildLimits(1, 0, 0, 1024, 0, 0, 0, 0);
        $request = new AdapterBuildRequest(AdapterContractVersion::Version1, $source, $profile, $toolchain, $limits);
        $output = new AdapterOutputSet($source, [], [], []);
        $artifact = (new AdapterBuildArtifactValidator())->create($source, $otherProfile, $toolchain, 'kongono/asset-adapter-vite', $output);
        $mismatch = new class (new AdapterBuildResult($artifact, $output, [])) implements AssetBuildAdapter {
            public function __construct(private AdapterBuildResult $result) {}
            public function build(AdapterBuildRequest $request): AdapterBuildResult
            {
                return $this->result;
            }
        };
        $workflow = new AssetApplicationWorkflow();
        try {
            $workflow->buildWithAdapter($mismatch, $request);
            self::fail('A mismatched adapter result must fail.');
        } catch (AssetAdapterOperationException $failure) {
            self::assertNull($failure->getPrevious());
        }

        $foreign = new \RuntimeException('private-adapter-value');
        $failing = new class ($foreign) implements AssetBuildAdapter {
            public function __construct(private \Throwable $failure) {}
            public function build(AdapterBuildRequest $request): AdapterBuildResult
            {
                throw $this->failure;
            }
        };
        try {
            $workflow->buildWithAdapter($failing, $request);
            self::fail('A foreign adapter failure must be normalized.');
        } catch (AssetAdapterOperationException $failure) {
            self::assertSame($foreign, $failure->getPrevious());
            self::assertStringNotContainsString('private-adapter-value', (string) $failure);
        }
    }

    public function testPlanBuildCheckAndResolveAreExactCurrent(): void
    {
        [$root, $definition] = AssetApplicationProbe::create();
        try {
            $workflow = new AssetApplicationWorkflow();
            $plan = $workflow->plan($definition);
            self::assertTrue($plan->publicationRequired());
            $entries = scandir($definition->outputDirectory());
            self::assertIsArray($entries);
            self::assertSame([], array_values(array_diff($entries, ['.', '..'])));

            $current = $workflow->build($definition);
            self::assertSame($plan->buildIdentity(), $current->resolutionArtifact()->buildIdentity());
            self::assertFalse($workflow->plan($definition)->publicationRequired());
            $beforeCheck = self::identities($definition->outputDirectory());
            self::assertSame($current->artifactBasename(), $workflow->check($definition)->artifactBasename());
            self::assertSame($beforeCheck, self::identities($definition->outputDirectory()));

            $resolved = $workflow->resolve(
                $definition,
                new LogicalAssetIdentity('app.style'),
                new AssetResolutionPolicy(new PublicAssetBaseUrl('https://example.test/assets/'), AssetCachePolicy::PublicImmutable, AssetIntegrityPolicy::RequireArtifactSha384, AssetPreloadPolicy::HonorArtifact),
            );
            self::assertSame('app.style', $resolved->primary()->logicalIdentity()?->value());
            self::assertStringStartsWith('https://example.test/assets/', $resolved->primary()->publicUrl()->value());

            file_put_contents($definition->outputDirectory() . '/' . $current->artifactBasename() . '/artifact.json', '{}');
            $this->expectException(AssetApplicationWorkflowException::class);
            $workflow->check($definition);
        } finally {
            AssetApplicationProbe::remove($root);
        }
    }

    public function testEmptySourceSetBuildsChecksAndHasNoFabricatedResolutionEntry(): void
    {
        $root = sys_get_temp_dir() . '/kongono-asset-empty-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root . '/var/assets', 0777, true));
        $source = $root . '/kongono.assets.json';
        self::assertIsInt(file_put_contents($source, "{\"schema\":\"kongono.asset-application-source\",\"version\":1,\"contract_version\":1,\"declarations\":[],\"dependencies\":[],\"entry_points\":[]}\n"));
        try {
            $definition = new AssetApplicationDefinition($root, $source, $root . '/var/assets');
            $workflow = new AssetApplicationWorkflow();
            self::assertSame(0, $workflow->plan($definition)->outputCount());
            $current = $workflow->build($definition);
            self::assertSame(0, $current->resolutionArtifact()->entryCount());
            self::assertSame($current->artifactBasename(), $workflow->check($definition)->artifactBasename());
            $policy = new AssetResolutionPolicy(new PublicAssetBaseUrl('/assets/'), AssetCachePolicy::NoStore, AssetIntegrityPolicy::Omit, AssetPreloadPolicy::Omit);
            $this->expectException(AssetResolutionOperationException::class);
            $workflow->resolve($definition, new LogicalAssetIdentity('missing.asset'), $policy);
        } finally {
            AssetApplicationProbe::remove($root);
        }
    }

    public function testMissingExtraTamperedAndStalePublicationsFailClosed(): void
    {
        foreach (['missing', 'extra', 'tampered', 'stale-source'] as $case) {
            [$root, $definition, $source] = AssetApplicationProbe::create();
            try {
                $workflow = new AssetApplicationWorkflow();
                $current = $workflow->build($definition);
                $target = $definition->outputDirectory() . '/' . $current->artifactBasename();
                match ($case) {
                    'missing' => unlink($target . '/artifact.json'),
                    'extra' => file_put_contents($target . '/extra.bin', 'extra'),
                    'tampered' => file_put_contents(self::firstOutput($target), 'tampered'),
                    'stale-source' => file_put_contents($source, str_replace('app.style', 'app.other', (string) file_get_contents($source))),
                };
                try {
                    $workflow->check($definition);
                    self::fail($case . ' publication must fail exact-current validation.');
                } catch (AssetApplicationWorkflowException $failure) {
                    self::assertSame('KGN-ASSET-APPLICATION-1942', $failure->diagnosticId());
                }
            } finally {
                AssetApplicationProbe::remove($root);
            }
        }
    }

    public function testOneWorkflowRemainsUsableAfterAResolutionFailure(): void
    {
        [$root, $definition] = AssetApplicationProbe::create();
        try {
            $workflow = new AssetApplicationWorkflow();
            $workflow->build($definition);
            $policy = new AssetResolutionPolicy(new PublicAssetBaseUrl('/assets/'), AssetCachePolicy::NoStore, AssetIntegrityPolicy::Omit, AssetPreloadPolicy::Omit);
            try {
                $workflow->resolve($definition, new LogicalAssetIdentity('missing.asset'), $policy);
                self::fail('Missing logical identity must fail.');
            } catch (AssetResolutionOperationException $failure) {
                self::assertSame('KGN-ASSET-APPLICATION-1944', $failure->diagnosticId());
            }
            self::assertSame('app.style', $workflow->resolve($definition, new LogicalAssetIdentity('app.style'), $policy)->primary()->logicalIdentity()?->value());
            self::assertSame($workflow->check($definition)->artifactBasename(), $workflow->check($definition)->artifactBasename());
        } finally {
            AssetApplicationProbe::remove($root);
        }
    }

    public function testDefinitionAndNamedSourcesRemainContainedOrdinaryAndBounded(): void
    {
        [$root, $definition, $source] = AssetApplicationProbe::create();
        $outsideSource = $root . '-outside.json';
        $outsideOutput = $root . '-outside-output';
        self::assertIsInt(file_put_contents($outsideSource, (string) file_get_contents($source)));
        self::assertTrue(mkdir($outsideOutput));
        try {
            $workflow = new AssetApplicationWorkflow();
            self::assertPackageFailure(static fn() => $workflow->plan(new AssetApplicationDefinition($root, $outsideSource, $definition->outputDirectory())));
            self::assertPackageFailure(static fn() => $workflow->plan(new AssetApplicationDefinition($root, $source, $outsideOutput)));

            self::assertIsInt(file_put_contents($source, "{}\r\n"));
            self::assertPackageFailure(static fn() => $workflow->plan($definition));

            $handle = fopen($source, 'wb');
            self::assertIsResource($handle);
            self::assertTrue(ftruncate($handle, AssetApplicationPathGuard::MAX_DEFINITION_BYTES + 1));
            fclose($handle);
            self::assertPackageFailure(static fn() => $workflow->plan($definition));

            self::assertIsInt(file_put_contents($source, "{\"schema\":\"kongono.asset-application-source\",\"version\":1,\"contract_version\":1,\"declarations\":[{\"identity\":\"app.style\",\"kind\":\"style\",\"content_type\":\"text/css\",\"source_path\":\"app.css\"}],\"dependencies\":[],\"entry_points\":[\"app.style\"]}\n"));
            $handle = fopen($root . '/app.css', 'wb');
            self::assertIsResource($handle);
            self::assertTrue(ftruncate($handle, AssetApplicationPathGuard::MAX_SOURCE_BYTES + 1));
            fclose($handle);
            self::assertPackageFailure(static fn() => $workflow->plan($definition));

            self::assertTrue(mkdir($root . '/source-directory'));
            self::assertIsInt(file_put_contents($source, str_replace('app.css', 'source-directory', (string) file_get_contents($source))));
            self::assertPackageFailure(static fn() => $workflow->plan($definition));
        } finally {
            AssetApplicationProbe::remove($root);
            unlink($outsideSource);
            rmdir($outsideOutput);
        }
    }

    private static function assertPackageFailure(callable $operation): void
    {
        try {
            $operation();
            self::fail('Unsafe application evidence must fail closed.');
        } catch (ExceptionInterface $failure) {
            self::assertStringStartsWith('KGN-ASSET-APPLICATION-', $failure->diagnosticId());
        }
    }

    /** @return array<string,string> */
    private static function identities(string $root): array
    {
        $identities = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $entry) {
            self::assertInstanceOf(\SplFileInfo::class, $entry);
            if ($entry->isFile()) {
                $path = str_replace('\\', '/', $entry->getPathname());
                $identities[substr($path, strlen($root) + 1)] = (string) hash_file('sha256', $entry->getPathname());
            }
        }
        ksort($identities, SORT_STRING);

        return $identities;
    }

    private static function firstOutput(string $target): string
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS)) as $entry) {
            self::assertInstanceOf(\SplFileInfo::class, $entry);
            if ($entry->isFile() && 'artifact.json' !== $entry->getFilename()) {
                return $entry->getPathname();
            }
        }
        self::fail('A native output object must exist.');
    }
}
