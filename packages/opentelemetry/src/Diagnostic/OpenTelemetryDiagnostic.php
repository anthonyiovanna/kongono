<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing OpenTelemetry bridge diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum OpenTelemetryDiagnostic: string
{
    case Configuration = 'KGN-OTEL-1001';
    case Mapping = 'KGN-OTEL-1002';
    case Backpressure = 'KGN-OTEL-1003';
    case Export = 'KGN-OTEL-1004';
    case Lifecycle = 'KGN-OTEL-1005';

    public function title(): string
    {
        return match ($this) {
            self::Configuration => 'Invalid OpenTelemetry configuration',
            self::Mapping => 'Unsupported or invalid OpenTelemetry mapping',
            self::Backpressure => 'OpenTelemetry queue backpressure',
            self::Export => 'OpenTelemetry transport export failed',
            self::Lifecycle => 'Invalid OpenTelemetry lifecycle transition',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Configuration => 'Provide values within the exact supported profile bounds.',
            self::Mapping => 'Provide one exact supported bounded Kongono observation shape.',
            self::Backpressure => 'Flush the owned bridge explicitly before recording more observations.',
            self::Export => 'Inspect the retained cause and decide explicitly whether to retry the retained queue.',
            self::Lifecycle => 'Use record, flush, and close only in their exact allowed lifecycle states.',
        };
    }

    public function context(?string $signalKind = null, ?int $queueCount = null, ?int $queueCapacity = null, ?string $stage = null): DiagnosticContext
    {
        $fields = [
            new DiagnosticFieldSchema('category', 'Category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('signal_kind', 'Signal kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('queue_count', 'Queue count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('queue_capacity', 'Queue capacity', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
        ];
        $values = ['category' => $this->category(), 'remediation' => $this->remediationToken()];
        if (null !== $signalKind) {
            $values['signal_kind'] = $signalKind;
        }
        if (null !== $queueCount) {
            $values['queue_count'] = $queueCount;
        }
        if (null !== $queueCapacity) {
            $values['queue_capacity'] = $queueCapacity;
        }
        if (null !== $stage) {
            $values['stage'] = $stage;
        }

        return new DiagnosticContext(
            new DiagnosticContextSchema('Operate explicit OpenTelemetry bridge', 'Bridge failures remain bounded and non-disclosing', $fields),
            $values,
        );
    }

    private function category(): string
    {
        return strtolower($this->name);
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::Configuration => 'correct_configuration',
            self::Mapping => 'correct_mapping',
            self::Backpressure => 'flush_queue',
            self::Export => 'inspect_transport',
            self::Lifecycle => 'correct_lifecycle',
        };
    }
}
