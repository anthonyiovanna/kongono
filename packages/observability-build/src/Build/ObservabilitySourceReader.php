<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Build;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ObservabilityBuild\Diagnostic\ObservabilityBuildDiagnostic;
use Kongono\ObservabilityBuild\Exception\InvalidObservabilitySourceException;
use Kongono\ObservabilityBuild\Internal\CanonicalJson;
use Kongono\ObservabilityBuild\Internal\PathGuard;
use Kongono\ObservabilityBuild\Model\ObservabilityConfigurationModel;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryResource;
use Throwable;

/** Reads and compiles the sole closed provider-free observability source. */
#[Api(ApiStatus::Experimental)]
final class ObservabilitySourceReader
{
    public const PROFILE = 'kongono.opentelemetry.otlp-http-protobuf.v1';

    /** @return array{model:ObservabilityConfigurationModel,path:string,bytes:string,size:int,sha256:string,absolute:string,stat:array<string|int,mixed>} */
    public function read(string $applicationRoot): array
    {
        $root = PathGuard::applicationRoot($applicationRoot);
        $snapshot = PathGuard::source($root);
        $this->validateBytes($snapshot['bytes']);
        try {
            $source = CanonicalJson::decodeObject($snapshot['bytes'], 8);
        } catch (JsonException) {
            $this->invalid();
        }
        if (!$this->hasExactKeys($source, ['schema', 'version', 'profile', 'resource', 'batch'])) {
            $this->invalid();
        }
        if (
            'kongono.observability' !== $source['schema']
            || 1 !== $source['version']
            || self::PROFILE !== $source['profile']
        ) {
            $this->invalid();
        }

        $resource = $source['resource'];
        if (!is_array($resource) || array_is_list($resource) || !$this->hasExactKeys($resource, ['service_name', 'service_version', 'deployment_environment_name'])) {
            $this->invalid();
        }
        $serviceName = $this->requiredText($resource['service_name'], 128);
        $serviceVersion = $this->optionalText($resource['service_version'], 64);
        $deploymentEnvironmentName = $this->optionalText($resource['deployment_environment_name'], 64);

        $batch = $source['batch'];
        if (!is_array($batch) || array_is_list($batch) || !$this->hasExactKeys($batch, ['queue_capacity', 'maximum_batch_size', 'transport_timeout_milliseconds'])) {
            $this->invalid();
        }
        if (
            !is_int($batch['queue_capacity'])
            || !is_int($batch['maximum_batch_size'])
            || !is_int($batch['transport_timeout_milliseconds'])
        ) {
            $this->invalid();
        }

        try {
            $typedResource = new OpenTelemetryResource(
                $serviceName,
                $serviceVersion,
                $deploymentEnvironmentName,
            );
            $batchPolicy = new OpenTelemetryBatchPolicy(
                $batch['queue_capacity'],
                $batch['maximum_batch_size'],
                $batch['transport_timeout_milliseconds'],
            );
        } catch (Throwable) {
            $this->invalid();
        }

        return [
            'model' => new ObservabilityConfigurationModel(self::PROFILE, $typedResource, $batchPolicy),
        ] + $snapshot;
    }

    private function validateBytes(string $bytes): void
    {
        if (
            '' === $bytes
            || strlen($bytes) > PathGuard::MAX_SOURCE_BYTES
            || str_starts_with($bytes, "\xEF\xBB\xBF")
            || 1 !== preg_match('//u', $bytes)
            || 1 === preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $bytes)
            || 1 === preg_match('/[\x{0080}-\x{009F}]/u', $bytes)
        ) {
            $this->invalid();
        }
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

    /** @param array<array-key, mixed> $value
     * @param list<string> $expected
     */
    private function hasExactKeys(array $value, array $expected): bool
    {
        $keys = [];
        foreach (array_keys($value) as $key) {
            if (!is_string($key)) {
                return false;
            }
            $keys[] = $key;
        }
        sort($keys, SORT_STRING);
        sort($expected, SORT_STRING);

        return $keys === $expected;
    }

    private function invalid(): never
    {
        throw new InvalidObservabilitySourceException(ObservabilityBuildDiagnostic::InvalidSource);
    }
}
