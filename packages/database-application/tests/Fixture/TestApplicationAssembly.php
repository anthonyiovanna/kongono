<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Tests\Fixture;

use Kongono\Composition\Runtime\GeneratedApplicationBoundary;
use Kongono\Composition\Runtime\GeneratedOperationBoundary;
use LogicException;
use Throwable;

final class TestApplicationAssembly implements GeneratedApplicationBoundary
{
    public static int $startCalls = 0;
    public static int $shutdownCalls = 0;
    private static int $active = 0;
    private bool $retired = false;
    private int $ordinal = 0;

    private function __construct() {}

    public static function start(TestInput $input): self
    {
        unset($input);
        ++self::$startCalls;

        return new self();
    }

    public function beginOperation(): GeneratedOperationBoundary
    {
        if ($this->retired) {
            throw new LogicException('retired');
        }
        ++self::$active;

        return new TestOperationAssembly(new TestRoot(++$this->ordinal));
    }

    public static function operationClosed(): void
    {
        --self::$active;
    }

    public function shutdown(?Throwable $primaryFailure = null): void
    {
        if ($this->retired) {
            return;
        }
        if (0 !== self::$active) {
            throw new LogicException('active_operation');
        }
        $this->retired = true;
        ++self::$shutdownCalls;
        if (null !== $primaryFailure) {
            throw $primaryFailure;
        }
    }

    public function isRetired(): bool
    {
        return $this->retired;
    }

    public static function reset(): void
    {
        self::$startCalls = 0;
        self::$shutdownCalls = 0;
        self::$active = 0;
        TestOperationAssembly::$closeCalls = 0;
    }
}
