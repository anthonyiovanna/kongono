<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Grammar\ViewNode;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Schema\ViewContextSchema;

/** Carries one validated logical view and its explicit context schema. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewSource
{
    /** @var list<ViewNode> */
    private array $body;

    /** @param list<ViewNode> $body */
    public function __construct(
        private LogicalViewIdentity $identity,
        private ViewContextSchema $contextSchema,
        array $body,
        private SourcePointer $sourcePointer,
    ) {
        $this->body = [...$body];
    }

    public function identity(): LogicalViewIdentity
    {
        return $this->identity;
    }
    public function contextSchema(): ViewContextSchema
    {
        return $this->contextSchema;
    }
    /** @return list<ViewNode> */
    public function body(): array
    {
        return $this->body;
    }
    public function sourcePointer(): SourcePointer
    {
        return $this->sourcePointer;
    }

    public function equals(self $other): bool
    {
        if (!$this->identity->equals($other->identity) || !$this->contextSchema->equals($other->contextSchema) || count($this->body) !== count($other->body)) {
            return false;
        }
        foreach ($this->body as $position => $node) {
            if (!$node->equals($other->body[$position])) {
                return false;
            }
        }

        return true;
    }
}
