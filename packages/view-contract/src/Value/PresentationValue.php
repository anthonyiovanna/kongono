<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Value;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Schema\PresentationValueKind;

/** Defines the sealed-by-convention presentation value behavior. */
#[Api(ApiStatus::Experimental)]
interface PresentationValue
{
    public function kind(): PresentationValueKind;

    public function equals(self $other): bool;

    public function depth(): int;

    public function nodeCount(): int;
}
