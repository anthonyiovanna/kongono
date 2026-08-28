<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ObservabilityBuild\Internal\CanonicalJson;
use Kongono\ObservabilityBuild\Internal\PathGuard;

/** Builds deterministic canonical evidence from one exact observability source. */
#[Api(ApiStatus::Experimental)]
final class ObservabilityArtifactBuilder
{
    public const MAX_ARTIFACT_BYTES = 8192;
    public const CONTRACT_VERSION = 1;

    /** @var list<string> */
    private const IMPLEMENTATION_PATHS = [
        'src/Build/ObservabilityArtifact.php',
        'src/Build/ObservabilityArtifactBuilder.php',
        'src/Build/ObservabilityArtifactPublisher.php',
        'src/Build/ObservabilityArtifactValidator.php',
        'src/Build/ObservabilitySourceReader.php',
        'src/Diagnostic/ObservabilityBuildDiagnostic.php',
        'src/Exception/ExceptionInterface.php',
        'src/Exception/InvalidObservabilityArtifactException.php',
        'src/Exception/InvalidObservabilitySourceException.php',
        'src/Exception/ObservabilityBuildException.php',
        'src/Exception/ObservabilityPublicationException.php',
        'src/Exception/ObservabilityWorkflowException.php',
        'src/Exception/StaleObservabilityArtifactException.php',
        'src/Internal/CanonicalJson.php',
        'src/Internal/PathGuard.php',
        'src/Internal/StrictJsonDecoder.php',
        'src/Model/ObservabilityConfigurationModel.php',
        'src/Workflow/CurrentObservabilityConfiguration.php',
        'src/Workflow/ObservabilityBuildWorkflow.php',
        'src/Workflow/PlannedObservabilityConfiguration.php',
    ];

    public function build(string $applicationRoot): ObservabilityArtifact
    {
        $source = (new ObservabilitySourceReader())->read($applicationRoot);
        $configuration = $source['model'];
        $resource = $configuration->resource();
        $batch = $configuration->batchPolicy();
        $model = [
            'profile' => $configuration->profile(),
            'resource' => [
                'service_name' => $resource->serviceName(),
                'service_version' => $resource->serviceVersion(),
                'deployment_environment_name' => $resource->deploymentEnvironmentName(),
            ],
            'batch' => [
                'queue_capacity' => $batch->queueCapacity(),
                'maximum_batch_size' => $batch->maximumBatchSize(),
                'transport_timeout_milliseconds' => $batch->transportTimeoutMilliseconds(),
            ],
        ];
        $modelSha256 = hash('sha256', CanonicalJson::encode($model));
        $implementationSha256 = $this->implementationSha256();
        $bytes = CanonicalJson::encode([
            'schema' => 'kongono.observability-artifact',
            'version' => 1,
            'profile' => $configuration->profile(),
            'resource' => $model['resource'],
            'batch' => $model['batch'],
            'source' => ['path' => PathGuard::SOURCE_PATH, 'sha256' => $source['sha256']],
            'model_sha256' => $modelSha256,
            'builder' => [
                'package' => 'kongono/observability-build',
                'contract_version' => self::CONTRACT_VERSION,
                'implementation_sha256' => $implementationSha256,
            ],
        ]);
        if (strlen($bytes) > self::MAX_ARTIFACT_BYTES) {
            throw new \LogicException('Observability artifact exceeded its fixed bound.');
        }
        PathGuard::reverify($source);

        return new ObservabilityArtifact(
            $bytes,
            hash('sha256', $bytes),
            $source['sha256'],
            $modelSha256,
            $implementationSha256,
            $configuration->profile(),
            $resource,
            $batch,
        );
    }

    private function implementationSha256(): string
    {
        $root = PathGuard::applicationRoot(dirname(__DIR__, 2));
        $records = [];
        foreach (self::IMPLEMENTATION_PATHS as $path) {
            $snapshot = PathGuard::artifact($root, $root . '/' . $path, PathGuard::MAX_SOURCE_BYTES);
            $records[] = implode("\t", [$path, (string) $snapshot['size'], $snapshot['sha256']]);
        }

        return hash('sha256', implode("\n", $records));
    }
}
