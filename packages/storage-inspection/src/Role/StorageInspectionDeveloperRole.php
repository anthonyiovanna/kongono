<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Role;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects an evidence purpose without executing Chie, Hantei, or Oshie. */
#[Api(ApiStatus::Experimental)]
enum StorageInspectionDeveloperRole: string
{
    case Chie = 'chie';
    case Hantei = 'hantei';
    case Oshie = 'oshie';

    public function purpose(): string
    {
        return match ($this) {
            self::Chie => 'explain',
            self::Hantei => 'verify',
            self::Oshie => 'teach',
        };
    }
}
