<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Tests\Fixture;

use Kongono\Composition\Runtime\GeneratedApplicationBoundary;
use Kongono\Composition\Runtime\GeneratedOperationBoundary;
use Kongono\Web\Application\WebApplication;
use Kongono\Web\Runtime\GeneratedWebRuntimeBootstrap;
use Kongono\Web\Runtime\GeneratedWebRuntimeRoot;
use LogicException;
use Throwable;

final class Phase5FOperationAssembly implements GeneratedOperationBoundary
{
    public function close(?Throwable $primaryFailure = null): void {}

    public function isClosed(): bool
    {
        return true;
    }
}

final class Phase5FApplicationAssembly implements GeneratedApplicationBoundary
{
    public static int $beginOperationCalls = 0;
    public static int $shutdownCalls = 0;

    private bool $retired = false;
    private readonly GeneratedWebRuntimeRoot $root;

    private function __construct(private readonly WebApplication $application, GeneratedWebRuntimeBootstrap $bootstrap)
    {
        $this->root = new GeneratedWebRuntimeRoot($application, $bootstrap);
    }

    public static function start(WebApplication $application, GeneratedWebRuntimeBootstrap $bootstrap): self
    {
        return new self($application, $bootstrap);
    }

    public function applicationRoot1(): GeneratedWebRuntimeRoot
    {
        return $this->root;
    }

    public function beginOperation(): GeneratedOperationBoundary
    {
        ++self::$beginOperationCalls;
        throw new LogicException('The web profile has no generated operation root.');
    }

    public function shutdown(?Throwable $primaryFailure = null): void
    {
        if ($this->retired) {
            return;
        }
        $this->retired = true;
        ++self::$shutdownCalls;
        $this->application->shutdown();
        if (null !== $primaryFailure) {
            throw $primaryFailure;
        }
    }

    public function isRetired(): bool
    {
        return $this->retired;
    }
}
