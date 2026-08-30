<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphComposition\Exception\InvalidCompositionGraphProjectionException;
use Kongono\Composition\Build\CompositionArtifact;
use Throwable;

/** Admits only the exact bounded in-memory Composition v2 artifact subset used by projection. */
#[Api(ApiStatus::Internal)]
final class CompositionArtifactGuard
{
    private const int MAX_ARTIFACT_BYTES = 4194304;
    private const int MAX_INPUTS = 256;
    private const int MAX_SERVICES = 4096;
    private const int MAX_BINDINGS = 4096;
    private const int MAX_ROOTS = 256;
    private const int MAX_ARGUMENTS = 128;
    private const int MAX_EDGES = 32768;

    /** @return array{model:array<string, mixed>, artifact_sha256:string} */
    public function admit(CompositionArtifact $artifact): array
    {
        $bytes = $artifact->canonicalBytes();
        $length = strlen($bytes);
        if (0 === $length) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('artifact_bytes');
        }
        if ($length > self::MAX_ARTIFACT_BYTES) {
            throw InvalidCompositionGraphProjectionException::limit('artifact_bytes', 'artifact', $length);
        }
        if (
            str_starts_with($bytes, "\xEF\xBB\xBF")
            || str_contains($bytes, "\r")
            || !str_ends_with($bytes, "\n")
            || str_ends_with($bytes, "\n\n")
        ) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('artifact_encoding');
        }

        try {
            $data = CanonicalJson::decodeObject($bytes);
        } catch (JsonException $exception) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('artifact_json', $exception);
        } catch (Throwable $exception) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('artifact_json', $exception);
        }
        try {
            if (CanonicalJson::encode($data) !== $bytes) {
                throw InvalidCompositionGraphProjectionException::invalidArtifact('artifact_canonical');
            }
        } catch (InvalidCompositionGraphProjectionException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('artifact_canonical', $exception);
        }

        if (array_keys($data) !== ['schema', 'version', 'source', 'project', 'builder', 'symbol_state', 'model_sha256', 'model']) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('top_level_members');
        }
        if ('kongono.composition-artifact' !== $data['schema'] || 1 !== $data['version']) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('artifact_schema');
        }

        $artifactSha256 = $artifact->artifactSha256();
        if (!self::digest($artifactSha256) || hash('sha256', $bytes) !== $artifactSha256) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('artifact_digest');
        }

        $source = self::object($data['source'], ['path', 'bytes', 'sha256'], 'source_shape');
        if (
            'kongono.composition.json' !== $source['path']
            || !is_int($source['bytes'])
            || $source['bytes'] < 0
            || !is_string($source['sha256'])
            || !self::digest($source['sha256'])
            || $artifact->sourceSha256() !== $source['sha256']
        ) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('source_identity');
        }

        $project = self::object($data['project'], ['composer_json_sha256', 'composer_lock_sha256'], 'project_shape');
        foreach (['composer_json_sha256', 'composer_lock_sha256'] as $key) {
            if (!is_string($project[$key]) || !self::digest($project[$key])) {
                throw InvalidCompositionGraphProjectionException::invalidArtifact('digest_grammar');
            }
        }

        $builder = self::object($data['builder'], ['package', 'contract_version', 'implementation_sha256'], 'builder_shape');
        if (
            'kongono/composition' !== $builder['package']
            || 1 !== $builder['contract_version']
            || !is_string($builder['implementation_sha256'])
            || !self::digest($builder['implementation_sha256'])
            || $artifact->builderImplementationSha256() !== $builder['implementation_sha256']
        ) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('builder_identity');
        }

        $symbolState = self::object(
            $data['symbol_state'],
            ['source_set_sha256', 'source_count', 'source_bytes', 'sources'],
            'symbol_state_shape',
        );
        $this->validateSymbolState($symbolState, $artifact);

        if (!is_string($data['model_sha256']) || !self::digest($data['model_sha256'])) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('model_digest');
        }
        if (!is_array($data['model']) || array_is_list($data['model'])) {
            throw InvalidCompositionGraphProjectionException::invalidModel('model_shape', 'artifact');
        }

        /** @var array<string, mixed> $model */
        $model = $data['model'];
        try {
            $modelSha256 = hash('sha256', CanonicalJson::encode($model));
        } catch (Throwable $exception) {
            throw InvalidCompositionGraphProjectionException::invalidModel('model_encoding', 'artifact', previous: $exception);
        }
        if ($modelSha256 !== $data['model_sha256'] || $artifact->modelSha256() !== $modelSha256) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('model_identity');
        }

        $this->validateModel($model);

        return ['model' => $model, 'artifact_sha256' => $artifactSha256];
    }

    /** @param array<string, mixed> $state */
    private function validateSymbolState(array $state, CompositionArtifact $artifact): void
    {
        if (
            !is_string($state['source_set_sha256'])
            || !self::digest($state['source_set_sha256'])
            || $artifact->sourceSetSha256() !== $state['source_set_sha256']
            || !is_int($state['source_count'])
            || $state['source_count'] < 0
            || !is_int($state['source_bytes'])
            || $state['source_bytes'] < 0
            || !is_array($state['sources'])
            || !array_is_list($state['sources'])
            || count($state['sources']) !== $state['source_count']
        ) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('symbol_state_identity');
        }

        $records = [];
        $aggregateBytes = 0;
        $previousPath = null;
        foreach ($state['sources'] as $position => $value) {
            $source = self::object($value, ['path', 'bytes', 'sha256'], 'symbol_source_shape');
            if (
                !is_string($source['path'])
                || '' === $source['path']
                || strlen($source['path']) > 4096
                || 1 !== preg_match('//u', $source['path'])
                || 1 === preg_match('/[\x00-\x1F\x7F]/', $source['path'])
                || !is_int($source['bytes'])
                || $source['bytes'] < 0
                || !is_string($source['sha256'])
                || !self::digest($source['sha256'])
            ) {
                throw InvalidCompositionGraphProjectionException::invalidArtifact('symbol_source_record');
            }
            if (null !== $previousPath && strcmp($previousPath, $source['path']) >= 0) {
                throw InvalidCompositionGraphProjectionException::invalidArtifact('symbol_source_order');
            }
            $records[] = $source['path'] . "\t" . $source['bytes'] . "\t" . $source['sha256'];
            $aggregateBytes += $source['bytes'];
            $previousPath = $source['path'];
            unset($position);
        }
        if (
            $aggregateBytes !== $state['source_bytes']
            || hash('sha256', implode("\n", $records)) !== $state['source_set_sha256']
        ) {
            throw InvalidCompositionGraphProjectionException::invalidArtifact('symbol_state_digest');
        }
    }

    /** @param array<string, mixed> $model */
    private function validateModel(array $model): void
    {
        if (array_keys($model) !== ['schema', 'version', 'inputs', 'services', 'bindings', 'roots']) {
            throw InvalidCompositionGraphProjectionException::invalidModel('model_members', 'artifact');
        }
        if ('kongono.composition' !== $model['schema']) {
            throw InvalidCompositionGraphProjectionException::invalidModel('model_schema', 'artifact');
        }
        if (2 !== $model['version']) {
            throw InvalidCompositionGraphProjectionException::unsupportedVersion();
        }

        $inputs = $this->list($model['inputs'], self::MAX_INPUTS, 'input');
        $services = $this->list($model['services'], self::MAX_SERVICES, 'service');
        $bindings = $this->list($model['bindings'], self::MAX_BINDINGS, 'binding');
        $roots = $this->list($model['roots'], self::MAX_ROOTS, 'root');
        if ([] === $roots) {
            throw InvalidCompositionGraphProjectionException::invalidModel('roots_empty', 'root', 0);
        }

        /** @var array<string, string> $inputLifetimes */
        $inputLifetimes = [];
        $previous = null;
        foreach ($inputs as $position => $value) {
            $input = self::object($value, ['contract', 'lifetime'], 'input_shape', 'input', $position, count($inputs));
            $contract = $this->symbol($input['contract'], 'input', $position);
            if (!is_string($input['lifetime']) || !in_array($input['lifetime'], ['application', 'operation'], true)) {
                throw InvalidCompositionGraphProjectionException::invalidModel('input_lifetime', 'input', count($inputs), $position);
            }
            $this->orderedUnique($previous, $contract, 'input', count($inputs), $position);
            $inputLifetimes[$contract] = $input['lifetime'];
            $previous = $contract;
        }

        /** @var array<string, array<string, mixed>> $serviceByClass */
        $serviceByClass = [];
        $previous = null;
        $edgeCount = 0;
        foreach ($services as $position => $value) {
            $service = self::object($value, ['class', 'lifetime', 'arguments', 'cleanup'], 'service_shape', 'service', $position, count($services));
            $class = $this->symbol($service['class'], 'service', $position);
            if (!is_string($service['lifetime']) || !in_array($service['lifetime'], ['application', 'operation', 'transient'], true)) {
                throw InvalidCompositionGraphProjectionException::invalidModel('service_lifetime', 'service', count($services), $position);
            }
            if (null !== $service['cleanup']) {
                $this->name($service['cleanup'], 'service', $position);
            }
            $arguments = $this->list($service['arguments'], self::MAX_ARGUMENTS, 'argument');
            foreach ($arguments as $argumentPosition => $argumentValue) {
                if (!is_array($argumentValue) || array_is_list($argumentValue) || 2 !== count($argumentValue)) {
                    throw InvalidCompositionGraphProjectionException::invalidModel('argument_shape', 'argument', count($arguments), $argumentPosition);
                }
                $keys = array_keys($argumentValue);
                if (!in_array($keys, [['parameter', 'service'], ['parameter', 'input'], ['parameter', 'default']], true)) {
                    throw InvalidCompositionGraphProjectionException::invalidModel('argument_members', 'argument', count($arguments), $argumentPosition);
                }
                $this->name($argumentValue['parameter'] ?? null, 'argument', $argumentPosition);
                $form = $keys[1];
                if ('default' === $form) {
                    if (true !== $argumentValue['default']) {
                        throw InvalidCompositionGraphProjectionException::invalidModel('default_value', 'argument', count($arguments), $argumentPosition);
                    }
                } else {
                    $this->symbol($argumentValue[$form], 'argument', $argumentPosition);
                    ++$edgeCount;
                }
            }
            $this->orderedUnique($previous, $class, 'service', count($services), $position);
            $serviceByClass[$class] = $service;
            $previous = $class;
        }
        if ($edgeCount > self::MAX_EDGES) {
            throw InvalidCompositionGraphProjectionException::limit('edge_count', 'argument', $edgeCount);
        }

        /** @var array<string, string> $bindingMap */
        $bindingMap = [];
        $previous = null;
        foreach ($bindings as $position => $value) {
            $binding = self::object($value, ['contract', 'service'], 'binding_shape', 'binding', $position, count($bindings));
            $contract = $this->symbol($binding['contract'], 'binding', $position);
            $service = $this->symbol($binding['service'], 'binding', $position);
            $this->orderedUnique($previous, $contract, 'binding', count($bindings), $position);
            if (!isset($serviceByClass[$service]) || isset($serviceByClass[$contract]) && $contract !== $service) {
                throw InvalidCompositionGraphProjectionException::invalidModel('binding_target', 'binding', count($bindings), $position);
            }
            $bindingMap[$contract] = $service;
            $previous = $contract;
        }

        $previousRoot = null;
        foreach ($roots as $position => $value) {
            $root = self::object($value, ['kind', 'contract'], 'root_shape', 'root', $position, count($roots));
            if (!is_string($root['kind']) || !in_array($root['kind'], ['application', 'operation'], true)) {
                throw InvalidCompositionGraphProjectionException::invalidModel('root_kind', 'root', count($roots), $position);
            }
            $contract = $this->symbol($root['contract'], 'root', $position);
            $identity = $root['kind'] . "\0" . $contract;
            $this->orderedUnique($previousRoot, $identity, 'root', count($roots), $position);
            $resolved = $serviceByClass[$contract]['class'] ?? $bindingMap[$contract] ?? null;
            if (!is_string($resolved) || !isset($serviceByClass[$resolved])) {
                throw InvalidCompositionGraphProjectionException::invalidModel('root_resolution', 'root', count($roots), $position);
            }
            $previousRoot = $identity;
        }

        foreach ($serviceByClass as $service) {
            /** @var list<array<string, mixed>> $arguments */
            $arguments = $service['arguments'];
            foreach ($arguments as $position => $argument) {
                if (isset($argument['input'])) {
                    if (!is_string($argument['input']) || !isset($inputLifetimes[$argument['input']])) {
                        throw InvalidCompositionGraphProjectionException::invalidModel('input_resolution', 'argument', count($arguments), $position);
                    }
                    continue;
                }
                if (isset($argument['service'])) {
                    if (!is_string($argument['service'])) {
                        throw InvalidCompositionGraphProjectionException::invalidModel('service_resolution', 'argument', count($arguments), $position);
                    }
                    $resolved = $serviceByClass[$argument['service']]['class'] ?? $bindingMap[$argument['service']] ?? null;
                    if (!is_string($resolved) || !isset($serviceByClass[$resolved])) {
                        throw InvalidCompositionGraphProjectionException::invalidModel('service_resolution', 'argument', count($arguments), $position);
                    }
                }
            }
        }
    }

    /** @return list<mixed> */
    private function list(mixed $value, int $maximum, string $recordKind): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw InvalidCompositionGraphProjectionException::invalidModel('records_not_list', $recordKind);
        }
        if (count($value) > $maximum) {
            throw InvalidCompositionGraphProjectionException::limit($recordKind . '_count', $recordKind, count($value));
        }

        return $value;
    }

    /**
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    private static function object(
        mixed $value,
        array $keys,
        string $violation,
        string $recordKind = 'artifact',
        ?int $position = null,
        ?int $count = null,
    ): array {
        if (!is_array($value) || array_is_list($value) || array_keys($value) !== $keys) {
            if ('artifact' === $recordKind) {
                throw InvalidCompositionGraphProjectionException::invalidArtifact($violation);
            }
            throw InvalidCompositionGraphProjectionException::invalidModel($violation, $recordKind, $count, $position);
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    private function symbol(mixed $value, string $recordKind, int $position): string
    {
        if (
            !is_string($value)
            || '' === $value
            || strlen($value) > 255
            || str_starts_with($value, '\\')
            || 1 !== preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*\z/D', $value)
        ) {
            throw InvalidCompositionGraphProjectionException::invalidModel('symbol_grammar', $recordKind, position: $position);
        }

        return $value;
    }

    private function name(mixed $value, string $recordKind, int $position): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > 128 || 1 !== preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/D', $value)) {
            throw InvalidCompositionGraphProjectionException::invalidModel('name_grammar', $recordKind, position: $position);
        }

        return $value;
    }

    private function orderedUnique(?string $previous, string $current, string $recordKind, int $count, int $position): void
    {
        if (null !== $previous && strcmp($previous, $current) >= 0) {
            throw InvalidCompositionGraphProjectionException::invalidModel('record_order', $recordKind, $count, $position);
        }
    }

    private static function digest(string $value): bool
    {
        return 64 === strlen($value) && 1 === preg_match('/\A[a-f0-9]{64}\z/D', $value);
    }
}
