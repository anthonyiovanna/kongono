<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Internal;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupResult;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupStatus;
use Kongono\CacheAdapterContract\Result\CacheAdapterMutationResult;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Entry\CacheEntryEvidence;
use Kongono\CacheContract\Freshness\CacheFreshness;
use Kongono\CacheContract\Freshness\CacheFreshnessWindow;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Operation\CacheOperation;
use Kongono\CacheContract\Outcome\CacheLookupOutcome;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;
use Kongono\CacheRuntime\Exception\InvalidCacheRuntimeException;
use Kongono\CacheRuntime\Result\CacheLookupResult;
use Kongono\CacheRuntime\Result\CacheMutationResult;
use Throwable;

/** Converts only completely validated adapter results into detached runtime evidence. */
#[Api(ApiStatus::Internal)]
final class AdapterResultValidator
{
    public static function lookup(
        CacheProfileDeclaration $profile,
        CacheKeyIdentity $requestedKey,
        CacheInstant $now,
        CacheAdapterLookupResult $result,
    ): CacheLookupResult {
        self::key($requestedKey, $result->keyIdentity(), CacheOperation::Lookup);

        return match ($result->status()) {
            CacheAdapterLookupStatus::Miss => new CacheLookupResult(CacheLookupOutcome::Miss, self::copyKey($requestedKey)),
            CacheAdapterLookupStatus::Rejected => new CacheLookupResult(CacheLookupOutcome::Rejected, self::copyKey($requestedKey)),
            CacheAdapterLookupStatus::Unavailable => new CacheLookupResult(CacheLookupOutcome::Unavailable, self::copyKey($requestedKey)),
            CacheAdapterLookupStatus::Found => self::found($profile, $requestedKey, $now, $result),
        };
    }

    public static function mutation(
        CacheProfileDeclaration $profile,
        CacheOperation $operation,
        CacheKeyIdentity $requestedKey,
        ?CacheAdapterEntry $candidate,
        CacheAdapterMutationResult $result,
    ): CacheMutationResult {
        self::key($requestedKey, $result->keyIdentity(), $operation);
        $allowed = match ($operation) {
            CacheOperation::Store => [CacheMutationOutcome::Stored, CacheMutationOutcome::Conflict, CacheMutationOutcome::Rejected, CacheMutationOutcome::Unavailable],
            CacheOperation::Replace => [CacheMutationOutcome::Stored, CacheMutationOutcome::NotFound, CacheMutationOutcome::Conflict, CacheMutationOutcome::Rejected, CacheMutationOutcome::Unavailable],
            CacheOperation::Remove => [CacheMutationOutcome::Removed, CacheMutationOutcome::NotFound, CacheMutationOutcome::Conflict, CacheMutationOutcome::Rejected, CacheMutationOutcome::Unavailable],
            CacheOperation::Lookup => [],
        };
        if (!in_array($result->outcome(), $allowed, true)) {
            throw InvalidCacheRuntimeException::invalidAdapterEvidence('outcome_not_allowed', $operation->value, $result->outcome()->value);
        }

        if (CacheMutationOutcome::Stored !== $result->outcome()) {
            if (null !== $result->entryEvidence()) {
                throw InvalidCacheRuntimeException::invalidAdapterEvidence('entry_presence', $operation->value, $result->outcome()->value);
            }

            return new CacheMutationResult($result->outcome(), self::copyKey($requestedKey));
        }

        $entry = $result->entryEvidence();
        if (!$entry instanceof CacheEntryEvidence || !$candidate instanceof CacheAdapterEntry) {
            throw InvalidCacheRuntimeException::invalidAdapterEvidence('entry_presence', $operation->value, $result->outcome()->value);
        }
        self::entry($profile, $requestedKey, $entry, $operation, $result->outcome()->value);
        if (!self::matchesCandidate($candidate, $entry)) {
            throw InvalidCacheRuntimeException::invalidAdapterEvidence('candidate_mismatch', $operation->value, $result->outcome()->value);
        }

        return new CacheMutationResult(CacheMutationOutcome::Stored, self::copyKey($requestedKey), self::copyEntry($entry));
    }

