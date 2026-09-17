<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Diagnostic\CacheBuildDiagnostic;
use Throwable;

/** Reports one invalid bounded profile declaration or complete set. */
#[Api(ApiStatus::Experimental)]
final class InvalidCacheProfileException extends CacheBuildException
{
    public static function invalid(string $violation, int $profileCount = 0, int $profilePosition = 0, int $limit = 0, ?Throwable $previous = null): self
    {
        $safe = in_array($violation, ['profile_name', 'value_limit', 'contract_version', 'source_identity', 'profiles_not_list', 'profile_count', 'profile_entry', 'duplicate_profile_name', 'duplicate_namespace'], true) ? $violation : 'invalid_profile';
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

        return new self(CacheBuildDiagnostic::InvalidProfile, $context, $previous);
    }
}
