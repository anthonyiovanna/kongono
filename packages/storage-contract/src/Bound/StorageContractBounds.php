<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Bound;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Publishes finite contract limits without claiming provider guarantees. */
#[Api(ApiStatus::Experimental)]
final class StorageContractBounds
{
    public const int MAX_NAMESPACE_BYTES = 64;
    public const int MAX_LOGICAL_IDENTITY_BYTES = 255;
    public const int MAX_OPERATION_ID_BYTES = 64;
    public const int MAX_VERSION_BYTES = 256;
    public const int MAX_CONTINUATION_BYTES = 512;
    public const int MAX_MEDIA_TYPE_BYTES = 127;
    public const int MAX_METADATA_ENTRIES = 32;
    public const int MAX_METADATA_NAME_BYTES = 64;
    public const int MAX_METADATA_VALUE_BYTES = 1_024;
    public const int MAX_CHUNK_BYTES = 8_388_608;
    public const int MAX_OBJECT_BYTES = 1_099_511_627_776;
    public const int MAX_LIST_PAGE_SIZE = 1_000;

    private function __construct() {}
}
