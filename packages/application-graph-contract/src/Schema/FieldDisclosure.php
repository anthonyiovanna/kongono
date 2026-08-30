<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Schema;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies presentation eligibility without granting access or authority. */
#[Api(ApiStatus::Experimental)]
enum FieldDisclosure: string
{
    case Safe = 'safe';
    case Protected = 'protected';
}
