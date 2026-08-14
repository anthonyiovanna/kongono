<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic;
use Kongono\ApplicationGraph\Exception\InvalidGraphException;

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
            throw new InvalidGraphException(
                GraphDiagnostic::EmptyNodeKey,
                'Node key must not be empty.',
                ['actual_length' => strlen($value)],
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
