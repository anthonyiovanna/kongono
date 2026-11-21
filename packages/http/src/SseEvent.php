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

    public function format(): string
    {
        $lines = [];
        if (null !== $this->id && '' !== $this->id) {
            $lines[] = 'id: ' . str_replace(["\r", "\n"], '', $this->id);
        }
        if (null !== $this->event && '' !== $this->event) {
            $lines[] = 'event: ' . str_replace(["\r", "\n"], '', $this->event);
        }
        if (null !== $this->retryMs && $this->retryMs > 0) {
            $lines[] = 'retry: ' . $this->retryMs;
        }
        foreach (explode("\n", str_replace("\r\n", "\n", $this->data)) as $dataLine) {
            $lines[] = 'data: ' . $dataLine;
        }

        return implode("\n", $lines) . "\n\n";
    }
}
