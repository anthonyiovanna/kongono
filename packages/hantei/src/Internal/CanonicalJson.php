<?php

declare(strict_types=1);

namespace Kongono\Hantei\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Hantei\Diagnostic\HanteiDiagnostic;
use Kongono\Hantei\Exception\InvalidHanteiValueException;
use Kongono\Hantei\HanteiReport;

/** Encodes the compact canonical Hantei report and safe error grammars. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    public const FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    public static function encodeReport(HanteiReport $report): string
    {
        return self::encode($report->toArray());
    }

    public static function encodeError(HanteiDiagnostic $diagnostic, string $uncertainty): string
    {
        if (!((HanteiDiagnostic::InvalidRequest === $diagnostic && 'invalid-command-usage' === $uncertainty)
            || (HanteiDiagnostic::UnexpectedCommandFailure === $diagnostic && 'unexpected-failure' === $uncertainty))) {
            throw InvalidHanteiValueException::errorEnvelope();
        }

        return self::encode([
            'schema' => 'kongono.hantei-error',
            'version' => 1,
            'role' => 'hantei',
            'status' => 'error',
            'diagnostic_id' => $diagnostic->value,
            'title' => $diagnostic->title(),
            'uncertainty' => $uncertainty,
        ]);
    }

    /** @param array<string, mixed> $value */
    private static function encode(array $value): string
    {
        return json_encode($value, self::FLAGS, 32) . "\n";
    }
}
