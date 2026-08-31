<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports missing, unnecessary, unknown, or non-claimant conflict evidence. */
#[Api(ApiStatus::Experimental)]
final class UnresolvedExtensionConflictException extends ExtensionAdmissionException
{
    private const array KINDS = ['missing_resolution', 'unnecessary_resolution', 'unknown_resolution', 'nonclaimant_winner', 'graph_identity_conflict'];

    public static function conflict(string $kind, ?Throwable $previous = null): self
    {
        return new self(['failure_kind' => in_array($kind, self::KINDS, true) ? $kind : 'unsupported_failure'], $previous);
    }
}
