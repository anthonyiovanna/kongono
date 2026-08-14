<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic;
use Kongono\ApplicationGraph\Exception\InvalidGraphException;

/**
 * Carries an open, opaque kind label for a structural node.
 *
 * The non-empty value is preserved byte-for-byte. It is not a registry entry or
 * finalized domain-schema vocabulary, and no built-in kinds exist in this phase.
 * Stability: Experimental.
 */
#[Api(ApiStatus::Experimental)]
final readonly class NodeKind
{
    public function __construct(private string $value)
    {
        if ('' === $value) {
            throw new InvalidGraphException(
                GraphDiagnostic::EmptyNodeKind,
                'Node kind must not be empty.',
                ['actual_length' => strlen($value)],
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
