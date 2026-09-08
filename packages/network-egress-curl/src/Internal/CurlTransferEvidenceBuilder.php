<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;

/** Converts bounded byte observations into honest neutral transfer evidence. */
#[Api(ApiStatus::Internal)]
final class CurlTransferEvidenceBuilder
{
    public function build(int $requestBytes, int $responseBytes, bool $responseComplete): EgressTransferEvidence
    {
        if ($responseBytes > 0) {
            return new EgressTransferEvidence(
                $responseComplete ? EgressTransferState::ResponseReceived : EgressTransferState::ResponsePartiallyReceived,
                max(1, $requestBytes),
                $responseBytes,
            );
        }
        if ($requestBytes > 0) {
            return new EgressTransferEvidence(EgressTransferState::RequestPartiallySent, $requestBytes, 0);
        }

        return new EgressTransferEvidence(EgressTransferState::None, 0, 0);
    }
}
