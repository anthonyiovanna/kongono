<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DependencyInjection\Contract\ResolverInterface;
use Kongono\DependencyInjection\Definition\ServiceDefinition;
use Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic;
use Kongono\DependencyInjection\Exception\InvalidDefinitionException;
use Kongono\DependencyInjection\Exception\UnknownServiceException;
use ReflectionClass;

/**
 * Builds explicit service graphs with transient construction on every edge.
 *
 * The immutable resolver retains only validated definitions. It has no ambient
 * state, service-instance cache, Kernel lifecycle behavior, Graph integration,
 * I/O, logging, telemetry, or cleanup hooks. Consumer constructors own their
 * side effects and concurrency safety.
 */
#[Api(ApiStatus::Experimental)]
final readonly class DependencyResolver implements ResolverInterface
{
    /** @var array<class-string, ServiceDefinition> */
    private array $definitions;

    /**
     * @param list<ServiceDefinition> $definitions
     *
     * @throws InvalidDefinitionException
     */
    public function __construct(array $definitions)
    {
        $definitions = self::validateRuntimeDefinitions($definitions);

        usort($definitions, static fn(ServiceDefinition $left, ServiceDefinition $right): int => strcmp(
            $left->contract(),
            $right->contract(),
        ));

        $definitionMap = [];

        foreach ($definitions as $definition) {
            $contract = $definition->contract();

            if (isset($definitionMap[$contract])) {
                throw new InvalidDefinitionException(
                    DependencyInjectionDiagnostic::DuplicateContractDefinition,
                    sprintf('Duplicate definition for contract "%s".', $contract),
                    ['contract_name' => $contract],
                );
            }

            $definitionMap[$contract] = $definition;
        }

        foreach ($definitionMap as $owner => $definition) {
            foreach ($definition->dependencies() as $position => $dependency) {
                if (!isset($definitionMap[$dependency])) {
                    throw new InvalidDefinitionException(
                        DependencyInjectionDiagnostic::MissingDependencyDefinition,
                        sprintf(
                            'Definition "%s" dependency at position %d is missing contract "%s".',
                            $owner,
                            $position,
                            $dependency,
                        ),
                        ['owner_contract' => $owner, 'missing_dependency' => $dependency],
                    );
                }
            }
        }

        $visited = [];

        foreach (array_keys($definitionMap) as $contract) {
            self::assertAcyclic($contract, $definitionMap, [], $visited);
        }

        $this->definitions = $definitionMap;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $contract
     *
     * @return T
     *
     * @throws UnknownServiceException
     * @throws InvalidDefinitionException if a constructed object violates the validated definition invariant
     * @throws \Throwable when registered autoloading or the resolved implementation constructor fails
     */
    public function resolve(string $contract): object
    {
        if (!interface_exists($contract)) {
            throw new UnknownServiceException(
                DependencyInjectionDiagnostic::RequestedContractDoesNotExist,
                sprintf('Unknown service contract "%s".', $contract),
                ['requested_contract' => $contract],
            );
        }

        $reflection = new ReflectionClass($contract);

        if (!$reflection->isInterface() || $reflection->getName() !== $contract || is_a($contract, ResolverInterface::class, true)) {
            throw new UnknownServiceException(
                DependencyInjectionDiagnostic::RequestedContractUnsupported,
                sprintf('Unknown service contract "%s".', $contract),
                [
                    'requested_contract' => $contract,
                    'rejection_reason' => $reflection->getName() !== $contract ? 'noncanonical' : 'resolver_contract',
                ],
            );
        }

        if (!isset($this->definitions[$contract])) {
            throw new UnknownServiceException(
                DependencyInjectionDiagnostic::RequestedContractUndefined,
                sprintf('No definition exists for service contract "%s".', $contract),
                ['requested_contract' => $contract],
            );
        }

        $definition = $this->definitions[$contract];
        $object = $this->construct($definition);

        if (!$object instanceof $contract) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ConstructedContractInvariantViolation,
                sprintf(
                    'Constructed implementation violates validated service contract "%s".',
                    $contract,
                ),
                [
                    'contract_name' => $definition->contract(),
                    'implementation_name' => $definition->implementation(),
                ],
            );
        }

        return $object;
    }

    /**
     * @param array<class-string, ServiceDefinition> $definitions
     * @param array<string, true>                    $visiting
     * @param array<string, true>                    $visited
     */
    private static function assertAcyclic(string $contract, array $definitions, array $visiting, array &$visited): void
    {
        if (isset($visited[$contract])) {
            return;
        }

        if (isset($visiting[$contract])) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::DependencyCycle,
                sprintf('Dependency cycle detected at contract "%s".', $contract),
                ['cycle_contract' => $contract],
            );
        }

        $visiting[$contract] = true;

        foreach ($definitions[$contract]->dependencies() as $dependency) {
            self::assertAcyclic($dependency, $definitions, $visiting, $visited);
        }

        $visited[$contract] = true;
    }

    private function construct(ServiceDefinition $definition): object
    {
        $arguments = [];

        foreach ($definition->dependencies() as $dependency) {
            $arguments[] = $this->construct($this->definitions[$dependency]);
        }

        $implementation = $definition->implementation();
        $object = new $implementation(...$arguments);
        $contract = $definition->contract();

        if (!$object instanceof $contract) {
            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ConstructedContractInvariantViolation,
                sprintf(
                    'Constructed implementation violates validated service contract "%s".',
                    $contract,
                ),
                ['contract_name' => $contract, 'implementation_name' => $implementation],
            );
        }

        return $object;
    }

    /**
     * @param array<array-key, mixed> $definitions
     *
     * @return list<ServiceDefinition>
     */
    private static function validateRuntimeDefinitions(array $definitions): array
    {
        if (!array_is_list($definitions)) {
            $contextValues = ['entry_count' => count($definitions)];
            $firstUnexpectedKey = self::firstUnexpectedListKey($definitions);

            if (null !== $firstUnexpectedKey) {
                $contextValues['first_unexpected_key'] = $firstUnexpectedKey;
            }

            throw new InvalidDefinitionException(
                DependencyInjectionDiagnostic::DefinitionsNotList,
                'Definitions must be supplied as a zero-based consecutive list.',
                $contextValues,
            );
        }

        foreach ($definitions as $position => $definition) {
            if (!$definition instanceof ServiceDefinition) {
                throw new InvalidDefinitionException(
                    DependencyInjectionDiagnostic::InvalidDefinitionEntry,
                    sprintf(
                        'Definition list entry at position %d must be a ServiceDefinition.',
                        $position,
                    ),
                    [
                        'definition_position' => $position,
                        'actual_value_kind' => match (gettype($definition)) {
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
        }

        return $definitions;
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
