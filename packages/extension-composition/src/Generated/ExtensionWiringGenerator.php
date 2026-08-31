<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionAdmission\Admission\AdmittedExtensionGraphInput;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionComposition\Artifact\GeneratedExtensionWiringArtifact;
use Kongono\ExtensionComposition\Exception\InvalidExtensionGrantPlanException;
use Kongono\ExtensionComposition\Grant\CapabilityDecision;
use Kongono\ExtensionComposition\Grant\CapabilityDisposition;
use Kongono\ExtensionComposition\Grant\CapabilityProviderLifetime;
use Kongono\ExtensionComposition\Implementation\TrustedExtensionImplementation;
use Kongono\ExtensionComposition\Internal\CanonicalJson;
use Kongono\ExtensionComposition\Internal\ExtensionGrantPlanValidator;
use Kongono\ExtensionComposition\Internal\GeneratedExtensionWiringEmitter;
use Kongono\ExtensionComposition\Internal\GeneratedExtensionWiringImplementation;
use Kongono\ExtensionComposition\Plan\ExtensionGrantPlan;
use Kongono\ExtensionContract\Capability\CapabilityRequest;
use Kongono\ExtensionContract\Capability\CapabilityRequirement;
use Kongono\ExtensionContract\Declaration\ExtensionDeclaration;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

/** Generates inert typed PHP only after exact admission, request, lifetime, and Reflection validation. */
#[Api(ApiStatus::Experimental)]
final class ExtensionWiringGenerator
{
    public const int MAX_GENERATED_BYTES = 4_194_304;

    /** @param array<array-key, mixed> $declarations */
    public function generate(
        array $declarations,
        AdmittedExtensionGraphInput $admission,
        ExtensionGrantPlan $plan,
    ): GeneratedExtensionWiringArtifact {
        $declarations = $this->declarations($declarations);
        try {
            $declarationArtifact = (new ExtensionDeclarationArtifactBuilder())->build($declarations);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidExtensionGrantPlanException::invalid('declaration_set_mismatch', previous: $failure);
        }
        if (!$declarationArtifact->declarationSetIdentity()->equals($admission->declarationSetIdentity())) {
            throw InvalidExtensionGrantPlanException::invalid('declaration_set_mismatch');
        }
        if (!$plan->admissionIdentity()->equals($admission->admissionIdentity())) {
            throw InvalidExtensionGrantPlanException::invalid('admission_identity_mismatch');
        }

        /** @var array<string, ExtensionDeclaration> $declarationsByIdentity */
        $declarationsByIdentity = [];
        foreach ($declarations as $declaration) {
            $declarationsByIdentity[$declaration->identity()->value()] = $declaration;
        }
        /** @var array<string, TrustedExtensionImplementation> $implementationsByIdentity */
        $implementationsByIdentity = [];
        foreach ($plan->implementations() as $implementation) {
            $implementationsByIdentity[$implementation->identity()->value()] = $implementation;
        }
        /** @var array<string, list<CapabilityDecision>> $decisionsByExtension */
        $decisionsByExtension = [];
        foreach ($plan->decisions() as $decision) {
            $decisionsByExtension[$decision->extensionIdentity()->value()][] = $decision;
        }

        $admitted = $admission->admittedExtensions();
        $previousIdentity = null;
        $validatedExtensions = [];
        $consumedDecisions = 0;
        foreach ($admitted as $position => $extension) {
            $identity = $extension->identity()->value();
            if (null !== $previousIdentity && strcmp($previousIdentity, $identity) >= 0) {
                throw InvalidExtensionGrantPlanException::invalid('admission_order', count($admitted), $position);
            }
            $previousIdentity = $identity;
            $declaration = $declarationsByIdentity[$identity] ?? null;
            if (null === $declaration || !$declaration->version()->equals($extension->version())) {
                throw InvalidExtensionGrantPlanException::invalid('declaration_missing', count($admitted), $position);
            }
            if (ExtensionCategory::TrustedInProcess !== $declaration->category()) {
                throw InvalidExtensionGrantPlanException::invalid('unsupported_extension_category', count($admitted), $position);
            }
            $implementation = $implementationsByIdentity[$identity] ?? null;
            if (null === $implementation) {
                throw InvalidExtensionGrantPlanException::invalid('implementation_missing', count($admitted), $position);
            }
            if (!$implementation->version()->equals($extension->version())) {
                throw InvalidExtensionGrantPlanException::invalid('implementation_version_mismatch', count($admitted), $position);
            }
            $extensionDecisions = $decisionsByExtension[$identity] ?? [];
            $matched = $this->matchRequests($declaration, $extensionDecisions);
            $consumedDecisions += count($matched);
            $constructorOrder = $this->validateImplementation($declaration, $implementation, $matched);
            $validatedExtensions[] = [
                'class' => $implementation->className(),
                'lifecycle' => $declaration->lifecycle()->value,
                'decisions' => $constructorOrder,
            ];
        }
        if (count($implementationsByIdentity) !== count($admitted)) {
            throw InvalidExtensionGrantPlanException::invalid('implementation_extra', count($implementationsByIdentity));
        }
        if ($consumedDecisions !== count($plan->decisions())) {
            throw InvalidExtensionGrantPlanException::invalid('decision_extra', count($plan->decisions()));
        }

        $applicationProviderCount = 0;
        $operationProviderCount = 0;
        /** @var array<string, array{name:string,lifetime:string}> $providerMap */
        $providerMap = [];
        foreach ($plan->decisions() as $decision) {
            if (CapabilityDisposition::Denied === $decision->disposition()) {
                continue;
            }
            $key = $decision->extensionIdentity()->value() . "\0" . $decision->constructorParameter();
            if (CapabilityProviderLifetime::Application === $decision->providerLifetime()) {
                ++$applicationProviderCount;
                $providerMap[$key] = ['name' => 'applicationProvider' . $applicationProviderCount, 'lifetime' => 'application'];
            } else {
                ++$operationProviderCount;
                $providerMap[$key] = ['name' => 'operationProvider' . $operationProviderCount, 'lifetime' => 'operation'];
            }
        }
        foreach ($validatedExtensions as $extensionIndex => &$extension) {
            foreach ($extension['decisions'] as $decisionIndex => &$decision) {
                if ('denied' === $decision['disposition']) {
                    continue;
                }
                $key = $decision['extension_identity'] . "\0" . $decision['parameter'];
                $decision['provider_name'] = $providerMap[$key]['name'];
            }
            unset($decision);
        }
        unset($extension);

        $implementationSha256 = GeneratedExtensionWiringImplementation::sha256();
        $inputIdentity = hash('sha256', CanonicalJson::encode([
            'schema' => 'kongono.generated-extension-wiring-input',
            'version' => 1,
            'declaration_set_identity' => $admission->declarationSetIdentity()->value(),
            'admission_identity' => $admission->admissionIdentity()->value(),
            'grant_plan_identity' => $plan->identity()->value(),
            'generator_implementation_sha256' => $implementationSha256,
        ]));
        $namespace = 'KongonoGenerated\\Extension\\V1\\A' . $inputIdentity;
        $bytes = (new GeneratedExtensionWiringEmitter())->emit(
            $namespace,
            $admission->declarationSetIdentity()->value(),
            $admission->admissionIdentity()->value(),
            $plan->identity()->value(),
            $implementationSha256,
            $validatedExtensions,
            count($plan->decisions()),
            $applicationProviderCount,
            $operationProviderCount,
        );
        if (strlen($bytes) > self::MAX_GENERATED_BYTES) {
            throw InvalidExtensionGrantPlanException::invalid('generated_bytes_limit');
        }

        return (new ExtensionWiringValidator())->validateStructure($bytes);
    }

