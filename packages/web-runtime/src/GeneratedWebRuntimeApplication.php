<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Runtime\GeneratedApplicationBoundary;
use Kongono\Composition\Runtime\LoadedGeneratedWiring;
use Kongono\Web\Runtime\Exception\InvalidGeneratedWebRuntimeException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/** Owns one generated application boundary paired with its exact web root. */
#[Api(ApiStatus::Experimental)]
final class GeneratedWebRuntimeApplication
{
    private function __construct(
        private readonly GeneratedApplicationBoundary $boundary,
        private readonly GeneratedWebRuntimeRoot $root,
    ) {}

    private function __clone(): void {}

    public static function start(LoadedGeneratedWiring $wiring, object ...$inputs): self
    {
        $rootMethod = self::validateProfile($wiring);
        $boundary = $wiring->start(...$inputs);

        try {
            $root = $rootMethod->invoke($boundary);
            if (!$root instanceof GeneratedWebRuntimeRoot) {
                throw new InvalidGeneratedWebRuntimeException('root_access_failed');
            }
        } catch (Throwable $failure) {
            $boundary->shutdown($failure);

            throw $failure;
        }

        return new self($boundary, $root);
    }

    public function runtime(): WebRuntimeAdapter
    {
        if ($this->isRetired()) {
            throw new InvalidGeneratedWebRuntimeException('application_retired');
        }

        return $this->root->runtime();
    }

    public function shutdown(): void
    {
        $this->boundary->shutdown();
    }

    public function isRetired(): bool
    {
        return $this->boundary->isRetired() || $this->root->isRetired();
    }

    private static function validateProfile(LoadedGeneratedWiring $wiring): ReflectionMethod
    {
        $application = new ReflectionClass($wiring->applicationClass());
        $operation = new ReflectionClass($wiring->operationClass());
        $applicationRoots = self::rootMethods($application, 'applicationRoot');
        if (['applicationRoot1'] !== array_keys($applicationRoots)) {
            throw new InvalidGeneratedWebRuntimeException('application_root_count');
        }
        if ([] !== self::rootMethods($operation, 'operationRoot')) {
            throw new InvalidGeneratedWebRuntimeException('operation_root_present');
        }

        $root = $applicationRoots['applicationRoot1'];
        $returnType = $root->getReturnType();
        if (
            !$root->isPublic()
            || $root->isStatic()
            || 0 !== $root->getNumberOfParameters()
            || !$returnType instanceof ReflectionNamedType
            || $returnType->isBuiltin()
            || $returnType->allowsNull()
            || GeneratedWebRuntimeRoot::class !== $returnType->getName()
        ) {
            throw new InvalidGeneratedWebRuntimeException('application_root_shape');
        }

        return $root;
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $class
     * @return array<string, ReflectionMethod>
     */
    private static function rootMethods(ReflectionClass $class, string $prefix): array
    {
        $methods = [];
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($class->getName() === $method->getDeclaringClass()->getName() && 1 === preg_match('/^' . $prefix . '[1-9][0-9]*$/D', $method->getName())) {
                $methods[$method->getName()] = $method;
            }
        }
        ksort($methods);

        return $methods;
    }
}
