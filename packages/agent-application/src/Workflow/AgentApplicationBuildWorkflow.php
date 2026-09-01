<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Workflow;

use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentApplication\Exception\AgentApplicationWorkflowException;
use Kongono\AgentApplication\Generated\AgentWiringGenerator;
use Kongono\AgentApplication\Generated\AgentWiringPublisher;
use Kongono\AgentApplication\Generated\AgentWiringValidator;
use Kongono\AgentApplication\Internal\PathGuard;
use Kongono\AgentApplication\Profile\AgentApplicationProfileFactory;
use Kongono\AgentApplication\Runtime\AgentWiringLoader;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactValidator;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Builds or write-free checks one exact declaration-to-generated-application workflow. */
#[Api(ApiStatus::Experimental)]
final class AgentApplicationBuildWorkflow
{
    private const int MAX_DECLARATION_ARTIFACT_BYTES = 131_072;

    public function build(string $applicationRoot, string $declarationArtifactFile, AgentDeclaration $declaration, AgentAuthorityPlan $plan, string $outputDirectory): CurrentAgentApplicationBuild
    {
        return $this->execute(true, $applicationRoot, $declarationArtifactFile, $declaration, $plan, $outputDirectory);
    }

    public function check(string $applicationRoot, string $declarationArtifactFile, AgentDeclaration $declaration, AgentAuthorityPlan $plan, string $outputDirectory): CurrentAgentApplicationBuild
    {
        return $this->execute(false, $applicationRoot, $declarationArtifactFile, $declaration, $plan, $outputDirectory);
    }

    private function execute(bool $publish, string $applicationRoot, string $declarationArtifactFile, AgentDeclaration $declaration, AgentAuthorityPlan $plan, string $outputDirectory): CurrentAgentApplicationBuild
    {
        try {
            $root = PathGuard::applicationRoot($applicationRoot);
            [, $output] = PathGuard::publicationLocations($root, $outputDirectory);
            $declarationPath = PathGuard::workflowFile($root, $declarationArtifactFile);
        } catch (Throwable $failure) {
            throw AgentApplicationWorkflowException::failed('path', $failure);
        }
        $declarationSnapshot = $this->snapshot($declarationPath, self::MAX_DECLARATION_ARTIFACT_BYTES, 'declaration_snapshot');
        try {
            (new AgentDeclarationArtifactValidator())->validateCurrent($declaration, $declarationSnapshot['bytes']);
            $profile = (new AgentApplicationProfileFactory())->create($declaration, $declarationSnapshot['bytes'], $plan);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AgentApplicationWorkflowException::failed('profile', $failure);
        }
        try {
            $generated = (new AgentWiringGenerator())->generate($profile);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AgentApplicationWorkflowException::failed('generate', $failure);
        }
        $generatedPath = $output . '/kongono-agent-wiring-v1-' . $generated->generatedWiringSha256() . '.php';
        if ($publish) {
            try {
                if ((new AgentWiringPublisher())->publish($root, $output, $generated) !== $generatedPath) {
                    throw AgentApplicationWorkflowException::failed('publish');
                }
            } catch (AgentApplicationWorkflowException $failure) {
                throw $failure;
            } catch (Throwable $failure) {
                throw AgentApplicationWorkflowException::failed('publish', $failure);
            }
        }
        $generatedSnapshot = $this->snapshot($generatedPath, AgentWiringGenerator::MAX_GENERATED_BYTES, 'generated_snapshot');
        try {
            (new AgentWiringValidator())->validateCurrent($profile, $generatedSnapshot['bytes']);
            $loaded = (new AgentWiringLoader())->load($root, $profile, $generatedPath);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AgentApplicationWorkflowException::failed('load', $failure);
        }
        $this->reverify($declarationSnapshot);
        $this->reverify($generatedSnapshot);

        return new CurrentAgentApplicationBuild($root, $profile, $generated, $loaded, $declarationSnapshot, $generatedSnapshot);
    }

    /** @return array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int,mixed>} */
    private function snapshot(string $path, int $limit, string $stage): array
    {
        $resolved = realpath($path);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $path || is_link($path) || !is_file($path)) {
            throw AgentApplicationWorkflowException::failed($stage);
        }
        $before = @stat($path);
        if (false === $before || $before['size'] > $limit) {
            throw AgentApplicationWorkflowException::failed($stage);
        }
        $bytes = @file_get_contents($path);
        $after = @stat($path);
        if (false === $bytes || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw AgentApplicationWorkflowException::failed($stage);
        }

        return ['absolute' => $path, 'bytes' => $bytes, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'stat' => $before];
    }

    /** @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int,mixed>} $snapshot */
    private function reverify(array $snapshot): void
    {
        $stat = @stat($snapshot['absolute']);
        $bytes = @file_get_contents($snapshot['absolute']);
        if (false === $stat) {
            throw AgentApplicationWorkflowException::failed('reverify');
        }
        if (false === $bytes || !$this->sameMetadata($snapshot['stat'], $stat) || strlen($bytes) !== $snapshot['size'] || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))) {
            throw AgentApplicationWorkflowException::failed('reverify');
        }
    }

    /**
     * @param array<string|int, mixed> $before
     * @param array<string|int, mixed> $after
     */
    private function sameMetadata(array $before, array $after): bool
    {
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
