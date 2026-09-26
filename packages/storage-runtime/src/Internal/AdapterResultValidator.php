<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\Content\StorageMediaType;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Identity\LogicalObjectIdentity;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Metadata\StorageMetadata;
use Kongono\StorageContract\Metadata\StorageMetadataEntry;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Outcome\StorageListOutcome;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Pagination\StorageContinuationToken;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Result\StorageListEntry;
use Kongono\StorageContract\Result\StorageListPage;
use Kongono\StorageContract\Result\StorageListResult;
use Kongono\StorageContract\Result\StorageMutationResult;
use Kongono\StorageContract\Result\StorageObjectEvidence;
use Kongono\StorageContract\Result\StorageReadResult;
use Kongono\StorageContract\Version\StorageObjectVersion;
use Kongono\StorageRuntime\Exception\InvalidStorageRuntimeException;

/** Validates and detaches untrusted neutral adapter evidence. */
#[Api(ApiStatus::Internal)]
final class AdapterResultValidator
{
    public static function read(StorageProfileDeclaration $profile, StorageReadRequest $request, StorageReadResult $result): StorageReadResult
    {
        $outcome = $result->outcome();
        $evidence = $result->evidence();
        if (StorageReadOutcome::Completed !== $outcome) {
            if (null !== $evidence) {
                throw self::invalid('read_evidence_relation', StorageOperation::Read, $outcome->value);
            }

            return new StorageReadResult($outcome);
        }
        if (!$evidence instanceof StorageObjectEvidence) {
            throw self::invalid('read_evidence_missing', StorageOperation::Read, $outcome->value);
        }
        self::object($profile, $request->objectIdentity(), $evidence, $request->maximumByteCount()->value(), StorageOperation::Read);

        return new StorageReadResult($outcome, self::copyObject($evidence));
    }

    public static function mutation(StorageProfileDeclaration $profile, StorageOperation $operation, StorageWriteRequest|StorageDeleteRequest $request, StorageMutationResult $result): StorageMutationResult
    {
        $outcome = $result->outcome();
        $allowed = match ($operation) {
            StorageOperation::Create => [StorageMutationOutcome::Completed, StorageMutationOutcome::AlreadyExists, StorageMutationOutcome::Rejected, StorageMutationOutcome::Unsupported, StorageMutationOutcome::Unavailable, StorageMutationOutcome::Uncertain],
            StorageOperation::Replace, StorageOperation::Delete => [StorageMutationOutcome::Completed, StorageMutationOutcome::Absent, StorageMutationOutcome::Conflict, StorageMutationOutcome::Rejected, StorageMutationOutcome::Unsupported, StorageMutationOutcome::Unavailable, StorageMutationOutcome::Uncertain],
            default => [],
        };
        if (!in_array($outcome, $allowed, true)) {
            throw self::invalid('outcome_not_allowed', $operation, $outcome->value);
        }
        $version = $result->resultingVersion();
        $digest = $result->resultingDigest();
        if (StorageMutationOutcome::Completed === $outcome) {
            if (!$version instanceof StorageObjectVersion) {
                throw self::invalid('mutation_version_missing', $operation, $outcome->value);
            }
            if ($request instanceof StorageWriteRequest) {
                if (!$digest instanceof StorageContentDigest || !$digest->equals($request->contentDigest())) {
                    throw self::invalid('mutation_digest_mismatch', $operation, $outcome->value);
                }
            } else {
                $expected = $request->condition()->version();
                if (null !== $digest || null === $expected || !$version->equals($expected)) {
                    throw self::invalid('delete_version_relation', $operation, $outcome->value);
                }
            }
        } elseif (null !== $version || null !== $digest) {
            throw self::invalid('mutation_evidence_relation', $operation, $outcome->value);
        }

        return new StorageMutationResult(
            $outcome,
            null === $version ? null : new StorageObjectVersion($version->value()),
            null === $digest ? null : new StorageContentDigest($digest->algorithm(), $digest->digest()),
        );
    }

