<?php

declare(strict_types=1);

namespace Kongono\Jobs;

use Kongono\Core\Cache\RedisClient;

/**
 * Zero-dependency Redis / Valkey distributed queue driver with atomic server-side Lua scripts
 * for delayed sorted sets, lease expiration reclaim, and generation + lease-token fencing.
 */
final readonly class RedisQueueDriver implements QueueDriverInterface
{
    private const string LUA_RESERVE = <<<'LUA'
local pendingKey = KEYS[1]
local reservedKey = KEYS[2]
local jobsKey = KEYS[3]
local terminalKey = KEYS[4]
local nowTs = tonumber(ARGV[1])
local leaseUntil = tonumber(ARGV[2])
local leaseToken = ARGV[3]

for _ = 1, 32 do
    local sourceKey = pendingKey
    local ids = redis.call('ZRANGEBYSCORE', pendingKey, '-inf', ARGV[1], 'LIMIT', 0, 1)
    if #ids == 0 then
        sourceKey = reservedKey
        ids = redis.call('ZRANGEBYSCORE', reservedKey, '-inf', ARGV[1], 'LIMIT', 0, 1)
        if #ids == 0 then
            return nil
        end
    end

    local id = ids[1]
    redis.call('ZREM', sourceKey, id)
    local raw = redis.call('HGET', jobsKey, id)
    if raw then
        local ok, job = pcall(cjson.decode, raw)
        if ok and type(job) == 'table' then
            local attempt = tonumber(job.attempt) or 0
            local maxAttempts = tonumber(job.max_attempts) or 1
            local gen = tonumber(job.generation) or 0
            if attempt >= maxAttempts then
                job.status = 'dead'
                job.generation = gen + 1
                job.lease_token = cjson.null
                job.lease_until = cjson.null
                if job.last_error == nil or job.last_error == cjson.null then
                    job.last_error = 'Job exceeded maxAttempts after lease expiration.'
                end
                redis.call('HSET', jobsKey, id, cjson.encode(job))
                redis.call('ZADD', terminalKey, nowTs, id)
            else
                job.attempt = attempt + 1
                job.generation = gen + 1
                job.lease_token = leaseToken
                job.lease_until = leaseUntil
                job.status = 'reserved'
                local updated = cjson.encode(job)
                redis.call('HSET', jobsKey, id, updated)
                redis.call('ZADD', reservedKey, leaseUntil, id)
                return updated
            end
        end
    end
end
return nil
LUA;

    private const string LUA_TRANSITION = <<<'LUA'
local reservedKey = KEYS[1]
local targetZsetKey = KEYS[2]
local jobsKey = KEYS[3]
local jobId = ARGV[1]
local expectedGen = tonumber(ARGV[2])
local expectedToken = ARGV[3]
local targetScore = tonumber(ARGV[4])
local newJobJson = ARGV[5]

local raw = redis.call('HGET', jobsKey, jobId)
if not raw then return 0 end
local ok, job = pcall(cjson.decode, raw)
if not ok or type(job) ~= 'table' then return 0 end
if job.status ~= 'reserved' then return 0 end
if tonumber(job.generation) ~= expectedGen then return 0 end
if tostring(job.lease_token or '') ~= expectedToken then return 0 end

redis.call('ZREM', reservedKey, jobId)
redis.call('HSET', jobsKey, jobId, newJobJson)
redis.call('ZADD', targetZsetKey, targetScore, jobId)
return 1
LUA;

    public function __construct(
        private RedisClient $redis,
        private string $prefix = 'kongono:queue:',
    ) {}

    public function push(
        string $queue,
        string $type,
        array $payload = [],
        int $delaySeconds = 0,
        int $maxAttempts = 3,
        ?int $now = null,
    ): Job {
        $ts = $now ?? time();
        $id = 'job_' . bin2hex(random_bytes(8));
        $availableAt = $ts + max(0, $delaySeconds);

        $job = new Job(
            id: $id,
            queue: $queue,
            type: $type,
            payload: $payload,
            attempt: 0,
            maxAttempts: max(1, $maxAttempts),
            generation: 0,
            leaseToken: null,
            availableAt: $availableAt,
            leaseUntil: null,
            status: JobStatus::Pending,
        );

        $this->saveJob($queue, $job);
        $this->redis->zAdd($this->key($queue, 'pending'), (float) $availableAt, $id);

        return $job;
    }

    public function reserve(string $queue, int $leaseSeconds = 60, ?int $now = null): ?Job
    {
        $ts = $now ?? time();
        $leaseUntil = $ts + max(1, $leaseSeconds);
        $token = bin2hex(random_bytes(12));

        $raw = $this->redis->eval(
            self::LUA_RESERVE,
            [
                $this->key($queue, 'pending'),
                $this->key($queue, 'reserved'),
                $this->key($queue, 'jobs'),
                $this->key($queue, 'terminal'),
            ],
            [(string) $ts, (string) $leaseUntil, $token],
        );

        return is_string($raw) ? $this->decodeJob($raw) : null;
    }

    public function acknowledge(Job $job): bool
    {
        if (null === $job->leaseToken) {
            return false;
        }

        $completed = new Job(
            id: $job->id,
            queue: $job->queue,
            type: $job->type,
            payload: $job->payload,
            attempt: $job->attempt,
            maxAttempts: $job->maxAttempts,
            generation: $job->generation,
            leaseToken: null,
            availableAt: $job->availableAt,
            leaseUntil: null,
            status: JobStatus::Completed,
            lastError: null,
        );

        return $this->atomicTransition($job, $this->key($job->queue, 'terminal'), time(), $completed);
    }

    public function releaseForRetry(Job $job, int $delaySeconds, ?string $error = null, ?int $now = null): bool
    {
        if (null === $job->leaseToken) {
            return false;
        }

        $nextAt = ($now ?? time()) + max(0, $delaySeconds);
        $retried = new Job(
            id: $job->id,
            queue: $job->queue,
            type: $job->type,
            payload: $job->payload,
            attempt: $job->attempt,
            maxAttempts: $job->maxAttempts,
            generation: $job->generation,
            leaseToken: null,
            availableAt: $nextAt,
            leaseUntil: null,
            status: JobStatus::Pending,
            lastError: $error,
        );

        return $this->atomicTransition($job, $this->key($job->queue, 'pending'), $nextAt, $retried);
    }

    public function markFailed(Job $job, JobStatus $terminalStatus, ?string $error = null): bool
    {
        if (null === $job->leaseToken) {
            return false;
        }

        $failed = new Job(
            id: $job->id,
            queue: $job->queue,
            type: $job->type,
            payload: $job->payload,
            attempt: $job->attempt,
            maxAttempts: $job->maxAttempts,
            generation: $job->generation,
            leaseToken: null,
            availableAt: $job->availableAt,
            leaseUntil: null,
            status: $terminalStatus,
            lastError: $error,
        );

        return $this->atomicTransition($job, $this->key($job->queue, 'terminal'), time(), $failed);
    }

    public function stats(string $queue): array
    {
        $counts = ['pending' => 0, 'reserved' => 0, 'completed' => 0, 'failed' => 0, 'dead' => 0];
        foreach ($this->redis->hGetAll($this->key($queue, 'jobs')) as $raw) {
            $job = $this->decodeJob($raw);
            if (null === $job) {
                continue;
            }
            ++$counts[$job->status->value];
        }

        return $counts;
    }

    public function prune(string $queue, int $olderThanTimestamp): int
    {
        $ids = $this->redis->zRangeByScore($this->key($queue, 'terminal'), '-inf', (string) $olderThanTimestamp);
        if ([] === $ids) {
            return 0;
        }

        $this->redis->zRem($this->key($queue, 'terminal'), ...$ids);
        $this->redis->hDel($this->key($queue, 'jobs'), ...$ids);

        return count($ids);
    }

    private function atomicTransition(Job $claim, string $targetZsetKey, int $targetScore, Job $nextState): bool
    {
        $res = $this->redis->eval(
            self::LUA_TRANSITION,
            [
                $this->key($claim->queue, 'reserved'),
                $targetZsetKey,
                $this->key($claim->queue, 'jobs'),
            ],
            [
                $claim->id,
                (string) $claim->generation,
                (string) $claim->leaseToken,
                (string) $targetScore,
                $this->encodeJob($nextState),
            ],
        );

        return 1 === $res;
    }

    private function saveJob(string $queue, Job $job): void
    {
        $this->redis->hSet($this->key($queue, 'jobs'), $job->id, $this->encodeJob($job));
    }

    private function encodeJob(Job $job): string
    {
        return json_encode([
            'id' => $job->id,
            'queue' => $job->queue,
            'type' => $job->type,
            'payload' => $job->payload,
            'attempt' => $job->attempt,
            'max_attempts' => $job->maxAttempts,
            'generation' => $job->generation,
            'lease_token' => $job->leaseToken,
            'available_at' => $job->availableAt,
            'lease_until' => $job->leaseUntil,
            'status' => $job->status->value,
            'last_error' => $job->lastError,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES, 64);
    }

    private function decodeJob(string $raw): ?Job
    {
        $data = json_decode($raw, true);
        if (!is_array($data) || !is_string($data['id'] ?? null) || !is_string($data['queue'] ?? null) || !is_string($data['type'] ?? null)) {
            return null;
        }

        $statusVal = is_string($data['status'] ?? null) ? $data['status'] : 'pending';
        $status = JobStatus::tryFrom($statusVal) ?? JobStatus::Pending;
        /** @var array<string, mixed> $payload */
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];

        return new Job(
            id: $data['id'],
            queue: $data['queue'],
            type: $data['type'],
            payload: $payload,
            attempt: is_int($data['attempt'] ?? null) ? $data['attempt'] : 0,
            maxAttempts: is_int($data['max_attempts'] ?? null) ? $data['max_attempts'] : 3,
            generation: is_int($data['generation'] ?? null) ? $data['generation'] : 0,
            leaseToken: is_string($data['lease_token'] ?? null) ? $data['lease_token'] : null,
            availableAt: is_int($data['available_at'] ?? null) ? $data['available_at'] : 0,
            leaseUntil: is_int($data['lease_until'] ?? null) ? $data['lease_until'] : null,
            status: $status,
            lastError: is_string($data['last_error'] ?? null) ? $data['last_error'] : null,
        );
    }

    private function key(string $queue, string $suffix): string
    {
        return $this->prefix . $queue . ':' . $suffix;
    }
}
