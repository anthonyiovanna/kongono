<?php

declare(strict_types=1);

namespace Kongono\Kernel\Api;

use Attribute;

/**
 * Attaches one explicit API-status value to a production class-like declaration.
 *
 * The non-repeatable attribute lets repository checks reject missing or conflicting
 * compatibility metadata deterministically. Status describes supported use only;
 * callers must not interpret it as authorization or runtime access control.
 *
 * This attribute and {@see ApiStatus} are Public bootstrap metadata by definition.
 * They are the sole types exempt from carrying the metadata they establish.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Api
{
    public function __construct(public ApiStatus $status) {}
}
