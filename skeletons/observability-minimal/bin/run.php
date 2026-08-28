<?php

declare(strict_types=1);

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Metrics\MetricDimensionSchema;
use Kongono\Metrics\MetricInstrument;
use Kongono\Metrics\MetricInstrumentKind;
use Kongono\Metrics\MetricPlan;
use Kongono\Metrics\MetricUnit;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\ObservabilityApplication\ApplicationObservabilityFactory;
use Kongono\ObservabilityApplication\ApplicationObservabilityPlan;
use Kongono\ObservabilityApplication\Exception\ApplicationObservabilityLifecycleException;
use Kongono\ObservabilityBuild\Workflow\ObservabilityBuildWorkflow;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use Kongono\OpenTelemetry\OtlpSignal;
use Kongono\OperationalEvents\OperationalEventDefinition;
use Kongono\OperationalEvents\OperationalEventSchema;
use Kongono\OperationalEvents\OperationalEventSeverity;
use Kongono\SecurityAudit\SecurityAuditDefinition;
use Kongono\SecurityAudit\SecurityAuditField;
use Kongono\SecurityAudit\SecurityAuditRetentionIntent;
use Kongono\SecurityAudit\SecurityAuditSchema;
use KongonoObservabilityMinimal\CapturingOtlpTransport;
use KongonoObservabilityMinimal\ExampleObservabilityWork;
use KongonoObservabilityMinimal\ExampleOpenTelemetryClock;
use KongonoObservabilityMinimal\ExampleTraceClock;
use KongonoObservabilityMinimal\ExampleTraceIdentifierSource;
use KongonoObservabilityMinimal\RecordTraceSampler;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = str_replace('\\', '/', dirname(__DIR__));
$output = $root . '/var/kongono/build';
$application = null;

try {
    $configuration = (new ObservabilityBuildWorkflow())->check($root, $output);
    $transport = new CapturingOtlpTransport();
    $application = (new ApplicationObservabilityFactory())->create(
        $configuration,
        new ExampleTraceClock(),
        new ExampleTraceIdentifierSource(),
        new RecordTraceSampler(),
        new OpenTelemetryTimeAnchor(0, 1_000_000),
        new ExampleOpenTelemetryClock(),
        $transport,
    );

    $context = ObservabilityContext::uncorrelated();
    $instrument = new MetricInstrument(
        new SignalIdentity('metric.example.operations', 1),
        MetricInstrumentKind::Counter,
        new MetricUnit('operations'),
        new MetricDimensionSchema([]),
    );
    $event = new OperationalEventDefinition(
        new SignalIdentity('operations.example.completed', 1),
        OperationalEventSeverity::Info,
        new OperationalEventSchema([]),
    );
    $auditKey = new AttributeKey('app.action');
    $auditValue = AttributeValue::text('observe');
    $audit = new SecurityAuditDefinition(
        new SignalIdentity('security.example.observed', 1),
        SecurityAuditRetentionIntent::Short,
        new SecurityAuditSchema([
            new SecurityAuditField(
                $auditKey,
                AttributeValueKind::Text,
                DiagnosticDisclosure::Safe,
                true,
                [$auditValue],
            ),
        ]),
    );
    $plan = new ApplicationObservabilityPlan(
        $context,
        new SignalIdentity('trace.example.operation', 1),
        new AttributeSet([]),
        new MetricPlan([$instrument]),
    );
    $result = $application->execute(
        $plan,
        new ExampleObservabilityWork(
            $context,
            $instrument,
            $event,
            $audit,
            new AttributeSet([new SignalAttribute($auditKey, $auditValue)]),
        ),
    );
    if ('observability-example-ok' !== $result) {
        throw new RuntimeException('The example work returned an unexpected result.');
    }
    if (4 !== $transport->payloadCount() || [OtlpSignal::Logs, OtlpSignal::Logs, OtlpSignal::Metrics, OtlpSignal::Traces] !== $transport->signals()) {
        throw new RuntimeException('The example did not preserve the four signal paths.');
    }

    $application->flush();
    $application->close();
    $application->close();
    if (1 !== $transport->closeCount()) {
        throw new RuntimeException('The example transport did not close exactly once.');
    }
    try {
        $application->flush();
        throw new RuntimeException('Post-close flush unexpectedly succeeded.');
    } catch (ApplicationObservabilityLifecycleException) {
    }

    $application = null;
    fwrite(STDOUT, "observability-example-ok\nsignal-paths-distinct\npost-close-refused\n");
    exit(0);
} catch (Throwable) {
    if (null !== $application) {
        try {
            $application->close();
        } catch (Throwable) {
        }
    }
    fwrite(STDERR, "Observability application run failed.\n");
    exit(1);
}
