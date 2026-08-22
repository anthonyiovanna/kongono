<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Emission;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PhpCgi\Internal\CgiNativeEmissionTarget;
use Kongono\PhpCgi\Internal\CgiResponseEmission;
use Psr\Http\Message\ResponseInterface;

/** Emits one completely preflighted bounded response through the selected CGI profile. */
#[Api(ApiStatus::Experimental)]
final class CgiResponseEmitter
{
    public function emit(
        ResponseInterface $response,
        string $requestMethod,
    ): void {
        CgiResponseEmission::emit($response, $requestMethod, new CgiNativeEmissionTarget());
    }
}
