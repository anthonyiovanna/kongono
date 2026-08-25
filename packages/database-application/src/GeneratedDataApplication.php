<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Runtime\GeneratedApplicationBoundary;
use Kongono\Composition\Runtime\LoadedGeneratedWiring;
use Kongono\DatabaseApplication\Exception\DataApplicationLifecycleException;
use Kongono\DatabaseApplication\Exception\InvalidGeneratedDataProfileException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/** Owns one generated application boundary with exactly one typed operation-root profile. */
#[Api(ApiStatus::Experimental)]
final class GeneratedDataApplication
{
    private function __construct(
        private readonly GeneratedApplicationBoundary $boundary,
        private readonly ReflectionMethod $rootMethod,
    ) {}

    private function __clone(): void {}

    public static function start(LoadedGeneratedWiring $wiring, object ...$inputs): self
    {
        $rootMethod = self::validateProfile($wiring);
        $boundary = $wiring->start(...$inputs);

        return new self($boundary, $rootMethod);
    }

    public function beginOperation(): GeneratedDataOperation
    {
        if ($this->boundary->isRetired()) {
            throw new DataApplicationLifecycleException('application_retired');
        }

        $operation = $this->boundary->beginOperation();
        try {
            $root = $this->rootMethod->invoke($operation);
            if (!$root instanceof DataOperationRoot) {
                throw new InvalidGeneratedDataProfileException('operation_root_access');
            }
        } catch (Throwable $failure) {
            $operation->close($failure);

            throw $failure;
        }

        return new GeneratedDataOperation($operation, $root);
    }

    public function shutdown(?Throwable $primaryFailure = null): void
    {
        $this->boundary->shutdown($primaryFailure);
    }

    public function isRetired(): bool
    {
        return $this->boundary->isRetired();
    }

    private static function validateProfile(LoadedGeneratedWiring $wiring): ReflectionMethod
    {
        $application = new ReflectionClass($wiring->applicationClass());
        $operation = new ReflectionClass($wiring->operationClass());
        if ([] !== self::rootMethods($application, 'applicationRoot')) {
            throw new InvalidGeneratedDataProfileException('application_root_present');
        }
        $operationRoots = self::rootMethods($operation, 'operationRoot');
        if (['operationRoot1'] !== array_keys($operationRoots)) {
            throw new InvalidGeneratedDataProfileException('operation_root_count');
        }

        $root = $operationRoots['operationRoot1'];
        $returnType = $root->getReturnType();
        if (
            !$root->isPublic()
            || $root->isStatic()
            || 0 !== $root->getNumberOfParameters()
            || !$returnType instanceof ReflectionNamedType
            || $returnType->isBuiltin()
            || $returnType->allowsNull()
            || !is_a($returnType->getName(), DataOperationRoot::class, true)
        ) {
            throw new InvalidGeneratedDataProfileException('operation_root_shape');
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
