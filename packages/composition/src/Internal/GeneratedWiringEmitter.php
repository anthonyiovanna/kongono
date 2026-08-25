<?php

declare(strict_types=1);

namespace Kongono\Composition\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Composition\Exception\InvalidGeneratedWiringException;

/**
 * Emits canonical ordinary PHP from the already validated detached composition model.
 *
 * @phpstan-type Argument array{parameter:string,service?:string,input?:string,default?:true}
 * @phpstan-type Service array{class:string,lifetime:string,arguments:list<Argument>,cleanup:string|null}
 * @phpstan-type Root array{kind:string,contract:string}
 */
#[Api(ApiStatus::Internal)]
final class GeneratedWiringEmitter
{
    /** @var array<string, Service> */
    private array $services = [];
    /** @var array<string, string> */
    private array $bindings = [];
    /** @var array<string, int> */
    private array $inputIndexes = [];
    /** @var list<Root> */
    private array $applicationRoots = [];
    /** @var list<Root> */
    private array $operationRoots = [];
    /** @var array<string, int> */
    private array $applicationProperties = [];
    /** @var array<string, true> */
    private array $retainedApplication = [];
    /** @var array<string, int> */
    private array $operationProperties = [];
    /** @var array<string, true> */
    private array $retainedOperation = [];
    /** @var array<string, true> */
    private array $operationInputs = [];
    /** @var array<string, true> */
    private array $applicationInputs = [];
    /** @var array<string, string> */
    private array $builtApplication = [];
    /** @var array<string, string> */
    private array $builtOperation = [];
    /** @var list<string> */
    private array $constructionLines = [];
    private int $transientCounter = 0;
    private string $scope = 'application';

