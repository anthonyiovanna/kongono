<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Vite;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Identity\ToolchainIdentity;
use Kongono\AssetAdapterVite\Exception\InvalidViteToolchainException;
use Kongono\AssetAdapterVite\Internal\PathGuard;
use Kongono\AssetAdapterVite\Internal\ViteAdapterGuard;

/** Carries explicit expected Node and Vite launch-artifact evidence. */
#[Api(ApiStatus::Experimental)]final readonly class ViteToolchain
{
    private ToolchainIdentity $identity;
    public function __construct(private string $nodePath, private string $viteCliPath, private string $nodeVersion, private string $viteVersion, private string $nodeSha256, private string $viteSha256)
    {
        if (PathGuard::normalize($nodePath) !== $nodePath || PathGuard::normalize($viteCliPath) !== $viteCliPath) {
            throw InvalidViteToolchainException::invalid('path_grammar');
        }ViteAdapterGuard::version($nodeVersion);
        ViteAdapterGuard::version($viteVersion);
        ViteAdapterGuard::sha256($nodeSha256);
        ViteAdapterGuard::sha256($viteSha256);
        $evidence = json_encode(['package' => 'kongono/asset-adapter-vite','profile' => ViteAdapterProfile::ClientEs2022V1->value,'node_version' => $nodeVersion,'vite_version' => $viteVersion,'node_sha256' => $nodeSha256,'vite_sha256' => $viteSha256], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
        $this->identity = ToolchainIdentity::fromEvidence($evidence);
    }
    public function authenticate(): void
    {
        foreach ([[$this->nodePath,$this->nodeSha256],[$this->viteCliPath,$this->viteSha256]] as [$path,$expected]) {
            $resolved = PathGuard::existingFile($path);
            $actual = hash_file('sha256', $resolved);
            if ($resolved !== $path || false === $actual || !hash_equals($expected, $actual)) {
                throw InvalidViteToolchainException::invalid('launch_changed');
            }
        }
    }
    public function nodePath(): string
    {
        return$this->nodePath;
    }public function viteCliPath(): string
    {
        return$this->viteCliPath;
    }public function nodeVersion(): string
    {
        return$this->nodeVersion;
    }public function viteVersion(): string
    {
        return$this->viteVersion;
    }public function nodeSha256(): string
    {
        return$this->nodeSha256;
    }public function viteSha256(): string
    {
        return$this->viteSha256;
    }public function identity(): ToolchainIdentity
    {
        return$this->identity;
    }
}
