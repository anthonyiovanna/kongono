<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Diagnostic\ExternalServiceApplicationDiagnostic;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Throwable;

/** Preserves exact lower-layer identity behind a closed nondisclosing stage. */
#[Api(ApiStatus::Experimental)]
final class ExternalServiceApplicationOperationException extends ExternalServiceApplicationException
{
    private function __construct(
        string $stage,
        ?Throwable $previous = null,
        private readonly ?EgressRuntimeResult $egressResult = null,
        bool $invariant = false,
    ) {
        parent::__construct(
            $invariant ? ExternalServiceApplicationDiagnostic::InternalInvariantFailed : ExternalServiceApplicationDiagnostic::OperationFailed,
            ['stage' => self::stage($stage)],
            $previous,
        );
    }

    public static function failed(string $stage, Throwable $previous, ?EgressRuntimeResult $egressResult = null): self
    {
        return new self($stage, $previous, $egressResult);
    }

    public static function invariant(string $stage): self
    {
        return new self($stage, invariant: true);
    }

    public function egressResult(): ?EgressRuntimeResult
    {
        return $this->egressResult;
    }

    private static function stage(string $stage): string
    {
        $allowed = [
            'run_input',
            'request_factory',
            'request_validation',
            'network_application',
            'credential_runtime',
            'result_mapping',
            'collaborator_state',
            'consumer_state',
        ];

        return in_array($stage, $allowed, true) ? $stage : 'unsupported_stage';
    }
}
