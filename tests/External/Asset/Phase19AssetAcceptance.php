<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Asset;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\AssetAdapterContract\Adapter\AdapterBuildLimits;
use Kongono\AssetAdapterContract\Adapter\AdapterBuildRequest;
use Kongono\AssetAdapterContract\AdapterContractVersion;
use Kongono\AssetAdapterVite\Process\SystemBuildProcessRunner;
use Kongono\AssetAdapterVite\Vite\ViteAdapterProfile;
use Kongono\AssetAdapterVite\Vite\ViteAssetBuildAdapter;
use Kongono\AssetAdapterVite\Vite\ViteToolchain;
use Kongono\AssetApplication\Definition\AssetApplicationDefinition;
use Kongono\AssetApplication\Summary\ResolvedAssetSummary;
use Kongono\AssetApplication\Workflow\AssetApplicationWorkflow;
use Kongono\AssetApplication\Workflow\CurrentAssetApplicationBuild;
use Kongono\AssetBuild\Source\AssetSource;
use Kongono\AssetBuild\Source\AssetSourcePath;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\EntryPoint\AssetEntryPoint;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetInspection\Derivation\AssetIntelligenceDeriver;
use Kongono\AssetInspection\Graph\AssetGraphEvidence;
use Kongono\AssetInspection\Inspection\AssetInspectionRequest;
use Kongono\AssetInspection\Inspection\AssetInspector;
use Kongono\AssetInspection\Projection\AssetObservationProjector;
use Kongono\AssetInspection\Projection\AssetSecurityAuditProjector;
use Kongono\AssetInspection\Role\AssetInspectionDeveloperRole;
use Kongono\AssetInspection\Verification\AssetIntelligenceVerdict;
use Kongono\AssetInspection\Verification\AssetIntelligenceVerifier;
use Kongono\AssetResolution\Browser\PublicAssetBaseUrl;
use Kongono\AssetResolution\Identity\AssetResolutionArtifactIdentity;
use Kongono\AssetResolution\Policy\AssetCachePolicy;
use Kongono\AssetResolution\Policy\AssetIntegrityPolicy;
use Kongono\AssetResolution\Policy\AssetPreloadPolicy;
use Kongono\AssetResolution\Policy\AssetResolutionPolicy;
use Kongono\AssetResolution\Resolution\ExactCurrentAssetResolver;
use Kongono\AssetResolution\Resolution\ResolvedAssetSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Schema\PresentationValueSchema;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewContract\Value\ViewContext;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

final class Phase19AssetAcceptance
{
    private const int STREAM_LIMIT = 1_048_576;
    private const int PROCESS_TIMEOUT_SECONDS = 180;
    private const string FAILURE = "Kongono Phase 19 external acceptance failed.\n";
    private int $caseCount = 0;

    public function __construct(private readonly string $repositoryRoot) {}

    /**
     * @param list<string> $arguments
     * @param resource     $stdout
     * @param resource     $stderr
     */
    public function run(array $arguments, $stdout, $stderr): int
    {
        try {
            if (['--child-success'] === $arguments) {
                return $this->successChild($stdout);
            }
            if (['--child-failure'] === $arguments) {
                $workspace = new TemporaryAssetWorkspace($this->repositoryRoot);
                try {
                    throw new RuntimeException(self::privateLiteral());
                } finally {
                    $workspace->cleanup();
                }
            }
            if (['--child-malformed'] === $arguments) {
                fwrite($stdout, "{}\n");

                return 0;
            }
            if (['--child-overflow-stdout'] === $arguments) {
                fwrite($stdout, str_repeat('x', self::STREAM_LIMIT + 1));

                return 0;
            }
            if (['--child-overflow-stderr'] === $arguments) {
                fwrite($stderr, str_repeat('x', self::STREAM_LIMIT + 1));

                return 1;
            }
            if (['--child-timeout'] === $arguments) {
                sleep(5);

                return 0;
            }
            if ([] !== $arguments) {
                throw new RuntimeException('phase19_mode_failed');
            }

            $before = $this->repositoryFingerprint();
            $residue = $this->temporaryResidue();
            $single = $this->child('--child-success');
            $failure = $this->child('--child-failure');
            $later = $this->child('--child-success');
            $sequential = $this->child('--child-success');
            [$concurrentA, $concurrentB] = $this->childPair();
            $malformed = $this->child('--child-malformed');
            $this->require($this->fails(fn() => $this->child('--child-overflow-stdout')));
            $this->require($this->fails(fn() => $this->child('--child-overflow-stderr')));
            $this->require($this->fails(fn() => $this->child('--child-timeout', 1)));
            $this->require(0 === $single->exitCode && '' === $single->stderr && $this->isCanonicalSuccess($single->stdout));
            $this->require(0 !== $failure->exitCode && '' === $failure->stdout && self::FAILURE === $failure->stderr);
            $this->require(0 === $later->exitCode && $single->stdout === $later->stdout && '' === $later->stderr);
            $this->require(0 === $sequential->exitCode && $single->stdout === $sequential->stdout && '' === $sequential->stderr);
            $this->require(0 === $concurrentA->exitCode && 0 === $concurrentB->exitCode);
            $this->require($single->stdout === $concurrentA->stdout && $concurrentA->stdout === $concurrentB->stdout);
            $this->require('' === $concurrentA->stderr && '' === $concurrentB->stderr);
            $this->require(0 === $malformed->exitCode && !$this->isCanonicalSuccess($malformed->stdout));
            $this->require(!str_contains($failure->stderr, self::privateLiteral()));
            $this->require($before === $this->repositoryFingerprint());
            $this->require($residue === $this->temporaryResidue());
            fwrite($stdout, $single->stdout);

            return 0;
        } catch (Throwable) {
            fwrite($stderr, self::FAILURE);

            return 1;
        }
    }

