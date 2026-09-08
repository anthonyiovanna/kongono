<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Transport;

use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressContract\Lifecycle\EgressLifecycleState;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class NegotiatedProtocolTest extends TestCase
{
    public function testAllClosedVocabulariesAreExact(): void
    {
        self::assertSame(['unknown', 'http_1_1', 'http_2', 'http_3'], array_column(NegotiatedProtocol::cases(), 'value'));
        self::assertSame(['created', 'validated', 'authorized', 'resolution_pending', 'resolved', 'attempt_pending', 'request_streaming', 'response_streaming', 'succeeded', 'failed', 'cancelled', 'uncertain', 'retired'], array_column(EgressLifecycleState::cases(), 'value'));
        self::assertSame(['none', 'request_partially_sent', 'request_sent', 'response_partially_received', 'response_received'], array_column(EgressTransferState::cases(), 'value'));
        self::assertSame(['rejected_before_attempt', 'succeeded', 'failed_before_effect', 'cancelled_before_effect', 'cancelled_after_possible_effect', 'uncertain_effect'], array_column(EgressOutcome::cases(), 'value'));
        self::assertSame(['not_requested', 'requested_before_attempt', 'requested_after_possible_effect'], array_column(CancellationState::cases(), 'value'));
        self::assertCount(13, EgressFailureKind::cases());
    }

    public function testContractValuesDoNotOfferGenericSerializationOrStringification(): void
    {
        foreach ([NegotiatedProtocol::Unknown, EgressLifecycleState::Created, EgressTransferState::None, EgressOutcome::RejectedBeforeAttempt, CancellationState::NotRequested] as $value) {
            $reflection = new ReflectionClass($value);
            self::assertFalse($reflection->implementsInterface('JsonSerializable'));
            self::assertFalse($reflection->implementsInterface('Stringable'));
            self::assertFalse($reflection->hasMethod('__serialize'));
        }
    }
}
