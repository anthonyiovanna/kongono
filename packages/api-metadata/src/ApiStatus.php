<?php

declare(strict_types=1);

namespace Kongono\ApiMetadata;

/**
 * Defines the compatibility and supported-use classification of a Kongono type.
 *
 * Status applies to the public and protected members of the classified type. It
 * communicates consumer support and compatibility intent; it does not grant
 * authorization, enforce package visibility, or create a security boundary.
 *
 * This enum and {@see Api} are Public bootstrap metadata by definition. They
 * cannot classify themselves without making the classification mechanism depend
 * circularly on its own resolution, so they are the only classification exception.
 */
enum ApiStatus: string
{
    case Internal = 'internal';
    case Experimental = 'experimental';
    case Public = 'public';
}
