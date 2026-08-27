<?php

declare(strict_types=1);

namespace Kongono\Composition\Build;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Composition\Exception\InvalidCompositionArtifactException;
use Kongono\Composition\Exception\StaleCompositionArtifactException;
use Kongono\Composition\Internal\CanonicalJson;

/** Proves artifact structure and exact equality with current application state. */
#[Api(ApiStatus::Experimental)]
final class CompositionArtifactValidator
{
    public function validateStructure(string $artifactBytes): CompositionArtifact
    {
        $this->validateBytes($artifactBytes);
        try {
            $artifact = CanonicalJson::decodeObject($artifactBytes, 64);
        } catch (JsonException) {
            $this->invalid('invalid_json');
        }
        if (CanonicalJson::encode($artifact) !== $artifactBytes) {
            $this->invalid('noncanonical_json');
        }
        $this->assertKeys($artifact, ['schema', 'version', 'source', 'project', 'builder', 'symbol_state', 'model_sha256', 'model']);
        if ('kongono.composition-artifact' !== ($artifact['schema'] ?? null) || 1 !== ($artifact['version'] ?? null)) {
            $this->invalid('schema_or_version');
        }

        $source = $this->object($artifact['source'] ?? null);
        $this->assertKeys($source, ['path', 'bytes', 'sha256']);
        if ('kongono.composition.json' !== ($source['path'] ?? null)) {
            $this->invalid('source_path');
        }
        $sourceBytes = $this->boundedInteger($source['bytes'] ?? null, 1, 1048576);
        $sourceSha256 = $this->digest($source['sha256'] ?? null);

        $project = $this->object($artifact['project'] ?? null);
        $this->assertKeys($project, ['composer_json_sha256', 'composer_lock_sha256']);
        $this->digest($project['composer_json_sha256'] ?? null);
        $this->digest($project['composer_lock_sha256'] ?? null);

        $builder = $this->object($artifact['builder'] ?? null);
        $this->assertKeys($builder, ['package', 'contract_version', 'implementation_sha256']);
        if ('kongono/composition' !== ($builder['package'] ?? null) || 1 !== ($builder['contract_version'] ?? null)) {
            $this->invalid('builder_contract');
        }
        $builderSha256 = $this->digest($builder['implementation_sha256'] ?? null);

        $symbolState = $this->object($artifact['symbol_state'] ?? null);
        $this->assertKeys($symbolState, ['source_set_sha256', 'source_count', 'source_bytes', 'sources']);
        $sourceSetSha256 = $this->digest($symbolState['source_set_sha256'] ?? null);
        $sourceCount = $this->boundedInteger($symbolState['source_count'] ?? null, 1, 32768);
        $declaringBytes = $this->boundedInteger($symbolState['source_bytes'] ?? null, 1, 4194304);
        $sources = $this->list($symbolState['sources'] ?? null);
        if (count($sources) !== $sourceCount) {
            $this->invalid('source_count');
        }
        $records = [];
        $total = 0;
        $previousPath = null;
        foreach ($sources as $sourceRecordValue) {
            $record = $this->object($sourceRecordValue);
            $this->assertKeys($record, ['path', 'bytes', 'sha256']);
            $path = $this->relativePath($record['path'] ?? null);
            if (null !== $previousPath && strcmp($previousPath, $path) >= 0) {
                $this->invalid('source_order');
            }
            $bytes = $this->boundedInteger($record['bytes'] ?? null, 1, 1048576);
            $sha256 = $this->digest($record['sha256'] ?? null);
            $records[] = implode("\t", [$path, (string) $bytes, $sha256]);
            $total += $bytes;
            $previousPath = $path;
        }
        if ($total !== $declaringBytes || hash('sha256', implode("\n", $records)) !== $sourceSetSha256) {
            $this->invalid('source_set_identity');
        }

        $modelSha256 = $this->digest($artifact['model_sha256'] ?? null);
        $model = $this->object($artifact['model'] ?? null);
        $this->validateModel($model);
        if (hash('sha256', CanonicalJson::encode($model)) !== $modelSha256) {
            $this->invalid('model_identity');
        }

        return new CompositionArtifact(
            $artifactBytes,
            hash('sha256', $artifactBytes),
            $sourceSha256,
            $modelSha256,
            $sourceSetSha256,
            $builderSha256,
        );
    }

    public function validateCurrent(string $applicationRoot, string $artifactBytes): CompositionArtifact
    {
        $artifact = $this->validateStructure($artifactBytes);
        $current = (new CompositionArtifactBuilder())->build($applicationRoot);
        if (!hash_equals($current->canonicalBytes(), $artifactBytes)) {
            throw new StaleCompositionArtifactException(CompositionDiagnostic::StaleArtifact, 'current_state_mismatch');
        }

        return $artifact;
    }

    private function validateBytes(string $bytes): void
    {
        if ('' === $bytes || strlen($bytes) > CompositionArtifactBuilder::MAX_ARTIFACT_BYTES || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            $this->invalid('artifact_bytes');
        }
    }

