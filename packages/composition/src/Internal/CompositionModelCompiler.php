<?php

declare(strict_types=1);

namespace Kongono\Composition\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Composition\Exception\InvalidCompositionSourceException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

/** Validates and canonicalizes the complete version-1 declaration model. */
#[Api(ApiStatus::Internal)]
final class CompositionModelCompiler
{
    private const MAX_INPUTS = 256;
    private const MAX_SERVICES = 4096;
    private const MAX_BINDINGS = 4096;
    private const MAX_ROOTS = 256;
    private const MAX_ARGUMENTS = 128;
    private const MAX_EDGES = 32768;
    private const MAX_SYMBOL_BYTES = 255;
    private const MAX_NAME_BYTES = 128;

    /** @var array<string, ReflectionClass<object>> */
    private array $symbols = [];

    /**
     * @param array<string, mixed> $source
     *
     * @return array{model:array<string, mixed>,sources:list<array{path:string,bytes:int,sha256:string}>,snapshots:list<array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>}>}
     */
    public function compile(string $resolvedRoot, array $source): array
    {
        $inputs = $this->declarationList($source['inputs'] ?? null, 'inputs', self::MAX_INPUTS);
        $services = $this->declarationList($source['services'] ?? null, 'services', self::MAX_SERVICES);
        $bindings = $this->declarationList($source['bindings'] ?? null, 'bindings', self::MAX_BINDINGS);
        $roots = $this->declarationList($source['roots'] ?? null, 'roots', self::MAX_ROOTS);
        if ([] === $roots) {
            $this->invalidDeclaration('roots_empty');
        }

        $canonicalInputs = $this->shapeInputs($inputs);
        $canonicalServices = $this->shapeServices($services);
        $canonicalBindings = $this->shapeBindings($bindings);
        $canonicalRoots = $this->shapeRoots($roots);

        $canonicalInputs = $this->sortRecords($canonicalInputs, ['contract']);
        $canonicalServices = $this->sortRecords($canonicalServices, ['class']);
        $canonicalBindings = $this->sortRecords($canonicalBindings, ['contract', 'service']);
        $canonicalRoots = $this->sortRecords($canonicalRoots, ['kind', 'contract']);

        /** @var list<string> $allSymbols */
        $allSymbols = [];
        foreach ($canonicalInputs as $record) {
            $allSymbols[] = $record['contract'];
        }
        foreach ($canonicalServices as $record) {
            $allSymbols[] = $record['class'];
            foreach ($record['arguments'] as $argument) {
                if (isset($argument['service']) && is_string($argument['service'])) {
                    $allSymbols[] = $argument['service'];
                }
                if (isset($argument['input']) && is_string($argument['input'])) {
                    $allSymbols[] = $argument['input'];
                }
            }
        }
        foreach ($canonicalBindings as $record) {
            $allSymbols[] = $record['contract'];
            $allSymbols[] = $record['service'];
        }
        foreach ($canonicalRoots as $record) {
            $allSymbols[] = $record['contract'];
        }
        $allSymbols = array_values(array_unique($allSymbols));
        sort($allSymbols, SORT_STRING);
        foreach ($allSymbols as $symbol) {
            $this->reflectSymbol($symbol);
        }

        /** @var array<string, array{class:string,lifetime:string,arguments:list<array<string,mixed>>,cleanup:null|string}> $serviceByClass */
        $serviceByClass = [];
        foreach ($canonicalServices as $service) {
            $class = $service['class'];
            if (isset($serviceByClass[$class])) {
                $this->invalidDeclaration('duplicate_service');
            }
            $reflection = $this->symbols[$class];
            if ($reflection->isInterface() || $reflection->isTrait() || $reflection->isEnum() || $reflection->isAbstract() || !$reflection->isInstantiable() || $reflection->isInternal()) {
                $this->invalidDeclaration('service_not_instantiable');
            }
            $serviceByClass[$class] = $service;
        }

        /** @var array<string, true> $inputSet */
        $inputSet = [];
        foreach ($canonicalInputs as $input) {
            $contract = $input['contract'];
            if (isset($inputSet[$contract])) {
                $this->invalidDeclaration('duplicate_input');
            }
            $reflection = $this->symbols[$contract];
            if ((!$reflection->isInterface() && !$reflection->isInstantiable()) || $reflection->isTrait() || $reflection->isEnum() || $reflection->isInternal()) {
                $this->invalidDeclaration('invalid_input_contract');
            }
            $inputSet[$contract] = true;
        }

        /** @var array<string, string> $bindingMap */
        $bindingMap = [];
        foreach ($canonicalBindings as $binding) {
            $contract = $binding['contract'];
            $service = $binding['service'];
            if (isset($bindingMap[$contract])) {
                $this->invalidDeclaration('duplicate_binding');
            }
            if (!isset($serviceByClass[$service]) || isset($serviceByClass[$contract]) && $contract !== $service) {
                $this->invalidGraph('invalid_binding_target');
            }
            if (!is_a($service, $contract, true)) {
                $this->invalidGraph('binding_contract_mismatch');
            }
            $bindingMap[$contract] = $service;
        }

        /** @var array<string, true> $rootSet */
        $rootSet = [];
        foreach ($canonicalRoots as $root) {
            $identity = $root['kind'] . "\0" . $root['contract'];
            if (isset($rootSet[$identity])) {
                $this->invalidDeclaration('duplicate_root');
            }
            $rootSet[$identity] = true;
        }

        /** @var array<string, list<string>> $edges */
        $edges = [];
        foreach ($canonicalServices as $service) {
            $class = $service['class'];
            $reflection = $this->symbols[$class];
            $constructor = $reflection->getConstructor();
            $arguments = $service['arguments'];
            if (null === $constructor) {
                if ([] !== $arguments) {
                    $this->invalidDeclaration('arguments_without_constructor');
                }
                $edges[$class] = [];
            } else {
                if (!$constructor->isPublic() || count($constructor->getParameters()) !== count($arguments)) {
                    $this->invalidDeclaration('constructor_contract');
                }
                $edges[$class] = $this->validateArguments($constructor, $arguments, $serviceByClass, $bindingMap, $inputSet);
            }
            $this->validateCleanup($reflection, $service['cleanup']);
        }

        foreach ($canonicalRoots as $root) {
            $resolved = $this->resolveService($root['contract'], $serviceByClass, $bindingMap);
            if (null === $resolved || $serviceByClass[$resolved]['lifetime'] !== $root['kind']) {
                $this->invalidGraph('invalid_root');
            }
        }
        $this->validateCycles($edges);
        $this->validateReachability($canonicalRoots, $serviceByClass, $bindingMap, $edges);

        $snapshotsByPath = [];
        foreach ($this->symbols as $reflection) {
            $file = $reflection->getFileName();
            if (false === $file) {
                throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidProvenance, 'symbol_without_source_file');
            }
            $snapshot = PathGuard::readAbsoluteDeclaringFile($resolvedRoot, $file);
            $snapshotsByPath[$snapshot['path']] = $snapshot;
        }
        ksort($snapshotsByPath, SORT_STRING);
        $sources = [];
        foreach ($snapshotsByPath as $snapshot) {
            $sources[] = ['path' => $snapshot['path'], 'bytes' => $snapshot['size'], 'sha256' => $snapshot['sha256']];
        }

