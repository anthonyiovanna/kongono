<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\ObservationKind;
use Kongono\OpenTelemetry\Exception\OpenTelemetryMappingException;
use Kongono\OpenTelemetry\OpenTelemetryResource;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use Kongono\OpenTelemetry\OtlpSignal;
use Opentelemetry\Proto\Collector\Logs\V1\ExportLogsServiceRequest;
use Opentelemetry\Proto\Collector\Metrics\V1\ExportMetricsServiceRequest;
use Opentelemetry\Proto\Collector\Trace\V1\ExportTraceServiceRequest;
use Opentelemetry\Proto\Common\V1\InstrumentationScope;
use Opentelemetry\Proto\Logs\V1\ResourceLogs;
use Opentelemetry\Proto\Logs\V1\ScopeLogs;
use Opentelemetry\Proto\Metrics\V1\ResourceMetrics;
use Opentelemetry\Proto\Metrics\V1\ScopeMetrics;
use Opentelemetry\Proto\Resource\V1\Resource;
use Opentelemetry\Proto\Trace\V1\ResourceSpans;
use Opentelemetry\Proto\Trace\V1\ScopeSpans;
use OpenTelemetry\SemConv\ResourceAttributes;
use OpenTelemetry\SemConv\Version;
use Throwable;

/** Builds one exact parseable OTLP request for one contiguous observation kind. */
#[Api(ApiStatus::Internal)]
final readonly class OtlpRequestFactory
{
    public const CONTENT_TYPE = 'application/x-protobuf';
    public const PROFILE_VERSION = 1;
    public const SCOPE_VERSION = '10F-profile-1';

    private OtlpTraceMapper $traceMapper;

    public function __construct(private OpenTelemetryResource $resource, OpenTelemetryTimeAnchor $timeAnchor)
    {
        $this->traceMapper = new OtlpTraceMapper($timeAnchor);
    }

    /**
     * @param non-empty-list<QueuedObservation> $records
     * @return array{OtlpSignal, string}
     */
    public function create(array $records): array
    {
        try {
            $kind = $records[0]->observation()->kind();
            foreach ($records as $record) {
                if ($record->observation()->kind() !== $kind) {
                    throw OpenTelemetryMappingException::invalid();
                }
            }
            $schemaUrl = Version::VERSION_1_38_0->url();
            $resource = $this->resourceMessage();
            return match ($kind) {
                ObservationKind::Trace => [
                    OtlpSignal::Traces,
                    (new ExportTraceServiceRequest())->setResourceSpans([
                        (new ResourceSpans())->setResource($resource)->setSchemaUrl($schemaUrl)->setScopeSpans([
                            (new ScopeSpans())->setScope($this->scope('kongono.tracing'))->setSchemaUrl($schemaUrl)->setSpans($this->traceMapper->map($records)),
                        ]),
                    ])->serializeToString(),
                ],
                ObservationKind::Metric => [
                    OtlpSignal::Metrics,
                    (new ExportMetricsServiceRequest())->setResourceMetrics([
                        (new ResourceMetrics())->setResource($resource)->setSchemaUrl($schemaUrl)->setScopeMetrics([
                            (new ScopeMetrics())->setScope($this->scope('kongono.metrics'))->setSchemaUrl($schemaUrl)->setMetrics(OtlpMetricMapper::map($records)),
                        ]),
                    ])->serializeToString(),
                ],
                ObservationKind::OperationalEvent, ObservationKind::SecurityAudit => [
                    OtlpSignal::Logs,
                    (new ExportLogsServiceRequest())->setResourceLogs([
                        (new ResourceLogs())->setResource($resource)->setSchemaUrl($schemaUrl)->setScopeLogs([
                            (new ScopeLogs())
                                ->setScope($this->scope($kind === ObservationKind::OperationalEvent ? 'kongono.operational-events' : 'kongono.security-audit'))
                                ->setSchemaUrl($schemaUrl)
                                ->setLogRecords(OtlpLogMapper::map($records)),
                        ]),
                    ])->serializeToString(),
                ],
            };
        } catch (OpenTelemetryMappingException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw OpenTelemetryMappingException::invalid($exception, $records[0]->observation()->kind()->value);
        }
    }

    private function resourceMessage(): Resource
    {
        $values = [
            ResourceAttributes::SERVICE_NAME => $this->resource->serviceName(),
            ResourceAttributes::TELEMETRY_SDK_LANGUAGE => 'php',
            ResourceAttributes::TELEMETRY_SDK_NAME => 'kongono',
        ];
        if (null !== $this->resource->serviceVersion()) {
            $values[ResourceAttributes::SERVICE_VERSION] = $this->resource->serviceVersion();
        }
        if (null !== $this->resource->deploymentEnvironmentName()) {
            $values[ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME] = $this->resource->deploymentEnvironmentName();
        }
        ksort($values, SORT_STRING);
        $attributes = [];
        foreach ($values as $key => $value) {
            $attributes[] = OtlpAttributeMapper::keyValue($key, $value);
        }

        return (new Resource())->setAttributes($attributes);
    }

    private function scope(string $name): InstrumentationScope
    {
        return (new InstrumentationScope())->setName($name)->setVersion(self::SCOPE_VERSION);
    }
}
