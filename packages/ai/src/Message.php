<?php

declare(strict_types=1);

namespace Kongono\Ai;

final readonly class Message
{
    /** @param list<ToolCall> $toolCalls */
    public function __construct(
        public Role $role,
        public string $content,
        public ?string $toolCallId = null,
        public array $toolCalls = [],
    ) {}

    public static function system(string $content): self
    {
        return new self(Role::System, $content);
    }

    public static function user(string $content): self
    {
        return new self(Role::User, $content);
    }

    /** @param list<ToolCall> $toolCalls */
    public static function assistant(string $content, array $toolCalls = []): self
    {
        return new self(Role::Assistant, $content, toolCalls: $toolCalls);
    }

    public static function tool(string $toolCallId, string $content): self
    {
        return new self(Role::Tool, $content, toolCallId: $toolCallId);
    }
}
