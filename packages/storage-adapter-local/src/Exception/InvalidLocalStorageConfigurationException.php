<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterLocal\Diagnostic\LocalStorageAdapterDiagnostic;

/** Reports one bounded configuration failure. */
#[Api(ApiStatus::Experimental)]
final class InvalidLocalStorageConfigurationException extends LocalStorageAdapterException
{
    public static function invalid(string $violation): self
    {
        return new self(LocalStorageAdapterDiagnostic::InvalidConfiguration, ['violation' => $violation]);
    }
}
