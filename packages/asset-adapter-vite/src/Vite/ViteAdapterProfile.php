<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Vite;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;

/** Selects the one fixed self-contained Vite Version 1 profile. */
#[Api(ApiStatus::Experimental)]enum ViteAdapterProfile: string
{
    case ClientEs2022V1 = 'vite-client-es2022/1';
    public function identity(): AdapterProfileIdentity
    {
        return new AdapterProfileIdentity($this->value);
    }
}
