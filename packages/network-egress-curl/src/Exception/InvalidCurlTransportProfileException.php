<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressCurl\Diagnostic\NetworkEgressCurlDiagnostic;

/** Reports one closed profile or request invariant without retaining input. */
#[Api(ApiStatus::Experimental)]
final class InvalidCurlTransportProfileException extends NetworkEgressCurlException
{
    private const array VIOLATIONS = [
        'body_cursor', 'body_read', 'buffer_budget', 'ca_bundle_bounds', 'ca_bundle_shape',
        'capability_unavailable', 'content_length', 'header_budget', 'header_grammar', 'host_header',
        'method', 'operation_origin', 'plain_http_disabled', 'protocol', 'reentrancy', 'request_budget', 'request_target',
        'response_budget', 'response_header', 'response_shape', 'scheme', 'stream_state',
        'unsupported_violation',
    ];

    private string $violation;

    public static function invalid(string $violation, int $observation = 0, int $limit = 0): self
    {
        $safe = in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation';
        $context = ['stage' => 'profile', 'violation' => $safe];
        if ($observation > 0) {
            $context['observation'] = $observation;
        }
        if ($limit > 0) {
            $context['limit'] = $limit;
        }
        $exception = new self(NetworkEgressCurlDiagnostic::InvalidTransportProfile, $context);
        $exception->violation = $safe;

        return $exception;
    }

    public static function environment(): self
    {
        $exception = new self(NetworkEgressCurlDiagnostic::CurlCapabilityUnavailable, [
            'stage' => 'environment',
            'violation' => 'capability_unavailable',
        ]);
        $exception->violation = 'capability_unavailable';

        return $exception;
    }

    public static function request(string $violation, int $observation = 0, int $limit = 0): self
    {
        $safe = in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation';
        $context = ['stage' => 'request', 'violation' => $safe];
        if ($observation > 0) {
            $context['observation'] = $observation;
        }
        if ($limit > 0) {
            $context['limit'] = $limit;
        }
        $exception = new self(NetworkEgressCurlDiagnostic::RequestRejected, $context);
        $exception->violation = $safe;

        return $exception;
    }

    public static function lifecycle(string $violation): self
    {
        $safe = in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation';
        $exception = new self(NetworkEgressCurlDiagnostic::InvalidTransportProfile, [
            'stage' => 'lifecycle',
            'violation' => $safe,
        ]);
        $exception->violation = $safe;

        return $exception;
    }

    public function violation(): string
    {
        return $this->violation;
    }
}
