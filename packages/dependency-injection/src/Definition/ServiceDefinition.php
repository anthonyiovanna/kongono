<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Definition;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DependencyInjection\Contract\ResolverInterface;
use Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic;
use Kongono\DependencyInjection\Exception\InvalidDefinitionException;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;

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

        $validatedContract = self::validatedServiceContract($contract, 'Service contract', 'service_contract');
        $validatedImplementation = self::validatedImplementation($implementation, $validatedContract);
        $validatedDependencies = [];

        foreach ($detachedDependencies as $position => $dependency) {
            $validatedDependencies[] = self::validatedServiceContract(
                $dependency,
                sprintf('Dependency at position %d', $position),
                'dependency_contract',
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
    private static function validatedServiceContract(string $contract, string $label, string $subjectRole): string
    {
        if (!interface_exists($contract)) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ContractDoesNotExist,
                sprintf('%s "%s" must name an existing interface.', $label, $contract),
                ['subject_role' => $subjectRole, 'contract_name' => $contract],
            );
        }

        $reflection = new ReflectionClass($contract);

        if (!$reflection->isInterface() || $reflection->getName() !== $contract) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ContractNotCanonical,
                sprintf('%s "%s" is not a canonical interface name.', $label, $contract),
                ['subject_role' => $subjectRole, 'contract_name' => $contract],
            );
        }

        if (is_a($contract, ResolverInterface::class, true)) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ResolverContractForbidden,
                sprintf('%s "%s" must not be the resolver contract or its subinterface.', $label, $contract),
                ['subject_role' => $subjectRole, 'contract_name' => $contract],
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
                ['implementation_name' => $implementation],
            );
        }

        $reflection = new ReflectionClass($implementation);

        if ($reflection->getName() !== $implementation) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ImplementationNotCanonical,
                sprintf('Implementation "%s" is not a canonical class name.', $implementation),
                ['implementation_name' => $implementation],
            );
        }

        if (!$reflection->isInstantiable() || $reflection->isAnonymous()) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ImplementationNotInstantiable,
                sprintf('Implementation "%s" must be publicly instantiable.', $implementation),
                ['implementation_name' => $implementation],
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
                ['contract_name' => $contract, 'implementation_name' => $implementation],
            );
        }

        if (is_a($implementation, ResolverInterface::class, true)) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ImplementationExposesResolver,
                sprintf('Implementation "%s" must not implement the resolver contract.', $implementation),
                ['implementation_name' => $implementation],
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
            $contextValues = ['entry_count' => count($dependencies)];
            $firstUnexpectedKey = self::firstUnexpectedListKey($dependencies);

            if (null !== $firstUnexpectedKey) {
                $contextValues['first_unexpected_key'] = $firstUnexpectedKey;
            }

            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::DependenciesNotList,
                'Dependencies must be supplied as a zero-based consecutive list.',
                $contextValues,
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
                    [
                        'dependency_position' => $position,
                        'actual_value_kind' => match (gettype($dependency)) {
                            'NULL' => 'null',
                            'boolean' => 'boolean',
                            'integer' => 'integer',
                            'double' => 'float',
                            'string' => 'string',
                            'array' => 'array',
                            'object' => 'object',
                            'resource' => 'resource',
                            'resource (closed)' => 'closed_resource',
                            default => 'unknown',
                        },
                    ],
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
                $dependencyCount = count($dependencies);

                throw new InvalidDefinitionException(
                    DependencyInjectionDiagnostic::DependenciesWithoutConstructor,
                    sprintf(
                        'Implementation "%s" has no constructor but declares %d dependencies.',
                        $implementation,
                        $dependencyCount,
                    ),
                    ['implementation_name' => $implementation, 'dependency_count' => $dependencyCount],
                );
            }

            return;
        }

        if (!$constructor->isPublic()) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ConstructorNotPublic,
                sprintf('Implementation "%s" must have a public constructor.', $implementation),
                ['implementation_name' => $implementation],
            );
        }

        $parameters = $constructor->getParameters();
        $expectedParameterCount = count($dependencies);
        $actualParameterCount = count($parameters);

        if ($actualParameterCount !== $expectedParameterCount) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ConstructorArityMismatch,
                sprintf(
                    'Implementation "%s" constructor parameter count does not match its dependency count.',
                    $implementation,
                ),
                [
                    'implementation_name' => $implementation,
                    'expected_parameter_count' => $expectedParameterCount,
                    'actual_parameter_count' => $actualParameterCount,
                ],
            );
        }

        foreach ($parameters as $position => $parameter) {
            $isOptional = $parameter->isOptional();
            $isDefaulted = $parameter->isDefaultValueAvailable();
            $isVariadic = $parameter->isVariadic();
            $isPassedByReference = $parameter->isPassedByReference();

            if ($isOptional || $isDefaulted || $isVariadic || $isPassedByReference) {
                $unsupportedModes = [];

                if ($isOptional) {
                    $unsupportedModes[] = 'optional';
                }

                if ($isDefaulted) {
                    $unsupportedModes[] = 'defaulted';
                }

                if ($isVariadic) {
                    $unsupportedModes[] = 'variadic';
                }

                if ($isPassedByReference) {
                    $unsupportedModes[] = 'by_reference';
                }

                throw new InvalidDefinitionException(
                    DependencyInjectionDiagnostic::UnsupportedConstructorParameterMode,
                    sprintf(
                        'Implementation "%s" constructor parameter %d must be required, non-variadic, and passed by value.',
                        $implementation,
                        $position,
                    ),
                    [
                        'implementation_name' => $implementation,
                        'parameter_position' => $position,
                        'unsupported_mode' => implode('+', $unsupportedModes),
                    ],
                );
            }

            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin() || $type->allowsNull()) {
                $actualTypeShape = match (true) {
                    null === $type => 'missing',
                    $type instanceof ReflectionUnionType => 'union',
                    $type instanceof ReflectionIntersectionType => 'intersection',
                    $type instanceof ReflectionNamedType && $type->allowsNull() => 'nullable',
                    $type instanceof ReflectionNamedType && $type->isBuiltin() => 'builtin',
                    default => 'non_interface',
                };

                throw new InvalidDefinitionException(
                    DependencyInjectionDiagnostic::UnsupportedConstructorParameterType,
                    sprintf(
                        'Implementation "%s" constructor parameter %d must have one non-nullable interface type.',
                        $implementation,
                        $position,
                    ),
                    [
                        'implementation_name' => $implementation,
                        'parameter_position' => $position,
                        'actual_type_shape' => $actualTypeShape,
                    ],
                );
            }

            $actualContract = $type->getName();
            $expectedContract = $dependencies[$position];

            if ($actualContract !== $expectedContract) {
                throw new InvalidDefinitionException(
                    DependencyInjectionDiagnostic::ConstructorDependencyTypeMismatch,
                    sprintf(
                        'Implementation "%s" constructor parameter %d type "%s" does not match dependency "%s".',
                        $implementation,
                        $position,
                        $actualContract,
                        $expectedContract,
                    ),
                    [
                        'implementation_name' => $implementation,
                        'parameter_position' => $position,
                        'expected_contract' => $expectedContract,
                        'actual_contract' => $actualContract,
                    ],
                );
            }
        }
    }

    /** @param array<array-key, mixed> $values */
    private static function firstUnexpectedListKey(array $values): ?string
    {
        $expectedKey = 0;

        foreach ($values as $key => $_value) {
            if ($key !== $expectedKey) {
                return is_int($key) ? (string) $key : null;
            }

            ++$expectedKey;
        }

        return null;
    }
}
