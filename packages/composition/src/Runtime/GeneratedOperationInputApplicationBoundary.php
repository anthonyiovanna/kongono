<?php

declare(strict_types=1);

namespace Kongono\Composition\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Adds explicit ordered operation inputs without changing the legacy operation entry contract. */
#[Api(ApiStatus::Experimental)]
interface GeneratedOperationInputApplicationBoundary extends GeneratedApplicationBoundary
{
    /** @return list<class-string> Ordered metadata only; it performs no lookup or authorization. */
    public static function expectedOperationInputContracts(): array;

    public function beginOperationWithInputs(
        GeneratedOperationInputs $inputs,
    ): GeneratedOperationBoundary;
}
