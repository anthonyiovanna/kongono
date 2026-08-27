<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\Composition\Runtime\GeneratedOperationInputApplicationBoundary;
use Kongono\Composition\Runtime\GeneratedOperationInputs;
use Kongono\Composition\Runtime\LoadedGeneratedWiring;
use Kongono\IdentityAccess\SecurityContext;
use Kongono\SecurityRuntime\Exception\InvalidGeneratedSecurityProfileException;
use Kongono\SecurityRuntime\Exception\SecurityRuntimeLifecycleException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/** Owns one exact generated application with one Security Context operation-input profile. */
#[Api(ApiStatus::Experimental)]
final class GeneratedSecurityApplication
{
    private function __construct(
        private readonly GeneratedOperationInputApplicationBoundary $boundary,
        private readonly ReflectionMethod $rootMethod,
    ) {}

    private function __clone(): void {}

    public static function start(LoadedGeneratedWiring $wiring, object ...$applicationInputs): self
    {
        $rootMethod = self::validateProfile($wiring);
        $boundary = $wiring->start(...$applicationInputs);
        if (!$boundary instanceof GeneratedOperationInputApplicationBoundary) {
            throw InvalidGeneratedSecurityProfileException::applicationBoundary();
        }

        return new self($boundary, $rootMethod);
    }

    public function beginAnonymousOperation(): GeneratedSecurityOperation
    {
        return $this->beginWithContext((new SecurityContextFactory())->anonymous());
    }

    public function beginAuthenticatedOperation(AuthenticationVerification $verification): GeneratedSecurityOperation
    {
        return $this->beginWithContext((new SecurityContextFactory())->authenticated($verification));
    }

    public function shutdown(?Throwable $primaryFailure = null): void
    {
        $this->boundary->shutdown($primaryFailure);
    }

    public function isRetired(): bool
    {
        return $this->boundary->isRetired();
    }

    private function beginWithContext(SecurityContext $context): GeneratedSecurityOperation
    {
        if ($this->boundary->isRetired()) {
            throw SecurityRuntimeLifecycleException::applicationRetired();
        }

        $operation = $this->boundary->beginOperationWithInputs(GeneratedOperationInputs::of($context));
        try {
            $root = $this->rootMethod->invoke($operation);
            if (!$root instanceof SecurityOperationRoot) {
                throw InvalidGeneratedSecurityProfileException::rootAccess();
            }
        } catch (Throwable $failure) {
            $operation->close($failure);

            throw $failure;
        }

        return new GeneratedSecurityOperation($operation, $root, $context);
    }

    private static function validateProfile(LoadedGeneratedWiring $wiring): ReflectionMethod
    {
        $application = new ReflectionClass($wiring->applicationClass());
        $operation = new ReflectionClass($wiring->operationClass());
        if (!$application->implementsInterface(GeneratedOperationInputApplicationBoundary::class)) {
            throw InvalidGeneratedSecurityProfileException::applicationBoundary();
        }
        if ([] !== self::rootMethods($application, 'applicationRoot')) {
            throw InvalidGeneratedSecurityProfileException::applicationRoot();
        }
        $operationRoots = self::rootMethods($operation, 'operationRoot');
        if (['operationRoot1'] !== array_keys($operationRoots)) {
            throw InvalidGeneratedSecurityProfileException::operationRootCount();
        }

        $metadataMethod = $application->getMethod('expectedOperationInputContracts');
        $metadataReturn = $metadataMethod->getReturnType();
        if (
            !$metadataMethod->isPublic()
            || !$metadataMethod->isStatic()
            || 0 !== $metadataMethod->getNumberOfParameters()
            || !$metadataReturn instanceof ReflectionNamedType
            || 'array' !== $metadataReturn->getName()
            || $metadataReturn->allowsNull()
        ) {
            throw InvalidGeneratedSecurityProfileException::operationInputs();
        }
        try {
            $operationInputContracts = $metadataMethod->invoke(null);
        } catch (Throwable) {
            throw InvalidGeneratedSecurityProfileException::operationInputs();
        }
        if ([SecurityContext::class] !== $operationInputContracts) {
            throw InvalidGeneratedSecurityProfileException::operationInputs();
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
            || !is_a($returnType->getName(), SecurityOperationRoot::class, true)
        ) {
            throw InvalidGeneratedSecurityProfileException::operationRootShape();
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
