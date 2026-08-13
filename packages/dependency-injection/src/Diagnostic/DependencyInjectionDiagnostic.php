<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Diagnostic;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

/**
 * Owns the fixed metadata for deliberate Dependency Injection failures.
 *
 * The backed identifier is the stable semantic identity. Titles and remediation
 * remain human diagnostic prose and carry no transport or disclosure policy.
 */
#[Api(ApiStatus::Internal)]
enum DependencyInjectionDiagnostic: string
{
    case ContractDoesNotExist = 'KGN-DI-1001';
    case ContractNotCanonical = 'KGN-DI-1002';
    case ResolverContractForbidden = 'KGN-DI-1003';
    case ImplementationDoesNotExist = 'KGN-DI-1004';
    case ImplementationNotCanonical = 'KGN-DI-1005';
    case ImplementationNotInstantiable = 'KGN-DI-1006';
    case ImplementationDoesNotSatisfyContract = 'KGN-DI-1007';
    case ImplementationExposesResolver = 'KGN-DI-1008';
    case DependenciesNotList = 'KGN-DI-1009';
    case DependencyEntryNotString = 'KGN-DI-1010';
    case DependenciesWithoutConstructor = 'KGN-DI-1011';
    case ConstructorNotPublic = 'KGN-DI-1012';
    case ConstructorArityMismatch = 'KGN-DI-1013';
    case UnsupportedConstructorParameterMode = 'KGN-DI-1014';
    case UnsupportedConstructorParameterType = 'KGN-DI-1015';
    case ConstructorDependencyTypeMismatch = 'KGN-DI-1016';
    case DefinitionsNotList = 'KGN-DI-1017';
    case InvalidDefinitionEntry = 'KGN-DI-1018';
    case DuplicateContractDefinition = 'KGN-DI-1019';
    case MissingDependencyDefinition = 'KGN-DI-1020';
    case DependencyCycle = 'KGN-DI-1021';
    case RequestedContractDoesNotExist = 'KGN-DI-1022';
    case RequestedContractUnsupported = 'KGN-DI-1023';
    case RequestedContractUndefined = 'KGN-DI-1024';
    case ConstructedContractInvariantViolation = 'KGN-DI-1025';

    public function title(): string
    {
        return match ($this) {
            self::ContractDoesNotExist => 'Service or dependency contract does not name an existing interface',
            self::ContractNotCanonical => 'Service or dependency contract is not canonical',
            self::ResolverContractForbidden => 'Resolver contract or subinterface is used as a service/dependency contract',
            self::ImplementationDoesNotExist => 'Implementation does not name an existing class',
            self::ImplementationNotCanonical => 'Implementation class name is not canonical',
            self::ImplementationNotInstantiable => 'Implementation is not publicly instantiable',
            self::ImplementationDoesNotSatisfyContract => 'Implementation does not implement the declared service contract',
            self::ImplementationExposesResolver => 'Implementation exposes the Kongono resolver contract',
            self::DependenciesNotList => 'Dependencies input is not a zero-based consecutive list',
            self::DependencyEntryNotString => 'Dependency-list entry is not a canonical-name string',
            self::DependenciesWithoutConstructor => 'Implementation has no constructor but declares dependencies',
            self::ConstructorNotPublic => 'Implementation constructor is not public',
            self::ConstructorArityMismatch => 'Constructor parameter count differs from dependency count',
            self::UnsupportedConstructorParameterMode => 'Constructor parameter is optional, defaulted, variadic, or by-reference',
            self::UnsupportedConstructorParameterType => 'Constructor parameter lacks one non-nullable interface type',
            self::ConstructorDependencyTypeMismatch => 'Constructor parameter type differs from the dependency at that position',
            self::DefinitionsNotList => 'Definitions input is not a zero-based consecutive list',
            self::InvalidDefinitionEntry => 'Definition-list entry is not a `ServiceDefinition`',
            self::DuplicateContractDefinition => 'More than one definition uses the same contract',
            self::MissingDependencyDefinition => 'A declared dependency has no definition',
            self::DependencyCycle => 'Definition graph contains a dependency cycle',
            self::RequestedContractDoesNotExist => 'Requested service name does not resolve to an existing interface',
            self::RequestedContractUnsupported => 'Requested interface is noncanonical or is the resolver contract',
            self::RequestedContractUndefined => 'No definition exists for the requested service contract',
            self::ConstructedContractInvariantViolation => 'Constructed object violates a previously validated service contract',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::ContractDoesNotExist => 'Supply the exact declared interface name and ensure it can autoload',
            self::ContractNotCanonical => "Use the declaration's exact case and spelling without a leading slash or alias",
            self::ResolverContractForbidden => 'Remove resolver injection and keep resolution at the composition root',
            self::ImplementationDoesNotExist => 'Supply the exact autoloadable implementation class',
            self::ImplementationNotCanonical => "Use the declaration's exact class name without alias or case variation",
            self::ImplementationNotInstantiable => 'Use a named, concrete, publicly instantiable class',
            self::ImplementationDoesNotSatisfyContract => 'Select an implementation satisfying that exact interface',
            self::ImplementationExposesResolver => 'Remove resolver implementation/injection from the service graph',
            self::DependenciesNotList => 'Reindex dependencies as a PHP list',
            self::DependencyEntryNotString => 'Supply an interface-name string at the reported position',
            self::DependenciesWithoutConstructor => 'Remove the dependency entries or define a matching public constructor',
            self::ConstructorNotPublic => 'Make the constructor public or choose another implementation',
            self::ConstructorArityMismatch => 'Make the ordered definition and constructor arity identical',
            self::UnsupportedConstructorParameterMode => 'Use a required, non-defaulted, non-variadic, by-value parameter',
            self::UnsupportedConstructorParameterType => 'Use one non-nullable named interface type',
            self::ConstructorDependencyTypeMismatch => 'Align the exact interface type and ordered dependency',
            self::DefinitionsNotList => 'Reindex definitions as a PHP list',
            self::InvalidDefinitionEntry => 'Replace the reported entry with a validated definition',
            self::DuplicateContractDefinition => 'Retain exactly one definition for that contract',
            self::MissingDependencyDefinition => 'Add the missing contract definition or remove the dependency',
            self::DependencyCycle => 'Break the reported cycle through explicit design changes',
            self::RequestedContractDoesNotExist => 'Request the exact autoloadable interface name',
            self::RequestedContractUnsupported => 'Use the exact supported service interface and never resolve the resolver itself',
            self::RequestedContractUndefined => 'Register an explicit definition at the composition root',
            self::ConstructedContractInvariantViolation => 'Treat this as a framework invariant failure and inspect the definition/implementation boundary',
        };
    }
}