    /** @param resource $stdout */
    private function successChild($stdout): int
    {
        $workspace = new TemporaryAssetWorkspace($this->repositoryRoot);
        try {
            [$nativeResolved, $nativeCurrent] = $this->directStack($workspace);
            $this->adapterStack($workspace, $nativeResolved);
            $this->inspect($nativeCurrent);
            $this->skeletonStack($workspace);
            $this->case($this->caseCount > 0);
            $bytes = self::successBytes($this->caseCount);
            $this->case(!str_contains($bytes, self::privateLiteral()));
        } finally {
            $workspace->cleanup();
        }
        fwrite($stdout, self::successBytes($this->caseCount));

        return 0;
    }

    /** @return array{ResolvedAssetSet,CurrentAssetApplicationBuild} */
    private function directStack(TemporaryAssetWorkspace $workspace): array
    {
        $applicationRoot = $workspace->directory('application');
        $workspace->directory('application/assets');
        $outputRoot = $workspace->directory('application/var/assets');
        $workspace->write('application/assets/app.js', "import './lazy.js';\nconsole.log('app');\n");
        $workspace->write('application/assets/lazy.js', "console.log('lazy');\n");
        $workspace->write('application/assets/app.css', "body { color: #123456; }\n");
        $sourcePath = $workspace->write('application/kongono.assets.json', self::nativeSourceBytes());
        $definition = new AssetApplicationDefinition($applicationRoot, $sourcePath, $outputRoot);
        $workflow = new AssetApplicationWorkflow();

        $emptyIdentity = $this->directoryIdentity($outputRoot);
        $firstPlan = $workflow->plan($definition);
        $secondPlan = $workflow->plan($definition);
        $this->case($firstPlan->buildIdentity() === $secondPlan->buildIdentity());
        $this->case($firstPlan->sourceSetIdentity() === $secondPlan->sourceSetIdentity());
        $this->case($firstPlan->graphIdentity() === $secondPlan->graphIdentity());
        $this->case($firstPlan->resolutionIdentity() === $secondPlan->resolutionIdentity());
        $this->case($firstPlan->publicationRequired() && $secondPlan->publicationRequired());
        $this->case($emptyIdentity === $this->directoryIdentity($outputRoot));

        $current = $workflow->build($definition);
        $publishedIdentity = $this->directoryIdentity($outputRoot);
        $again = $workflow->build($definition);
        $this->case($current->resolutionArtifact()->bytes() === $again->resolutionArtifact()->bytes());
        $this->case($current->artifactBasename() === $again->artifactBasename());
        $this->case($publishedIdentity === $this->directoryIdentity($outputRoot));
        $checked = $workflow->check($definition);
        $this->case($current->resolutionArtifact()->bytes() === $checked->resolutionArtifact()->bytes());
        $this->case($publishedIdentity === $this->directoryIdentity($outputRoot));
        $this->case(!$workflow->plan($definition)->publicationRequired());
        $this->case(3 === $current->declarationCount() && 3 === $current->dependencyCount());
        $this->case(1 === $current->entryPointCount() && 3 === $current->outputCount());

        $identity = new LogicalAssetIdentity('app.script');
        $minimal = $workflow->resolve($definition, $identity, new AssetResolutionPolicy(
            new PublicAssetBaseUrl('/assets/'),
            AssetCachePolicy::NoStore,
            AssetIntegrityPolicy::Omit,
            AssetPreloadPolicy::Omit,
        ));
        $explicit = $workflow->resolve($definition, $identity, new AssetResolutionPolicy(
            new PublicAssetBaseUrl('https://cdn.example.test/static/'),
            AssetCachePolicy::PublicImmutable,
            AssetIntegrityPolicy::RequireArtifactSha384,
            AssetPreloadPolicy::HonorArtifact,
        ));
        $this->case('app.script' === $minimal->primary()->logicalIdentity()?->value());
        $this->case(str_starts_with($minimal->primary()->publicUrl()->value(), '/assets/objects/sha256/'));
        $this->case('no-store' === $minimal->primary()->httpMetadata()->cacheControl());
        $this->case(null === $minimal->primary()->browserIntegrity());
        $this->case('none' === $minimal->primary()->preloadHint()->value);
        $this->case(str_starts_with($explicit->primary()->publicUrl()->value(), 'https://cdn.example.test/static/objects/sha256/'));
        $this->case('public, max-age=31536000, immutable' === $explicit->primary()->httpMetadata()->cacheControl());
        $this->case(str_starts_with((string) $explicit->primary()->browserIntegrity()?->value(), 'sha384-'));
        $this->case(2 === count($explicit->dependencies()));
        $this->case($explicit->primary()->contentIdentity()->value() !== $identity->value());
        $this->case($explicit->primary()->publicUrl()->value() !== $sourcePath);
        $this->case($current->artifactBasename() !== $explicit->primary()->contentIdentity()->value());

        $stale = new AssetResolutionArtifactIdentity(str_repeat('f', 64));
        $this->case($this->fails(fn() => (new ExactCurrentAssetResolver())->resolve($current->resolutionArtifact(), $stale, $identity, new AssetResolutionPolicy(new PublicAssetBaseUrl('/assets/'), AssetCachePolicy::NoStore, AssetIntegrityPolicy::Omit, AssetPreloadPolicy::Omit))));
        $this->nativeFailures($workspace, $definition, $current);
        $healthy = $workflow->resolve($definition, $identity, new AssetResolutionPolicy(new PublicAssetBaseUrl('/assets/'), AssetCachePolicy::PrivateRevalidate, AssetIntegrityPolicy::RequireArtifactSha384, AssetPreloadPolicy::HonorArtifact));
        $this->case('private, no-cache' === $healthy->primary()->httpMetadata()->cacheControl());
        $this->viewMapping($explicit);

        return [$explicit, $current];
    }

