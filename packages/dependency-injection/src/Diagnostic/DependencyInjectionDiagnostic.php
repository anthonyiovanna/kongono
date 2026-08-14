<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

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

    public function contextSchema(): DiagnosticContextSchema
    {
        return match ($this) {
            self::ContractDoesNotExist => new DiagnosticContextSchema(
                'Validate service contract',
                'Contract must name an existing interface',
                [
                    new DiagnosticFieldSchema('subject_role', 'Subject role', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('contract_name', 'Contract name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                ],
            ),
            self::ContractNotCanonical => new DiagnosticContextSchema(
                'Validate service contract',
                'Contract must use its canonical declared name',
                [
                    new DiagnosticFieldSchema('subject_role', 'Subject role', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('contract_name', 'Contract name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                ],
            ),
            self::ResolverContractForbidden => new DiagnosticContextSchema(
                'Validate service contract',
                'Resolver contract cannot enter the service graph',
                [
                    new DiagnosticFieldSchema('subject_role', 'Subject role', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('contract_name', 'Contract name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                ],
            ),
            self::ImplementationDoesNotExist => new DiagnosticContextSchema(
                'Validate implementation',
                'Implementation must name an existing class',
                [new DiagnosticFieldSchema('implementation_name', 'Implementation name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected)],
            ),
            self::ImplementationNotCanonical => new DiagnosticContextSchema(
                'Validate implementation',
                'Implementation must use its canonical declared name',
                [new DiagnosticFieldSchema('implementation_name', 'Implementation name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected)],
            ),
            self::ImplementationNotInstantiable => new DiagnosticContextSchema(
                'Validate implementation',
                'Implementation must be publicly instantiable',
                [new DiagnosticFieldSchema('implementation_name', 'Implementation name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected)],
            ),
            self::ImplementationDoesNotSatisfyContract => new DiagnosticContextSchema(
                'Validate implementation',
                'Implementation must satisfy the declared contract',
                [
                    new DiagnosticFieldSchema('contract_name', 'Contract name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                    new DiagnosticFieldSchema('implementation_name', 'Implementation name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                ],
            ),
            self::ImplementationExposesResolver => new DiagnosticContextSchema(
                'Validate implementation',
                'Implementation must not expose the resolver contract',
                [new DiagnosticFieldSchema('implementation_name', 'Implementation name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected)],
            ),
            self::DependenciesNotList => new DiagnosticContextSchema(
                'Validate dependency list',
                'Dependencies must be a PHP list',
                [
                    new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('first_unexpected_key', 'First unexpected key', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
                ],
            ),
            self::DependencyEntryNotString => new DiagnosticContextSchema(
                'Validate dependency entry',
                'Dependency entry must be a contract-name string',
                [
                    new DiagnosticFieldSchema('dependency_position', 'Dependency position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('actual_value_kind', 'Actual value kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            self::DependenciesWithoutConstructor => new DiagnosticContextSchema(
                'Validate constructor dependencies',
                'Dependency declarations require a constructor',
                [
                    new DiagnosticFieldSchema('implementation_name', 'Implementation name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                    new DiagnosticFieldSchema('dependency_count', 'Dependency count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                ],
            ),
            self::ConstructorNotPublic => new DiagnosticContextSchema(
                'Validate constructor',
                'Constructor must be public',
                [new DiagnosticFieldSchema('implementation_name', 'Implementation name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected)],
            ),
            self::ConstructorArityMismatch => new DiagnosticContextSchema(
                'Validate constructor arity',
                'Constructor and definition arity must match exactly',
                [
                    new DiagnosticFieldSchema('implementation_name', 'Implementation name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                    new DiagnosticFieldSchema('expected_parameter_count', 'Expected parameter count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('actual_parameter_count', 'Actual parameter count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                ],
            ),
            self::UnsupportedConstructorParameterMode => new DiagnosticContextSchema(
                'Validate constructor parameter mode',
                'Parameter must be required, by-value, non-variadic, and non-defaulted',
                [
                    new DiagnosticFieldSchema('implementation_name', 'Implementation name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                    new DiagnosticFieldSchema('parameter_position', 'Parameter position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('unsupported_mode', 'Unsupported mode', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            self::UnsupportedConstructorParameterType => new DiagnosticContextSchema(
                'Validate constructor parameter type',
                'Parameter must have one non-nullable interface type',
                [
                    new DiagnosticFieldSchema('implementation_name', 'Implementation name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                    new DiagnosticFieldSchema('parameter_position', 'Parameter position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('actual_type_shape', 'Actual type shape', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            self::ConstructorDependencyTypeMismatch => new DiagnosticContextSchema(
                'Validate constructor dependency type',
                'Parameter type must equal the ordered dependency contract',
                [
                    new DiagnosticFieldSchema('implementation_name', 'Implementation name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                    new DiagnosticFieldSchema('parameter_position', 'Parameter position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('expected_contract', 'Expected contract', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                    new DiagnosticFieldSchema('actual_contract', 'Actual contract', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                ],
            ),
            self::DefinitionsNotList => new DiagnosticContextSchema(
                'Validate definition list',
                'Definitions must be a PHP list',
                [
                    new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('first_unexpected_key', 'First unexpected key', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
                ],
            ),
            self::InvalidDefinitionEntry => new DiagnosticContextSchema(
                'Validate definition entry',
                'Every definitions-list entry must be a ServiceDefinition',
                [
                    new DiagnosticFieldSchema('definition_position', 'Definition position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('actual_value_kind', 'Actual value kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            self::DuplicateContractDefinition => new DiagnosticContextSchema(
                'Validate definition uniqueness',
                'Each service contract may have one definition',
                [new DiagnosticFieldSchema('contract_name', 'Contract name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected)],
            ),
            self::MissingDependencyDefinition => new DiagnosticContextSchema(
                'Validate definition graph completeness',
                'Every dependency contract must have a definition',
                [
                    new DiagnosticFieldSchema('owner_contract', 'Owner contract', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                    new DiagnosticFieldSchema('missing_dependency', 'Missing dependency', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                ],
            ),
            self::DependencyCycle => new DiagnosticContextSchema(
                'Validate definition graph acyclicity',
                'Definition graph must be acyclic',
                [new DiagnosticFieldSchema('cycle_contract', 'Cycle contract', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected)],
            ),
            self::RequestedContractDoesNotExist => new DiagnosticContextSchema(
                'Resolve service',
                'Requested service must name an existing interface',
                [new DiagnosticFieldSchema('requested_contract', 'Requested contract', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected)],
            ),
            self::RequestedContractUnsupported => new DiagnosticContextSchema(
                'Resolve service',
                'Requested contract must be canonical and not the resolver contract',
                [
                    new DiagnosticFieldSchema('requested_contract', 'Requested contract', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                    new DiagnosticFieldSchema('rejection_reason', 'Rejection reason', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            self::RequestedContractUndefined => new DiagnosticContextSchema(
                'Resolve service',
                'Requested service must have a definition',
                [new DiagnosticFieldSchema('requested_contract', 'Requested contract', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected)],
            ),
            self::ConstructedContractInvariantViolation => new DiagnosticContextSchema(
                'Construct service',
                'Constructed object must satisfy the validated contract',
                [
                    new DiagnosticFieldSchema('contract_name', 'Contract name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                    new DiagnosticFieldSchema('implementation_name', 'Implementation name', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                ],
            ),
        };
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

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
