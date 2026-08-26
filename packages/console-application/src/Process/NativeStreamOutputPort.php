<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Process;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleApplication\Exception\InvalidConsoleApplicationValueException;
use Kongono\ConsolePresentation\CommandOutputPort;

/** Writes one completed document to one caller-owned native stream. */
#[Api(ApiStatus::Experimental)]
final readonly class NativeStreamOutputPort implements CommandOutputPort
{
    /** @param resource $stream */
    public function __construct(private mixed $stream)
    {
        if (!is_resource($stream) || 'stream' !== get_resource_type($stream)) {
            throw InvalidConsoleApplicationValueException::applicationFailure();
        }
    }

    public function write(string $bytes): void
    {
        $written = @fwrite($this->stream, $bytes);
        if (false === $written || strlen($bytes) !== $written) {
            throw InvalidConsoleApplicationValueException::applicationFailure();
        }
    }
}
