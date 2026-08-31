<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionComposition\Artifact\GeneratedExtensionWiringArtifact;
use Kongono\ExtensionComposition\Generated\ExtensionWiringGenerator;
use Kongono\ExtensionComposition\Generated\ExtensionWiringValidator;
use Kongono\ExtensionComposition\Implementation\TrustedExtensionImplementation;
use Kongono\ExtensionComposition\Plan\ExtensionGrantPlan;
use Kongono\ExtensionComposition\Runtime\GeneratedExtensionApplicationBoundary;
use Kongono\ExtensionComposition\Runtime\GeneratedExtensionOperationBoundary;
use Kongono\ExtensionHost\Contract\ApplicationExtensionInterface;
use Kongono\ExtensionHost\Contract\OperationExtensionInterface;
use Kongono\ExtensionHost\Exception\InvalidGeneratedExtensionHostException;
use Kongono\ExtensionHost\Internal\PathGuard;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/** Loads one exact caller-selected local generated file after complete artifact, plan, path, and shape validation. */
#[Api(ApiStatus::Experimental)]
final class ExtensionHostLoader
{
    public function load(
        GeneratedExtensionWiringArtifact $artifact,
        ExtensionGrantPlan $plan,
        string $generatedPhpFile,
    ): LoadedExtensionWiring {
        if (!$artifact->grantPlanIdentity()->equals($plan->identity())) {
            throw InvalidGeneratedExtensionHostException::invalid('plan_identity_mismatch');
        }

        try {
            $validated = (new ExtensionWiringValidator())->validateStructure($artifact->canonicalBytes());
        } catch (Throwable $failure) {
            throw InvalidGeneratedExtensionHostException::invalid('artifact_invalid', $failure);
        }
        if (!$this->sameArtifact($artifact, $validated)) {
            throw InvalidGeneratedExtensionHostException::invalid('artifact_invalid');
        }

        $snapshot = PathGuard::readGeneratedFile($generatedPhpFile, ExtensionWiringGenerator::MAX_GENERATED_BYTES);
        if (basename($snapshot['absolute']) !== $artifact->wiringSha256() . '.php') {
            throw InvalidGeneratedExtensionHostException::invalid('generated_file_name');
        }
        if (!hash_equals($artifact->wiringSha256(), $snapshot['sha256']) || !hash_equals($artifact->canonicalBytes(), $snapshot['bytes'])) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_file_mismatch');
        }

        $applicationClass = $artifact->applicationClass();
        $operationClass = $artifact->operationClass();
        if (class_exists($applicationClass, false) || class_exists($operationClass, false)) {
            throw InvalidGeneratedExtensionHostException::invalid('class_collision');
        }

        PathGuard::reverify($snapshot);
        try {
            require $snapshot['absolute'];
        } catch (Throwable $failure) {
            throw InvalidGeneratedExtensionHostException::invalid('require_failed', $failure);
        }

        try {
            $this->validateLoadedShape($applicationClass, $operationClass, $artifact, $plan);
        } catch (InvalidGeneratedExtensionHostException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_shape', $failure);
        }

