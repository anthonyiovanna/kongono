<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Grammar;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Source\SourcePointer;

/** Carries one validated static-name quoted attribute. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewAttribute
{
    public function __construct(
        private string $name,
        private ViewAttributeValue $value,
        private SourcePointer $sourcePointer,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function value(): ViewAttributeValue
    {
        return $this->value;
    }

    public function sourcePointer(): SourcePointer
    {
        return $this->sourcePointer;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->value->equals($other->value);
    }
}
