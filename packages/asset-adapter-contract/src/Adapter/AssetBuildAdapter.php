<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines the sole tool-neutral effect-facing asset adapter operation. */
#[Api(ApiStatus::Experimental)]
interface AssetBuildAdapter
{
    public function build(AdapterBuildRequest $request): AdapterBuildResult;
}
