<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Factory;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Message\UploadedFile;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

/** Creates uploaded-file values only from explicit readable streams. */
#[Api(ApiStatus::Experimental)]
final class UploadedFileFactory implements UploadedFileFactoryInterface
{
    public function createUploadedFile(StreamInterface $stream, ?int $size = null, int $error = UPLOAD_ERR_OK, ?string $clientFilename = null, ?string $clientMediaType = null): UploadedFileInterface
    {
        if (!$stream->isReadable()) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidStreamResource, ['actual_value_kind' => 'unreadable_stream']);
        }
        return new UploadedFile($stream, $size ?? $stream->getSize(), $error, $clientFilename, $clientMediaType);
    }
}
