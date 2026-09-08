<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Transfer;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names bounded transfer evidence without claiming remote processing. */
#[Api(ApiStatus::Experimental)]
enum EgressTransferState: string
{
    case None = 'none';
    case RequestPartiallySent = 'request_partially_sent';
    case RequestSent = 'request_sent';
    case ResponsePartiallyReceived = 'response_partially_received';
    case ResponseReceived = 'response_received';
}
