<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Vite;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Adapter\AdapterBuildRequest;
use Kongono\AssetAdapterContract\Adapter\AdapterBuildResult;
use Kongono\AssetAdapterContract\Adapter\AssetBuildAdapter;
use Kongono\AssetAdapterContract\Artifact\AdapterBuildArtifactValidator;
use Kongono\AssetAdapterContract\Exception\AssetAdapterExecutionException;
use Kongono\AssetAdapterContract\Output\AdapterOutputSet;
use Kongono\AssetAdapterContract\Process\BuildProcessCommand;
use Kongono\AssetAdapterContract\Process\BuildProcessRunner;
use Kongono\AssetAdapterVite\Exception\InvalidViteToolchainException;
use Kongono\AssetAdapterVite\Exception\ViteBuildExecutionException;
use Kongono\AssetAdapterVite\Exception\ViteWorkspaceException;
use Kongono\AssetAdapterVite\Internal\FileTreeReader;
use Kongono\AssetAdapterVite\Internal\PathGuard;
use Kongono\AssetAdapterVite\Manifest\ViteManifestDecoder;
use Kongono\AssetAdapterVite\Manifest\ViteManifestTranslator;
use Kongono\AssetAdapterVite\Workspace\ViteWorkspace;
use Kongono\AssetAdapterVite\Workspace\ViteWorkspaceFactory;
use Throwable;

/** Executes one fixed-profile Vite build behind the neutral adapter port. */
#[Api(ApiStatus::Experimental)]
final readonly class ViteAssetBuildAdapter implements AssetBuildAdapter
{
    public function __construct(private string $workspaceParent, private ViteToolchain $toolchain, private BuildProcessRunner $runner)
    {
        PathGuard::existingDirectory($workspaceParent);
    }

    public function build(AdapterBuildRequest $request): AdapterBuildResult
    {
        if (!$request->profileIdentity()->equals(ViteAdapterProfile::ClientEs2022V1->identity())) {
            throw InvalidViteToolchainException::invalid('profile_mismatch');
        }
        if (!$request->toolchainIdentity()->equals($this->toolchain->identity())) {
            throw InvalidViteToolchainException::invalid('toolchain_mismatch');
        }
        if (0 === $request->sourceSet()->sourceCount()) {
            $set = new AdapterOutputSet($request->sourceSet(), [], [], [], $request->limits()->outputDependencyDepth());
            $artifact = (new AdapterBuildArtifactValidator())->create($request->sourceSet(), $request->profileIdentity(), $request->toolchainIdentity(), 'kongono/asset-adapter-vite', $set, $request->limits()->outputDependencyDepth());
            return new AdapterBuildResult($artifact, $set, []);
        }

        $this->toolchain->authenticate();
        $workspace = null;
        $primary = null;
        $result = null;
        try {
            $workspace = (new ViteWorkspaceFactory($this->workspaceParent))->create($request->sourceSet());
            $command = new BuildProcessCommand([
                $this->toolchain->nodePath(), $this->toolchain->viteCliPath(), 'build', $workspace->sourceRoot(), '--config', $workspace->configPath(), '--configLoader', 'native', '--logLevel', 'error',
            ], $workspace->root(), ['NODE_ENV' => 'production', 'NO_COLOR' => '1', 'FORCE_COLOR' => '0'], $request->limits()->timeoutSeconds(), $request->limits()->stdoutBytes(), $request->limits()->stderrBytes());
            $process = $this->runner->run($command);
            if (!$process->started()) {
                throw ViteBuildExecutionException::failed('process_start');
            }
            if (!$process->succeeded()) {
                throw ViteBuildExecutionException::failed(null === $process->exitCode() || $process->timedOut() || $process->stdoutLimitExceeded() || $process->stderrLimitExceeded() ? 'process_lifecycle' : 'process_nonzero');
            }
            $reader = new FileTreeReader();
            $manifestName = 'kongono-vite-manifest.json';
            $manifestBytes = $reader->readFile($workspace->outputRoot() . '/' . $manifestName, $request->limits()->manifestBytes());
            $manifest = (new ViteManifestDecoder())->decode($manifestBytes, $request->limits()->manifestBytes());
            $expected = [$manifestName, ...$manifest->emittedFiles()];
            $allBytes = $reader->read($workspace->outputRoot(), $expected, $request->limits()->emittedFiles(), max($request->limits()->manifestBytes(), $request->limits()->emittedFileBytes()), $request->limits()->aggregateEmittedBytes() + strlen($manifestBytes));
            unset($allBytes[$manifestName]);
            $set = (new ViteManifestTranslator())->translate($request->sourceSet(), $request->profileIdentity(), $manifest, $allBytes, $request->limits()->outputDependencyDepth());
            $artifact = (new AdapterBuildArtifactValidator())->create($request->sourceSet(), $request->profileIdentity(), $request->toolchainIdentity(), 'kongono/asset-adapter-vite', $set, $request->limits()->outputDependencyDepth());
            $objects = [];
            foreach ($set->outputs() as $output) {
                $objects[$output->path()->value()] = $output->bytes();
            } ksort($objects, SORT_STRING);
            $result = new AdapterBuildResult($artifact, $set, $objects);
        } catch (Throwable $failure) {
            $primary = $failure;
        }

        try {
            if ($workspace instanceof ViteWorkspace) {
                $workspace->cleanup();
            }
        } catch (Throwable $cleanup) {
            throw ViteWorkspaceException::failed('cleanup_failed', $primary, 'failed');
        }
        if (null !== $primary) {
            if ($primary instanceof \Error || $primary instanceof InvalidViteToolchainException || $primary instanceof ViteBuildExecutionException || $primary instanceof ViteWorkspaceException || $primary instanceof \Kongono\AssetAdapterVite\Exception\InvalidViteManifestException) {
                throw $primary;
            }
            throw AssetAdapterExecutionException::failed('adapter_failed', $primary);
        }
        return $result;
    }
}
