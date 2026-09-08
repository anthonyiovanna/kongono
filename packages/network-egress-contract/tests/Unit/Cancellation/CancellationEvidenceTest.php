<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Cancellation;

use Kongono\NetworkEgressContract\Cancellation\CancellationEvidence;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use PHPUnit\Framework\TestCase;

final class CancellationEvidenceTest extends TestCase
{
    public function testVocabularyBoundsAccessorsAndEqualityAreExact(): void
    {
        self::assertSame(['not_requested', 'requested_before_attempt', 'requested_after_possible_effect'], array_column(CancellationState::cases(), 'value'));
        foreach ([0, 1000000] as $sequence) {
            $evidence = new CancellationEvidence(CancellationState::NotRequested, $sequence);
            self::assertSame(CancellationState::NotRequested, $evidence->state());
            self::assertSame($sequence, $evidence->observationSequence());
            self::assertTrue($evidence->equals(new CancellationEvidence(CancellationState::NotRequested, $sequence)));
        }
    }

    public function testInvalidSequencesFailClosed(): void
    {
        foreach ([-1, 1000001] as $sequence) {
            try {
                new CancellationEvidence(CancellationState::NotRequested, $sequence);
                self::fail('Expected sequence rejection.');
            } catch (InvalidNetworkEgressContractException $exception) {
                self::assertContains($exception->diagnosticId(), ['KGN-EGRESS-CONTRACT-1507', 'KGN-EGRESS-CONTRACT-1508']);
            }
        }
    }
}
