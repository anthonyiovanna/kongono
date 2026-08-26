<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Carries only a neutral failure category and bounded diagnostic identity. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandFailure
{
    public function __construct(
        private CommandFailureCategory $category,
        private string $diagnosticIdentifier,
    ) {
        if (
            '' === $diagnosticIdentifier
            || strlen($diagnosticIdentifier) > 96
            || 1 !== preg_match('/^KGN-[A-Z0-9]+(?:-[A-Z0-9]+)*-[0-9]{4}$/D', $diagnosticIdentifier)
        ) {
            throw InvalidConsoleValueException::failure();
        }
    }

    public function category(): CommandFailureCategory
    {
        return $this->category;
    }

    public function diagnosticIdentifier(): string
    {
        return $this->diagnosticIdentifier;
    }
}
