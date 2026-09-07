<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationContract\Stream\ModelStreamEvent;

/** Carries exactly one already-normalized event or final result. */
#[Api(ApiStatus::Experimental)]
final readonly class InvocationAdapterEmission
{
    private function __construct(
        private ?ModelStreamEvent $event,
        private ?ModelResult $result,
    ) {}

    public static function event(ModelStreamEvent $event): self
    {
        return new self($event, null);
    }

    public static function result(ModelResult $result): self
    {
        return new self(null, $result);
    }

    public function eventValue(): ?ModelStreamEvent
    {
        return $this->event;
    }

    public function resultValue(): ?ModelResult
    {
        return $this->result;
    }
}
