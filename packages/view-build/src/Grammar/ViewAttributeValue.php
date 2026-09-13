<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Grammar;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one validated literal or schema-resolved attribute value. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewAttributeValue
{
    private function __construct(private ViewAttributeValueKind $kind, private string $value) {}

    public static function literal(string $text): self
    {
        return new self(ViewAttributeValueKind::Literal, $text);
    }

    public static function value(string $path): self
    {
        return new self(ViewAttributeValueKind::Value, $path);
    }

    public function kind(): ViewAttributeValueKind
    {
        return $this->kind;
    }

    public function text(): ?string
    {
        return ViewAttributeValueKind::Literal === $this->kind ? $this->value : null;
    }

    public function path(): ?string
    {
        return ViewAttributeValueKind::Value === $this->kind ? $this->value : null;
    }

    public function equals(self $other): bool
    {
        return $this->kind === $other->kind && $this->value === $other->value;
    }
}
