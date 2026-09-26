<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Guarantee;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares one closed bounded adapter limitation. */
#[Api(ApiStatus::Experimental)]
enum StorageAdapterLimitation: string
{
    case LocalFilesystemOnly = 'local_filesystem_only';
    case SingleHostOnly = 'single_host_only';
    case FilesystemDependentAtomicity = 'filesystem_dependent_atomicity';
    case FilesystemDependentDurability = 'filesystem_dependent_durability';
    case NoSnapshotListing = 'no_snapshot_listing';
    case NoPublicUrl = 'no_public_url';
    case NoReplication = 'no_replication';
    case NoEncryptionGuarantee = 'no_encryption_guarantee';
    case NoCrossProcessTransaction = 'no_cross_process_transaction';
    case NoOutOfBandWriterProtection = 'no_out_of_band_writer_protection';
}
