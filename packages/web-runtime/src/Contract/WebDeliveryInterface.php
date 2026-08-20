<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Contract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Defines one server-owned inbound delivery and its exact transport cleanup.
 *
 * Implementations evaluate only current server-controlled transport evidence;
 * request data cannot enable trusted presentation. One fresh implementation
 * instance owns one delivery and releases its delivery resources in close().
 */
#[Api(ApiStatus::Public)]
interface WebDeliveryInterface
{
    public function evaluateTrustedPresentationPermission(): AdapterControlledDeliveryPermission;

    public function request(): ServerRequestInterface;

    public function send(ResponseInterface $response): void;

    public function close(): void;
}
