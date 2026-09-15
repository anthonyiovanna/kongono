<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Delivery;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries an inert closed preload hint without requesting browser output. */
#[Api(ApiStatus::Experimental)]
enum PreloadHint: string
{
    case None = 'none';
    case Preload = 'preload';
    case ModulePreload = 'module_preload';
}
