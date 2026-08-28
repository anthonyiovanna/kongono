<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\ObservationRecorder;
use Kongono\SecurityAudit\Exception\SecurityAuditRecordingException;
use Kongono\SecurityAudit\Internal\SecurityAuditObservationFactory;
use Throwable;

/** Records independent audit evidence through one explicit recorder capability. */
#[Api(ApiStatus::Experimental)]
final readonly class SecurityAuditRuntime
{
    public function __construct(private ObservationRecorder $recorder) {}

    public function record(SecurityAuditEvidence $evidence): void
    {
        $observation = SecurityAuditObservationFactory::create($evidence);
        try {
            $this->recorder->record($observation);
        } catch (Throwable $failure) {
            throw SecurityAuditRecordingException::failed($failure);
        }
    }
}
