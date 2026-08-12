<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Model;

use Kongono\ApplicationGraph\Exception\InvalidGraphException;
use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

/**
 * Carries an open, opaque kind label for a directed relation.
 *
 * The non-empty value is preserved byte-for-byte. It selects no dependency,
 * call, event, security, or other domain vocabulary. Stability: Experimental.
 */
#[Api(ApiStatus::Experimental)]
final readonly class RelationKind
{
    public function __construct(private string $value)
    {
        if ('' === $value) {
            throw new InvalidGraphException('Relation kind must not be empty.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