    private function nativeFailures(TemporaryAssetWorkspace $workspace, AssetApplicationDefinition $definition, CurrentAssetApplicationBuild $current): void
    {
        $workflow = new AssetApplicationWorkflow();
        $target = $definition->outputDirectory() . '/' . $current->artifactBasename();
        $metadataPath = $target . '/artifact.json';
        $metadata = (string) file_get_contents($metadataPath);
        $objects = $this->ordinaryFiles($target, 'artifact.json');
        $objectPath = $objects[0];
        $objectBytes = (string) file_get_contents($objectPath);

        $this->withRestoredFile($metadataPath, $metadata, '', fn() => $workflow->check($definition));
        $this->withRestoredFile($metadataPath, $metadata, '{}', fn() => $workflow->check($definition));
        $this->withRestoredFile($objectPath, $objectBytes, 'changed', fn() => $workflow->check($definition));
        $this->case(unlink($objectPath));
        try {
            $this->case($this->fails(fn() => $workflow->check($definition)));
        } finally {
            $this->case(strlen($objectBytes) === file_put_contents($objectPath, $objectBytes));
        }

        $extra = $target . '/additional.bin';
        $this->case(5 === file_put_contents($extra, 'extra'));
        try {
            $this->case($this->fails(fn() => $workflow->check($definition)));
        } finally {
            $this->case(unlink($extra));
        }

        $sourcePath = $definition->sourceFile();
        $source = (string) file_get_contents($sourcePath);
        $this->case(strlen(self::privateLiteral()) === file_put_contents($sourcePath, self::privateLiteral()));
        try {
            $this->case($this->fails(fn() => $workflow->check($definition)));
        } finally {
            $this->case(strlen($source) === file_put_contents($sourcePath, $source));
        }

        foreach (['', '{}', '{', str_replace('"version":1', '"version":2', self::nativeSourceBytes()), str_replace('"entry_points":["app.script"]', '"entry_points":[]', self::nativeSourceBytes())] as $bad) {
            $this->case(strlen($bad) === file_put_contents($sourcePath, $bad));
            try {
                $this->case($this->fails(fn() => $workflow->plan($definition)));
            } finally {
                $this->case(strlen($source) === file_put_contents($sourcePath, $source));
            }
        }

        $conflictRoot = $workspace->directory('application/conflict');
        $conflictDefinition = new AssetApplicationDefinition($definition->applicationRoot(), $sourcePath, $conflictRoot);
        $plan = $workflow->plan($conflictDefinition);
        $conflict = $workspace->directory('application/conflict/' . $plan->artifactBasename());
        $workspace->write('application/conflict/' . $plan->artifactBasename() . '/artifact.json', $metadata);
        $workspace->write('application/conflict/' . $plan->artifactBasename() . '/conflict.bin', 'conflict');
        $this->case($this->fails(fn() => $workflow->build($conflictDefinition)));
        $this->case('conflict' === file_get_contents($conflict . '/conflict.bin'));
        $this->case($current->artifactBasename() === $workflow->check($definition)->artifactBasename());
    }

    private function adapterStack(TemporaryAssetWorkspace $workspace, ResolvedAssetSet $native): void
    {
        $parent = $workspace->directory('adapter');
        $emulator = $workspace->write('vite-emulator.php', self::emulatorBytes());
        $php = str_replace('\\', '/', (string) realpath(PHP_BINARY));
        $emulator = str_replace('\\', '/', (string) realpath($emulator));
        $phpHash = hash_file('sha256', $php);
        $emulatorHash = hash_file('sha256', $emulator);
        if (!is_string($phpHash) || !is_string($emulatorHash)) {
            throw new RuntimeException('phase19_adapter_failed');
        }
        $toolchain = new ViteToolchain($php, $emulator, PHP_VERSION, '1.0.0-test', $phpHash, $emulatorHash);
        $source = $this->adapterSource('SUCCESS');
        $request = $this->adapterRequest($source, $toolchain, 3, 1024, 1024);
        $adapter = new ViteAssetBuildAdapter($parent, $toolchain, new SystemBuildProcessRunner());
        $workflow = new AssetApplicationWorkflow();
        $first = $workflow->buildWithAdapter($adapter, $request);
        $second = $workflow->buildWithAdapter($adapter, $request);
        $this->case($first->resolutionArtifact()->bytes() === $second->resolutionArtifact()->bytes());
        $this->case($first->adapterProfile() === ViteAdapterProfile::ClientEs2022V1->value);
        $this->case(1 === $first->entryCount() && 0 === $first->dependencyCount() && 1 === $first->outputCount());
        $this->case([] === glob($parent . '/.kongono-vite-*', GLOB_NOSORT));

        $resolved = (new ExactCurrentAssetResolver())->resolve(
            $first->resolutionArtifact(),
            $first->resolutionArtifact()->identity(),
            new LogicalAssetIdentity('main'),
            new AssetResolutionPolicy(new PublicAssetBaseUrl('/adapter/'), AssetCachePolicy::NoStore, AssetIntegrityPolicy::RequireArtifactSha384, AssetPreloadPolicy::HonorArtifact),
        );
        $this->case('adapter' === $first->resolutionArtifact()->producerKind());
        $this->case('main' === $resolved->primary()->logicalIdentity()?->value());
        $this->case(AssetKind::Script === $resolved->primary()->kind());
        $this->case(AssetContentType::TextJavascript === $resolved->primary()->contentType());
        $this->case(str_starts_with($resolved->primary()->publicUrl()->value(), '/adapter/objects/sha256/'));
        $this->case($native->primary()->kind() === $resolved->primary()->kind());
        $this->case($native->primary()->contentType() === $resolved->primary()->contentType());
        $this->case($native->primary()->contentIdentity()->value() !== $resolved->primary()->contentIdentity()->value());

        foreach (['NONZERO', 'MALFORMED', 'DUPLICATE', 'PARTIAL', 'EXTRA', 'OUTSIDE', 'LINK'] as $mode) {
            $bad = $this->adapterRequest($this->adapterSource($mode), $toolchain, 3, 1024, 1024);
            $this->case($this->fails(fn() => $workflow->buildWithAdapter($adapter, $bad)));
            $this->case([] === glob($parent . '/.kongono-vite-*', GLOB_NOSORT));
        }
        foreach ([['STDOUT', 32, 1024, 3], ['STDERR', 1024, 32, 3], ['TIMEOUT', 1024, 1024, 1]] as [$mode, $stdout, $stderr, $timeout]) {
            $bad = $this->adapterRequest($this->adapterSource($mode), $toolchain, $timeout, $stdout, $stderr);
            $this->case($this->fails(fn() => $workflow->buildWithAdapter($adapter, $bad)));
            $this->case([] === glob($parent . '/.kongono-vite-*', GLOB_NOSORT));
        }

        $changed = new ViteToolchain($php, $emulator, PHP_VERSION, '1.0.0-test', $phpHash, str_repeat('0', 64));
        $this->case($this->fails(fn() => $workflow->buildWithAdapter(new ViteAssetBuildAdapter($parent, $changed, new SystemBuildProcessRunner()), $this->adapterRequest($source, $changed, 3, 1024, 1024))));
        $notExecutable = $workspace->write('not-executable.txt', 'ordinary but not executable');
        $notExecutable = str_replace('\\', '/', (string) realpath($notExecutable));
        $notHash = hash_file('sha256', $notExecutable);
        if (!is_string($notHash)) {
            throw new RuntimeException('phase19_adapter_failed');
        }
        $start = new ViteToolchain($notExecutable, $emulator, '1.0.0-test', '1.0.0-test', $notHash, $emulatorHash);
        $this->case($this->fails(fn() => $workflow->buildWithAdapter(new ViteAssetBuildAdapter($parent, $start, new SystemBuildProcessRunner()), $this->adapterRequest($source, $start, 3, 1024, 1024))));
        $healthy = $workflow->buildWithAdapter($adapter, $request);
        $this->case($healthy->resolutionArtifact()->bytes() === $first->resolutionArtifact()->bytes());
        $this->case([] === glob($parent . '/.kongono-vite-*', GLOB_NOSORT));

        $adapterCurrent = new CurrentAssetApplicationBuild($first->resolutionArtifact(), 'adapter-evidence-only', 1, 0, 1, 1);
        $this->inspect($adapterCurrent);
    }

