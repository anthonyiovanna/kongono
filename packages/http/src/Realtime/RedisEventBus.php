<?php

declare(strict_types=1);

namespace Kongono\Http\Realtime;

use Closure;
use Kongono\Core\Cache\RedisClient;
use Kongono\Http\SseEvent;

/**
 * Distributed multi-worker / multi-node SSE EventBus backed by Redis / Valkey.
 *
 * Uses a single atomic server-side Lua script (`EVAL`) to assign globally monotonic event IDs,
 * append to a bounded sorted-set ring buffer for cross-worker `Last-Event-ID` replay (`since()`),
 * and broadcast via Redis `PUBLISH` so events published on Worker #1 reach SSE clients on Worker #2+.
 */
final class RedisEventBus implements EventBusInterface
{
    private const string LUA_PUBLISH = <<<'LUA'
local seqKey = KEYS[1]
local histKey = KEYS[2]
local pubsubChannel = ARGV[1]
local eventName = ARGV[2]
local payloadJson = ARGV[3]
local maxHistory = tonumber(ARGV[4])
local ttlSeconds = tonumber(ARGV[5])

local id = redis.call('INCR', seqKey)
local envelope = cjson.encode({id = tostring(id), event = eventName, data = payloadJson})
redis.call('ZADD', histKey, id, envelope)
local total = redis.call('ZCARD', histKey)
if total > maxHistory then
    redis.call('ZREMRANGEBYRANK', histKey, 0, total - maxHistory - 1)
end
if ttlSeconds > 0 then
    redis.call('EXPIRE', histKey, ttlSeconds)
end
redis.call('PUBLISH', pubsubChannel, envelope)
return id
LUA;

    /** @var array<string, array<int, Closure(SseEvent): void>> */
    private array $subscribers = [];

    private int $nextSubscriberId = 0;

    /**
     * @param positive-int $maxHistoryPerChannel
     * @param positive-int $ttlSeconds
     */
    public function __construct(
        private readonly RedisClient $redis,
        private readonly string $prefix = 'kongono:sse:',
        private readonly int $maxHistoryPerChannel = 100,
        private readonly int $ttlSeconds = 3600,
    ) {}

    public function publish(string $channel, string $event, array $payload): SseEvent
    {
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $reply = $this->redis->eval(
            self::LUA_PUBLISH,
            [
                $this->prefix . 'seq:' . $channel,
                $this->prefix . 'hist:' . $channel,
            ],
            [
                $this->prefix . 'pubsub:' . $channel,
                $event,
                $payloadJson,
                (string) $this->maxHistoryPerChannel,
                (string) $this->ttlSeconds,
            ],
        );

        $id = is_int($reply) ? (string) $reply : (is_string($reply) ? $reply : '1');
        $sse = new SseEvent(data: $payloadJson, event: $event, id: $id);

        foreach ($this->subscribers[$channel] ?? [] as $listener) {
            $listener($sse);
        }

        return $sse;
    }

    /**
     * Subscribe an in-process listener on this worker; returns an unsubscribe closure.
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

    public function since(string $channel, ?string $lastEventId = null): array
    {
        $minScore = (null !== $lastEventId && '' !== trim($lastEventId) && is_numeric(trim($lastEventId)))
            ? '(' . ((int) trim($lastEventId))
            : '-inf';

        $rawEntries = $this->redis->zRangeByScore($this->prefix . 'hist:' . $channel, $minScore, '+inf');
        $events = [];
        foreach ($rawEntries as $raw) {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded) || !is_string($decoded['data'] ?? null)) {
                continue;
            }
            $events[] = new SseEvent(
                data: $decoded['data'],
                event: is_string($decoded['event'] ?? null) ? $decoded['event'] : null,
                id: is_scalar($decoded['id'] ?? null) ? (string) $decoded['id'] : null,
            );
        }

        return $events;
    }

    public function clear(?string $channel = null): void
    {
        if (null !== $channel) {
            $this->redis->del($this->prefix . 'hist:' . $channel, $this->prefix . 'seq:' . $channel);

            return;
        }

        $escaped = str_replace(['\\', '*', '?', '[', ']'], ['\\\\', '\\*', '\\?', '\\[', '\\]'], $this->prefix);
        $cursor = '0';
        do {
            [$cursor, $keys] = $this->redis->scan($cursor, $escaped . '*', 500);
            if ([] !== $keys) {
                $this->redis->del(...$keys);
            }
        } while ('0' !== $cursor);
    }
}
