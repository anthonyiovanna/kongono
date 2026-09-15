<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Asset;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies one closed asset kind. */
#[Api(ApiStatus::Experimental)]
enum AssetKind: string
{
    case Script = 'script';
    case Style = 'style';
    case Image = 'image';
    case Font = 'font';
    case Media = 'media';
    case WebAssembly = 'web_assembly';
    case Data = 'data';
}