    private function inspect(CurrentAssetApplicationBuild $current): void
    {
        $request = new AssetInspectionRequest($current);
        $inspection = (new AssetInspector())->inspect($request);
        $again = (new AssetInspector())->inspect($request);
        $this->case($inspection->canonicalBytes() === $again->canonicalBytes());
        $this->case($inspection->flag('structurally_validated'));
        $this->case($inspection->flag('supplied_current_evidence'));
        $this->case(!$inspection->flag('filesystem_read'));
        foreach ([self::privateLiteral(), 'app.script', 'assets/app.js', 'https://cdn.example.test/'] as $omitted) {
            $this->case(!str_contains($inspection->canonicalBytes(), $omitted));
        }

        $context = ObservabilityContext::uncorrelated();
        foreach (AssetInspectionDeveloperRole::cases() as $role) {
            $graphRole = GraphRole::from($role->value);
            $graph = new ApplicationGraphIntelligenceView(
                $graphRole,
                str_repeat('a', 64),
                new GraphIdentity(str_repeat('b', 64)),
                GraphContractVersion::Version1,
                10,
                6,
                ['composition.input' => 1, 'composition.service' => 4, 'composition.contract' => 3, 'composition.root' => 2],
                ['composition.depends_on' => 2, 'composition.consumes_input' => 1, 'composition.implements_contract' => 2, 'composition.exposes_root' => 1],
            );
            $view = (new AssetIntelligenceDeriver())->derive($inspection, $role, $graph);
            $observation = (new AssetObservationProjector())->project($inspection, $context);
            $audit = (new AssetSecurityAuditProjector())->project($inspection, $context);
            $verification = (new AssetIntelligenceVerifier())->verify($request, $inspection, $view, $observation, $audit, $graph);
            $this->case(AssetIntelligenceVerdict::Pass === $verification->verdict());
            $this->case(AssetGraphEvidence::RELATIONSHIP === $view->graphEvidence()->relationship());
            $this->case($view->graphEvidence()->supplied());
            $this->case($role->purpose() === $view->purpose());
            foreach ([$observation->attributes(), $audit->attributes()] as $attributes) {
                $projection = null;
                foreach ($attributes->attributes() as $attribute) {
                    if ('inspection.projection' === $attribute->key()->value()) {
                        $projection = $attribute->value()->value();
                    }
                }
                $this->case('constructed-not-recorded' === $projection);
            }
            $serialized = $view->toArray();
            foreach (['role_execution', 'repair', 'filesystem_access', 'source_output_reading', 'building', 'resolution', 'network_access', 'recording', 'exporting', 'production_observation', 'certification'] as $marker) {
                $this->case('not-performed' === $serialized[$marker]);
            }
        }
    }

    private function viewMapping(ResolvedAssetSet $resolved): void
    {
        $summary = ResolvedAssetSummary::fromResolved($resolved);
        $schema = new ViewContextSchema(PresentationValueSchema::record([
            new PresentationFieldSchema('asset_url', PresentationValueSchema::string(4096), true),
            new PresentationFieldSchema('asset_integrity', PresentationValueSchema::string(256), true),
        ]));
        $context = new ViewContext([
            'asset_url' => new StringValue($summary->publicUrl()),
            'asset_integrity' => new StringValue($summary->integrity() ?? ''),
        ]);
        $this->case($schema->matches($context));
        $this->case(['asset_integrity', 'asset_url'] === array_keys($context->fields()));
        $this->case(!str_contains($summary->publicUrl(), '<script'));
        $this->case(!in_array('render', get_class_methods($context), true));
        $this->case(!in_array('emitHeaders', get_class_methods($summary), true));
    }

