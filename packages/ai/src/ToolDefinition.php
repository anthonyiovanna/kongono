<?php

declare(strict_types=1);

namespace Kongono\Ai;

final readonly class ToolDefinition
{
    /** @param array<string, mixed> $parametersSchema JSON Schema object */
    public function __construct(
        public string $name,
        public string $description,
        public array $parametersSchema = ['type' => 'object', 'properties' => []],
    ) {}
}
