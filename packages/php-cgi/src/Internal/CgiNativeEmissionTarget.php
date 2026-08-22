<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Internal;

use Closure;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use RuntimeException;

/** Performs only the selected PHP CGI status, field, and output calls. */
#[Api(ApiStatus::Internal)]
final class CgiNativeEmissionTarget implements CgiEmissionTarget
{
    public function headersSent(): bool
    {
        return self::nativeCall(static fn(): bool => headers_sent());
    }

    public function setStatus(int $status): void
    {
        if (false === self::nativeCall(static fn(): int|bool => http_response_code($status))) {
            throw new RuntimeException('Native CGI status emission failed.');
        }
    }

    public function emitHeader(string $name, string $value, bool $replace): void
    {
        self::nativeCall(static function () use ($name, $value, $replace): void {
            header($name . ': ' . $value, $replace);
        });
    }

    public function writeBody(string $bytes): int|false
    {
        return self::nativeCall(
            static fn(): int|false => file_put_contents('php://output', $bytes, FILE_APPEND),
        );
    }

    /**
     * @template T
     * @param Closure(): T $operation
     * @return T
     */
    private static function nativeCall(Closure $operation): mixed
    {
        $previousHandler = null;
        $previousHandler = set_error_handler(
            static function (int $severity, string $message, string $file, int $line) use (&$previousHandler): never {
                if (null !== $previousHandler) {
                    $previousHandler($severity, $message, $file, $line);
                }

                throw new RuntimeException('Native CGI emission call failed.');
            },
        );

        try {
            return $operation();
        } finally {
            restore_error_handler();
        }
    }
}
