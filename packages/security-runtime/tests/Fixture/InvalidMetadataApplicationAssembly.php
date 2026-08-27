<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime\Tests\Fixture;

use Kongono\Composition\Runtime\GeneratedOperationBoundary;
use Kongono\Composition\Runtime\GeneratedOperationInputApplicationBoundary;
use Kongono\Composition\Runtime\GeneratedOperationInputs;
use Throwable;

final class InvalidMetadataApplicationAssembly implements GeneratedOperationInputApplicationBoundary
{
    public static int $startCalls = 0;

    public static function start(): self
    {
        ++self::$startCalls;

        return new self();
    }

    /** @return list<class-string> */
    public static function expectedOperationInputContracts(): array
    {
        return [];
    }

    public function beginOperation(): GeneratedOperationBoundary
    {
        throw new \LogicException('unused');
    }

    public function beginOperationWithInputs(GeneratedOperationInputs $inputs): GeneratedOperationBoundary
    {
        unset($inputs);
        throw new \LogicException('unused');
    }

    public function shutdown(?Throwable $primaryFailure = null): void
    {
        unset($primaryFailure);
    }

    public function isRetired(): bool
    {
        return false;
    }
}
