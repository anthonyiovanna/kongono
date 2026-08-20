<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Factory;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\HttpMessageRuntimeException;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Message\Stream;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/** Creates explicit resource-backed streams without a factory registry. */
#[Api(ApiStatus::Experimental)]
final class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        $resource = @fopen('php://temp', 'w+b');
        if (false === $resource) {
            throw new HttpMessageRuntimeException(HttpMessageDiagnostic::StreamOperationFailed, ['operation' => 'open_temp']);
        }
        $stream = new Stream($resource);
        try {
            $offset = 0;
            while ($offset < strlen($content)) {
                $written = $stream->write(substr($content, $offset));
                if (0 === $written) {
                    throw new HttpMessageRuntimeException(HttpMessageDiagnostic::StreamOperationFailed, ['operation' => 'write_temp']);
                }
                $offset += $written;
            }
            $stream->rewind();
        } catch (\Throwable $exception) {
            $stream->close();
            throw $exception;
        }
        return $stream;
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (!self::validMode($mode)) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidStreamMode, ['mode_length' => strlen($mode), 'violation' => str_contains($mode, "\0") ? 'nul' : 'grammar']);
        }
        $resource = @fopen($filename, $mode);
        if (false === $resource) {
            throw new HttpMessageRuntimeException(HttpMessageDiagnostic::StreamOperationFailed, ['operation' => 'open_file']);
        }
        return new Stream($resource);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        $stream = new Stream($resource);
        if (!$stream->isReadable()) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidStreamResource, ['actual_value_kind' => 'unreadable_stream']);
        }
        return $stream;
    }

    private static function validMode(string $mode): bool
    {
        if ('' === $mode || !str_contains('rwaxc', $mode[0])) {
            return false;
        }

        $modifiers = [];
        for ($position = 1, $length = strlen($mode); $position < $length; ++$position) {
            $modifier = $mode[$position];
            if (!str_contains('+bten', $modifier) || isset($modifiers[$modifier])) {
                return false;
            }
            $modifiers[$modifier] = true;
        }

        return !(isset($modifiers['b']) && isset($modifiers['t']));
    }
}
