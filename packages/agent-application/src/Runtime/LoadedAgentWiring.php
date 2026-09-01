<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Runtime;

use Kongono\AgentApplication\Exception\AgentWiringLoadException;
use Kongono\AgentApplication\Generated\GeneratedAgentApplicationBoundary;
use Kongono\AgentApplication\Profile\AgentApplicationProfile;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Owns one exact loaded generated boundary and permits exactly one start. */
#[Api(ApiStatus::Experimental)]
final class LoadedAgentWiring
{
    private bool $started = false;

    /** @internal Constructed only after loader shape validation. */
    public function __construct(private readonly GeneratedAgentApplicationBoundary $boundary, private readonly AgentApplicationProfile $profile) {}

    public function start(): AgentApplication
    {
        if ($this->started) {
            throw AgentWiringLoadException::failed('start');
        }
        $this->started = true;
        try {
            return $this->boundary->start($this->profile);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw AgentWiringLoadException::failed('start', $failure);
        }
    }

    public function started(): bool
    {
        return $this->started;
    }
}
