<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Entry;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PhpCgi\Delivery\CgiWebDelivery;
use Kongono\Web\Runtime\WebRuntimeAdapter;

/** Enters one already-composed Web Runtime invocation through a fresh CGI delivery. */
#[Api(ApiStatus::Experimental)]
final class CgiWebEntry
{
    private function __construct() {}

    public static function run(WebRuntimeAdapter $runtime): void
    {
        $runtime->handle(CgiWebDelivery::capture());
    }
}