    /** @param array<string, mixed> $model */
    public function emit(
        array $model,
        string $compositionArtifactSha256,
        string $modelSha256,
        string $implementationSha256,
    ): string {
        $this->loadModel($model);
        $namespace = 'KongonoGenerated\\Composition\\V1\\A' . $compositionArtifactSha256;
        $applicationClass = $namespace . '\\ApplicationAssembly';
        $operationClass = $namespace . '\\OperationAssembly';
        $metadata = json_encode([
            'schema' => 'kongono.generated-wiring',
            'version' => 1,
            'composition_artifact_sha256' => $compositionArtifactSha256,
            'model_sha256' => $modelSha256,
            'generator_implementation_sha256' => $implementationSha256,
            'generated_namespace' => $namespace,
            'application_class' => $applicationClass,
            'operation_class' => $operationClass,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            '// ' . $metadata,
            '',
            'namespace ' . $namespace . ';',
            '',
            'use Kongono\\Composition\\Diagnostic\\CompositionDiagnostic;',
            'use Kongono\\Composition\\Exception\\RetiredCompositionBoundaryException;',
            'use Kongono\\Composition\\Runtime\\GeneratedApplicationBoundary;',
            'use Kongono\\Composition\\Runtime\\GeneratedApplicationState;',
            'use Kongono\\Composition\\Runtime\\GeneratedOperationBoundary;',
            'use Kongono\\Composition\\Runtime\\LifecycleFailureComposer;',
            'use Throwable;',
            '',
            ...$this->operationClassLines(),
            '',
            ...$this->applicationClassLines(),
        ];

        return implode("\n", $lines) . "\n";
    }

    /** @param array<string, mixed> $model */
    private function loadModel(array $model): void
    {
        $inputs = $this->records($model['inputs'] ?? null);
        foreach ($inputs as $index => $input) {
            $contract = $this->string($input['contract'] ?? null);
            $this->inputIndexes[$contract] = $index + 1;
        }
        foreach ($this->records($model['services'] ?? null) as $record) {
            $class = $this->string($record['class'] ?? null);
            $arguments = [];
            foreach ($this->records($record['arguments'] ?? null) as $argument) {
                $typed = ['parameter' => $this->string($argument['parameter'] ?? null)];
                if (isset($argument['service'])) {
                    $typed['service'] = $this->string($argument['service']);
                } elseif (isset($argument['input'])) {
                    $typed['input'] = $this->string($argument['input']);
                } elseif (true === ($argument['default'] ?? null)) {
                    $typed['default'] = true;
                } else {
                    $this->invalid('model_argument');
                }
                $arguments[] = $typed;
            }
            $cleanup = $record['cleanup'] ?? null;
            if (null !== $cleanup && !is_string($cleanup)) {
                $this->invalid('model_cleanup');
            }
            $this->services[$class] = [
                'class' => $class,
                'lifetime' => $this->string($record['lifetime'] ?? null),
                'arguments' => $arguments,
                'cleanup' => $cleanup,
            ];
        }
        foreach ($this->records($model['bindings'] ?? null) as $binding) {
            $this->bindings[$this->string($binding['contract'] ?? null)] = $this->string($binding['service'] ?? null);
        }
        foreach ($this->records($model['roots'] ?? null) as $record) {
            $root = ['kind' => $this->string($record['kind'] ?? null), 'contract' => $this->string($record['contract'] ?? null)];
            if ('application' === $root['kind']) {
                $this->applicationRoots[] = $root;
            } else {
                $this->operationRoots[] = $root;
            }
        }
        foreach ($this->applicationRoots as $root) {
            $seen = [];
            $this->collectApplicationInputs($this->resolve($root['contract']), true, $seen);
        }
        foreach ($this->operationRoots as $root) {
            $seen = [];
            $this->collectApplicationInputs($this->resolve($root['contract']), false, $seen);
        }

        $applicationNeeded = [];
        foreach ([...$this->applicationRoots, ...$this->operationRoots] as $root) {
            $seen = [];
            $this->collectApplicationServices($this->resolve($root['contract']), $applicationNeeded, $seen);
        }
        $applicationClasses = array_keys($applicationNeeded);
        sort($applicationClasses, SORT_STRING);
        foreach ($applicationClasses as $index => $class) {
            $this->applicationProperties[$class] = $index + 1;
        }

        $operationNeeded = [];
        foreach ($this->operationRoots as $root) {
            $seen = [];
            $this->collectOperationState($this->resolve($root['contract']), $operationNeeded, $seen);
        }
        $operationClasses = array_keys($operationNeeded);
        sort($operationClasses, SORT_STRING);
        foreach ($operationClasses as $index => $class) {
            $this->operationProperties[$class] = $index + 1;
        }
        foreach ($this->applicationRoots as $root) {
            $this->retainedApplication[$this->resolve($root['contract'])] = true;
        }
        foreach ($this->operationRoots as $root) {
            $this->retainedOperation[$this->resolve($root['contract'])] = true;
        }
        foreach ($applicationClasses as $class) {
            if ($this->operationUsesApplication($class)) {
                $this->retainedApplication[$class] = true;
            }
        }
    }

    /**
     * @param array<string, true> $needed
     * @param array<string, true> $seen
     */
    private function collectApplicationServices(string $class, array &$needed, array &$seen): void
    {
        if (isset($seen[$class])) {
            return;
        }
        $seen[$class] = true;
        $service = $this->service($class);
        if ('application' === $service['lifetime']) {
            $needed[$class] = true;
        }
        foreach ($service['arguments'] as $argument) {
            if (isset($argument['service'])) {
                $this->collectApplicationServices($this->resolve($argument['service']), $needed, $seen);
            }
        }
    }

    /** @param array<string, true> $seen */
    private function collectApplicationInputs(string $class, bool $applicationScope, array &$seen): void
    {
        $scopeKey = ($applicationScope ? 'application:' : 'operation:') . $class;
        if (isset($seen[$scopeKey])) {
            return;
        }
        $seen[$scopeKey] = true;
        $service = $this->service($class);
        if ('application' === $service['lifetime']) {
            $applicationScope = true;
        } elseif ('operation' === $service['lifetime']) {
            $applicationScope = false;
        }
        foreach ($service['arguments'] as $argument) {
            if ($applicationScope && isset($argument['input'])) {
                $this->applicationInputs[$argument['input']] = true;
            }
            if (isset($argument['service'])) {
                $this->collectApplicationInputs($this->resolve($argument['service']), $applicationScope, $seen);
            }
        }
    }

    /**
     * @param array<string, true> $needed
     * @param array<string, true> $seen
     */
    private function collectOperationState(string $class, array &$needed, array &$seen): void
    {
        if (isset($seen[$class])) {
            return;
        }
        $seen[$class] = true;
        $service = $this->service($class);
        if ('application' === $service['lifetime']) {
            return;
        }
        if ('operation' === $service['lifetime']) {
            $needed[$class] = true;
        }
        foreach ($service['arguments'] as $argument) {
            if (isset($argument['input'])) {
                $this->operationInputs[$argument['input']] = true;
            }
            if (isset($argument['service'])) {
                $this->collectOperationState($this->resolve($argument['service']), $needed, $seen);
            }
        }
    }

    /** @return list<string> */
    private function applicationClassLines(): array
    {
        $lines = ['final class ApplicationAssembly extends GeneratedApplicationState implements GeneratedApplicationBoundary', '{'];
        $lines[] = '    /** @var list<\\Closure(): void> */';
        $lines[] = '    private array $cleanup = [];';
        foreach ($this->operationInputs as $contract => $_) {
            $lines[] = '    private \\' . $contract . ' $input' . $this->inputIndexes[$contract] . ';';
        }
        foreach ($this->applicationProperties as $class => $index) {
            if (!isset($this->retainedApplication[$class])) {
                continue;
            }
            $lines[] = '    private \\' . $class . ' $applicationService' . $index . ';';
        }
        $lines[] = '';
        $lines[] = '    private function __construct() {}';
        $lines[] = '';
        $parameters = [];
        foreach ($this->inputIndexes as $contract => $index) {
            $parameters[] = '\\' . $contract . ' $input' . $index;
        }
        if ([] === $parameters) {
            $lines[] = '    public static function start(): self';
            $lines[] = '    {';
        } else {
            $lines[] = '    public static function start(';
            foreach ($parameters as $parameter) {
                $lines[] = '        ' . $parameter . ',';
            }
            $lines[] = '    ): self {';
        }
        $lines[] = '        $assembly = new self();';
        foreach ($this->operationInputs as $contract => $_) {
            $index = $this->inputIndexes[$contract];
            $lines[] = '        $assembly->input' . $index . ' = $input' . $index . ';';
        }
        $this->scope = 'application';
        $this->constructionLines = [];
        $this->builtApplication = [];
        $this->transientCounter = 0;
        foreach (array_keys($this->applicationProperties) as $class) {
            $this->construct($class);
        }
        $captures = ['$assembly'];
        foreach ($this->applicationInputs as $contract => $_) {
            $index = $this->inputIndexes[$contract];
            $captures[] = '$input' . $index;
        }
        $lines[] = '        $construct = static function () use (' . implode(', ', $captures) . '): void {';
        foreach ($this->generatedConstructionLines() as $line) {
            $lines[] = '            ' . $line;
        }
        $lines[] = '        };';
        $lines[] = '        try {';
        $lines[] = '            $construct();';
        $lines[] = '        } catch (Throwable $primaryFailure) {';
        $lines[] = '            $assembly->retireAfterConstructionFailure();';
        $lines[] = '            $cleanupFailures = $assembly->runCleanup();';
        $lines[] = "            LifecycleFailureComposer::throwIfNeeded('application-construction', \$primaryFailure, \$cleanupFailures);";
        $lines[] = '            throw $primaryFailure;';
        $lines[] = '        }';
        $lines[] = '';
        $lines[] = '        return $assembly;';
        $lines[] = '    }';
        foreach ($this->applicationRoots as $index => $root) {
            $class = $this->resolve($root['contract']);
            $lines[] = '';
            $lines[] = '    public function applicationRoot' . ($index + 1) . '(): \\' . $root['contract'];
            $lines[] = '    {';
            $lines[] = "        \$this->assertNotRetired('application_retired');";
            $lines[] = '        return $this->applicationService' . $this->applicationProperties[$class] . ';';
            $lines[] = '    }';
        }
        $lines[] = '';
        $lines[] = '    public function beginOperation(): OperationAssembly';
        $lines[] = '    {';
        $lines[] = "        \$this->assertNotRetired('application_retired');";
        $lines[] = '        $this->acquireOperationLease();';
        $lines[] = '';
        $lines[] = '        return new OperationAssembly(';
        $lines[] = '            owner: $this,';
        foreach ($this->applicationProperties as $class => $index) {
            if ($this->operationUsesApplication($class)) {
                $lines[] = '            applicationService' . $index . ': $this->applicationService' . $index . ',';
            }
        }
        foreach ($this->operationInputs as $contract => $_) {
            $index = $this->inputIndexes[$contract];
            $lines[] = '            input' . $index . ': $this->input' . $index . ',';
        }
        $lines[] = '        );';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public function shutdown(?Throwable $primaryFailure = null): void';
        $lines[] = '    {';
        $lines[] = '        if ($this->retired()) {';
        $lines[] = '            return;';
        $lines[] = '        }';
        $lines[] = '        $this->beginShutdown();';
        $lines[] = '        $cleanupFailures = $this->runCleanup();';
        $lines[] = "        LifecycleFailureComposer::throwIfNeeded('application', \$primaryFailure, \$cleanupFailures);";
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public function isRetired(): bool';
        $lines[] = '    {';
        $lines[] = '        return $this->retired();';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    /** @return list<Throwable> */';
        $lines[] = '    private function runCleanup(): array';
        $lines[] = '    {';
        $lines[] = '        $failures = [];';
        $lines[] = '        while (null !== ($cleanup = array_pop($this->cleanup))) {';
        $lines[] = '            try {';
        $lines[] = '                $cleanup();';
        $lines[] = '            } catch (Throwable $failure) {';
        $lines[] = '                $failures[] = $failure;';
        $lines[] = '            }';
        $lines[] = '        }';
        $lines[] = '        return $failures;';
        $lines[] = '    }';
        $lines[] = '}';

        return $lines;
    }

    /** @return list<string> */
    private function operationClassLines(): array
    {
        $lines = ['final class OperationAssembly implements GeneratedOperationBoundary', '{'];
        $lines[] = '    /** @var list<\\Closure(): void> */';
        $lines[] = '    private array $cleanup = [];';
        $lines[] = '    private bool $closed = false;';
        foreach ($this->applicationProperties as $class => $index) {
            if ($this->operationUsesApplication($class)) {
                $lines[] = '    private \\' . $class . ' $applicationService' . $index . ';';
            }
        }
        foreach ($this->operationInputs as $contract => $_) {
            $lines[] = '    private \\' . $contract . ' $input' . $this->inputIndexes[$contract] . ';';
        }
        foreach ($this->operationProperties as $class => $index) {
            if (!isset($this->retainedOperation[$class])) {
                continue;
            }
            $lines[] = '    private \\' . $class . ' $operationService' . $index . ';';
        }
        $lines[] = '';
        $lines[] = '    public function __construct(';
        $lines[] = '        private readonly ApplicationAssembly $owner,';
        foreach ($this->applicationProperties as $class => $index) {
            if ($this->operationUsesApplication($class)) {
                $lines[] = '        \\' . $class . ' $applicationService' . $index . ',';
            }
        }
        foreach ($this->operationInputs as $contract => $_) {
            $lines[] = '        \\' . $contract . ' $input' . $this->inputIndexes[$contract] . ',';
        }
        $lines[] = '    ) {';
        foreach ($this->applicationProperties as $class => $index) {
            if ($this->operationUsesApplication($class)) {
                $lines[] = '        $this->applicationService' . $index . ' = $applicationService' . $index . ';';
            }
        }
        foreach ($this->operationInputs as $contract => $_) {
            $index = $this->inputIndexes[$contract];
            $lines[] = '        $this->input' . $index . ' = $input' . $index . ';';
        }
        $this->scope = 'operation';
        $this->constructionLines = [];
        $this->builtOperation = [];
        $this->transientCounter = 0;
        foreach ($this->applicationProperties as $class => $index) {
            if ($this->operationUsesApplication($class)) {
                $this->builtApplication[$class] = '$this->applicationService' . $index;
            }
        }
        foreach (array_keys($this->operationProperties) as $class) {
            $this->construct($class);
        }
        $lines[] = '        $construct = function (): void {';
        foreach ($this->generatedConstructionLines() as $line) {
            $lines[] = '            ' . $line;
        }
        $lines[] = '        };';
        $lines[] = '        try {';
        $lines[] = '            $construct();';
        $lines[] = '        } catch (Throwable $primaryFailure) {';
        $lines[] = '            $this->closed = true;';
        $lines[] = '            $cleanupFailures = $this->runCleanup();';
        $lines[] = '            $this->owner->releaseOperationLease();';
        $lines[] = "            LifecycleFailureComposer::throwIfNeeded('operation-construction', \$primaryFailure, \$cleanupFailures);";
        $lines[] = '            throw $primaryFailure;';
        $lines[] = '        }';
        $lines[] = '    }';
        foreach ($this->operationRoots as $index => $root) {
            $class = $this->resolve($root['contract']);
            $lines[] = '';
            $lines[] = '    public function operationRoot' . ($index + 1) . '(): \\' . $root['contract'];
            $lines[] = '    {';
            $lines[] = '        $this->assertOpen();';
            $lines[] = '        return $this->operationService' . $this->operationProperties[$class] . ';';
            $lines[] = '    }';
        }
        $lines[] = '';
        $lines[] = '    public function close(?Throwable $primaryFailure = null): void';
        $lines[] = '    {';
        $lines[] = '        if ($this->closed) {';
        $lines[] = '            return;';
        $lines[] = '        }';
        $lines[] = '        $this->closed = true;';
        $lines[] = '        $cleanupFailures = $this->runCleanup();';
        $lines[] = '        $this->owner->releaseOperationLease();';
        $lines[] = "        LifecycleFailureComposer::throwIfNeeded('operation', \$primaryFailure, \$cleanupFailures);";
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public function isClosed(): bool';
        $lines[] = '    {';
        $lines[] = '        return $this->closed;';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    private function assertOpen(): void';
        $lines[] = '    {';
        $lines[] = '        if ($this->closed) {';
        $lines[] = "            throw new RetiredCompositionBoundaryException(CompositionDiagnostic::CompositionBoundaryUnavailable, 'operation_closed');";
        $lines[] = '        }';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    /** @return list<Throwable> */';
        $lines[] = '    private function runCleanup(): array';
        $lines[] = '    {';
        $lines[] = '        $failures = [];';
        $lines[] = '        while (null !== ($cleanup = array_pop($this->cleanup))) {';
        $lines[] = '            try {';
        $lines[] = '                $cleanup();';
        $lines[] = '            } catch (Throwable $failure) {';
        $lines[] = '                $failures[] = $failure;';
        $lines[] = '            }';
        $lines[] = '        }';
        $lines[] = '        return $failures;';
        $lines[] = '    }';
        $lines[] = '}';

        return $lines;
    }

    private function construct(string $class): string
    {
        $service = $this->service($class);
        if ('application' === $service['lifetime'] && isset($this->builtApplication[$class])) {
            return $this->builtApplication[$class];
        }
        if ('operation' === $service['lifetime'] && isset($this->builtOperation[$class])) {
            return $this->builtOperation[$class];
        }
        if ('application' === $service['lifetime'] && 'operation' === $this->scope) {
            return $this->builtApplication[$class] ?? $this->invalid('missing_application_dependency');
        }
        if ('operation' === $service['lifetime'] && 'application' === $this->scope) {
            $this->invalid('application_reaches_operation');
        }

        if ('application' === $service['lifetime']) {
            $variable = '$applicationService' . $this->applicationProperties[$class];
        } elseif ('operation' === $service['lifetime']) {
            $variable = '$operationService' . $this->operationProperties[$class];
        } else {
            $variable = '$transient' . ++$this->transientCounter;
        }

        $arguments = [];
        foreach ($service['arguments'] as $argument) {
            if (isset($argument['default'])) {
                continue;
            }
            if (isset($argument['input'])) {
                $index = $this->inputIndexes[$argument['input']];
                $arguments[] = [$argument['parameter'], 'application' === $this->scope ? '$input' . $index : '$this->input' . $index];
            } else {
                $contract = $argument['service'] ?? $this->invalid('model_argument');
                $arguments[] = [$argument['parameter'], $this->construct($this->resolve($contract))];
            }
        }
        if ([] === $arguments) {
            $this->constructionLines[] = $variable . ' = new \\' . $class . '();';
        } else {
            $this->constructionLines[] = $variable . ' = new \\' . $class . '(';
            foreach ($arguments as [$parameter, $expression]) {
                $this->constructionLines[] = '    ' . $parameter . ': ' . $expression . ',';
            }
            $this->constructionLines[] = ');';
        }
        if (null !== $service['cleanup']) {
            $owner = 'application' === $this->scope ? '$assembly' : '$this';
            $this->constructionLines[] = $owner . '->cleanup[] = static function () use (' . $variable . '): void {';
            $this->constructionLines[] = '    ' . $variable . '->' . $service['cleanup'] . '();';
            $this->constructionLines[] = '};';
        }
        if ('application' === $service['lifetime']) {
            if (isset($this->retainedApplication[$class])) {
                $this->constructionLines[] = '$assembly->applicationService' . $this->applicationProperties[$class] . ' = ' . $variable . ';';
            }
            $this->builtApplication[$class] = $variable;
        } elseif ('operation' === $service['lifetime']) {
            if (isset($this->retainedOperation[$class])) {
                $this->constructionLines[] = '$this->operationService' . $this->operationProperties[$class] . ' = ' . $variable . ';';
            }
            $this->builtOperation[$class] = $variable;
        }

        return $variable;
    }

    private function operationUsesApplication(string $class): bool
    {
        foreach ($this->operationRoots as $root) {
            $seen = [];
            if ($this->reachesApplication($this->resolve($root['contract']), $class, $seen)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function generatedConstructionLines(): array
    {
        return $this->constructionLines;
    }

    /** @param array<string, true> $seen */
    private function reachesApplication(string $current, string $target, array &$seen): bool
    {
        if ($current === $target) {
            return true;
        }
        if (isset($seen[$current])) {
            return false;
        }
        $seen[$current] = true;
        $service = $this->service($current);
        if ('application' === $service['lifetime']) {
            return false;
        }
        foreach ($service['arguments'] as $argument) {
            if (isset($argument['service']) && $this->reachesApplication($this->resolve($argument['service']), $target, $seen)) {
                return true;
            }
        }

        return false;
    }

    private function resolve(string $contract): string
    {
        if (isset($this->services[$contract])) {
            return $contract;
        }

        return $this->bindings[$contract] ?? $this->invalid('unresolved_contract');
    }

    /** @return Service */
    private function service(string $class): array
    {
        return $this->services[$class] ?? $this->invalid('unknown_service');
    }

    /** @return list<array<string, mixed>> */
    private function records(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->invalid('model_shape');
        }
        foreach ($value as $record) {
            if (!is_array($record) || array_is_list($record)) {
                $this->invalid('model_shape');
            }
        }

        /** @var list<array<string, mixed>> $value */
        return $value;
    }

    private function string(mixed $value): string
    {
        if (!is_string($value)) {
            $this->invalid('model_shape');
        }

        return $value;
    }

    private function invalid(string $violation): never
    {
        throw new InvalidGeneratedWiringException(CompositionDiagnostic::InvalidGeneratedWiring, $violation);
    }
}