    /**
     * @param array<array-key, mixed> $declarations
     * @return list<ExtensionDeclaration>
     */
    private function declarations(array $declarations): array
    {
        if (!array_is_list($declarations)) {
            throw InvalidExtensionGrantPlanException::invalid('declaration_set_mismatch', count($declarations));
        }
        foreach ($declarations as $position => $declaration) {
            if (!$declaration instanceof ExtensionDeclaration) {
                throw InvalidExtensionGrantPlanException::invalid('declaration_set_mismatch', count($declarations), $position);
            }
        }
        /** @var list<ExtensionDeclaration> $declarations */
        return $declarations;
    }

    /**
     * @param list<CapabilityDecision> $decisions
     * @return list<CapabilityDecision>
     */
    private function matchRequests(ExtensionDeclaration $declaration, array $decisions): array
    {
        $byRequest = [];
        foreach ($decisions as $decision) {
            if (!$decision->extensionVersion()->equals($declaration->version())) {
                throw InvalidExtensionGrantPlanException::invalid('decision_extension_version');
            }
            $byRequest[ExtensionGrantPlanValidator::decisionKey($decision)] = $decision;
        }
        $matched = [];
        foreach ($declaration->capabilityRequests() as $request) {
            $key = $this->requestKey($declaration, $request);
            $decision = $byRequest[$key] ?? null;
            if (null === $decision) {
                throw InvalidExtensionGrantPlanException::invalid('decision_missing');
            }
            if (CapabilityRequirement::Required === $request->requirement() && CapabilityDisposition::Denied === $decision->disposition()) {
                throw InvalidExtensionGrantPlanException::invalid('required_capability_denied');
            }
            if (ExtensionLifecycle::Application === $declaration->lifecycle() && CapabilityProviderLifetime::Operation === $decision->providerLifetime()) {
                throw InvalidExtensionGrantPlanException::invalid('application_reaches_operation_provider');
            }
            $matched[] = $decision;
            unset($byRequest[$key]);
        }
        if ([] !== $byRequest) {
            throw InvalidExtensionGrantPlanException::invalid('decision_extra', count($byRequest));
        }

        return $matched;
    }

