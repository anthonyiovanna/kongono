<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Internal;

use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressCurl\Internal\CurlTransferEvidenceBuilder;
use PHPUnit\Framework\TestCase;

final class CurlTransferEvidenceBuilderTest extends TestCase
{
    public function testBeforeEffectPartialRequestPartialResponseAndCompletionRemainDistinct(): void
    {
        $builder = new CurlTransferEvidenceBuilder();
        self::assertSame(EgressTransferState::None, $builder->build(0, 0, false)->state());
        self::assertSame(EgressTransferState::RequestPartiallySent, $builder->build(10, 0, false)->state());
        self::assertSame(EgressTransferState::ResponsePartiallyReceived, $builder->build(10, 20, false)->state());
        self::assertSame(EgressTransferState::ResponseReceived, $builder->build(10, 20, true)->state());
    }
}