    /** @param array<string, mixed> $model */
    private function validateModel(array $model): void
    {
        $this->assertKeys($model, ['schema', 'version', 'inputs', 'services', 'bindings', 'roots']);
        $modelVersion = $model['version'] ?? null;
        if ('kongono.composition' !== ($model['schema'] ?? null) || !in_array($modelVersion, [1, 2], true)) {
            $this->invalid('model_schema');
        }
        $inputs = $this->list($model['inputs'] ?? null);
        $services = $this->list($model['services'] ?? null);
        $bindings = $this->list($model['bindings'] ?? null);
        $roots = $this->list($model['roots'] ?? null);
        if (count($inputs) > 256 || count($services) > 4096 || count($bindings) > 4096 || [] === $roots || count($roots) > 256) {
            $this->invalid('model_bounds');
        }

        $previous = null;
        foreach ($inputs as $value) {
            $record = $this->object($value);
            $this->assertKeys($record, 1 === $modelVersion ? ['contract'] : ['contract', 'lifetime']);
            $contract = $this->symbol($record['contract'] ?? null);
            if (2 === $modelVersion && !in_array($record['lifetime'] ?? null, ['application', 'operation'], true)) {
                $this->invalid('model_input_lifetime');
            }
            $this->assertIncreasing($previous, $contract);
            $previous = $contract;
        }
        if (2 === $modelVersion && count(array_filter($inputs, static fn(mixed $input): bool => is_array($input) && 'operation' === ($input['lifetime'] ?? null))) > 32) {
            $this->invalid('model_bounds');
        }

        $previous = null;
        $edgeCount = 0;
        foreach ($services as $value) {
            $record = $this->object($value);
            $this->assertKeys($record, ['class', 'lifetime', 'arguments', 'cleanup']);
            $class = $this->symbol($record['class'] ?? null);
            $this->assertIncreasing($previous, $class);
            $previous = $class;
            if (!in_array($record['lifetime'] ?? null, ['application', 'operation', 'transient'], true)) {
                $this->invalid('model_lifetime');
            }
            if (null !== ($record['cleanup'] ?? null)) {
                $this->name($record['cleanup']);
            }
            $arguments = $this->list($record['arguments'] ?? null);
            if (count($arguments) > 128) {
                $this->invalid('model_bounds');
            }
            foreach ($arguments as $argumentValue) {
                $argument = $this->object($argumentValue);
                $keys = array_keys($argument);
                if (2 !== count($keys) || 'parameter' !== $keys[0] || !in_array($keys[1], ['service', 'input', 'default'], true)) {
                    $this->invalid('model_argument');
                }
                $this->name($argument['parameter'] ?? null);
                $form = $keys[1];
                if ('default' === $form) {
                    if (true !== $argument['default']) {
                        $this->invalid('model_argument');
                    }
                } else {
                    $this->symbol($argument[$form]);
                    ++$edgeCount;
                }
            }
        }
        if ($edgeCount > 32768) {
            $this->invalid('model_bounds');
        }

        $previous = null;
        foreach ($bindings as $value) {
            $record = $this->object($value);
            $this->assertKeys($record, ['contract', 'service']);
            $identity = $this->symbol($record['contract'] ?? null) . "\0" . $this->symbol($record['service'] ?? null);
            $this->assertIncreasing($previous, $identity);
            $previous = $identity;
        }

        $previous = null;
        foreach ($roots as $value) {
            $record = $this->object($value);
            $this->assertKeys($record, ['kind', 'contract']);
            $kind = $record['kind'] ?? null;
            if (!is_string($kind) || !in_array($kind, ['application', 'operation'], true)) {
                $this->invalid('model_root');
            }
            $identity = $kind . "\0" . $this->symbol($record['contract'] ?? null);
            $this->assertIncreasing($previous, $identity);
            $previous = $identity;
        }
    }

    /** @param array<string, mixed> $value
     * @param list<string> $keys
     */
    private function assertKeys(array $value, array $keys): void
    {
        if (array_keys($value) !== $keys) {
            $this->invalid('invalid_shape');
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid('invalid_shape');
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->invalid('invalid_shape');
        }

        return $value;
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/^[a-f0-9]{64}$/D', $value)) {
            $this->invalid('invalid_digest');
        }

        return $value;
    }

    private function boundedInteger(mixed $value, int $minimum, int $maximum): int
    {
        if (!is_int($value) || $value < $minimum || $value > $maximum) {
            $this->invalid('invalid_count');
        }

        return $value;
    }

    private function relativePath(mixed $value): string
    {
        if (!is_string($value) || '' === $value || str_contains($value, '\\') || str_contains($value, ':') || str_starts_with($value, '/') || 1 !== preg_match('/^[A-Za-z0-9._-]+(?:\/[A-Za-z0-9._-]+)*$/D', $value)) {
            $this->invalid('invalid_source_path');
        }

        return $value;
    }

    private function symbol(mixed $value): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > 255 || str_starts_with($value, '\\') || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $value)) {
            $this->invalid('invalid_symbol');
        }

        return $value;
    }

    private function name(mixed $value): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > 128 || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value)) {
            $this->invalid('invalid_name');
        }

        return $value;
    }

    private function assertIncreasing(?string $previous, string $current): void
    {
        if (null !== $previous && strcmp($previous, $current) >= 0) {
            $this->invalid('model_order');
        }
    }

    private function invalid(string $violation): never
    {
        throw new InvalidCompositionArtifactException(CompositionDiagnostic::InvalidArtifact, $violation);
    }
}