    private function requestKey(ExtensionDeclaration $declaration, CapabilityRequest $request): string
    {
        return implode("\0", [$declaration->identity()->value(), $request->identity()->value(), (string) $request->identity()->version(), $request->purpose()->value(), $request->requirement()->value]);
    }

    /**
     * @param list<CapabilityDecision> $decisions
     * @return list<array<string, string>>
     */
    private function validateImplementation(ExtensionDeclaration $declaration, TrustedExtensionImplementation $implementation, array $decisions): array
    {
        $class = $implementation->className();
        try {
            $implementationExists = class_exists($class);
        } catch (Throwable $failure) {
            throw InvalidExtensionGrantPlanException::invalid('implementation_not_found', previous: $failure);
        }
        if (!$implementationExists) {
            throw InvalidExtensionGrantPlanException::invalid('implementation_not_found');
        }
        $reflection = new ReflectionClass($class);
        if ($reflection->getName() !== $class) {
            throw InvalidExtensionGrantPlanException::invalid('implementation_symbol_case');
        }
        if ($reflection->isInternal() || $reflection->isAnonymous() || !$reflection->isInstantiable() || $reflection->isInterface() || $reflection->isTrait() || $reflection->isEnum()) {
            throw InvalidExtensionGrantPlanException::invalid('implementation_not_instantiable');
        }
        $constructor = $reflection->getConstructor();
        if (null === $constructor) {
            if ([] !== $decisions) {
                throw InvalidExtensionGrantPlanException::invalid('constructor_parameter_missing');
            }
            return [];
        }
        if (!$constructor->isPublic() || $constructor->isStatic()) {
            throw InvalidExtensionGrantPlanException::invalid('implementation_constructor');
        }
        $byParameter = [];
        foreach ($decisions as $decision) {
            $byParameter[$decision->constructorParameter()] = $decision;
        }
        $ordered = [];
        foreach ($constructor->getParameters() as $position => $parameter) {
            $decision = $byParameter[$parameter->getName()] ?? null;
            if (null === $decision) {
                throw InvalidExtensionGrantPlanException::invalid('constructor_parameter_extra', count($constructor->getParameters()), $position);
            }
            if ($parameter->isVariadic() || $parameter->isPassedByReference() || $parameter->isPromoted()) {
                throw InvalidExtensionGrantPlanException::invalid('constructor_parameter_shape', count($constructor->getParameters()), $position);
            }
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin() || $type->allowsNull()) {
                throw InvalidExtensionGrantPlanException::invalid('constructor_parameter_shape', count($constructor->getParameters()), $position);
            }
            $provider = $decision->providerContract();
            try {
                $providerExists = class_exists($provider) || interface_exists($provider);
            } catch (Throwable $failure) {
                throw InvalidExtensionGrantPlanException::invalid('provider_not_found', position: $position, previous: $failure);
            }
            if (!$providerExists) {
                throw InvalidExtensionGrantPlanException::invalid('provider_not_found', position: $position);
            }
            $providerReflection = new ReflectionClass($provider);
            if ($providerReflection->getName() !== $provider || $type->getName() !== $provider) {
                throw InvalidExtensionGrantPlanException::invalid('provider_contract_mismatch', position: $position);
            }
            if ($providerReflection->isInternal() || $providerReflection->isTrait() || $providerReflection->isEnum()) {
                throw InvalidExtensionGrantPlanException::invalid('provider_contract_shape', position: $position);
            }
            if (CapabilityDisposition::Denied === $decision->disposition() && (!$parameter->isOptional() || !$parameter->isDefaultValueAvailable())) {
                throw InvalidExtensionGrantPlanException::invalid('denied_parameter_required', position: $position);
            }
            $providerLifetime = $decision->providerLifetime();
            if (CapabilityDisposition::Granted === $decision->disposition() && null === $providerLifetime) {
                throw InvalidExtensionGrantPlanException::invalid('invalid_provider_lifetime', position: $position);
            }
            $providerLifetimeValue = '';
            if (null !== $providerLifetime) {
                $providerLifetimeValue = $providerLifetime->value;
            }
            $ordered[] = [
                'extension_identity' => $declaration->identity()->value(),
                'parameter' => $parameter->getName(),
                'provider' => $provider,
                'disposition' => $decision->disposition()->value,
                'provider_lifetime' => $providerLifetimeValue,
            ];
            unset($byParameter[$parameter->getName()]);
        }
        if ([] !== $byParameter) {
            throw InvalidExtensionGrantPlanException::invalid('constructor_parameter_missing', count($byParameter));
        }

        return $ordered;
    }
}
