<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Render\RenderRequest;
use Kongono\ViewHttp\Policy\ViewResponsePolicy;

/** Carries one exact render request and one explicit application-owned response policy. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewApplicationRunInput
{
    public function __construct(private RenderRequest $request, private ViewResponsePolicy $responsePolicy) {}

    public function request(): RenderRequest
    {
        return $this->request;
    }
    public function responsePolicy(): ViewResponsePolicy
    {
        return $this->responsePolicy;
    }
}
