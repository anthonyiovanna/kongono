<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects the explicit provider storage preference without asserting retention behavior. */
#[Api(ApiStatus::Experimental)]
enum ResponseStoragePreference: string
{
    case Disabled = 'disabled';
    case Enabled = 'enabled';

    public function store(): bool
    {
        return self::Enabled === $this;
    }
}
