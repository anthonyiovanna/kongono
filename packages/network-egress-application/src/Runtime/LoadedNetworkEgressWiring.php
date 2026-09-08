<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Runtime;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressWiringLoadException;
use Kongono\NetworkEgressApplication\Generated\GeneratedNetworkEgressApplicationBoundary;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;
use Throwable;

/** Owns one exact loaded generated boundary and permits exactly one start. */
#[Api(ApiStatus::Experimental)]
final class LoadedNetworkEgressWiring
{
    private bool $started = false;

    /** @internal Constructed only after loader shape validation. */
    public function __construct(private readonly GeneratedNetworkEgressApplicationBoundary $boundary, private readonly NetworkEgressApplicationProfile $profile) {}

    public function start(EgressResolver $resolver, EgressTransport $transport): NetworkEgressApplication
    {
        if ($this->started) {
            throw NetworkEgressWiringLoadException::failed('start');
        }
        $this->started = true;
        try {
            return $this->boundary->start($this->profile, $resolver, $transport);
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw NetworkEgressWiringLoadException::failed('start', $failure);
        }
    }

    public function started(): bool
    {
        return $this->started;
    }
}
