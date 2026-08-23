<?php

declare(strict_types=1);

namespace Kongono\Oshie\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Oshie\Diagnostic\OshieDiagnostic;
use Kongono\Oshie\Exception\InvalidOshieRequestException;
use Kongono\Oshie\Exception\InvalidOshieValueException;
use Kongono\Oshie\OshieResponse;

/** Encodes exact compact Oshie response and fixed safe error grammars. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    public const MAX_RESPONSE_BYTES = 262144;
    private const FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    public static function encodeResponse(OshieResponse $response): string
    {
        return self::encode($response->toArray());
    }

    public static function encodeError(OshieDiagnostic $diagnostic, string $uncertainty): string
    {
        if (!((OshieDiagnostic::InvalidRequest === $diagnostic && 'invalid-command-usage' === $uncertainty) || (OshieDiagnostic::UnexpectedCommandFailure === $diagnostic && 'unexpected-failure' === $uncertainty))) {
            throw InvalidOshieValueException::errorEnvelope();
        }
        return self::encode(['schema' => 'kongono.oshie-error', 'version' => 1, 'role' => 'oshie', 'status' => 'error', 'diagnostic_id' => $diagnostic->value, 'title' => $diagnostic->title(), 'uncertainty' => $uncertainty]);
    }

    /** @param array<string, mixed> $value */
    private static function encode(array $value): string
    {
        $bytes = json_encode($value, self::FLAGS, 32) . "\n";
        if (strlen($bytes) > self::MAX_RESPONSE_BYTES) {
            throw new InvalidOshieRequestException(OshieDiagnostic::ResponseLimitExceeded, 'response-limit');
        }
        return $bytes;
    }
}
