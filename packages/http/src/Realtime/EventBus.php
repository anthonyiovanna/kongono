<?php

declare(strict_types=1);

namespace Kongono\Http\Realtime;

use Closure;
use Kongono\Http\SseEvent;

/**
 * Persistent-runtime-safe in-process Pub/Sub EventBus with bounded ring-buffer history
 * and Last-Event-ID replay for real-time Server-Sent Events (SSE) channels.
 */
final class EventBus
{
    private int $sequence = 0;

    /** @var array<string, list<SseEvent>> */
    private array $history = [];

    /** @var array<string, array<int, Closure(SseEvent): void>> */
    private array $subscribers = [];

    private int $nextSubscriberId = 0;

    /**
     * @param positive-int $maxHistoryPerChannel
     * @param positive-int $maxChannels
     */
    public function __construct(
        private readonly int $maxHistoryPerChannel = 100,
        private readonly int $maxChannels = 1000,
    ) {}

    /**
     * Publish an event onto a channel and notify all active subscribers.
     *
     * @param array<string, mixed> $payload
     */
    public function publish(string $channel, string $event, array $payload): SseEvent
    {
        $id = (string) (++$this->sequence);
        $sse = SseEvent::json($payload, event: $event, id: $id);

        if (!isset($this->history[$channel]) && count($this->history) >= $this->maxChannels) {
            unset($this->history[array_key_first($this->history)]);
        }
        $events = $this->history[$channel] ?? [];
        unset($this->history[$channel]);
        $events[] = $sse;
        if (count($events) > $this->maxHistoryPerChannel) {
            array_shift($events);
        }
        $this->history[$channel] = $events;

        foreach ($this->subscribers[$channel] ?? [] as $listener) {
            $listener($sse);
        }

        return $sse;
    }

    /**
     * Subscribe to live events on a channel; returns an unsubscribe closure suitable for `$scope->defer(...)`.
     *
     * @param Closure(SseEvent): void $listener
     * @return Closure(): void
     */
    public function subscribe(string $channel, Closure $listener): Closure
    {
        $subId = ++$this->nextSubscriberId;
        $this->subscribers[$channel][$subId] = $listener;

        return function () use ($channel, $subId): void {
            unset($this->subscribers[$channel][$subId]);
            if ([] === ($this->subscribers[$channel] ?? [])) {
                unset($this->subscribers[$channel]);
            }
        };
    }

    /**
     * Return buffered events for a channel strictly newer than $lastEventId (supports HTTP Last-Event-ID replay).
     *
     * @return list<SseEvent>
     */
    public function since(string $channel, ?string $lastEventId = null): array
    {
        $events = $this->history[$channel] ?? [];
        if (null === $lastEventId || '' === trim($lastEventId)) {
            return $events;
        }

        $cursor = (int) $lastEventId;
        $missed = [];
        foreach ($events as $ev) {
            if (null !== $ev->id && (int) $ev->id > $cursor) {
                $missed[] = $ev;
            }
        }

        return $missed;
    }

    public function clear(?string $channel = null): void
    {
        if (null === $channel) {
            $this->history = [];
        } else {
            unset($this->history[$channel]);
        }
    }
}
