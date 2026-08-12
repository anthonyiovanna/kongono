<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Model;

use Kongono\ApplicationGraph\Exception\InvalidGraphException;
use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

/**
 * Identifies one node within the structural snapshot that receives the key.
 *
 * The non-empty value is opaque and preserved byte-for-byte. It is graph-local,
 * not a stable cross-build Graph ID, Documentation ID, URI, UUID, package
 * coordinate, or PHP symbol. Stability: Experimental.
 */
#[Api(ApiStatus::Experimental)]
final readonly class NodeKey
{
    public function __construct(private string $value)
    {
        if ('' === $value) {
            throw new InvalidGraphException('Node key must not be empty.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
