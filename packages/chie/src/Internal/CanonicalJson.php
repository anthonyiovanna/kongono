<?php

declare(strict_types=1);

namespace Kongono\Chie\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\ChieResponse;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\InvalidChieRequestException;
use Kongono\Chie\Exception\InvalidChieValueException;
use Kongono\Chie\Proposal\ChiePatchProposal;

/** Encodes the exact compact Chie response and fixed safe error grammars. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    public const MAX_RESPONSE_BYTES = 131072;
    public const FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    public static function encodeResponse(ChieResponse $response): string
    {
        return self::encode($response->toArray());
    }

    public static function encodeProposal(ChiePatchProposal $proposal): string
    {
        return self::encode($proposal->toArray());
    }

    public static function encodeProposalFailure(string $status, string $uncertainty): string
    {
        if (!in_array($status, ['blocked', 'stale', 'unavailable'], true) || !in_array($uncertainty, ['proposal-blocked', 'proposal-stale', 'proposal-unavailable'], true)) {
            throw InvalidChieValueException::errorEnvelope();
        }

        return self::encode([
            'schema' => 'kongono.chie-proposal-response',
            'version' => 1,
            'role' => 'chie',
            'status' => $status,
            'operation' => 'propose',
            'uncertainty' => $uncertainty,
        ]);
    }

    public static function encodeError(ChieDiagnostic $diagnostic, string $uncertainty): string
    {
        if (!(
            (ChieDiagnostic::InvalidRequest === $diagnostic && 'invalid-command-usage' === $uncertainty)
            || (ChieDiagnostic::ProposalSpecInvalid === $diagnostic && 'spec-shape' === $uncertainty)
            || (ChieDiagnostic::UnexpectedCommandFailure === $diagnostic && 'unexpected-failure' === $uncertainty)
        )) {
            throw InvalidChieValueException::errorEnvelope();
        }

        return self::encode([
            'schema' => 'kongono.chie-error',
            'version' => 1,
            'role' => 'chie',
            'status' => 'error',
            'diagnostic_id' => $diagnostic->value,
            'title' => $diagnostic->title(),
            'uncertainty' => $uncertainty,
        ]);
    }

    /** @param array<string, mixed> $value */
    private static function encode(array $value): string
    {
        $bytes = json_encode($value, self::FLAGS, 32) . "\n";
        if (strlen($bytes) > self::MAX_RESPONSE_BYTES) {
            throw new InvalidChieRequestException(ChieDiagnostic::ResponseLimitExceeded, 'response-limit');
        }

        return $bytes;
    }
}
