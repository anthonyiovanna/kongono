<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewHttp\Diagnostic\ViewHttpDiagnostic;
use Throwable;

/** Reports PSR-17 creation and PSR-7 assembly failures. */
#[Api(ApiStatus::Experimental)]
final class ViewHttpAdaptationException extends ViewHttpException
{
    public static function stream(string $violation, int $outputByteCount, ?Throwable $previous = null): self
    {
        return new self(ViewHttpDiagnostic::StreamCreationFailed, ['violation' => self::allow($violation, ['factory_throwable', 'not_readable', 'not_seekable', 'size_mismatch', 'content_mismatch', 'position_mismatch']), 'output_byte_count' => max(0, $outputByteCount)], $previous);
    }

    public static function response(string $violation, int $statusCode, ?Throwable $previous = null): self
    {
        return new self(ViewHttpDiagnostic::ResponseCreationFailed, ['violation' => self::allow($violation, ['factory_throwable', 'status_mismatch', 'reason_mismatch']), 'status_code' => max(0, $statusCode)], $previous);
    }

    public static function assembly(string $violation, int $statusCode, int $headerCount, ?Throwable $previous = null): self
    {
        return new self(ViewHttpDiagnostic::ResponseAssemblyFailed, ['violation' => self::allow($violation, ['mutation_throwable', 'status_mismatch', 'reason_mismatch', 'body_mismatch', 'header_mismatch', 'unexpected_header']), 'status_code' => max(0, $statusCode), 'header_count' => max(0, $headerCount)], $previous);
    }

    /** @param list<string> $allowed */
    private static function allow(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : 'unsupported_violation';
    }
}
