<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Diagnostic\StorageBuildDiagnostic;
use Throwable;

/** Reports one invalid bounded profile declaration or complete set. */
#[Api(ApiStatus::Experimental)]
final class InvalidStorageProfileException extends StorageBuildException
{
    public static function invalid(string $violation, int $profileCount = 0, int $profilePosition = 0, int $limit = 0, ?Throwable $previous = null): self
    {
        $safe = in_array($violation, ['profile_name', 'max_chunk_bytes', 'max_object_bytes', 'max_list_page_size', 'chunk_object_relation', 'contract_version', 'empty_capabilities', 'digest_capability_relation', 'source_identity', 'profiles_not_list', 'profile_count', 'profile_entry', 'duplicate_profile_name', 'duplicate_namespace'], true) ? $violation : 'invalid_profile';
        $context = ['violation' => $safe];
        if ($profileCount > 0) {
            $context['profile_count'] = $profileCount;
        }
        if ($profilePosition > 0) {
            $context['profile_position'] = $profilePosition;
        }
        if ($limit > 0) {
            $context['limit'] = $limit;
        }

        return new self(StorageBuildDiagnostic::InvalidProfile, $context, $previous);
    }
}
