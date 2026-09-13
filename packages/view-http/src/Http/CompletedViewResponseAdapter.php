<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Http;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Render\RenderResult;
use Kongono\ViewHttp\Internal\ResponseAssembler;
use Kongono\ViewHttp\Internal\ViewHttpGuard;
use Kongono\ViewHttp\Policy\ViewResponsePolicy;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** Adapts one completed view into one exact caller-policy PSR-7 response. */
#[Api(ApiStatus::Experimental)]
final readonly class CompletedViewResponseAdapter
{
    public function __construct(private ResponseFactoryInterface $responseFactory, private StreamFactoryInterface $streamFactory) {}

    public function adapt(RenderResult $result, ViewResponsePolicy $policy): ResponseInterface
    {
        ViewHttpGuard::completedResult($result);

        return ResponseAssembler::assemble($this->responseFactory, $this->streamFactory, $result, $policy);
    }
}
