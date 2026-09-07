<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Exception\ModelInvocationWiringLoadException;
use Kongono\ModelInvocationApplication\Generated\GeneratedModelInvocationApplicationBoundary;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringGenerator;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringValidator;
use Kongono\ModelInvocationApplication\Internal\PathGuard;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapter;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

/** Snapshots, exact-current validates, reverifies, and loads one explicit trusted PHP file once. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationWiringLoader
{
    public function load(string $applicationRoot, ModelInvocationApplicationProfile $profile, string $generatedFile): LoadedModelInvocationWiring
    {
        $path = PathGuard::loadFile($applicationRoot, $generatedFile);
        $snapshot = $this->snapshot($path);
        try {
            $wiring = (new ModelInvocationWiringValidator())->validateCurrent($profile, $snapshot['bytes']);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ModelInvocationWiringLoadException::failed('validation', $failure);
        }
        if (basename($path) !== 'kongono-model-invocation-wiring-v1-' . $wiring->generatedWiringSha256() . '.php') {
            throw ModelInvocationWiringLoadException::failed('identity');
        }
        $class = $wiring->generatedClass();
        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false) || enum_exists($class, false)) {
            throw ModelInvocationWiringLoadException::failed('collision');
        }
        $this->reverify($path, $snapshot);
        try {
            require $path;
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ModelInvocationWiringLoadException::failed('require', $failure);
        }
        try {
            if (!class_exists($class, false)) {
                throw ModelInvocationWiringLoadException::failed('shape');
            }
            $reflection = new ReflectionClass($class);
            if ($reflection->getName() !== $class || !$reflection->isFinal() || !$reflection->implementsInterface(GeneratedModelInvocationApplicationBoundary::class) || !$reflection->isInstantiable()) {
                throw ModelInvocationWiringLoadException::failed('shape');
            }
            $constructor = $reflection->getConstructor();
            if (null !== $constructor && 0 !== $constructor->getNumberOfRequiredParameters()) {
                throw ModelInvocationWiringLoadException::failed('shape');
            }
            $method = $reflection->getMethod('start');
            $parameters = $method->getParameters();
            if (2 !== count($parameters)) {
                throw ModelInvocationWiringLoadException::failed('shape');
            }
            $firstType = $parameters[0]->getType() ?? null;
            $secondType = $parameters[1]->getType() ?? null;
            $returnType = $method->getReturnType();
            if (!$method->isPublic() || $method->isStatic() || !$firstType instanceof ReflectionNamedType || ModelInvocationApplicationProfile::class !== $firstType->getName() || !$secondType instanceof ReflectionNamedType || InvocationAdapter::class !== $secondType->getName() || !$returnType instanceof ReflectionNamedType || ModelInvocationApplication::class !== $returnType->getName()) {
                throw ModelInvocationWiringLoadException::failed('shape');
            }
            $boundary = $reflection->newInstance();
        } catch (ModelInvocationWiringLoadException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ModelInvocationWiringLoadException::failed('shape', $failure);
        }
        if (!$boundary instanceof GeneratedModelInvocationApplicationBoundary) {
            throw ModelInvocationWiringLoadException::failed('shape');
        }

        return new LoadedModelInvocationWiring($boundary, $profile);
    }

    /** @return array{bytes:string,size:int,sha256:string,stat:array<string|int,int>} */
    private function snapshot(string $path): array
    {
        $before = $this->stat($path);
        if (false === $before || $before['size'] > ModelInvocationWiringGenerator::MAX_GENERATED_BYTES) {
            throw ModelInvocationWiringLoadException::failed('snapshot');
        }
        $bytes = @file_get_contents($path);
        $after = $this->stat($path);
        if (false === $bytes || false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw ModelInvocationWiringLoadException::failed('snapshot');
        }

        return ['bytes' => $bytes, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'stat' => $before];
    }

    /** @param array{bytes:string,size:int,sha256:string,stat:array<string|int,int>} $snapshot */
    private function reverify(string $path, array $snapshot): void
    {
        $stat = $this->stat($path);
        $bytes = @file_get_contents($path);
        if (false === $stat || false === $bytes || !$this->sameMetadata($snapshot['stat'], $stat) || strlen($bytes) !== $snapshot['size'] || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))) {
            throw ModelInvocationWiringLoadException::failed('snapshot');
        }
    }

    /** @return array<string|int, int>|false */
    private function stat(string $path): array|false
    {
        return @stat($path);
    }

    /**
     * @param array<string|int, int> $before
     * @param array<string|int, int> $after
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