        /** @var class-string<GeneratedExtensionApplicationBoundary> $applicationClass */
        /** @var class-string<GeneratedExtensionOperationBoundary> $operationClass */
        return new LoadedExtensionWiring($artifact->wiringSha256(), $applicationClass, $operationClass);
    }

    private function sameArtifact(GeneratedExtensionWiringArtifact $expected, GeneratedExtensionWiringArtifact $actual): bool
    {
        return hash_equals($expected->wiringSha256(), $actual->wiringSha256())
            && hash_equals($expected->canonicalBytes(), $actual->canonicalBytes())
            && $expected->declarationSetIdentity()->equals($actual->declarationSetIdentity())
            && $expected->admissionIdentity()->equals($actual->admissionIdentity())
            && $expected->grantPlanIdentity()->equals($actual->grantPlanIdentity())
            && hash_equals($expected->generatorImplementationSha256(), $actual->generatorImplementationSha256())
            && $expected->generatedNamespace() === $actual->generatedNamespace()
            && $expected->applicationClass() === $actual->applicationClass()
            && $expected->operationClass() === $actual->operationClass()
            && $expected->implementationCount() === $actual->implementationCount()
            && $expected->decisionCount() === $actual->decisionCount()
            && $expected->applicationProviderCount() === $actual->applicationProviderCount()
            && $expected->operationProviderCount() === $actual->operationProviderCount();
    }

    private function validateLoadedShape(
        string $applicationClass,
        string $operationClass,
        GeneratedExtensionWiringArtifact $artifact,
        ExtensionGrantPlan $plan,
    ): void {
        if (!class_exists($applicationClass, false) || !class_exists($operationClass, false)) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_shape');
        }
        $application = new ReflectionClass($applicationClass);
        $operation = new ReflectionClass($operationClass);
        if (
            !$application->isFinal()
            || !$operation->isFinal()
            || !$application->implementsInterface(GeneratedExtensionApplicationBoundary::class)
            || !$operation->implementsInterface(GeneratedExtensionOperationBoundary::class)
        ) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_shape');
        }

        $this->hostedMethod($application->getMethod('startHosted'), true, 'array', $applicationClass);
        $this->hostedMethod($application->getMethod('beginHostedOperation'), false, 'array', GeneratedExtensionOperationBoundary::class);
        $this->listMethod($application->getMethod('applicationExtensions'));
        $this->listMethod($operation->getMethod('operationExtensions'));
        if (count($application->getMethod('start')->getParameters()) !== $artifact->applicationProviderCount()) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_shape');
        }
        if (count($application->getMethod('beginOperation')->getParameters()) !== $artifact->operationProviderCount()) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_shape');
        }

        $applicationImplementations = $this->accessorImplementations($application, 'applicationExtension', ApplicationExtensionInterface::class);
        $operationImplementations = $this->accessorImplementations($operation, 'operationExtension', OperationExtensionInterface::class);
        $loadedImplementations = [...$applicationImplementations, ...$operationImplementations];
        if (count($loadedImplementations) !== $artifact->implementationCount() || count($loadedImplementations) !== count(array_unique($loadedImplementations))) {
            throw InvalidGeneratedExtensionHostException::invalid('implementation_mismatch');
        }
        $plannedImplementations = array_map(
            static fn(TrustedExtensionImplementation $implementation): string => $implementation->className(),
            $plan->implementations(),
        );
        sort($loadedImplementations, SORT_STRING);
        sort($plannedImplementations, SORT_STRING);
        if ($loadedImplementations !== $plannedImplementations) {
            throw InvalidGeneratedExtensionHostException::invalid('implementation_mismatch');
        }
    }

    private function hostedMethod(ReflectionMethod $method, bool $static, string $parameterType, string $returnType): void
    {
        $parameters = $method->getParameters();
        $parameter = $parameters[0] ?? null;
        $type = $parameter?->getType();
        $return = $method->getReturnType();
        if (
            !$method->isPublic()
            || $method->isStatic() !== $static
            || 1 !== count($parameters)
            || !$type instanceof ReflectionNamedType
            || $type->getName() !== $parameterType
            || $type->allowsNull()
            || !$return instanceof ReflectionNamedType
            || $return->getName() !== $returnType
            || $return->allowsNull()
        ) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_shape');
        }
    }

    private function listMethod(ReflectionMethod $method): void
    {
        $return = $method->getReturnType();
        if (!$method->isPublic() || $method->isStatic() || [] !== $method->getParameters() || !$return instanceof ReflectionNamedType || 'array' !== $return->getName() || $return->allowsNull()) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_shape');
        }
    }

    /**
     * @param ReflectionClass<object> $class
     * @param class-string             $lifecycleInterface
     *
     * @return list<class-string>
     */
    private function accessorImplementations(ReflectionClass $class, string $prefix, string $lifecycleInterface): array
    {
        $accessors = [];
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (1 !== preg_match('/\A' . $prefix . '([1-9][0-9]*)\z/D', $method->getName(), $matches)) {
                continue;
            }
            $return = $method->getReturnType();
            if ($method->isStatic() || [] !== $method->getParameters() || !$return instanceof ReflectionNamedType || $return->isBuiltin() || $return->allowsNull()) {
                throw InvalidGeneratedExtensionHostException::invalid('generated_shape');
            }
            $implementation = $return->getName();
            if (!class_exists($implementation) || !is_subclass_of($implementation, $lifecycleInterface)) {
                throw InvalidGeneratedExtensionHostException::invalid('lifecycle_mismatch');
            }
            $accessors[(int) $matches[1]] = $implementation;
        }
        if ([] !== $accessors) {
            ksort($accessors, SORT_NUMERIC);
            if (array_keys($accessors) !== range(1, count($accessors))) {
                throw InvalidGeneratedExtensionHostException::invalid('generated_shape');
            }
        }

        return array_values($accessors);
    }
}
