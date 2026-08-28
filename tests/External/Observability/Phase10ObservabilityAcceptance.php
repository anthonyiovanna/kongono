<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Observability;

use Closure;
use JsonException;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Metrics\MetricDimensionSchema;
use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricInstrumentKind;
use Kongono\Metrics\MetricMeasurement;
use Kongono\Metrics\MetricNumber;
use Kongono\Metrics\MetricPlan;
use Kongono\Metrics\MetricUnit;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\CorrelationId;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\ObservabilityApplication\ApplicationObservabilityFactory;
use Kongono\ObservabilityApplication\ApplicationObservabilityOperation;
use Kongono\ObservabilityApplication\ApplicationObservabilityOperationState;
use Kongono\ObservabilityApplication\ApplicationObservabilityPlan;
use Kongono\ObservabilityApplication\ApplicationObservabilityWork;
use Kongono\ObservabilityApplication\Exception\ApplicationObservabilityLifecycleException;
use Kongono\ObservabilityBuild\Workflow\ObservabilityBuildWorkflow;
use Kongono\OpenTelemetry\Exception\OpenTelemetryBackpressureException;
use Kongono\OpenTelemetry\Exception\OpenTelemetryExportException;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryBridge;
use Kongono\OpenTelemetry\OpenTelemetryClock;
use Kongono\OpenTelemetry\OpenTelemetryResource;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use Kongono\OpenTelemetry\OtlpHttpTransport;
use Kongono\OpenTelemetry\OtlpSignal;
use Kongono\OperationalEvents\OperationalEvent;
use Kongono\OperationalEvents\OperationalEventDefinition;
use Kongono\OperationalEvents\OperationalEventField;
use Kongono\OperationalEvents\OperationalEventSchema;
use Kongono\OperationalEvents\OperationalEventSeverity;
use Kongono\SecurityAudit\SecurityAuditDefinition;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\SecurityAudit\SecurityAuditField;
use Kongono\SecurityAudit\SecurityAuditOutcome;
use Kongono\SecurityAudit\SecurityAuditRetentionIntent;
use Kongono\SecurityAudit\SecurityAuditSchema;
use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceClock;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TraceIdentifierSource;
use Kongono\Tracing\TraceSampler;
use Kongono\Tracing\TraceSamplingDecision;
use Kongono\Tracing\TraceSamplingRequest;
use Opentelemetry\Proto\Collector\Logs\V1\ExportLogsServiceRequest;
use Opentelemetry\Proto\Collector\Metrics\V1\ExportMetricsServiceRequest;
use Opentelemetry\Proto\Collector\Trace\V1\ExportTraceServiceRequest;
use Opentelemetry\Proto\Logs\V1\ResourceLogs;
use Opentelemetry\Proto\Logs\V1\ScopeLogs;
use RuntimeException;
use Throwable;
use Traversable;

/** Executes the closed real-process Phase 10 observability acceptance profile. */
final class Phase10ObservabilityAcceptance
{
    private const int FAILURE = 1;
    private const int MAX_STREAM_BYTES = 1_048_576;
    private const int PROCESS_TIMEOUT_SECONDS = 900;
    private const string FAILURE_BYTES = "phase10_acceptance_failed\n";
    private const string PRIVATE_FIXTURE = 'Phase10PrivateFixture-DoNotDisclose';
    private const string ENVIRONMENT_SECRET = 'Phase10EnvironmentSecret-DoNotDisclose';

    private string $repositoryRoot = '';

    /** @var list<string> */
    private array $composerCommand = [];

    private int $caseCount = 0;

    /** @var list<string> */
    private array $observedOutput = [];

    private ?ApplicationObservabilityOperation $operationEvidence = null;

