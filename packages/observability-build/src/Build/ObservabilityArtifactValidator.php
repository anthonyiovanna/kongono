<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Build;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ObservabilityBuild\Diagnostic\ObservabilityBuildDiagnostic;
use Kongono\ObservabilityBuild\Exception\InvalidObservabilityArtifactException;
use Kongono\ObservabilityBuild\Exception\StaleObservabilityArtifactException;
use Kongono\ObservabilityBuild\Internal\CanonicalJson;
use Kongono\ObservabilityBuild\Internal\PathGuard;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryResource;
use Throwable;

/** Separately proves canonical structure and equality with complete current state. */
#[Api(ApiStatus::Internal)]
final class ObservabilityArtifactValidator
{
    public function validateStructure(string $bytes): ObservabilityArtifact
    {
        if (
            '' === $bytes
            || strlen($bytes) > ObservabilityArtifactBuilder::MAX_ARTIFACT_BYTES
            || str_starts_with($bytes, "\xEF\xBB\xBF")
            || str_contains($bytes, "\r")
            || str_contains($bytes, "\0")
            || 1 !== preg_match('//u', $bytes)
            || !str_ends_with($bytes, "\n")
            || str_ends_with($bytes, "\n\n")
            || 1 === preg_match('/[\x{0080}-\x{009F}]/u', $bytes)
        ) {
            $this->invalid();
        }
        try {
            $data = CanonicalJson::decodeObject($bytes, 16);
        } catch (JsonException) {
            $this->invalid();
        }
        if (
            CanonicalJson::encode($data) !== $bytes
            || array_keys($data) !== ['schema', 'version', 'profile', 'resource', 'batch', 'source', 'model_sha256', 'builder']
        ) {
            $this->invalid();
        }
        if (
            'kongono.observability-artifact' !== $data['schema']
            || 1 !== $data['version']
            || ObservabilitySourceReader::PROFILE !== $data['profile']
        ) {
            $this->invalid();
        }

        $resource = $this->object($data['resource']);
        if (array_keys($resource) !== ['service_name', 'service_version', 'deployment_environment_name']) {
            $this->invalid();
        }
        $serviceName = $this->requiredText($resource['service_name'], 128);
        $serviceVersion = $this->optionalText($resource['service_version'], 64);
        $deploymentEnvironmentName = $this->optionalText($resource['deployment_environment_name'], 64);

        $batch = $this->object($data['batch']);
        if (array_keys($batch) !== ['queue_capacity', 'maximum_batch_size', 'transport_timeout_milliseconds']) {
            $this->invalid();
        }
        $queueCapacity = $this->integer($batch['queue_capacity']);
        $maximumBatchSize = $this->integer($batch['maximum_batch_size']);
        $transportTimeoutMilliseconds = $this->integer($batch['transport_timeout_milliseconds']);

        try {
            $typedResource = new OpenTelemetryResource($serviceName, $serviceVersion, $deploymentEnvironmentName);
            $batchPolicy = new OpenTelemetryBatchPolicy($queueCapacity, $maximumBatchSize, $transportTimeoutMilliseconds);
        } catch (Throwable) {
            $this->invalid();
        }

        $source = $this->object($data['source']);
        if (array_keys($source) !== ['path', 'sha256'] || PathGuard::SOURCE_PATH !== $source['path']) {
            $this->invalid();
        }
        $sourceSha256 = $this->digest($source['sha256']);
        $modelSha256 = $this->digest($data['model_sha256']);
        $model = [
            'profile' => $data['profile'],
            'resource' => $resource,
            'batch' => $batch,
        ];
        if (!hash_equals(hash('sha256', CanonicalJson::encode($model)), $modelSha256)) {
            $this->invalid();
        }

        $builder = $this->object($data['builder']);
        if (
            array_keys($builder) !== ['package', 'contract_version', 'implementation_sha256']
            || 'kongono/observability-build' !== $builder['package']
            || ObservabilityArtifactBuilder::CONTRACT_VERSION !== $builder['contract_version']
        ) {
            $this->invalid();
        }
        $implementationSha256 = $this->digest($builder['implementation_sha256']);

        return new ObservabilityArtifact(
            $bytes,
            hash('sha256', $bytes),
            $sourceSha256,
            $modelSha256,
            $implementationSha256,
            ObservabilitySourceReader::PROFILE,
            $typedResource,
            $batchPolicy,
        );
    }

    public function validateCurrent(string $applicationRoot, string $bytes): ObservabilityArtifact
    {
        $artifact = $this->validateStructure($bytes);
        $current = (new ObservabilityArtifactBuilder())->build($applicationRoot);
        if (!hash_equals($current->canonicalBytes(), $bytes)) {
            throw new StaleObservabilityArtifactException(ObservabilityBuildDiagnostic::StaleArtifact);
        }

        return $artifact;
    }

    /** @return array<string,mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid();
        }

        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                $this->invalid();
            }
            $object[$key] = $item;
        }

        return $object;
    }

    private function requiredText(mixed $value, int $maximumBytes): string
    {
        if (
            !is_string($value)
            || '' === $value
            || strlen($value) > $maximumBytes
            || 1 !== preg_match('//u', $value)
            || 1 === preg_match('/[\x00-\x1F\x7F]/', $value)
            || 1 === preg_match('/[\x{0080}-\x{009F}]/u', $value)
        ) {
            $this->invalid();
        }

        return $value;
    }

    private function optionalText(mixed $value, int $maximumBytes): ?string
    {
        if (null === $value) {
            return null;
        }

        return $this->requiredText($value, $maximumBytes);
    }

    private function integer(mixed $value): int
    {
        if (!is_int($value)) {
            $this->invalid();
        }

        return $value;
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/^[a-f0-9]{64}$/D', $value)) {
            $this->invalid();
        }

        return $value;
    }

    private function invalid(): never
    {
        throw new InvalidObservabilityArtifactException(ObservabilityBuildDiagnostic::InvalidArtifact);
    }
}
