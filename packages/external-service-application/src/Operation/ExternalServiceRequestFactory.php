<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Operation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Psr\Http\Message\RequestInterface;

/** Lets caller code construct one service request while credential material is borrowed. */
#[Api(ApiStatus::Experimental)]
interface ExternalServiceRequestFactory
{
    public function create(
        ServiceBinding $binding,
        ExternalServiceOperationIdentity $operationIdentity,
        #[\SensitiveParameter]
        string $credentialMaterial,
    ): RequestInterface;
}