    private function skeletonStack(TemporaryAssetWorkspace $workspace): void
    {
        $application = $workspace->copyTree($this->repositoryRoot . '/skeletons/asset-minimal', 'skeletons/asset-minimal');
        $packages = $workspace->directory('packages');
        $this->localizeLockedPackages($application, $packages);
        $environment = $this->processEnvironment($workspace->root());
        $install = $this->process([...$this->composer(), 'install', '--no-interaction', '--no-ansi', '--no-plugins', '--no-scripts'], $application, $environment);
        $this->case(0 === $install->exitCode);
        $this->case('1' === $environment['COMPOSER_DISABLE_NETWORK']);
        $this->case(!isset($environment['HTTP_PROXY'], $environment['HTTPS_PROXY'], $environment['ALL_PROXY']));
        $owned = $this->ownedIdentity($application);

        $directBuild = $this->process([PHP_BINARY, 'bin/asset.php', 'build'], $application, $environment);
        $afterDirect = $this->generatedIdentity($application);
        $composerBuild = $this->process([...$this->composer(), 'asset:build', '--no-interaction', '--no-ansi'], $application, $environment);
        $this->case(0 === $directBuild->exitCode && '' === $directBuild->stderr);
        $this->case($directBuild->stdout === $composerBuild->stdout && '' === $composerBuild->stderr);
        $this->case($afterDirect === $this->generatedIdentity($application));

        $beforeCheck = $this->generatedIdentity($application);
        $directCheck = $this->process([PHP_BINARY, 'bin/asset.php', 'check'], $application, $environment);
        $composerCheck = $this->process([...$this->composer(), 'asset:check', '--no-interaction', '--no-ansi'], $application, $environment);
        $this->case(0 === $directCheck->exitCode && '' === $directCheck->stderr);
        $this->case($directCheck->stdout === $composerCheck->stdout && '' === $composerCheck->stderr);
        $this->case($beforeCheck === $this->generatedIdentity($application));

        $directResolve = $this->process([PHP_BINARY, 'bin/asset.php', 'resolve'], $application, $environment);
        $composerResolve = $this->process([...$this->composer(), 'asset:resolve', '--no-interaction', '--no-ansi'], $application, $environment);
        $this->case(0 === $directResolve->exitCode && '' === $directResolve->stderr);
        $this->case($directResolve->stdout === $composerResolve->stdout && '' === $composerResolve->stderr);
        $resolved = json_decode($directResolve->stdout, true, flags: JSON_THROW_ON_ERROR);
        $this->case(is_array($resolved) && 'app.style' === ($resolved['logical_identity'] ?? null));

        $mapping = $this->process([PHP_BINARY, '-r', self::skeletonMappingProbe(), $application], $application, $environment);
        $this->case(0 === $mapping->exitCode && "mapped\n" === $mapping->stdout && '' === $mapping->stderr);
        $this->case($owned === $this->ownedIdentity($application));

        $artifact = $this->singleGenerated($application . '/var/assets', 'artifact.json');
        $this->case(8 === file_put_contents($artifact, 'conflict'));
        $conflict = $this->process([...$this->composer(), 'asset:build', '--no-interaction', '--no-ansi'], $application, $environment);
        $this->case(0 !== $conflict->exitCode && 'conflict' === file_get_contents($artifact));
        $this->case(!str_contains($conflict->stdout . $conflict->stderr, $application));

        $source = $application . '/kongono.assets.json';
        $sourceBytes = (string) file_get_contents($source);
        $this->case(strlen(self::privateLiteral()) === file_put_contents($source, self::privateLiteral()));
        $rejected = $this->process([...$this->composer(), 'asset:check', '--no-interaction', '--no-ansi'], $application, $environment);
        $this->case(0 !== $rejected->exitCode);
        $this->case(!str_contains($rejected->stdout . $rejected->stderr, self::privateLiteral()));
        $this->case(!str_contains($rejected->stdout . $rejected->stderr, $application));
        $this->case(strlen($sourceBytes) === file_put_contents($source, $sourceBytes));
        $this->case($owned === $this->ownedIdentity($application));
    }

    private function adapterRequest(AssetSourceSet $source, ViteToolchain $toolchain, int $timeout, int $stdout, int $stderr): AdapterBuildRequest
    {
        return new AdapterBuildRequest(
            AdapterContractVersion::Version1,
            $source,
            ViteAdapterProfile::ClientEs2022V1->identity(),
            $toolchain->identity(),
            new AdapterBuildLimits($timeout, $stdout, $stderr, 10_000, 10, 10_000, 20_000, 10),
        );
    }

    private function adapterSource(string $mode): AssetSourceSet
    {
        $logical = new LogicalAssetIdentity('main');
        $set = new AssetDeclarationSet(
            ContractVersion::Version1,
            [new AssetDeclaration(ContractVersion::Version1, $logical, AssetKind::Script, AssetContentType::TextJavascript)],
            [],
            [new AssetEntryPoint(ContractVersion::Version1, $logical)],
        );

        return new AssetSourceSet($set, [new AssetSource($logical, new AssetSourcePath('main.js'), 'MODE_' . $mode)]);
    }

    private function withRestoredFile(string $path, string $original, string $replacement, callable $operation): void
    {
        $this->case(strlen($replacement) === file_put_contents($path, $replacement));
        try {
            $this->case($this->fails($operation));
        } finally {
            $this->case(strlen($original) === file_put_contents($path, $original));
        }
    }

