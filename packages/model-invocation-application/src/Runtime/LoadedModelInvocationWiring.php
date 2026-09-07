<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Exception\ModelInvocationWiringLoadException;
use Kongono\ModelInvocationApplication\Generated\GeneratedModelInvocationApplicationBoundary;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapter;
use Throwable;

/** Owns one exact loaded generated boundary and permits exactly one start. */
#[Api(ApiStatus::Experimental)]
final class LoadedModelInvocationWiring
{
    private bool $started = false;

    /** @internal Constructed only after loader shape validation. */
    public function __construct(private readonly GeneratedModelInvocationApplicationBoundary $boundary, private readonly ModelInvocationApplicationProfile $profile) {}

    public function start(InvocationAdapter $adapter): ModelInvocationApplication
    {
        if ($this->started) {
            throw ModelInvocationWiringLoadException::failed('start');
        }
        $this->started = true;
        try {
            return $this->boundary->start($this->profile, $adapter);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ModelInvocationWiringLoadException::failed('start', $failure);
        }
    }

    public function started(): bool
    {
        return $this->started;
    }
}