        return [
            'model' => [
                'schema' => 'kongono.composition',
                'version' => 1,
                'inputs' => $canonicalInputs,
                'services' => $canonicalServices,
                'bindings' => $canonicalBindings,
                'roots' => $canonicalRoots,
            ],
            'sources' => $sources,
            'snapshots' => array_values($snapshotsByPath),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function declarationList(mixed $value, string $name, int $maximum): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->invalidDeclaration($name . '_not_list');
        }
        if (count($value) > $maximum) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::CompositionLimitExceeded, $name . '_count');
        }
        foreach ($value as $record) {
            if (!is_array($record) || array_is_list($record)) {
                $this->invalidDeclaration($name . '_record_shape');
            }
        }

        /** @var list<array<string, mixed>> $value */
        return $value;
    }

    /** @param list<array<string, mixed>> $records
     * @return list<array{contract:string}>
     */
    private function shapeInputs(array $records): array
    {
        $result = [];
        foreach ($records as $record) {
            $this->assertKeys($record, ['contract'], 'input_shape');
            $result[] = ['contract' => $this->symbol($record['contract'] ?? null)];
        }

        return $result;
    }

    /** @param list<array<string, mixed>> $records
     * @return list<array{class:string,lifetime:string,arguments:list<array<string,mixed>>,cleanup:null|string}>
     */
    private function shapeServices(array $records): array
    {
        $result = [];
        $edges = 0;
        foreach ($records as $record) {
            $this->assertKeys($record, ['arguments', 'class', 'cleanup', 'lifetime'], 'service_shape');
            $class = $this->symbol($record['class'] ?? null);
            $lifetime = $record['lifetime'] ?? null;
            if (!is_string($lifetime) || !in_array($lifetime, ['application', 'operation', 'transient'], true)) {
                $this->invalidDeclaration('invalid_lifetime');
            }
            $cleanup = $record['cleanup'] ?? null;
            if (null !== $cleanup) {
                $cleanup = $this->name($cleanup, 'cleanup_name');
            }
            $argumentsValue = $record['arguments'] ?? null;
            if (!is_array($argumentsValue) || !array_is_list($argumentsValue)) {
                $this->invalidDeclaration('arguments_not_list');
            }
            if (count($argumentsValue) > self::MAX_ARGUMENTS) {
                throw new InvalidCompositionSourceException(CompositionDiagnostic::CompositionLimitExceeded, 'arguments_count');
            }
            $arguments = [];
            foreach ($argumentsValue as $argument) {
                if (!is_array($argument) || array_is_list($argument) || 2 !== count($argument) || !array_key_exists('parameter', $argument)) {
                    $this->invalidDeclaration('argument_shape');
                }
                $parameter = $this->name($argument['parameter'], 'parameter_name');
                $forms = array_values(array_intersect(['service', 'input', 'default'], array_keys($argument)));
                if (1 !== count($forms)) {
                    $this->invalidDeclaration('argument_source_form');
                }
                $form = $forms[0];
                if ('default' === $form) {
                    if (true !== $argument['default']) {
                        $this->invalidDeclaration('default_value');
                    }
                    $arguments[] = ['parameter' => $parameter, 'default' => true];
                } else {
                    $edges += 1;
                    $arguments[] = ['parameter' => $parameter, $form => $this->symbol($argument[$form])];
                }
            }
            $result[] = ['class' => $class, 'lifetime' => $lifetime, 'arguments' => $arguments, 'cleanup' => $cleanup];
        }
        if ($edges > self::MAX_EDGES) {
            throw new InvalidCompositionSourceException(CompositionDiagnostic::CompositionLimitExceeded, 'dependency_edges');
        }

        return $result;
    }

    /** @param list<array<string, mixed>> $records
     * @return list<array{contract:string,service:string}>
     */
    private function shapeBindings(array $records): array
    {
        $result = [];
        foreach ($records as $record) {
            $this->assertKeys($record, ['contract', 'service'], 'binding_shape');
            $result[] = ['contract' => $this->symbol($record['contract'] ?? null), 'service' => $this->symbol($record['service'] ?? null)];
        }

        return $result;
    }

    /** @param list<array<string, mixed>> $records
     * @return list<array{kind:string,contract:string}>
     */
    private function shapeRoots(array $records): array
    {
        $result = [];
        foreach ($records as $record) {
            $this->assertKeys($record, ['contract', 'kind'], 'root_shape');
            $kind = $record['kind'] ?? null;
            if (!is_string($kind) || !in_array($kind, ['application', 'operation'], true)) {
                $this->invalidDeclaration('root_kind');
            }
            $result[] = ['kind' => $kind, 'contract' => $this->symbol($record['contract'] ?? null)];
        }

        return $result;
    }

    /**
     * @template T of array<string, mixed>
     *
     * @param list<T>      $records
     * @param list<string> $keys
     *
     * @return list<T>
     */
    private function sortRecords(array $records, array $keys): array
    {
        usort($records, static function (array $left, array $right) use ($keys): int {
            foreach ($keys as $key) {
                $leftValue = $left[$key] ?? null;
                $rightValue = $right[$key] ?? null;
                if (!is_string($leftValue) || !is_string($rightValue)) {
                    return 0;
                }
                $comparison = strcmp($leftValue, $rightValue);
                if (0 !== $comparison) {
                    return $comparison;
                }
            }

            return 0;
        });

        return $records;
    }

    private function symbol(mixed $value): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > self::MAX_SYMBOL_BYTES || str_starts_with($value, '\\') || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $value)) {
            $this->invalidDeclaration('invalid_symbol');
        }

        return $value;
    }

    private function name(mixed $value, string $violation): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > self::MAX_NAME_BYTES || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value)) {
            $this->invalidDeclaration($violation);
        }

        return $value;
    }

    /** @param array<string, mixed> $record
     * @param list<string> $keys
     */
    private function assertKeys(array $record, array $keys, string $violation): void
    {
        $actual = array_keys($record);
        sort($actual, SORT_STRING);
        sort($keys, SORT_STRING);
        if ($actual !== $keys) {
            $this->invalidDeclaration($violation);
        }
    }

    /** @return ReflectionClass<object> */
    private function reflectSymbol(string $symbol): ReflectionClass
    {
        if (isset($this->symbols[$symbol])) {
            return $this->symbols[$symbol];
        }
        try {
            if (!class_exists($symbol) && !interface_exists($symbol)) {
                $this->invalidDeclaration('symbol_not_found');
            }
            $reflection = new ReflectionClass($symbol);
        } catch (InvalidCompositionSourceException $exception) {
            throw $exception;
        } catch (Throwable) {
            $this->invalidDeclaration('symbol_autoload_failed');
        }
        if ($reflection->getName() !== $symbol) {
            $this->invalidDeclaration('symbol_not_canonical');
        }
        $this->symbols[$symbol] = $reflection;

        return $reflection;
    }

    /**
     * @param list<array<string, mixed>> $arguments
     * @param array<string, array{class:string,lifetime:string,arguments:list<array<string,mixed>>,cleanup:null|string}> $serviceByClass
     * @param array<string, string> $bindingMap
     * @param array<string, true> $inputSet
     * @return list<string>
     */
    private function validateArguments(ReflectionMethod $constructor, array $arguments, array $serviceByClass, array $bindingMap, array $inputSet): array
    {
        $edges = [];
        foreach ($constructor->getParameters() as $position => $parameter) {
            $argument = $arguments[$position];
            if ($argument['parameter'] !== $parameter->getName()) {
                $this->invalidDeclaration('parameter_name_mismatch');
            }
            $typeName = $this->parameterType($parameter);
            if (isset($argument['default'])) {
                if (!$parameter->isDefaultValueAvailable()) {
                    $this->invalidDeclaration('default_unavailable');
                }
                continue;
            }
            if (isset($argument['input'])) {
                $contract = $argument['input'];
                if (!is_string($contract) || !isset($inputSet[$contract]) || $contract !== $typeName) {
                    $this->invalidGraph('input_resolution');
                }
                continue;
            }
            if (isset($argument['service'])) {
                $contract = $argument['service'];
                if (!is_string($contract) || $contract !== $typeName) {
                    $this->invalidDeclaration('constructor_type_mismatch');
                }
                $resolved = $this->resolveService($contract, $serviceByClass, $bindingMap);
                if (null === $resolved) {
                    $this->invalidGraph('missing_service');
                }
                $edges[] = $resolved;
                continue;
            }
            $this->invalidDeclaration('argument_source_form');
        }

        return $edges;
    }

    private function parameterType(ReflectionParameter $parameter): string
    {
        if ($parameter->isPassedByReference() || $parameter->isVariadic()) {
            $this->invalidDeclaration('constructor_parameter_mode');
        }
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin() || $type->allowsNull() || in_array($type->getName(), ['self', 'parent', 'static'], true)) {
            $this->invalidDeclaration('constructor_parameter_type');
        }
        $name = $type->getName();
        $this->reflectSymbol($name);

        return $name;
    }

    /**
     * @param array<string, array{class:string,lifetime:string,arguments:list<array<string,mixed>>,cleanup:null|string}> $serviceByClass
     * @param array<string, string> $bindingMap
     */
    private function resolveService(string $contract, array $serviceByClass, array $bindingMap): ?string
    {
        if (isset($serviceByClass[$contract])) {
            return $contract;
        }

        return $bindingMap[$contract] ?? null;
    }

    /** @param ReflectionClass<object> $service */
    private function validateCleanup(ReflectionClass $service, ?string $cleanup): void
    {
        if (null === $cleanup) {
            return;
        }
        if (!$service->hasMethod($cleanup)) {
            $this->invalidDeclaration('cleanup_missing');
        }
        $method = $service->getMethod($cleanup);
        $returnType = $method->getReturnType();
        if (!$method->isPublic() || $method->isStatic() || 0 !== $method->getNumberOfParameters() || !$returnType instanceof ReflectionNamedType || 'void' !== $returnType->getName() || $method->getDeclaringClass()->getName() !== $service->getName() || str_starts_with($cleanup, '__')) {
            $this->invalidDeclaration('cleanup_signature');
        }
    }

    /** @param array<string, list<string>> $edges */
    private function validateCycles(array $edges): void
    {
        $state = [];
        $classes = array_keys($edges);
        sort($classes, SORT_STRING);
        foreach ($classes as $class) {
            $this->visitCycle($class, $edges, $state);
        }
    }

    /** @param array<string, list<string>> $edges
     * @param array<string, int> $state
     */
    private function visitCycle(string $class, array $edges, array &$state): void
    {
        if (2 === ($state[$class] ?? 0)) {
            return;
        }
        if (1 === ($state[$class] ?? 0)) {
            $this->invalidGraph('dependency_cycle');
        }
        $state[$class] = 1;
        foreach ($edges[$class] ?? [] as $target) {
            $this->visitCycle($target, $edges, $state);
        }
        $state[$class] = 2;
    }

    /**
     * @param list<array{kind:string,contract:string}> $roots
     * @param array<string, array{class:string,lifetime:string,arguments:list<array<string,mixed>>,cleanup:null|string}> $serviceByClass
     * @param array<string, string> $bindingMap
     * @param array<string, list<string>> $edges
     */
    private function validateReachability(array $roots, array $serviceByClass, array $bindingMap, array $edges): void
    {
        foreach ($roots as $root) {
            $service = $this->resolveService($root['contract'], $serviceByClass, $bindingMap);
            if (null === $service) {
                $this->invalidGraph('invalid_root');
            }
            $seen = [];
            $this->walkReachability($service, $root['kind'], $serviceByClass, $edges, $seen);
        }
    }

    /**
     * @param array<string, array{class:string,lifetime:string,arguments:list<array<string,mixed>>,cleanup:null|string}> $serviceByClass
     * @param array<string, list<string>> $edges
     * @param array<string, true> $seen
     */
    private function walkReachability(string $service, string $boundary, array $serviceByClass, array $edges, array &$seen): void
    {
        if (isset($seen[$service])) {
            return;
        }
        $seen[$service] = true;
        if ('application' === $boundary && 'operation' === $serviceByClass[$service]['lifetime']) {
            $this->invalidGraph('application_reaches_operation');
        }
        foreach ($edges[$service] ?? [] as $target) {
            $this->walkReachability($target, $boundary, $serviceByClass, $edges, $seen);
        }
    }

    private function invalidDeclaration(string $violation): never
    {
        throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidDeclaration, $violation);
    }

    private function invalidGraph(string $violation): never
    {
        throw new InvalidCompositionSourceException(CompositionDiagnostic::InvalidCompositionGraph, $violation);
    }
}