    /** @return list<string> */
    private function ordinaryFiles(string $root, string $excludedBasename): array
    {
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $entry) {
            if (!$entry instanceof SplFileInfo || !$entry->isFile() || $entry->isLink()) {
                throw new RuntimeException('phase19_inventory_failed');
            }
            if ($excludedBasename !== $entry->getFilename()) {
                $files[] = str_replace('\\', '/', $entry->getPathname());
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }

    private function localizeLockedPackages(string $application, string $packages): void
    {
        $lockPath = $application . '/composer.lock';
        $lock = json_decode((string) file_get_contents($lockPath), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($lock) || !is_array($lock['packages'] ?? null)) {
            throw new RuntimeException('phase19_skeleton_failed');
        }
        $locked = $lock['packages'];
        foreach ($locked as $index => $package) {
            if (!is_array($package) || !is_string($package['name'] ?? null) || !str_starts_with($package['name'], 'kongono/') || !is_array($package['dist'] ?? null)) {
                throw new RuntimeException('phase19_skeleton_failed');
            }
            $basename = substr($package['name'], strlen('kongono/'));
            $this->copyTree($this->repositoryRoot . '/packages/' . $basename, $packages . '/' . $basename);
            $package['dist'] = ['type' => 'path', 'url' => '../../packages/' . $basename, 'reference' => $package['dist']['reference'] ?? null, 'shasum' => null];
            $package['transport-options'] = ['symlink' => false, 'relative' => true];
            unset($package['source']);
            $locked[$index] = $package;
        }
        $lock['packages'] = $locked;
        $bytes = json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        if (strlen($bytes) !== file_put_contents($lockPath, $bytes)) {
            throw new RuntimeException('phase19_skeleton_failed');
        }
    }

    private function copyTree(string $source, string $destination): void
    {
        if ((!mkdir($destination, 0700, true) && !is_dir($destination)) || is_link($source)) {
            throw new RuntimeException('phase19_copy_failed');
        }
        $source = str_replace('\\', '/', $source);
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $entry) {
            if (!$entry instanceof SplFileInfo || $entry->isLink()) {
                throw new RuntimeException('phase19_copy_failed');
            }
            $relative = substr(str_replace('\\', '/', $entry->getPathname()), strlen($source) + 1);
            $target = $destination . '/' . $relative;
            if ($entry->isDir()) {
                if (!mkdir($target, 0700, true) && !is_dir($target)) {
                    throw new RuntimeException('phase19_copy_failed');
                }
            } elseif (!$entry->isFile() || !copy($entry->getPathname(), $target)) {
                throw new RuntimeException('phase19_copy_failed');
            }
        }
    }

    /** @return list<string> */
    private function composer(): array
    {
        $path = getenv('PATH');
        foreach (is_string($path) ? explode(PATH_SEPARATOR, $path) : [] as $directory) {
            $candidate = rtrim($directory, '\\/') . '/composer.phar';
            $resolved = realpath($candidate);
            if (false !== $resolved && is_file($resolved) && !is_link($candidate)) {
                return [PHP_BINARY, str_replace('\\', '/', $resolved)];
            }
        }
        throw new RuntimeException('phase19_composer_failed');
    }

    /** @return array<string, string> */
    private function processEnvironment(string $temporary): array
    {
        $environment = [
            'COMPOSER_CACHE_DIR' => $temporary . '/composer-cache',
            'COMPOSER_DISABLE_NETWORK' => '1',
            'COMPOSER_HOME' => $temporary . '/composer-home',
            'COMPOSER_NO_INTERACTION' => '1',
        ];
        foreach (['SystemRoot', 'WINDIR', 'COMSPEC', 'PATH', 'TEMP', 'TMP', 'TMPDIR'] as $name) {
            $value = getenv($name);
            if (is_string($value) && '' !== $value) {
                $environment[$name] = $value;
            }
        }

        return $environment;
    }

    /**
     * @param list<string>          $command
     * @param array<string, string> $environment
     */
    private function process(array $command, string $directory, array $environment, int $timeout = self::PROCESS_TIMEOUT_SECONDS): ProcessResult
    {
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $directory, $environment, ['bypass_shell' => true]);
        if (!is_resource($process) || !isset($pipes[0], $pipes[1], $pipes[2])) {
            throw new RuntimeException('phase19_process_failed');
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $status = proc_get_status($process);
        $deadline = microtime(true) + $timeout;
        $overflow = false;
        $timedOut = false;
        try {
            while ($status['running']) {
                $this->appendBounded($stdout, $this->readAvailable($pipes[1]), $overflow);
                $this->appendBounded($stderr, $this->readAvailable($pipes[2]), $overflow);
                if (microtime(true) >= $deadline) {
                    $timedOut = true;
                    break;
                }
                usleep(10_000);
                $status = proc_get_status($process);
            }
            if (!$timedOut) {
                $this->appendBounded($stdout, $this->readAvailable($pipes[1]), $overflow);
                $this->appendBounded($stderr, $this->readAvailable($pipes[2]), $overflow);
            }
        } finally {
            foreach ([1, 2] as $pipe) {
                if (isset($pipes[$pipe]) && is_resource($pipes[$pipe])) {
                    fclose($pipes[$pipe]);
                }
            }
        }
        if ($timedOut && $status['running']) {
            proc_terminate($process);
        }
        $closed = proc_close($process);
        if ($timedOut || $overflow) {
            throw new RuntimeException('phase19_process_failed');
        }
        $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;

        return new ProcessResult($exit, $stdout, $stderr);
    }

    private function child(string $mode, int $timeout = self::PROCESS_TIMEOUT_SECONDS): ProcessResult
    {
        return $this->closeCaptured($this->openCaptured([PHP_BINARY, __DIR__ . '/run.php', $mode]), $timeout);
    }

    /** @return array{ProcessResult, ProcessResult} */
    private function childPair(): array
    {
        $first = $this->openCaptured([PHP_BINARY, __DIR__ . '/run.php', '--child-success']);
        $second = $this->openCaptured([PHP_BINARY, __DIR__ . '/run.php', '--child-success']);

        return [$this->closeCaptured($first), $this->closeCaptured($second)];
    }

