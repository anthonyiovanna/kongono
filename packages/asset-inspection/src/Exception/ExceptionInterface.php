<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks failures owned by the asset-inspection package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
