<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Runtime;

use Kongono\AgentApplication\Exception\AgentWiringLoadException;
use Kongono\AgentApplication\Generated\AgentWiringGenerator;
use Kongono\AgentApplication\Generated\AgentWiringValidator;
use Kongono\AgentApplication\Generated\GeneratedAgentApplicationBoundary;
use Kongono\AgentApplication\Internal\PathGuard;
use Kongono\AgentApplication\Profile\AgentApplicationProfile;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

/** Snapshots, exact-current validates, reverifies, and loads one explicit trusted PHP file once. */
#[Api(ApiStatus::Experimental)]
final class AgentWiringLoader
{
    public function load(string $applicationRoot, AgentApplicationProfile $profile, string $generatedFile): LoadedAgentWiring
    {
        $path = PathGuard::loadFile($applicationRoot, $generatedFile);
        $snapshot = $this->snapshot($path);
        try {
            $wiring = (new AgentWiringValidator())->validateCurrent($profile, $snapshot['bytes']);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AgentWiringLoadException::failed('validation', $failure);
        }
        if (basename($path) !== 'kongono-agent-wiring-v1-' . $wiring->generatedWiringSha256() . '.php') {
            throw AgentWiringLoadException::failed('identity');
        }
        $class = $wiring->generatedClass();
        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false) || enum_exists($class, false)) {
            throw AgentWiringLoadException::failed('collision');
        }
        $this->reverify($path, $snapshot);
        try {
            require $path;
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AgentWiringLoadException::failed('require', $failure);
        }
        try {
            if (!class_exists($class, false)) {
                throw AgentWiringLoadException::failed('shape');
            }
            $reflection = new ReflectionClass($class);
            if ($reflection->getName() !== $class || !$reflection->isFinal() || !$reflection->implementsInterface(GeneratedAgentApplicationBoundary::class) || !$reflection->isInstantiable()) {
                throw AgentWiringLoadException::failed('shape');
            }
            $constructor = $reflection->getConstructor();
            if (null !== $constructor && 0 !== $constructor->getNumberOfRequiredParameters()) {
                throw AgentWiringLoadException::failed('shape');
            }
            $method = $reflection->getMethod('start');
            $parameters = $method->getParameters();
            $parameterType = $parameters[0]->getType() ?? null;
            $returnType = $method->getReturnType();
            if (!$method->isPublic() || $method->isStatic() || count($parameters) !== 1 || !$parameterType instanceof ReflectionNamedType || $parameterType->getName() !== AgentApplicationProfile::class || !$returnType instanceof ReflectionNamedType || $returnType->getName() !== AgentApplication::class) {
                throw AgentWiringLoadException::failed('shape');
            }
            $boundary = $reflection->newInstance();
        } catch (AgentWiringLoadException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AgentWiringLoadException::failed('shape', $failure);
        }
        if (!$boundary instanceof GeneratedAgentApplicationBoundary) {
            throw AgentWiringLoadException::failed('shape');
        }

        return new LoadedAgentWiring($boundary, $profile);
    }

    /** @return array{bytes:string,size:int,sha256:string,stat:array<string|int,mixed>} */
    private function snapshot(string $path): array
    {
        $before = @stat($path);
        if (false === $before || $before['size'] > AgentWiringGenerator::MAX_GENERATED_BYTES) {
            throw AgentWiringLoadException::failed('snapshot');
        }
        $bytes = @file_get_contents($path);
        $after = @stat($path);
        if (false === $bytes || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw AgentWiringLoadException::failed('snapshot');
        }

        return ['bytes' => $bytes, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'stat' => $before];
    }

    /** @param array{bytes:string,size:int,sha256:string,stat:array<string|int,mixed>} $snapshot */
    private function reverify(string $path, array $snapshot): void
    {
        $stat = @stat($path);
        $bytes = @file_get_contents($path);
        if (false === $stat) {
            throw AgentWiringLoadException::failed('snapshot');
        }
        if (false === $bytes || !$this->sameMetadata($snapshot['stat'], $stat) || strlen($bytes) !== $snapshot['size'] || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))) {
            throw AgentWiringLoadException::failed('snapshot');
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
