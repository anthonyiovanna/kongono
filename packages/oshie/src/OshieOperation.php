<?php

declare(strict_types=1);

namespace Kongono\Oshie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the four closed Phase 4F read-only learning operations. */
#[Api(ApiStatus::Experimental)]
enum OshieOperation: string
{
    case Catalog = 'catalog';
    case Lesson = 'lesson';
    case Exercise = 'exercise';
    case Check = 'check';
}
