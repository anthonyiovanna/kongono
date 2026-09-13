<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Grammar;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Source\SourcePointer;

/** Carries one package-owned validated node in the closed source language. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewNode
{
    /** @param list<ViewAttribute> $attributes
     * @param list<ViewNode> $children
     */
    private function __construct(
        private ViewNodeKind $kind,
        private SourcePointer $sourcePointer,
        private ?string $text = null,
        private ?string $path = null,
        private ?string $name = null,
        private array $attributes = [],
        private array $children = [],
        private ?string $alias = null,
        private ?int $maxItems = null,
    ) {}

    public static function text(string $text, SourcePointer $pointer): self
    {
        return new self(ViewNodeKind::Text, $pointer, text: $text);
    }

    public static function value(string $path, SourcePointer $pointer): self
    {
        return new self(ViewNodeKind::Value, $pointer, path: $path);
    }

    /** @param list<ViewAttribute> $attributes
     * @param list<ViewNode> $children
     */
    public static function element(string $name, array $attributes, array $children, SourcePointer $pointer): self
    {
        return new self(ViewNodeKind::Element, $pointer, name: $name, attributes: [...$attributes], children: [...$children]);
    }

    /** @param list<ViewNode> $body */
    public static function each(string $path, string $alias, int $maxItems, array $body, SourcePointer $pointer): self
    {
        return new self(ViewNodeKind::Each, $pointer, path: $path, children: [...$body], alias: $alias, maxItems: $maxItems);
    }

    public function kind(): ViewNodeKind
    {
        return $this->kind;
    }
    public function sourcePointer(): SourcePointer
    {
        return $this->sourcePointer;
    }
    public function textValue(): ?string
    {
        return $this->text;
    }
    public function path(): ?string
    {
        return $this->path;
    }
    public function name(): ?string
    {
        return $this->name;
    }
    /** @return list<ViewAttribute> */
    public function attributes(): array
    {
        return $this->attributes;
    }
    /** @return list<ViewNode> */
    public function children(): array
    {
        return $this->children;
    }
    public function alias(): ?string
    {
        return $this->alias;
    }
    public function maxItems(): ?int
    {
        return $this->maxItems;
    }

    public function equals(self $other): bool
    {
        if ($this->kind !== $other->kind || $this->text !== $other->text || $this->path !== $other->path || $this->name !== $other->name || $this->alias !== $other->alias || $this->maxItems !== $other->maxItems || count($this->attributes) !== count($other->attributes) || count($this->children) !== count($other->children)) {
            return false;
        }
        foreach ($this->attributes as $position => $attribute) {
            if (!$attribute->equals($other->attributes[$position])) {
                return false;
            }
        }
        foreach ($this->children as $position => $child) {
            if (!$child->equals($other->children[$position])) {
                return false;
            }
        }

        return true;
    }
}
