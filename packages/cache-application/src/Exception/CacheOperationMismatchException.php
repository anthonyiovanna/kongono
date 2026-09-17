<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheApplication\Diagnostic\CacheApplicationDiagnostic;

/** Reports use of evidence with the wrong explicit operation. */
#[Api(ApiStatus::Experimental)]
final class CacheOperationMismatchException extends CacheApplicationException
{
    public static function mismatch(): self
    {
        return new self(CacheApplicationDiagnostic::OperationMismatch, 'operation');
    }
}
