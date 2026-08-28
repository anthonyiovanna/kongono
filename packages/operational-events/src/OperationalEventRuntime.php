<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\ObservationRecorder;
use Kongono\OperationalEvents\Exception\OperationalEventRecordingException;
use Kongono\OperationalEvents\Internal\OperationalEventObservationFactory;
use Throwable;

/** Records independent admitted operational events through one explicit capability. */
#[Api(ApiStatus::Experimental)]
final readonly class OperationalEventRuntime
{
    public function __construct(private ObservationRecorder $recorder) {}

    public function record(OperationalEvent $event): void
    {
        $observation = OperationalEventObservationFactory::create($event);
        try {
            $this->recorder->record($observation);
        } catch (Throwable $failure) {
            throw OperationalEventRecordingException::failed($failure);
        }
    }
}
