<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic;
use Kongono\ApplicationGraph\Exception\InvalidGraphException;

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
            throw new InvalidGraphException(
                GraphDiagnostic::EmptyRelationKind,
                'Relation kind must not be empty.',
                ['actual_length' => strlen($value)],
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