    public static function list(StorageProfileDeclaration $profile, StorageListRequest $request, StorageListResult $result): StorageListResult
    {
        $outcome = $result->outcome();
        $page = $result->page();
        if (StorageListOutcome::Completed !== $outcome) {
            if (null !== $page) {
                throw self::invalid('list_page_relation', StorageOperation::List, $outcome->value);
            }

            return new StorageListResult($outcome);
        }
        if (!$page instanceof StorageListPage || count($page->entries()) > $request->pageSize() || count($page->entries()) > $profile->limits()->maxListPageSize()) {
            throw self::invalid('list_page_limit', StorageOperation::List, $outcome->value);
        }
        $entries = [];
        $previous = null;
        foreach ($page->entries() as $entry) {
            if (!$entry->identity()->namespace()->equals($request->namespace())) {
                throw self::invalid('list_namespace_mismatch', StorageOperation::List, $outcome->value);
            }
            if (null !== $entry->byteCount() && $entry->byteCount()->value() > $profile->limits()->maxObjectBytes()) {
                throw self::invalid('list_object_limit', StorageOperation::List, $outcome->value, ['byte_count' => $entry->byteCount()->value(), 'limit' => $profile->limits()->maxObjectBytes()]);
            }
            $key = $entry->identity()->logicalIdentity()->value();
            if (null !== $previous && strcmp($previous, $key) >= 0) {
                throw self::invalid('list_order', StorageOperation::List, $outcome->value);
            }
            $previous = $key;
            $entries[] = new StorageListEntry(
                self::copyIdentity($entry->identity()),
                null === $entry->byteCount() ? null : new StorageByteCount($entry->byteCount()->value()),
                null === $entry->objectVersion() ? null : new StorageObjectVersion($entry->objectVersion()->value()),
            );
        }
        $token = $page->continuationToken();

        return new StorageListResult($outcome, new StorageListPage($entries, $page->complete(), null === $token ? null : new StorageContinuationToken($token->value())));
    }

    private static function object(StorageProfileDeclaration $profile, StorageObjectIdentity $expected, StorageObjectEvidence $evidence, int $maximum, StorageOperation $operation): void
    {
        if (!$evidence->identity()->equals($expected) || !$evidence->identity()->namespace()->equals($profile->namespace())) {
            throw self::invalid('object_identity_mismatch', $operation, 'completed');
        }
        $count = $evidence->byteCount()->value();
        if ($count > $maximum || $count > $profile->limits()->maxObjectBytes()) {
            throw self::invalid('object_byte_limit', $operation, 'completed', ['byte_count' => $count, 'limit' => min($maximum, $profile->limits()->maxObjectBytes())]);
        }
        $digest = $evidence->contentDigest();
        $expectsDigest = $profile->capabilities()->contains(StorageCapability::ContentDigest);
        if ($expectsDigest !== (null !== $digest) || (null !== $digest && $digest->algorithm() !== $profile->digestAlgorithm())) {
            throw self::invalid('object_digest_relation', $operation, 'completed');
        }
        if (!$profile->capabilities()->contains(StorageCapability::Metadata) && [] !== $evidence->metadata()->entries()) {
            throw self::invalid('object_metadata_relation', $operation, 'completed');
        }
        if (null === $evidence->objectVersion()) {
            throw self::invalid('object_version_missing', $operation, 'completed');
        }
    }

    private static function copyObject(StorageObjectEvidence $evidence): StorageObjectEvidence
    {
        $digest = $evidence->contentDigest();
        $mediaType = $evidence->mediaType();
        $version = $evidence->objectVersion();
        $metadata = [];
        foreach ($evidence->metadata()->entries() as $entry) {
            $metadata[] = new StorageMetadataEntry($entry->name(), $entry->value());
        }

        return new StorageObjectEvidence(
            self::copyIdentity($evidence->identity()),
            new StorageByteCount($evidence->byteCount()->value()),
            null === $digest ? null : new StorageContentDigest($digest->algorithm(), $digest->digest()),
            null === $mediaType ? null : new StorageMediaType($mediaType->value()),
            new StorageMetadata($metadata),
            null === $version ? null : new StorageObjectVersion($version->value()),
        );
    }

    private static function copyIdentity(StorageObjectIdentity $identity): StorageObjectIdentity
    {
        return new StorageObjectIdentity(new StorageNamespace($identity->namespace()->value()), new LogicalObjectIdentity($identity->logicalIdentity()->value()));
    }

    /** @param array<string, string|int|bool> $context */
    private static function invalid(string $violation, StorageOperation $operation, ?string $outcome = null, array $context = []): InvalidStorageRuntimeException
    {
        return InvalidStorageRuntimeException::evidence($violation, $operation->value, $outcome, context: $context);
    }
}
