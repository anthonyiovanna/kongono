<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Exception\InvalidViewSourceException;

/** Carries one bounded canonical JSON Pointer, never a filesystem path. */
#[Api(ApiStatus::Experimental)]
final readonly class SourcePointer
{
    public function __construct(private string $value)
    {
        if (strlen($value) < 1 || strlen($value) > 256 || 1 !== preg_match('/\A(?:\/(?:[^~\/]|~0|~1)*)+\z/D', $value)) {
            throw InvalidViewSourceException::invalid('source_pointer_grammar');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
