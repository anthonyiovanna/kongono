<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Runtime;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressWiringLoadException;
use Kongono\NetworkEgressApplication\Generated\GeneratedNetworkEgressApplicationBoundary;
use Kongono\NetworkEgressApplication\Generated\NetworkEgressWiringGenerator;
use Kongono\NetworkEgressApplication\Generated\NetworkEgressWiringValidator;
use Kongono\NetworkEgressApplication\Internal\PathGuard;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactValidator;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

/** Snapshots, exact-current validates, loads, and reverifies one explicit trusted PHP file once. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressWiringLoader
{
    public function load(string $applicationRoot, NetworkEgressApplicationProfile $profile, string $generatedFile): LoadedNetworkEgressWiring
    {
        $path = PathGuard::loadFile($applicationRoot, $generatedFile);
        $snapshot = $this->snapshot($path);
        try {
            (new EgressPolicyArtifactValidator())->validateCurrent($profile->source(), $profile->artifact()->canonicalBytes());
            $wiring = (new NetworkEgressWiringValidator())->validateCurrent($profile, $snapshot['bytes']);
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw NetworkEgressWiringLoadException::failed('validation', $failure);
        }
        if (basename($path) !== 'kongono-network-egress-wiring-v1-' . $wiring->generatedWiringSha256() . '.php') {
            throw NetworkEgressWiringLoadException::failed('identity');
        }
        $class = $wiring->generatedClass();
        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false) || enum_exists($class, false)) {
            throw NetworkEgressWiringLoadException::failed('collision');
        }
        $this->reverify($path, $snapshot);
        try {
            $returned = require $path;
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw NetworkEgressWiringLoadException::failed('require', $failure);
        }
        if (1 !== $returned) {
            throw NetworkEgressWiringLoadException::failed('return');
        }
        if (PathGuard::loadFile($applicationRoot, $generatedFile) !== $path) {
            throw NetworkEgressWiringLoadException::failed('reverify');
        }
        $this->reverify($path, $snapshot);
        try {
            if (!class_exists($class, false)) {
                throw NetworkEgressWiringLoadException::failed('shape');
            }
            $reflection = new ReflectionClass($class);
            if ($reflection->getName() !== $class || !$reflection->isFinal() || !$reflection->implementsInterface(GeneratedNetworkEgressApplicationBoundary::class) || !$reflection->isInstantiable()) {
                throw NetworkEgressWiringLoadException::failed('shape');
            }
            $constructor = $reflection->getConstructor();
            if (null !== $constructor && 0 !== $constructor->getNumberOfRequiredParameters()) {
                throw NetworkEgressWiringLoadException::failed('shape');
            }
            $method = $reflection->getMethod('start');
            $parameters = $method->getParameters();
            if (3 !== count($parameters)) {
                throw NetworkEgressWiringLoadException::failed('shape');
            }
            $firstType = $parameters[0]->getType();
            $secondType = $parameters[1]->getType();
            $thirdType = $parameters[2]->getType();
            $returnType = $method->getReturnType();
            if (!$method->isPublic() || $method->isStatic()
                || !$firstType instanceof ReflectionNamedType || NetworkEgressApplicationProfile::class !== $firstType->getName()
                || !$secondType instanceof ReflectionNamedType || EgressResolver::class !== $secondType->getName()
                || !$thirdType instanceof ReflectionNamedType || EgressTransport::class !== $thirdType->getName()
                || !$returnType instanceof ReflectionNamedType || NetworkEgressApplication::class !== $returnType->getName()
            ) {
                throw NetworkEgressWiringLoadException::failed('shape');
            }
            $boundary = $reflection->newInstance();
        } catch (NetworkEgressWiringLoadException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw NetworkEgressWiringLoadException::failed('shape', $failure);
        }
        if (!$boundary instanceof GeneratedNetworkEgressApplicationBoundary) {
            throw NetworkEgressWiringLoadException::failed('shape');
        }

        return new LoadedNetworkEgressWiring($boundary, $profile);
    }

    /** @return array{bytes:string,size:int,sha256:string,stat:array<string|int,int>} */
    private function snapshot(string $path): array
    {
        $before = $this->stat($path);
        if (false === $before || $before['size'] > NetworkEgressWiringGenerator::MAX_GENERATED_BYTES) {
            throw NetworkEgressWiringLoadException::failed('snapshot');
        }
        $bytes = @file_get_contents($path);
        $after = $this->stat($path);
        if (false === $bytes || false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw NetworkEgressWiringLoadException::failed('snapshot');
        }

        return ['bytes' => $bytes, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'stat' => $before];
    }

    /** @param array{bytes:string,size:int,sha256:string,stat:array<string|int,int>} $snapshot */
    private function reverify(string $path, array $snapshot): void
    {
        $stat = $this->stat($path);
        $bytes = @file_get_contents($path);
        if (false === $stat || false === $bytes || !$this->sameMetadata($snapshot['stat'], $stat) || strlen($bytes) !== $snapshot['size'] || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))) {
            throw NetworkEgressWiringLoadException::failed('reverify');
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
