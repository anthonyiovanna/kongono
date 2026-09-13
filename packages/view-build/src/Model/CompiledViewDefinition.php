<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Grammar\ViewNode;
use Kongono\ViewContract\Identity\LogicalViewIdentity;
use Kongono\ViewContract\Schema\ViewContextSchema;

/** Carries one canonical compiled definition and complete ordered source map. */
#[Api(ApiStatus::Internal)]
final readonly class CompiledViewDefinition
{
    /** @var list<ViewNode> */
    private array $body;

    /** @var list<SourceMapEntry> */
    private array $sourceMap;

    /** @param list<ViewNode> $body
     * @param list<SourceMapEntry> $sourceMap
     */
    public function __construct(
        private LogicalViewIdentity $identity,
        private ViewContextSchema $contextSchema,
        array $body,
        private int $operationCount,
        array $sourceMap,
    ) {
        $this->body = [...$body];
        $this->sourceMap = [...$sourceMap];
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
    public function operationCount(): int
    {
        return $this->operationCount;
    }
    /** @return list<SourceMapEntry> */
    public function sourceMap(): array
    {
        return $this->sourceMap;
    }

    public function equals(self $other): bool
    {
        if (!$this->identity->equals($other->identity) || !$this->contextSchema->equals($other->contextSchema) || $this->operationCount !== $other->operationCount || count($this->body) !== count($other->body) || count($this->sourceMap) !== count($other->sourceMap)) {
            return false;
        }
        foreach ($this->body as $position => $node) {
            if (!$node->equals($other->body[$position])) {
                return false;
            }
        }
        foreach ($this->sourceMap as $position => $entry) {
            if (!$entry->equals($other->sourceMap[$position])) {
                return false;
            }
        }

        return true;
    }
}
