<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressRuntime\Diagnostic\NetworkEgressRuntimeDiagnostic;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Throwable;

/** Preserves one exact unexpected collaborator failure behind bounded evidence. */
#[Api(ApiStatus::Experimental)]
final class EgressOperationException extends NetworkEgressRuntimeException
{
    /** @param array<string, string|int|bool> $context */
    public function __construct(
        private readonly EgressRuntimeResult $result,
        NetworkEgressRuntimeDiagnostic $diagnostic,
        array $context,
        Throwable $previous,
    ) {
        if (EgressOutcome::Succeeded === $result->operationResult()->receipt()->outcome()) {
            throw InvalidEgressRuntimeException::invalid('result_relation');
        }
        parent::__construct($diagnostic, $context, $previous);
    }

    public function result(): EgressRuntimeResult
    {
        return $this->result;
    }
}