    /**
     * @param array<array-key, mixed> $arguments
     * @param resource                $stdout
     * @param resource                $stderr
     */
    public function run(array $arguments, mixed $stdout, mixed $stderr): int
    {
        $temporary = null;
        $before = null;
        $failed = false;

        try {
            $this->require(array_is_list($arguments) && [] === $arguments, 'invalid_invocation');
            $this->require(is_resource($stdout) && is_resource($stderr), 'invalid_output_ports');
            $root = realpath(dirname(__DIR__, 3));
            if (false === $root || is_link($root) || !is_dir($root)) {
                throw new RuntimeException('repository_root_invalid');
            }
            $this->repositoryRoot = self::normalize($root);
            $this->composerCommand = $this->resolveComposerCommand();
            $before = $this->repositoryStatus();
            $temporary = TemporaryObservabilityApplication::create($this->repositoryRoot);
            $this->accept($temporary);
        } catch (Throwable) {
            $failed = true;
        } finally {
            if (null !== $temporary) {
                try {
                    $root = $temporary->root();
                    $temporary->cleanup();
                    $this->require(!file_exists($root) && !is_link($root), 'temporary_residue');
                    $this->caseCompleted();
                } catch (Throwable) {
                    $failed = true;
                }
            }
            if (null !== $before) {
                try {
                    $this->require($before === $this->repositoryStatus(), 'repository_status_changed');
                    $this->caseCompleted();
                } catch (Throwable) {
                    $failed = true;
                }
            }
        }

        if ($failed || $this->caseCount < 1) {
            $this->write($stderr, self::FAILURE_BYTES);

            return self::FAILURE;
        }

        $success = json_encode([
            'status' => 'ok',
            'profile' => 'kongono-phase10-observability',
            'case_count' => $this->caseCount,
            'cleanup' => 'complete',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        $this->write($stdout, $success);

        return 0;
    }

    private function accept(TemporaryObservabilityApplication $temporary): void
    {
        $applicationRoot = $temporary->applicationRoot();
        $composerHome = $temporary->root() . '/composer-home';
        $temporary->write($composerHome . '/cache/.keep', '');
        $environment = [
            'COMPOSER_CACHE_DIR' => $composerHome . '/cache',
            'COMPOSER_DISABLE_NETWORK' => '1',
            'COMPOSER_HOME' => $composerHome,
            'KONGONO_PHASE10_UNRELATED_SECRET' => self::ENVIRONMENT_SECRET,
        ];

        $install = $this->process([
            ...$this->composerCommand,
            'install',
            '--no-interaction',
            '--no-ansi',
            '--no-plugins',
            '--no-progress',
        ], $applicationRoot, $environment);
        $this->expectCompleted($install);
        $this->observe($install);
        $this->require(is_file($applicationRoot . '/vendor/autoload.php'), 'application_install_missing');
        $this->require(!$this->containsLink($applicationRoot . '/vendor'), 'application_install_linked');
        $this->caseCompleted();

        $lockIdentity = hash('sha256', $temporary->read($temporary->composerLock()));
        $build = $this->composerScript($applicationRoot, 'app:build', $environment);
        $this->expectSuccess($build);
        $this->observe($build);
        $identities = $this->buildIdentities($build->stdout());
        $this->require(1 === count($temporary->buildFiles()), 'build_output_count_invalid');
        $this->require($lockIdentity === hash('sha256', $temporary->read($temporary->composerLock())), 'application_lock_changed');
        $this->caseCompleted();

        $buildIdentity = $temporary->buildIdentity();
        $check = $this->composerScript($applicationRoot, 'app:check', $environment);
        $this->expectSuccess($check);
        $this->observe($check);
        $this->require($identities === $this->buildIdentities($check->stdout()), 'check_identity_mismatch');
        $this->require($buildIdentity === $temporary->buildIdentity(), 'check_wrote_output');
        $this->caseCompleted();

        $run = $this->composerScript($applicationRoot, 'app:run', $environment);
        $this->expectSuccess($run);
        $this->observe($run);
        $this->require("observability-example-ok\nsignal-paths-distinct\npost-close-refused\n" === $run->stdout() && '' === $run->stderr(), 'application_run_invalid');
        $this->caseCompleted();

        $repeatBuild = $this->composerScript($applicationRoot, 'app:build', $environment);
        $this->expectSuccess($repeatBuild);
        $this->observe($repeatBuild);
        $this->require($build->stdout() === $repeatBuild->stdout() && $buildIdentity === $temporary->buildIdentity(), 'repeat_build_changed_state');
        $this->caseCompleted();

        $this->acceptConfigurationCurrentness($temporary, $environment);
        $this->acceptConsoleReadOnly($temporary);
        $this->acceptApplicationAndProtobuf($temporary);
        $this->acceptBridgeFailureBoundaries();
        $this->acceptFocusedPackageProof();
        $this->acceptEarlierPhases();

        $allOutput = implode('', $this->observedOutput);
        foreach ([
            'private_fixture' => self::PRIVATE_FIXTURE,
            'environment_secret' => self::ENVIRONMENT_SECRET,
            'temporary_root' => $temporary->root(),
            'temporary_windows_root' => str_replace('/', '\\', $temporary->root()),
            'username' => get_current_user(),
            'hostname' => false !== gethostname() ? gethostname() : 'phase10-hostname-unavailable',
            'stack_trace' => 'Stack trace',
            'lowercase_stack_trace' => 'stack trace',
            'terminal_escape' => "\033",
        ] as $label => $protected) {
            $this->require('' === $protected || !str_contains($allOutput, $protected), 'protected_output_disclosed_' . $label);
        }
        $this->caseCompleted();
    }

    /** @param array<string, string> $environment */
    private function acceptConfigurationCurrentness(TemporaryObservabilityApplication $temporary, array $environment): void
    {
        $source = $temporary->read($temporary->source());
        $sourceValue = $this->object(json_decode($source, true, flags: JSON_THROW_ON_ERROR), 'configuration_fixture_invalid');
        $buildFiles = $temporary->buildFiles();
        $this->require(1 === count($buildFiles), 'artifact_inventory_invalid');
        $artifact = $temporary->buildDirectory() . '/' . $buildFiles[0];
        $artifactBytes = $temporary->read($artifact);

        $variants = [];
        $variant = $sourceValue;
        $variant['endpoint'] = 'https://invalid.example';
        $variants[] = $variant;
        $variant = $sourceValue;
        $variant['credential'] = self::PRIVATE_FIXTURE;
        $variants[] = $variant;
        $variant = $sourceValue;
        $variant['profile'] = 'unsupported-profile';
        $variants[] = $variant;
        $variant = $sourceValue;
        $batch = $this->object($variant['batch'] ?? null, 'configuration_batch_invalid');
        $batch['queue_capacity'] = 0;
        $variant['batch'] = $batch;
        $variants[] = $variant;
        $variant = $sourceValue;
        $variant['extension'] = ['provider' => 'unapproved'];
        $variants[] = $variant;

        foreach ($variants as $configurationVariant) {
            $temporary->write($temporary->source(), json_encode($configurationVariant, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
            $failure = $this->composerScript($temporary->applicationRoot(), 'app:build', $environment);
            $this->expectFailure($failure);
            $this->observe($failure);
            $this->caseCompleted();
        }
        $temporary->write($temporary->source(), $source);

        $temporary->write($artifact, $artifactBytes . "tampered\n");
        $tampered = $this->composerScript($temporary->applicationRoot(), 'app:check', $environment);
        $this->expectFailure($tampered);
        $this->observe($tampered);
        $temporary->write($artifact, $artifactBytes);
        $this->caseCompleted();

        $temporary->unlink($artifact);
        $missing = $this->composerScript($temporary->applicationRoot(), 'app:check', $environment);
        $this->expectFailure($missing);
        $this->observe($missing);
        $temporary->write($artifact, $artifactBytes);
        $this->caseCompleted();

        $healthy = $this->composerScript($temporary->applicationRoot(), 'app:check', $environment);
        $this->expectSuccess($healthy);
        $this->observe($healthy);
        $this->caseCompleted();
    }

    private function acceptConsoleReadOnly(TemporaryObservabilityApplication $temporary): void
    {
        $edge = $this->ordinaryExecutable($this->repositoryRoot . '/vendor/bin/kongo');
        $arguments = [
            '--application-root=' . $temporary->applicationRoot(),
            '--output-directory=' . $temporary->buildDirectory(),
        ];
        $before = $temporary->treeIdentity();
        foreach (['observability:inspect', 'observability:check'] as $command) {
            $machine = $this->process([PHP_BINARY, $edge, '--json', $command, ...$arguments], $this->repositoryRoot);
            $human = $this->process([PHP_BINARY, $edge, $command, ...$arguments], $this->repositoryRoot);
            $this->expectSuccess($machine);
            $this->expectSuccess($human);
            $this->observe($machine);
            $this->observe($human);
            $document = $this->canonicalJsonLine($machine->stdout());
            $this->require('succeeded' === ($document['outcome'] ?? null), 'observability_command_outcome_invalid');
            $this->require(str_contains($human->stdout(), 'kongono.opentelemetry.otlp-http-protobuf.v1'), 'observability_human_profile_missing');
            $this->require($before === $temporary->treeIdentity(), 'observability_command_wrote_state');
            $this->caseCompleted();
        }

        foreach (['--plan', '--apply'] as $intent) {
            $mutation = $this->process([PHP_BINARY, $edge, '--json', 'observability:check', $intent, ...$arguments], $this->repositoryRoot);
            $this->expectFailure($mutation);
            $this->observe($mutation);
            $this->require($before === $temporary->treeIdentity(), 'observability_mutation_changed_state');
            $this->caseCompleted();
        }
    }

    private function acceptApplicationAndProtobuf(TemporaryObservabilityApplication $temporary): void
    {
        $configuration = (new ObservabilityBuildWorkflow())->check($temporary->applicationRoot(), $temporary->buildDirectory());
        $transport = new class implements OtlpHttpTransport {
            /** @var list<OtlpSignal> */ public array $signals = [];
            /** @var list<string> */ public array $payloads = [];
            /** @var list<string> */ public array $endpoints = [];
            public int $flushes = 0;
            public int $closes = 0;
            public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
            {
                if ($signal->endpointSuffix() !== $endpointSuffix || 'application/x-protobuf' !== $contentType || $timeoutMilliseconds < 1) {
                    throw new RuntimeException('capture_mapping_invalid');
                }
                $this->signals[] = $signal;
                $this->endpoints[] = $endpointSuffix;
                $this->payloads[] = $payload;
            }
            public function flush(int $timeoutMilliseconds): void
            {
                if ($timeoutMilliseconds < 1) {
                    throw new RuntimeException('capture_timeout_invalid');
                }
                ++$this->flushes;
            }
            public function close(int $timeoutMilliseconds): void
            {
                if ($timeoutMilliseconds < 1) {
                    throw new RuntimeException('capture_timeout_invalid');
                }
                ++$this->closes;
            }
        };
        $application = (new ApplicationObservabilityFactory())->create(
            $configuration,
            new class implements TraceClock {
                private int $tick = 0;
                public function nowNanoseconds(): int
                {
                    return ++$this->tick;
                }
            },
            new class implements TraceIdentifierSource {
                private int $trace = 0;
                private int $span = 0;
                public function nextTraceId(): TraceId
                {
                    return new TraceId(sprintf('%032x', ++$this->trace));
                }
                public function nextSpanId(): SpanId
                {
                    return new SpanId(sprintf('%016x', ++$this->span));
                }
            },
            new readonly class implements TraceSampler {
                public function decide(TraceSamplingRequest $request): TraceSamplingDecision
                {
                    unset($request);

                    return TraceSamplingDecision::Record;
                }
            },
            new OpenTelemetryTimeAnchor(0, 1),
            new class implements OpenTelemetryClock {
                private int $epoch = 10_000;
                public function epochNanoseconds(): int
                {
                    return ++$this->epoch;
                }
            },
            $transport,
        );

        $context = ObservabilityContext::correlated(new CorrelationId('phase10-correlation'));
        $dimensionKey = new AttributeKey('app.region');
        $dimensionValue = AttributeValue::text('east');
        $instrument = new MetricInstrument(
            new SignalIdentity('metric.acceptance.operations', 1),
            MetricInstrumentKind::Counter,
            new MetricUnit('operations'),
            new MetricDimensionSchema([]),
        );
        $eventDefinition = new OperationalEventDefinition(
            new SignalIdentity('operations.acceptance.completed', 1),
            OperationalEventSeverity::Info,
            new OperationalEventSchema([new OperationalEventField($dimensionKey, AttributeValueKind::Text, DiagnosticDisclosure::Safe, true, [$dimensionValue])]),
        );
        $auditDefinition = new SecurityAuditDefinition(
            new SignalIdentity('security.acceptance.observed', 1),
            SecurityAuditRetentionIntent::Short,
            new SecurityAuditSchema([new SecurityAuditField($dimensionKey, AttributeValueKind::Text, DiagnosticDisclosure::Protected, true, [$dimensionValue])]),
        );
        $attributes = new AttributeSet([new SignalAttribute($dimensionKey, $dimensionValue)]);
        $plan = new ApplicationObservabilityPlan(
            $context,
            new SignalIdentity('trace.acceptance.operation', 1),
            new AttributeSet([]),
            new MetricPlan([$instrument]),
        );
        $this->operationEvidence = null;
        $capture = function (ApplicationObservabilityOperation $operation): void {
            $this->operationEvidence = $operation;
        };
        $result = $application->execute($plan, new /** @implements ApplicationObservabilityWork<string> */ readonly class ($context, $instrument, $eventDefinition, $auditDefinition, $attributes, $capture) implements ApplicationObservabilityWork {
            /** @param Closure(ApplicationObservabilityOperation): void $capture */
            public function __construct(
                private ObservabilityContext $context,
                private MetricInstrument $instrument,
                private OperationalEventDefinition $event,
                private SecurityAuditDefinition $audit,
                private AttributeSet $attributes,
                private Closure $capture,
            ) {}
            public function execute(ApplicationObservabilityOperation $operation): mixed
            {
                ($this->capture)($operation);
                $child = $operation->traceOperation()->beginSpan(new SignalIdentity('trace.acceptance.child', 1), new AttributeSet([]));
                $child->complete(ObservationStatus::Ok);
                $operation->metricCollection()->record($this->instrument, new MetricMeasurement(new MetricNumber(1), new AttributeSet([])));
                $operation->recordOperationalEvent(new OperationalEvent($this->context, $this->event, ObservationStatus::Ok, $this->attributes));
                $operation->recordSecurityAudit(new SecurityAuditEvidence($this->context, $this->audit, SecurityAuditOutcome::Succeeded, $this->attributes));

                return 'phase10-work-complete';
            }
        });
        $this->require('phase10-work-complete' === $result, 'application_result_invalid');
        $this->require($this->operationEvidence instanceof ApplicationObservabilityOperation && ApplicationObservabilityOperationState::Closed === $this->operationEvidence->state(), 'operation_state_retained');
        $this->caseCompleted();

        $this->require(
            [OtlpSignal::Traces, OtlpSignal::Logs, OtlpSignal::Logs, OtlpSignal::Metrics, OtlpSignal::Traces] === $transport->signals,
            'signal_order_invalid',
        );
        $this->require(5 === count($transport->payloads) && 1 === $transport->flushes, 'export_lifecycle_invalid');
        $scopes = [];
        foreach ($transport->payloads as $index => $payload) {
            $this->require('' !== $payload && !str_contains($payload, self::PRIVATE_FIXTURE), 'protobuf_payload_invalid');
            match ($transport->signals[$index]) {
                OtlpSignal::Traces => (new ExportTraceServiceRequest())->mergeFromString($payload),
                OtlpSignal::Metrics => (new ExportMetricsServiceRequest())->mergeFromString($payload),
                OtlpSignal::Logs => array_push($scopes, ...$this->logScopes($payload)),
            };
        }
        $this->require(['kongono.operational-events', 'kongono.security-audit'] === $scopes, 'log_scope_separation_invalid');
        $this->caseCompleted();

        $application->flush();
        $application->close();
        $application->close();
        $this->require(3 === $transport->flushes && 1 === $transport->closes, 'application_close_order_invalid');
        try {
            $application->flush();
            throw new RuntimeException('post_close_flush_succeeded');
        } catch (ApplicationObservabilityLifecycleException) {
        }
        $this->caseCompleted();
    }

    private function acceptBridgeFailureBoundaries(): void
    {
        $transport = new class implements OtlpHttpTransport {
            public int $sends = 0;
            public int $flushes = 0;
            public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
            {
                ++$this->sends;
            }
            public function flush(int $timeoutMilliseconds): void
            {
                ++$this->flushes;
            }
            public function close(int $timeoutMilliseconds): void {}
        };
        $bridge = $this->bridge($transport, 1, 1);
        $bridge->record($this->gauge('one'));
        try {
            $bridge->record($this->gauge('two'));
            throw new RuntimeException('backpressure_not_visible');
        } catch (OpenTelemetryBackpressureException $failure) {
            $this->require(!str_contains((string) $failure, self::PRIVATE_FIXTURE), 'backpressure_disclosure');
        }
        $this->require(1 === $bridge->queuedCount() && 0 === $transport->sends && 0 === $transport->flushes, 'backpressure_changed_transport');
        $bridge->flush();
        $this->caseCompleted();

        $primary = new RuntimeException(self::PRIVATE_FIXTURE);
        $failingTransport = new readonly class ($primary) implements OtlpHttpTransport {
            public function __construct(private RuntimeException $failure) {}
            public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
            {
                throw $this->failure;
            }
            public function flush(int $timeoutMilliseconds): void {}
            public function close(int $timeoutMilliseconds): void {}
        };
        $failing = $this->bridge($failingTransport, 2, 1);
        $failing->record($this->gauge('failure'));
        try {
            $failing->flush();
            throw new RuntimeException('transport_failure_not_visible');
        } catch (OpenTelemetryExportException $failure) {
            $this->require($primary === $failure->getPrevious(), 'transport_failure_identity_lost');
            $this->require(!str_contains((string) $failure, self::PRIVATE_FIXTURE), 'transport_failure_disclosed');
        }
        $this->require(1 === $failing->queuedCount(), 'failed_export_dropped_queue');
        $this->caseCompleted();
    }

    private function acceptFocusedPackageProof(): void
    {
        foreach ([
            'packages/observability/tests',
            'packages/tracing/tests',
            'packages/metrics/tests',
            'packages/operational-events/tests',
            'packages/security-audit/tests',
            'packages/opentelemetry/tests',
            'packages/observability-build/tests',
            'packages/observability-application/tests',
        ] as $suite) {
            $result = $this->process([
                PHP_BINARY,
                $this->ordinaryExecutable($this->repositoryRoot . '/vendor/bin/phpunit'),
                '--configuration=' . $this->repositoryRoot . '/phpunit.xml.dist',
                '--colors=never',
                '--do-not-record-test-run-history',
                $suite,
            ], $this->repositoryRoot, ['KONGONO_PHASE10_UNRELATED_SECRET' => self::ENVIRONMENT_SECRET]);
            $this->expectSuccess($result);
            $this->observe($result);
            $this->caseCompleted();
        }
    }

    private function acceptEarlierPhases(): void
    {
        foreach (['acceptance:phase8' => 'kongono-phase8-cli', 'acceptance:phase9' => 'kongono-phase9-security'] as $script => $profile) {
            $result = $this->process([...$this->composerCommand, '--no-interaction', '--no-ansi', $script], $this->repositoryRoot, [
                'COMPOSER_DISABLE_NETWORK' => '1',
                'KONGONO_PHASE10_UNRELATED_SECRET' => self::ENVIRONMENT_SECRET,
            ]);
            $this->expectSuccess($result);
            $this->observe($result);
            $document = $this->canonicalJsonLine($result->stdout());
            $this->require($profile === ($document['profile'] ?? null), 'prior_phase_profile_invalid');
            $this->caseCompleted();
        }
    }

    private function bridge(OtlpHttpTransport $transport, int $capacity, int $batch): OpenTelemetryBridge
    {
        return new OpenTelemetryBridge(
            new OpenTelemetryResource('phase10-acceptance'),
            new OpenTelemetryBatchPolicy($capacity, $batch, 100),
            new OpenTelemetryTimeAnchor(0, 1),
            new readonly class implements OpenTelemetryClock {
                public function epochNanoseconds(): int
                {
                    return 1;
                }
            },
            $transport,
        );
    }

    private function gauge(string $name): Observation
    {
        return new Observation(
            ObservabilityContext::uncorrelated(),
            new SignalIdentity($name, 1),
            ObservationKind::Metric,
            ObservationStatus::Unset,
            new AttributeSet([
                $this->text('metric.instrument', 'gauge'),
                $this->text('metric.unit', 'items'),
                $this->integer('metric.measurement_count', 1),
                $this->integer('metric.last', 1),
            ]),
        );
    }

    /** @return list<string> */
    private function logScopes(string $payload): array
    {
        $request = new ExportLogsServiceRequest();
        $request->mergeFromString($payload);
        $resource = $this->firstMessage($request->getResourceLogs(), ResourceLogs::class);
        $names = [];
        foreach ($this->messages($resource->getScopeLogs(), ScopeLogs::class) as $scope) {
            $instrumentation = $scope->getScope();
            if (null === $instrumentation) {
                throw new RuntimeException('log_scope_missing');
            }
            $names[] = $instrumentation->getName();
        }
        $this->require([] !== $names, 'log_scope_missing');

        return $names;
    }

    /**
     * @template T of object
     * @param class-string<T>  $type
     * @return T
     */
    private function firstMessage(mixed $messages, string $type): object
    {
        $typedMessages = $this->messages($messages, $type);
        if ([] !== $typedMessages) {
            return $typedMessages[0];
        }

        throw new RuntimeException('protobuf_message_missing');
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return list<T>
     */
    private function messages(mixed $messages, string $type): array
    {
        if (!$messages instanceof Traversable) {
            throw new RuntimeException('protobuf_collection_invalid');
        }
        $typedMessages = [];
        foreach ($messages as $message) {
            if ($message instanceof $type) {
                $typedMessages[] = $message;
            }
        }

        return $typedMessages;
    }

    private function text(string $key, string $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::text($value));
    }

    private function integer(string $key, int $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::integer($value));
    }

    /** @return array<string, string> */
    private function buildIdentities(string $output): array
    {
        $identities = [];
        foreach (['observability_artifact_sha256', 'source_sha256', 'profile'] as $name) {
            if (1 !== preg_match('/^' . preg_quote($name, '/') . '=([^\r\n]+)$/m', $output, $match)) {
                throw new RuntimeException('build_identity_missing');
            }
            $identities[$name] = $match[1];
        }
        $this->require(1 === preg_match('/\A[a-f0-9]{64}\z/D', $identities['observability_artifact_sha256']), 'artifact_identity_invalid');
        $this->require(1 === preg_match('/\A[a-f0-9]{64}\z/D', $identities['source_sha256']), 'source_identity_invalid');
        $this->require('kongono.opentelemetry.otlp-http-protobuf.v1' === $identities['profile'], 'profile_identity_invalid');

        return $identities;
    }

    /** @param array<string, string|null> $environment */
    private function composerScript(string $workingDirectory, string $script, array $environment): ProcessResult
    {
        return $this->process([...$this->composerCommand, '--no-interaction', '--no-ansi', $script], $workingDirectory, $environment);
    }

    /** @return list<string> */
    private function resolveComposerCommand(): array
    {
        $candidates = [];
        $configured = getenv('COMPOSER_BINARY');
        if (is_string($configured) && '' !== $configured) {
            $candidates[] = $configured;
        }
        $path = getenv('PATH');
        if (is_string($path)) {
            foreach (explode(PATH_SEPARATOR, $path) as $directory) {
                if ('' !== $directory) {
                    $candidates[] = rtrim($directory, '\\/') . DIRECTORY_SEPARATOR . 'composer.phar';
                }
            }
        }
        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);
            if (false !== $resolved && !is_link($candidate) && is_file($resolved) && is_readable($resolved)) {
                return [PHP_BINARY, self::normalize($resolved)];
            }
        }
        throw new RuntimeException('composer_binary_unavailable');
    }

    /**
     * @param list<string>                    $command
     * @param array<string, string|null>|null $environment
     */
    private function process(array $command, string $workingDirectory, ?array $environment = null): ProcessResult
    {
        if ([] === $command) {
            return new ProcessResult(false, null, '', '', 'invalid_process');
        }
        $inherited = getenv();
        if (null !== $environment) {
            foreach ($environment as $name => $value) {
                if (null === $value) {
                    unset($inherited[$name]);
                } else {
                    $inherited[$name] = $value;
                }
            }
        }
        $pipes = [];
        $process = @proc_open(
            $command,
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            $workingDirectory,
            $inherited,
            ['bypass_shell' => true, 'suppress_errors' => true, 'binary_pipes' => true],
        );
        if (!is_resource($process) || 3 !== count($pipes)) {
            return new ProcessResult(false, null, '', '', 'process_start_failed');
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $blocked = null;
        $observedExit = null;
        $deadline = microtime(true) + self::PROCESS_TIMEOUT_SECONDS;
        try {
            while (true) {
                $this->drain($pipes[1], $stdout);
                $this->drain($pipes[2], $stderr);
                if (strlen($stdout) > self::MAX_STREAM_BYTES || strlen($stderr) > self::MAX_STREAM_BYTES) {
                    $blocked = 'output_limit';
                    proc_terminate($process);
                    break;
                }
                $status = proc_get_status($process);
                if (!$status['running']) {
                    $observedExit = $status['exitcode'] >= 0 ? $status['exitcode'] : null;
                    break;
                }
                if (microtime(true) >= $deadline) {
                    $blocked = 'timeout';
                    proc_terminate($process);
                    break;
                }
                usleep(10_000);
            }
            stream_set_blocking($pipes[1], true);
            stream_set_blocking($pipes[2], true);
            $this->drain($pipes[1], $stdout);
            $this->drain($pipes[2], $stderr);
        } finally {
            foreach ([1, 2] as $index) {
                if (isset($pipes[$index]) && is_resource($pipes[$index])) {
                    fclose($pipes[$index]);
                }
            }
        }
        $closedExit = proc_close($process);
        $exit = null !== $observedExit ? $observedExit : ($closedExit >= 0 ? $closedExit : null);

        return new ProcessResult(true, $exit, substr($stdout, 0, self::MAX_STREAM_BYTES), substr($stderr, 0, self::MAX_STREAM_BYTES), $blocked);
    }

    /** @param resource $stream */
    private function drain(mixed $stream, string &$buffer): void
    {
        while (true) {
            $chunk = fread($stream, 65_536);
            if (false === $chunk || '' === $chunk) {
                return;
            }
            $buffer .= $chunk;
        }
    }

    private function repositoryStatus(): string
    {
        $result = $this->process(['git', '-c', 'core.excludesfile=', '-C', $this->repositoryRoot, 'status', '--porcelain=v1', '--untracked-files=all'], $this->repositoryRoot);
        $this->require($result->started() && null === $result->blockedReason() && 0 === $result->exitCode() && '' === $result->stderr(), 'git_status_failed');

        return $result->stdout();
    }

    private function containsLink(string $root): bool
    {
        $resolved = realpath($root);
        if (false === $resolved || is_link($root) || !is_dir($resolved)) {
            return true;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($resolved, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $entry) {
            if ($entry instanceof \SplFileInfo) {
                $path = self::normalize($entry->getPathname());
                $target = @readlink($path);
                if ($entry->isLink() || false !== $target && strtolower(self::normalize($target)) !== strtolower($path)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function ordinaryExecutable(string $path): string
    {
        $resolved = realpath($path);
        if (false === $resolved || is_link($path) || !is_file($resolved) || !is_readable($resolved)) {
            throw new RuntimeException('executable_invalid');
        }

        return self::normalize($resolved);
    }

    private function expectCompleted(ProcessResult $result): void
    {
        $this->require($result->started() && null === $result->blockedReason() && 0 === $result->exitCode(), 'process_not_completed');
    }

    private function expectSuccess(ProcessResult $result): void
    {
        $this->expectCompleted($result);
        $this->require('' === $result->stderr(), 'success_stderr_not_empty');
    }

    private function expectFailure(ProcessResult $result): void
    {
        $this->require($result->started() && null === $result->blockedReason() && null !== $result->exitCode() && 0 !== $result->exitCode(), 'failure_process_invalid');
    }

    private function observe(ProcessResult $result): void
    {
        $this->observedOutput[] = $result->stdout();
        $this->observedOutput[] = $result->stderr();
    }

    /** @return array<string, mixed> */
    private function canonicalJsonLine(string $bytes): array
    {
        $this->require('' !== $bytes && !str_contains($bytes, "\r") && 1 === substr_count($bytes, "\n") && str_ends_with($bytes, "\n"), 'json_line_framing_invalid');
        try {
            $document = json_decode(substr($bytes, 0, -1), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            throw new RuntimeException('json_line_invalid', 0, $failure);
        }
        $document = $this->object($document, 'json_line_object_required');
        $this->require($bytes === json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n", 'json_line_not_canonical');

        return $document;
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, string $failure): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new RuntimeException($failure);
        }

        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new RuntimeException($failure);
            }
            $object[$key] = $item;
        }

        return $object;
    }

    private function caseCompleted(): void
    {
        ++$this->caseCount;
    }

    private function require(bool $condition, string $token): void
    {
        if (!$condition) {
            throw new RuntimeException($token);
        }
    }

    /** @param resource $stream */
    private function write(mixed $stream, string $bytes): void
    {
        for ($offset = 0, $length = strlen($bytes); $offset < $length;) {
            $written = fwrite($stream, substr($bytes, $offset));
            if (false === $written || 0 === $written) {
                throw new RuntimeException('acceptance_output_failed');
            }
            $offset += $written;
        }
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