    private static function found(CacheProfileDeclaration $profile, CacheKeyIdentity $requestedKey, CacheInstant $now, CacheAdapterLookupResult $result): CacheLookupResult
    {
        $entry = $result->entryEvidence();
        if (!$entry instanceof CacheEntryEvidence) {
            throw InvalidCacheRuntimeException::invalidAdapterEvidence('entry_presence', CacheOperation::Lookup->value, $result->status()->value);
        }
        self::entry($profile, $requestedKey, $entry, CacheOperation::Lookup, $result->status()->value);
        try {
            $freshness = $entry->freshnessWindow()->freshnessAt($now);
        } catch (Error $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw InvalidCacheRuntimeException::invalidAdapterEvidence('time_relation', CacheOperation::Lookup->value, $result->status()->value, $failure);
        }
        if (CacheFreshness::Expired === $freshness) {
            return new CacheLookupResult(CacheLookupOutcome::Expired, self::copyKey($requestedKey));
        }

        return new CacheLookupResult(CacheLookupOutcome::FreshHit, self::copyKey($requestedKey), self::copyEntry($entry));
    }

    private static function entry(CacheProfileDeclaration $profile, CacheKeyIdentity $requestedKey, CacheEntryEvidence $entry, CacheOperation $operation, string $outcome): void
    {
        match ($entry->contractVersion()) {
            ContractVersion::Version1 => null,
        };
        if (!$entry->keyIdentity()->equals($requestedKey)) {
            throw InvalidCacheRuntimeException::invalidAdapterEvidence('key_mismatch', $operation->value, $outcome);
        }
        if (!$entry->codecIdentity()->equals($profile->codec())) {
            throw InvalidCacheRuntimeException::invalidAdapterEvidence('codec_mismatch', $operation->value, $outcome);
        }
        if ($entry->valueBytes()->byteCount() > $profile->valueLimit()->bytes()) {
            throw InvalidCacheRuntimeException::invalidAdapterEvidence(
                'value_limit',
                $operation->value,
                $outcome,
                byteCount: $entry->valueBytes()->byteCount(),
                limit: $profile->valueLimit()->bytes(),
            );
        }
        if (!$entry->valueBytes()->contentIdentity()->equals($entry->contentIdentity())) {
            throw InvalidCacheRuntimeException::invalidAdapterEvidence('content_mismatch', $operation->value, $outcome);
        }
        if ($entry->freshnessWindow()->lifetimeMicroseconds() !== $profile->lifetime()->microseconds()) {
            throw InvalidCacheRuntimeException::invalidAdapterEvidence('freshness_mismatch', $operation->value, $outcome);
        }
    }

    private static function key(CacheKeyIdentity $requested, CacheKeyIdentity $returned, CacheOperation $operation): void
    {
        if (!$requested->equals($returned)) {
            throw InvalidCacheRuntimeException::invalidAdapterEvidence('key_mismatch', $operation->value);
        }
    }

    private static function matchesCandidate(CacheAdapterEntry $candidate, CacheEntryEvidence $entry): bool
    {
        return $candidate->keyIdentity()->equals($entry->keyIdentity())
            && $candidate->codecIdentity()->equals($entry->codecIdentity())
            && $candidate->valueBytes()->equals($entry->valueBytes())
            && $candidate->contentIdentity()->equals($entry->contentIdentity())
            && $candidate->freshnessWindow()->equals($entry->freshnessWindow());
    }

    private static function copyKey(CacheKeyIdentity $key): CacheKeyIdentity
    {
        return new CacheKeyIdentity($key->digest());
    }

    private static function copyEntry(CacheEntryEvidence $entry): CacheEntryEvidence
    {
        return new CacheEntryEvidence(
            $entry->contractVersion(),
            self::copyKey($entry->keyIdentity()),
            new CacheCodecIdentity($entry->codecIdentity()->name(), $entry->codecIdentity()->version()),
            new CacheValueBytes($entry->valueBytes()->bytes()),
            new CacheContentIdentity($entry->contentIdentity()->digest()),
            new CacheFreshnessWindow(
                new CacheInstant($entry->freshnessWindow()->storedAt()->microseconds()),
                new CacheInstant($entry->freshnessWindow()->expiresAt()->microseconds()),
            ),
            new CacheEntryVersion($entry->entryVersion()->token()),
        );
    }

    private function __construct() {}
}
