<?php

declare(strict_types=1);

namespace Kongono\Jobs;

use Kongono\Core\Cache\RedisClient;

/**
 * Zero-dependency Redis / Valkey distributed queue driver with atomic server-side Lua scripts
 * for delayed sorted sets, lease expiration reclaim, generation + lease-token fencing,
 * `uniqueKey` deduplication, and dead-letter replay.
 */
final readonly class RedisQueueDriver implements QueueDriverInterface
{
    private const string LUA_PUSH = <<<'LUA'
local pendingKey = KEYS[1]
local jobsKey = KEYS[2]
local newId = ARGV[1]
local availableAt = tonumber(ARGV[2])
local newJobJson = ARGV[3]

redis.call('HSET', jobsKey, newId, newJobJson)
redis.call('ZADD', pendingKey, availableAt, newId)
return 1
LUA;

    private const string LUA_PUSH_UNIQUE = <<<'LUA'
local pendingKey = KEYS[1]
local jobsKey = KEYS[2]
local uniqueKeyHash = KEYS[3]
local uniqueKey = ARGV[1]
local newId = ARGV[2]
local availableAt = tonumber(ARGV[3])
local newJobJson = ARGV[4]

local existingId = redis.call('HGET', uniqueKeyHash, uniqueKey)
if existingId then
    local existingRaw = redis.call('HGET', jobsKey, existingId)
    if existingRaw then
        local ok, existing = pcall(cjson.decode, existingRaw)
        if ok and type(existing) == 'table' and (existing.status == 'pending' or existing.status == 'reserved') then
            return existingRaw
        end
    end
end

redis.call('HSET', jobsKey, newId, newJobJson)
redis.call('ZADD', pendingKey, availableAt, newId)
redis.call('HSET', uniqueKeyHash, uniqueKey, newId)
return newJobJson
LUA;

    private const string LUA_RESERVE = <<<'LUA'
local pendingKey = KEYS[1]
local reservedKey = KEYS[2]
local jobsKey = KEYS[3]
local terminalKey = KEYS[4]
local countsKey = KEYS[5]
local uniqueKeyHash = KEYS[6]
local failedKey = KEYS[7]
local nowTs = tonumber(ARGV[1])
local leaseUntil = tonumber(ARGV[2])
local leaseToken = ARGV[3]

for _ = 1, 32 do
    local p = redis.call('ZRANGEBYSCORE', pendingKey, '-inf', ARGV[1], 'WITHSCORES', 'LIMIT', 0, 1)
    local r = redis.call('ZRANGEBYSCORE', reservedKey, '-inf', ARGV[1], 'WITHSCORES', 'LIMIT', 0, 1)
    local sourceKey = nil
    local id = nil
    if #p > 0 and #r > 0 then
        if tonumber(r[2]) <= tonumber(p[2]) then
            sourceKey = reservedKey
            id = r[1]
        else
            sourceKey = pendingKey
            id = p[1]
        end
    elseif #p > 0 then
        sourceKey = pendingKey
        id = p[1]
    elseif #r > 0 then
        sourceKey = reservedKey
        id = r[1]
    else
        return nil
    end

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
                if type(job.unique_key) == 'string' and job.unique_key ~= '' then
                    if redis.call('HGET', uniqueKeyHash, job.unique_key) == id then
                        redis.call('HDEL', uniqueKeyHash, job.unique_key)
                    end
                end
                redis.call('HSET', jobsKey, id, cjson.encode(job))
                redis.call('ZADD', terminalKey, nowTs, id)
                redis.call('ZADD', failedKey, nowTs, id)
                redis.call('HINCRBY', countsKey, 'dead', 1)
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
local countsKey = KEYS[4]
local uniqueKeyHash = KEYS[5]
local failedKey = KEYS[6]
local jobId = ARGV[1]
local expectedGen = tonumber(ARGV[2])
local expectedToken = ARGV[3]
local targetScore = tonumber(ARGV[4])
local newJobJson = ARGV[5]
local terminalStatus = ARGV[6]

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
if terminalStatus == 'failed' or terminalStatus == 'dead' then
    redis.call('ZADD', failedKey, targetScore, jobId)
end
if terminalStatus == 'completed' or terminalStatus == 'failed' or terminalStatus == 'dead' then
    redis.call('HINCRBY', countsKey, terminalStatus, 1)
    if type(job.unique_key) == 'string' and job.unique_key ~= '' then
        if redis.call('HGET', uniqueKeyHash, job.unique_key) == jobId then
            redis.call('HDEL', uniqueKeyHash, job.unique_key)
        end
    end
end
return 1
LUA;

    private const string LUA_PRUNE = <<<'LUA'
local terminalKey = KEYS[1]
local jobsKey = KEYS[2]
local countsKey = KEYS[3]
local failedKey = KEYS[4]
local maxScore = ARGV[1]

local ids = redis.call('ZRANGEBYSCORE', terminalKey, '-inf', maxScore, 'LIMIT', 0, 500)
if #ids == 0 then return 0 end
for _, id in ipairs(ids) do
    local raw = redis.call('HGET', jobsKey, id)
    if raw then
        local ok, job = pcall(cjson.decode, raw)
        if ok and type(job) == 'table' then
            local st = tostring(job.status or '')
            if st == 'completed' or st == 'failed' or st == 'dead' then
                local cur = tonumber(redis.call('HGET', countsKey, st)) or 0
                if cur > 0 then
                    redis.call('HINCRBY', countsKey, st, -1)
                end
            end
        end
    end
    redis.call('ZREM', terminalKey, id)
    redis.call('ZREM', failedKey, id)
    redis.call('HDEL', jobsKey, id)
end
return #ids
LUA;

    private const string LUA_LIST_FAILED = <<<'LUA'
local failedKey = KEYS[1]
local jobsKey = KEYS[2]
local maxLimit = tonumber(ARGV[1]) or 100

local ids = redis.call('ZREVRANGE', failedKey, 0, maxLimit - 1)
local out = {}
for _, id in ipairs(ids) do
    local raw = redis.call('HGET', jobsKey, id)
    if raw then
        local ok, job = pcall(cjson.decode, raw)
        if ok and type(job) == 'table' and (job.status == 'failed' or job.status == 'dead') then
            out[#out + 1] = raw
            if #out >= maxLimit then
                break
            end
        end
    end
end
return out
LUA;

    private const string LUA_RETRY_FAILED = <<<'LUA'
local terminalKey = KEYS[1]
local pendingKey = KEYS[2]
local jobsKey = KEYS[3]
local countsKey = KEYS[4]
local uniqueKeyHash = KEYS[5]
local failedKey = KEYS[6]
local targetId = ARGV[1]
local nowTs = tonumber(ARGV[2])
local startOffset = tonumber(ARGV[3]) or 0

local ids = {}
if targetId ~= '' then
    ids = {targetId}
else
    ids = redis.call('ZRANGE', failedKey, startOffset, startOffset + 499)
end

local requeued = 0
local skipped = 0
for _, id in ipairs(ids) do
    local raw = redis.call('HGET', jobsKey, id)
    if raw then
        local ok, job = pcall(cjson.decode, raw)
        if ok and type(job) == 'table' and (job.status == 'failed' or job.status == 'dead') then
            local duplicateActive = false
            if type(job.unique_key) == 'string' and job.unique_key ~= '' then
                local existingId = redis.call('HGET', uniqueKeyHash, job.unique_key)
                if existingId and existingId ~= id then
                    local existingRaw = redis.call('HGET', jobsKey, existingId)
                    if existingRaw then
                        local eok, existing = pcall(cjson.decode, existingRaw)
                        if eok and type(existing) == 'table' and (existing.status == 'pending' or existing.status == 'reserved') then
                            duplicateActive = true
                        end
                    end
                end
            end
            if not duplicateActive then
                local oldSt = tostring(job.status)
                local cur = tonumber(redis.call('HGET', countsKey, oldSt)) or 0
                if cur > 0 then
                    redis.call('HINCRBY', countsKey, oldSt, -1)
                end
                job.status = 'pending'
                job.attempt = 0
                job.generation = (tonumber(job.generation) or 0) + 1
                job.lease_token = cjson.null
                job.lease_until = cjson.null
                job.available_at = nowTs
                job.last_error = cjson.null
                redis.call('ZREM', terminalKey, id)
                redis.call('ZREM', failedKey, id)
                redis.call('HSET', jobsKey, id, cjson.encode(job))
                redis.call('ZADD', pendingKey, nowTs, id)
                if type(job.unique_key) == 'string' and job.unique_key ~= '' then
                    redis.call('HSET', uniqueKeyHash, job.unique_key, id)
                end
                requeued = requeued + 1
            else
                skipped = skipped + 1
            end
        else
            redis.call('ZREM', failedKey, id)
        end
    else
        redis.call('ZREM', failedKey, id)
    end
end
return {requeued, #ids, skipped}
LUA;

    private const string LUA_FLUSH_FAILED = <<<'LUA'
local terminalKey = KEYS[1]
local jobsKey = KEYS[2]
local countsKey = KEYS[3]
local failedKey = KEYS[4]

local ids = redis.call('ZRANGE', failedKey, 0, 499)
local removed = 0
for _, id in ipairs(ids) do
    local raw = redis.call('HGET', jobsKey, id)
    if raw then
        local ok, job = pcall(cjson.decode, raw)
        if ok and type(job) == 'table' and (job.status == 'failed' or job.status == 'dead') then
            local st = tostring(job.status)
            local cur = tonumber(redis.call('HGET', countsKey, st)) or 0
            if cur > 0 then
                redis.call('HINCRBY', countsKey, st, -1)
            end
            redis.call('ZREM', terminalKey, id)
            redis.call('ZREM', failedKey, id)
            redis.call('HDEL', jobsKey, id)
            removed = removed + 1
        else
            redis.call('ZREM', failedKey, id)
        end
    else
        redis.call('ZREM', failedKey, id)
    end
end
return {removed, #ids}
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
        ?string $uniqueKey = null,
    ): Job {
        $ts = $now ?? time();
        $id = 'job_' . bin2hex(random_bytes(8));
        $availableAt = $ts + max(0, $delaySeconds);
        $cleanUnique = null !== $uniqueKey && '' !== trim($uniqueKey) ? trim($uniqueKey) : null;

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
            uniqueKey: $cleanUnique,
        );

        if (null !== $cleanUnique) {
            $raw = $this->redis->eval(
                self::LUA_PUSH_UNIQUE,
                [
                    $this->key($queue, 'pending'),
                    $this->key($queue, 'jobs'),
                    $this->key($queue, 'unique'),
                ],
                [$cleanUnique, $id, (string) $availableAt, $this->encodeJob($job)],
            );

            return is_string($raw) ? ($this->decodeJob($raw) ?? $job) : $job;
        }

        $this->redis->eval(
            self::LUA_PUSH,
            [
                $this->key($queue, 'pending'),
                $this->key($queue, 'jobs'),
            ],
            [$id, (string) $availableAt, $this->encodeJob($job)],
        );

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
                $this->key($queue, 'counts'),
                $this->key($queue, 'unique'),
                $this->key($queue, 'failed'),
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
            uniqueKey: $job->uniqueKey,
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
            uniqueKey: $job->uniqueKey,
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
            uniqueKey: $job->uniqueKey,
        );

        return $this->atomicTransition($job, $this->key($job->queue, 'terminal'), time(), $failed);
    }

    public function stats(string $queue): array
    {
        $terminalCounts = $this->redis->hGetAll($this->key($queue, 'counts'));

        return [
            'pending' => $this->redis->zCard($this->key($queue, 'pending')),
            'reserved' => $this->redis->zCard($this->key($queue, 'reserved')),
            'completed' => max(0, (int) ($terminalCounts['completed'] ?? 0)),
            'failed' => max(0, (int) ($terminalCounts['failed'] ?? 0)),
            'dead' => max(0, (int) ($terminalCounts['dead'] ?? 0)),
        ];
    }

    public function prune(string $queue, int $olderThanTimestamp): int
    {
        $totalPruned = 0;
        do {
            $batch = $this->redis->eval(
                self::LUA_PRUNE,
                [
                    $this->key($queue, 'terminal'),
                    $this->key($queue, 'jobs'),
                    $this->key($queue, 'counts'),
                    $this->key($queue, 'failed'),
                ],
                [(string) $olderThanTimestamp],
            );
            $count = is_int($batch) ? $batch : 0;
            $totalPruned += $count;
        } while ($count >= 500);

        return $totalPruned;
    }

    public function listFailed(string $queue, int $limit = 100): array
    {
        $res = $this->redis->eval(
            self::LUA_LIST_FAILED,
            [
                $this->key($queue, 'failed'),
                $this->key($queue, 'jobs'),
            ],
            [(string) max(1, $limit)],
        );

        if (!is_array($res)) {
            return [];
        }

        $jobs = [];
        foreach ($res as $raw) {
            if (is_string($raw)) {
                $decoded = $this->decodeJob($raw);
                if (null !== $decoded) {
                    $jobs[] = $decoded;
                }
            }
        }

        return $jobs;
    }

    public function retryFailed(string $queue, ?string $jobId = null, ?int $now = null): int
    {
        $ts = $now ?? time();
        $totalRequeued = 0;
        $offset = 0;
        do {
            $res = $this->redis->eval(
                self::LUA_RETRY_FAILED,
                [
                    $this->key($queue, 'terminal'),
                    $this->key($queue, 'pending'),
                    $this->key($queue, 'jobs'),
                    $this->key($queue, 'counts'),
                    $this->key($queue, 'unique'),
                    $this->key($queue, 'failed'),
                ],
                [$jobId ?? '', (string) $ts, (string) $offset],
            );
            $requeued = is_array($res) && is_int($res[0] ?? null) ? $res[0] : (is_int($res) ? $res : 0);
            $scanned = is_array($res) && is_int($res[1] ?? null) ? $res[1] : $requeued;
            $skipped = is_array($res) && is_int($res[2] ?? null) ? $res[2] : 0;
            $totalRequeued += $requeued;
            $offset += $skipped;
        } while (null === $jobId && $scanned >= 500);

        return $totalRequeued;
    }

    public function flushFailed(string $queue): int
    {
        $totalRemoved = 0;
        do {
            $res = $this->redis->eval(
                self::LUA_FLUSH_FAILED,
                [
                    $this->key($queue, 'terminal'),
                    $this->key($queue, 'jobs'),
                    $this->key($queue, 'counts'),
                    $this->key($queue, 'failed'),
                ],
                [],
            );
            $removed = is_array($res) && is_int($res[0] ?? null) ? $res[0] : (is_int($res) ? $res : 0);
            $scanned = is_array($res) && is_int($res[1] ?? null) ? $res[1] : $removed;
            $totalRemoved += $removed;
        } while ($scanned >= 500);

        return $totalRemoved;
    }

    private function atomicTransition(Job $claim, string $targetZsetKey, int $targetScore, Job $nextState): bool
    {
        $res = $this->redis->eval(
            self::LUA_TRANSITION,
            [
                $this->key($claim->queue, 'reserved'),
                $targetZsetKey,
                $this->key($claim->queue, 'jobs'),
                $this->key($claim->queue, 'counts'),
                $this->key($claim->queue, 'unique'),
                $this->key($claim->queue, 'failed'),
            ],
            [
                $claim->id,
                (string) $claim->generation,
                (string) $claim->leaseToken,
                (string) $targetScore,
                $this->encodeJob($nextState),
                $nextState->status->value,
            ],
        );

        return 1 === $res;
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
            'unique_key' => $job->uniqueKey,
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
            uniqueKey: is_string($data['unique_key'] ?? null) && '' !== $data['unique_key'] ? $data['unique_key'] : null,
        );
    }

    private function key(string $queue, string $suffix): string
    {
        // Wrap queue name in Redis Cluster hash tags so multi-key Lua scripts always share one slot
        $safeQueue = str_replace(['{', '}'], '_', $queue);

        return $this->prefix . '{' . $safeQueue . '}:' . $suffix;
    }
}
