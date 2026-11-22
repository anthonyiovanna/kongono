<?php

declare(strict_types=1);

namespace Kongono\Http\Realtime;

use Kongono\Http\SseEvent;

/**
 * Contract for Server-Sent Events (SSE) publish/replay event buses.
 *
 * Implemented by in-process `EventBus` (single worker / testing) and distributed
 * `RedisEventBus` (multi-worker FrankenPHP / multi-node horizontal scale).
 */
interface EventBusInterface
{
    /**
     * Publish an event onto a channel and return the persisted SSE frame with monotonic ID.
     *
     * @param array<string, mixed> $payload
     */
    public function publish(string $channel, string $event, array $payload): SseEvent;

    /**
     * Return buffered events for a channel strictly newer than `$lastEventId` (HTTP `Last-Event-ID` replay).
     *
     * @return list<SseEvent>
     */
    public function since(string $channel, ?string $lastEventId = null): array;

    /** Clear buffered history for one channel or all channels. */
    public function clear(?string $channel = null): void;
}