    /** @param list<string> $command
     * @return array{resource, TemporaryAssetWorkspace, string, string}
     */
    private function openCaptured(array $command): array
    {
        $workspace = new TemporaryAssetWorkspace($this->repositoryRoot);
        $stdoutPath = $workspace->write('stdout', '');
        $stderrPath = $workspace->write('stderr', '');
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['file', $stdoutPath, 'wb'], ['file', $stderrPath, 'wb']], $pipes, $this->repositoryRoot, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            $workspace->cleanup();
            throw new RuntimeException('phase19_process_failed');
        }
        fclose($pipes[0]);

        return [$process, $workspace, $stdoutPath, $stderrPath];
    }

    /** @param array{resource, TemporaryAssetWorkspace, string, string} $opened */
    private function closeCaptured(array $opened, int $timeout = self::PROCESS_TIMEOUT_SECONDS): ProcessResult
    {
        [$process, $workspace, $stdoutPath, $stderrPath] = $opened;
        $status = proc_get_status($process);
        $deadline = microtime(true) + $timeout;
        try {
            while ($status['running'] && microtime(true) < $deadline) {
                usleep(10_000);
                $status = proc_get_status($process);
            }
            $timedOut = $status['running'];
            if ($timedOut) {
                proc_terminate($process);
            }
            $closed = proc_close($process);
            $stdout = file_get_contents($stdoutPath, false, null, 0, self::STREAM_LIMIT + 1);
            $stderr = file_get_contents($stderrPath, false, null, 0, self::STREAM_LIMIT + 1);
            if ($timedOut || false === $stdout || false === $stderr || strlen($stdout) > self::STREAM_LIMIT || strlen($stderr) > self::STREAM_LIMIT) {
                throw new RuntimeException('phase19_process_failed');
            }
            $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;

            return new ProcessResult($exit, $stdout, $stderr);
        } finally {
            $workspace->cleanup();
        }
    }

    private function repositoryFingerprint(): string
    {
        $result = $this->closeCaptured($this->openCaptured(['git', '-c', 'safe.directory=' . str_replace('\\', '/', $this->repositoryRoot), 'status', '--porcelain=v1', '--untracked-files=all']));
        $this->require(0 === $result->exitCode);

        return hash('sha256', $result->stdout);
    }

    private function directoryIdentity(string $root): string
    {
        $root = str_replace('\\', '/', (string) realpath($root));
        $records = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $entry) {
            if (!$entry instanceof SplFileInfo || $entry->isLink() || !$entry->isFile()) {
                throw new RuntimeException('phase19_workspace_failed');
            }
            $path = str_replace('\\', '/', $entry->getPathname());
            $bytes = file_get_contents($path);
            if (false === $bytes) {
                throw new RuntimeException('phase19_workspace_failed');
            }
            $records[] = substr($path, strlen($root) + 1) . '|' . strlen($bytes) . '|' . hash('sha256', $bytes);
        }
        sort($records, SORT_STRING);

        return hash('sha256', implode("\n", $records));
    }

    private function temporaryResidue(): string
    {
        $paths = glob(rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/') . '/kongono-phase19-*', GLOB_NOSORT);
        if (false === $paths) {
            throw new RuntimeException('phase19_acceptance_failed');
        }
        sort($paths, SORT_STRING);

        return hash('sha256', implode("\n", $paths));
    }

    /** @return array<string, string> */
    private function ownedIdentity(string $application): array
    {
        $ownership = json_decode((string) file_get_contents($application . '/kongono.skeleton.json'), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($ownership) || !is_array($ownership['template_paths'] ?? null)) {
            throw new RuntimeException('phase19_skeleton_failed');
        }
        $identity = [];
        foreach ($ownership['template_paths'] as $path) {
            if (!is_string($path)) {
                throw new RuntimeException('phase19_skeleton_failed');
            }
            $sha256 = hash_file('sha256', $application . '/' . $path);
            if (!is_string($sha256)) {
                throw new RuntimeException('phase19_skeleton_failed');
            }
            $identity[$path] = $sha256;
        }

        return $identity;
    }

    /** @return array<string, string> */
    private function generatedIdentity(string $application): array
    {
        $identity = [];
        $root = $application . '/var/assets';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $entry) {
            if (!$entry instanceof SplFileInfo || !$entry->isFile() || '.gitignore' === $entry->getFilename()) {
                continue;
            }
            $path = str_replace('\\', '/', $entry->getPathname());
            $sha256 = hash_file('sha256', $path);
            if (!is_string($sha256)) {
                throw new RuntimeException('phase19_skeleton_failed');
            }
            $identity[substr($path, strlen($application) + 1)] = $sha256;
        }
        ksort($identity, SORT_STRING);

        return $identity;
    }

    private function singleGenerated(string $root, string $basename): string
    {
        $matches = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $entry) {
            if ($entry instanceof SplFileInfo && $entry->isFile() && $basename === $entry->getFilename()) {
                $matches[] = str_replace('\\', '/', $entry->getPathname());
            }
        }
        if (1 !== count($matches)) {
            throw new RuntimeException('phase19_skeleton_failed');
        }

        return $matches[0];
    }

    private function appendBounded(string &$buffer, string $chunk, bool &$overflow): void
    {
        $remaining = self::STREAM_LIMIT - strlen($buffer);
        if (strlen($chunk) > $remaining) {
            $overflow = true;
            $buffer .= substr($chunk, 0, max(0, $remaining));

            return;
        }
        $buffer .= $chunk;
    }

    /** @param resource $stream */
    private function readAvailable($stream): string
    {
        $bytes = stream_get_contents($stream, self::STREAM_LIMIT + 1);
        if (false === $bytes) {
            throw new RuntimeException('phase19_process_failed');
        }

        return $bytes;
    }

    private function isCanonicalSuccess(string $bytes): bool
    {
        if (!str_ends_with($bytes, "\n") || str_contains(substr($bytes, 0, -1), "\n")) {
            return false;
        }
        try {
            $decoded = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return false;
        }

        return is_array($decoded)
            && ['status', 'profile', 'case_count', 'cleanup'] === array_keys($decoded)
            && 'ok' === $decoded['status']
            && 'kongono-phase19-asset-platform' === $decoded['profile']
            && is_int($decoded['case_count'])
            && $decoded['case_count'] > 0
            && 'complete' === $decoded['cleanup']
            && self::successBytes($decoded['case_count']) === $bytes;
    }

    private function fails(callable $operation): bool
    {
        try {
            $operation();
        } catch (Throwable $failure) {
            return !str_contains((string) $failure, self::privateLiteral());
        }

        return false;
    }

    private function case(bool $passed): void
    {
        $this->require($passed);
        ++$this->caseCount;
    }

    private function require(bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException('phase19_acceptance_failed');
        }
    }

    private static function successBytes(int $caseCount): string
    {
        return json_encode(['status' => 'ok', 'profile' => 'kongono-phase19-asset-platform', 'case_count' => $caseCount, 'cleanup' => 'complete'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
    }

    private static function privateLiteral(): string
    {
        return 'private-phase19-' . 'adversarial.example/secret';
    }

    private static function nativeSourceBytes(): string
    {
        return json_encode([
            'schema' => 'kongono.asset-application-source',
            'version' => 1,
            'contract_version' => 1,
            'declarations' => [
                ['identity' => 'app.script', 'kind' => 'script', 'content_type' => 'text/javascript', 'source_path' => 'assets/app.js'],
                ['identity' => 'app.lazy', 'kind' => 'script', 'content_type' => 'text/javascript', 'source_path' => 'assets/lazy.js'],
                ['identity' => 'app.style', 'kind' => 'style', 'content_type' => 'text/css', 'source_path' => 'assets/app.css'],
            ],
            'dependencies' => [
                ['source' => 'app.script', 'target' => 'app.style', 'kind' => 'resource'],
                ['source' => 'app.script', 'target' => 'app.lazy', 'kind' => 'dynamic'],
                ['source' => 'app.lazy', 'target' => 'app.style', 'kind' => 'static'],
            ],
            'entry_points' => ['app.script'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
    }

    private static function emulatorBytes(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

$sourceRoot = str_replace('\\', '/', $argv[2] ?? '');
$outputRoot = str_replace('\\', '/', dirname($sourceRoot)) . '/output';
$mode = file_get_contents($sourceRoot . '/main.js');
if (!is_string($mode)) {
    exit(91);
}
if ('MODE_NONZERO' === $mode) {
    fwrite(STDERR, 'private-phase19-' . 'adversarial.example/secret');
    exit(7);
}
if ('MODE_STDOUT' === $mode) {
    fwrite(STDOUT, str_repeat('x', 4096));
    exit(0);
}
if ('MODE_STDERR' === $mode) {
    fwrite(STDERR, str_repeat('x', 4096));
    exit(1);
}
if ('MODE_TIMEOUT' === $mode) {
    sleep(3);
    exit(0);
}
if (!is_dir($outputRoot . '/assets') && !mkdir($outputRoot . '/assets', 0700, true)) {
    exit(92);
}
$manifest = '{"main.js":{"file":"assets/main.js","src":"main.js","isEntry":true}}';
if ('MODE_MALFORMED' === $mode) {
    file_put_contents($outputRoot . '/kongono-vite-manifest.json', '{');
    exit(0);
}
if ('MODE_DUPLICATE' === $mode) {
    file_put_contents($outputRoot . '/kongono-vite-manifest.json', '{"main.js":{"file":"assets/main.js","src":"main.js","isEntry":true},"main.js":{"file":"assets/main.js","src":"main.js","isEntry":true}}');
    file_put_contents($outputRoot . '/assets/main.js', "duplicate\n");
    exit(0);
}
if ('MODE_PARTIAL' === $mode) {
    file_put_contents($outputRoot . '/kongono-vite-manifest.json', $manifest);
    exit(0);
}
if ('MODE_EXTRA' === $mode) {
    file_put_contents($outputRoot . '/kongono-vite-manifest.json', $manifest);
    file_put_contents($outputRoot . '/assets/main.js', "built\n");
    file_put_contents($outputRoot . '/additional.js', "extra\n");
    exit(0);
}
if ('MODE_OUTSIDE' === $mode) {
    file_put_contents($outputRoot . '/kongono-vite-manifest.json', '{"main.js":{"file":"../escape.js","src":"main.js","isEntry":true}}');
    exit(0);
}
if ('MODE_LINK' === $mode) {
    file_put_contents($outputRoot . '/kongono-vite-manifest.json', $manifest);
    if (!@symlink($sourceRoot . '/main.js', $outputRoot . '/assets/main.js')) {
        mkdir($outputRoot . '/assets/main.js');
    }
    exit(0);
}
file_put_contents($outputRoot . '/kongono-vite-manifest.json', $manifest);
file_put_contents($outputRoot . '/assets/main.js', "console.log('adapter-built');\n");
exit(0);
PHP;
    }

    private static function skeletonMappingProbe(): string
    {
        return <<<'PHP'
$root = str_replace('\\', '/', $argv[1]);
require $root . '/vendor/autoload.php';
$workflow = new AssetMinimalApp\AssetWorkflow($root);
[$schema, $context] = AssetMinimalApp\ViewAssetContext::fromResolved($workflow->resolve());
if (!$schema instanceof Kongono\ViewContract\Schema\ViewContextSchema || !$context instanceof Kongono\ViewContract\Value\ViewContext || !$schema->matches($context) || method_exists($context, 'render')) {
    exit(1);
}
fwrite(STDOUT, "mapped\n");
PHP;
    }
}
