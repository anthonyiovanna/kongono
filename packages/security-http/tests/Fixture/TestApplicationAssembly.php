<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp\Tests\Fixture;

use Closure;
use Kongono\Composition\Runtime\GeneratedOperationBoundary;
use Kongono\Composition\Runtime\GeneratedOperationInputApplicationBoundary;
use Kongono\Composition\Runtime\GeneratedOperationInputs;
use Kongono\IdentityAccess\SecurityContext;
use LogicException;
use Throwable;

final class TestApplicationAssembly implements GeneratedOperationInputApplicationBoundary
{
    public static int $beginCalls = 0;
    public static ?Closure $onBegin = null;
    private static int $active = 0;
    private bool $retired = false;
    private int $ordinal = 0;

    private function __construct() {}

    public static function start(): self
    {
        return new self();
    }

    /** @return list<class-string> */
    public static function expectedOperationInputContracts(): array
    {
        return [SecurityContext::class];
    }

    public function beginOperation(): GeneratedOperationBoundary
    {
        return $this->beginOperationWithInputs(GeneratedOperationInputs::none());
    }

    public function beginOperationWithInputs(GeneratedOperationInputs $inputs): GeneratedOperationBoundary
    {
        if ($this->retired) {
            throw new LogicException('retired application');
        }
        $inputs->validateCount(1);
        $context = $inputs->validatedAt(0, SecurityContext::class);
        ++self::$active;
        ++self::$beginCalls;
        $callback = self::$onBegin;
        if (null !== $callback) {
            $callback();
        }

        return new TestOperationAssembly(new TestSecurityRoot(++$this->ordinal, $context));
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
            throw new LogicException('active operation');
        }
        $this->retired = true;
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
        self::$beginCalls = 0;
        self::$onBegin = null;
        self::$active = 0;
        TestOperationAssembly::$closeCalls = 0;
        TestOperationAssembly::$cleanupFailure = null;
    }
}
