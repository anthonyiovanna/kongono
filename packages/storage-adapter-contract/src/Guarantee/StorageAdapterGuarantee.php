<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Guarantee;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares one closed bounded adapter guarantee. */
#[Api(ApiStatus::Experimental)]
enum StorageAdapterGuarantee: string
{
    case CreateIfAbsent = 'create_if_absent';
    case VersionMatchedReplace = 'version_matched_replace';
    case VersionMatchedDelete = 'version_matched_delete';
    case ReadAfterCompletedMutation = 'read_after_completed_mutation';
    case OrdinalBoundedListing = 'ordinal_bounded_listing';
    case MetadataRoundTrip = 'metadata_round_trip';
    case ContentDigestVerification = 'content_digest_verification';
    case OperationCleanup = 'operation_cleanup';
}
