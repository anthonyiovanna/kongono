<?php

declare(strict_types=1);

namespace Kongono\Http;

/** Typed Server-Sent Event frame for `Response::sse()`. */
final readonly class SseEvent
{
    public function __construct(
        public string $data,
        public ?string $event = null,
        public ?string $id = null,
        public ?int $retryMs = null,
    ) {}

    public static function json(mixed $payload, ?string $event = null, ?string $id = null): self
    {
        return new self(
            data: json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            event: $event,
            id: $id,
        );
    }

    /**
     * Build a targeted single-element Live 2.5 SSE patch (`event: kgn:patch`).
     * Allows high-frequency cell updates (e.g. `#bid-1234` price ticks) without morphing an entire table.
     *
     * Supported modes: `text` (zero HTML parsing), `inner`, `outer`, `append`, `prepend`, `remove`.
     */
    public static function patch(
        string $target,
        string $content = '',
        string $mode = 'text',
        ?string $id = null,
        bool $flash = true,
    ): self {
        $cleanTarget = trim($target);
        if ('' === $cleanTarget) {
            throw new \InvalidArgumentException('SseEvent::patch() requires a non-empty target selector.');
        }
        $normalizedMode = in_array($mode, ['text', 'inner', 'outer', 'append', 'prepend', 'remove'], true)
            ? $mode
            : throw new \InvalidArgumentException(sprintf('Unsupported SSE patch mode "%s".', $mode));

        return self::json(
            payload: [
                'target' => $cleanTarget,
                'mode' => $normalizedMode,
                'content' => $content,
                'flash' => $flash,
            ],
            event: 'kgn:patch',
            id: $id,
        );
    }

    /**
     * Emit multiple targeted cell patches atomically in one `event: kgn:patch` SSE frame.
     *
     * @param list<array{target: string, content?: string, mode?: string, flash?: bool}> $patches
     */
    public static function batchPatches(array $patches, ?string $id = null): self
    {
        $normalized = [];
        foreach ($patches as $patch) {
            $cleanTarget = trim($patch['target']);
            $mode = $patch['mode'] ?? 'text';
            if ('' === $cleanTarget || !in_array($mode, ['text', 'inner', 'outer', 'append', 'prepend', 'remove'], true)) {
                throw new \InvalidArgumentException(sprintf('Invalid batch SSE patch for target "%s" / mode "%s".', $cleanTarget, $mode));
            }
            $normalized[] = [
                'target' => $cleanTarget,
                'mode' => $mode,
                'content' => $patch['content'] ?? '',
                'flash' => $patch['flash'] ?? true,
            ];
        }

        return self::json(payload: $normalized, event: 'kgn:patch', id: $id);
    }

    public function format(): string
    {
        $lines = [];
        if (null !== $this->id && '' !== $this->id) {
            $lines[] = 'id: ' . str_replace(["\r", "\n", "\0"], '', $this->id);
        }
        if (null !== $this->event && '' !== $this->event) {
            $lines[] = 'event: ' . str_replace(["\r", "\n", "\0"], '', $this->event);
        }
        if (null !== $this->retryMs && $this->retryMs > 0) {
            $lines[] = 'retry: ' . $this->retryMs;
        }
        // Normalize CRLF and bare CR (WHATWG EventSource treats bare \r as a line delimiter!)
        $normalizedData = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $this->data);
        foreach (explode("\n", $normalizedData) as $dataLine) {
            $lines[] = 'data: ' . $dataLine;
        }

        return implode("\n", $lines) . "\n\n";
    }
}
