<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Definition;

use Kongono\DependencyInjection\Contract\ResolverInterface;
use Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic;
use Kongono\DependencyInjection\Exception\InvalidDefinitionException;
use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Defines one canonical interface contract and its explicit constructor graph.
 *
 * Construction validates all supplied names and the exact positional signature
 * before publishing immutable state. Reflection checks declarations only; it
 * never discovers or infers dependencies.
 */
#[Api(ApiStatus::Experimental)]
final readonly class ServiceDefinition
{
    /** @var class-string */
    private string $contract;

    /** @var class-string */
    private string $implementation;

    /** @var list<class-string> */
    private array $dependencies;

    /**
     * @param class-string       $contract
     * @param class-string       $implementation
     * @param list<class-string> $dependencies
     *
     * @throws InvalidDefinitionException
     * @throws \Throwable when registered autoloading fails
     */
    public function __construct(string $contract, string $implementation, array $dependencies)
    {
        $detachedDependencies = self::detachRuntimeDependencies($dependencies);

        $validatedContract = self::validatedServiceContract($contract, 'Service contract');
        $validatedImplementation = self::validatedImplementation($implementation, $validatedContract);
        $validatedDependencies = [];

        foreach ($detachedDependencies as $position => $dependency) {
            $validatedDependencies[] = self::validatedServiceContract(
                $dependency,
                sprintf('Dependency at position %d', $position),
            );
        }

        self::assertConstructorSignature($validatedImplementation, $validatedDependencies);

        $this->contract = $validatedContract;
        $this->implementation = $implementation;
        $this->dependencies = $validatedDependencies;
    }

    /** @return class-string */
    public function contract(): string
    {
        return $this->contract;
    }

    /** @return class-string */
    public function implementation(): string
    {
        return $this->implementation;
    }

    /** @return list<class-string> */
    public function dependencies(): array
    {
        return $this->dependencies;
    }

    /** @return class-string */
    private static function validatedServiceContract(string $contract, string $label): string
    {
        if (!interface_exists($contract)) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ContractDoesNotExist,
                sprintf('%s "%s" must name an existing interface.', $label, $contract),
            );
        }

        $reflection = new ReflectionClass($contract);

        if (!$reflection->isInterface() || $reflection->getName() !== $contract) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ContractNotCanonical,
                sprintf('%s "%s" is not a canonical interface name.', $label, $contract),
            );
        }

        if (is_a($contract, ResolverInterface::class, true)) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ResolverContractForbidden,
                sprintf('%s "%s" must not be the resolver contract or its subinterface.', $label, $contract),
            );
        }

        return $contract;
    }

    /** @return class-string */
    private static function validatedImplementation(string $implementation, string $contract): string
    {
        if (!class_exists($implementation)) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ImplementationDoesNotExist,
                sprintf(
                    'Implementation "%s" must name an existing concrete class.',
                    $implementation,
                ),
            );
        }

        $reflection = new ReflectionClass($implementation);

        if ($reflection->getName() !== $implementation) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ImplementationNotCanonical,
                sprintf('Implementation "%s" is not a canonical class name.', $implementation),
            );
        }

        if (!$reflection->isInstantiable() || $reflection->isAnonymous()) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ImplementationNotInstantiable,
                sprintf('Implementation "%s" must be publicly instantiable.', $implementation),
            );
        }

        if (!$reflection->implementsInterface($contract)) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ImplementationDoesNotSatisfyContract,
                sprintf(
                    'Implementation "%s" must implement service contract "%s".',
                    $implementation,
                    $contract,
                ),
            );
        }

        if (is_a($implementation, ResolverInterface::class, true)) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ImplementationExposesResolver,
                sprintf('Implementation "%s" must not implement the resolver contract.', $implementation),
            );
        }

        return $implementation;
    }

    /**
     * @param array<array-key, mixed> $dependencies
     *
     * @return list<string>
     */
    private static function detachRuntimeDependencies(array $dependencies): array
    {
        if (!array_is_list($dependencies)) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::DependenciesNotList,
                'Dependencies must be supplied as a zero-based consecutive list.',
            );
        }

        $detachedDependencies = [];

        foreach ($dependencies as $position => $dependency) {
            if (!is_string($dependency)) {
                throw new InvalidDefinitionException(
                    DependencyInjectionDiagnostic::DependencyEntryNotString,
                    sprintf(
                        'Dependency at position %d must be a canonical interface name string.',
                        $position,
                    ),
                );
            }

            $detachedDependencies[] = $dependency;
        }

        return $detachedDependencies;
    }

    /**
     * @param class-string       $implementation
     * @param list<class-string> $dependencies
     */
    private static function assertConstructorSignature(string $implementation, array $dependencies): void
    {
        $constructor = (new ReflectionClass($implementation))->getConstructor();

        if (null === $constructor) {
            if ([] !== $dependencies) {
                throw new InvalidDefinitionException(
                    DependencyInjectionDiagnostic::DependenciesWithoutConstructor,
                    sprintf(
                        'Implementation "%s" has no constructor but declares %d dependencies.',
                        $implementation,
                        count($dependencies),
                    ),
                );
            }

            return;
        }

        if (!$constructor->isPublic()) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ConstructorNotPublic,
                sprintf('Implementation "%s" must have a public constructor.', $implementation),
            );
        }

        $parameters = $constructor->getParameters();

        if (count($parameters) !== count($dependencies)) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ConstructorArityMismatch,
                sprintf(
                    'Implementation "%s" constructor parameter count does not match its dependency count.',
                    $implementation,
                ),
            );
        }

        foreach ($parameters as $position => $parameter) {
            if ($parameter->isOptional() || $parameter->isDefaultValueAvailable() || $parameter->isVariadic() || $parameter->isPassedByReference()) {
                throw new InvalidDefinitionException(
                    DependencyInjectionDiagnostic::UnsupportedConstructorParameterMode,
                    sprintf(
                        'Implementation "%s" constructor parameter %d must be required, non-variadic, and passed by value.',
                        $implementation,
                        $position,
                    ),
                );
            }

            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin() || $type->allowsNull()) {
                throw new InvalidDefinitionException(
                    DependencyInjectionDiagnostic::UnsupportedConstructorParameterType,
                    sprintf(
                        'Implementation "%s" constructor parameter %d must have one non-nullable interface type.',
                        $implementation,
                        $position,
                    ),
                );
            }

            if ($type->getName() !== $dependencies[$position]) {
                throw new InvalidDefinitionException(
                    DependencyInjectionDiagnostic::ConstructorDependencyTypeMismatch,
                    sprintf(
                        'Implementation "%s" constructor parameter %d type "%s" does not match dependency "%s".',
                        $implementation,
                        $position,
                        $type->getName(),
                        $dependencies[$position],
                    ),
                );
            }
        }
    }
}
