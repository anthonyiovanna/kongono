<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Role;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects an intended developer-intelligence purpose without executing a role. */
#[Api(ApiStatus::Experimental)]
enum DeveloperIntelligenceRole: string
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
