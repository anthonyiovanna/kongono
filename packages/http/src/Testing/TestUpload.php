<?php

declare(strict_types=1);

namespace Kongono\Http\Testing;

use Kongono\Http\UploadedFile;

/**
 * Deterministic in-memory PSR-7 upload factories for testing multipart routes and
 * magic-byte-verified `UploadedFileStore` endpoints without creating temporary files.
 */
final readonly class TestUpload
{
    /** Valid 1x1 transparent PNG binary (passes strict magic-byte image/png sniffing). */
    private const string PNG_1X1_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public static function fake(
        string $filename,
        string $contents,
        string $mediaType = 'application/octet-stream',
    ): UploadedFile {
        return UploadedFile::fromString($contents, $filename, $mediaType);
    }

    public static function fakeText(string $filename = 'notes.txt', string $contents = "Hello Kongono\n"): UploadedFile
    {
        return UploadedFile::fromString($contents, $filename, 'text/plain');
    }

    /** @param array<array-key, mixed> $data */
    public static function fakeJson(string $filename = 'data.json', array $data = ['ok' => true]): UploadedFile
    {
        $encoded = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return UploadedFile::fromString($encoded, $filename, 'application/json');
    }

    public static function fakePng(string $filename = 'avatar.png'): UploadedFile
    {
        return UploadedFile::fromString(base64_decode(self::PNG_1X1_BASE64, true), $filename, 'image/png');
    }

    public static function fakeJpeg(string $filename = 'photo.jpg'): UploadedFile
    {
        $bytes = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9";

        return UploadedFile::fromString($bytes, $filename, 'image/jpeg');
    }

    public static function fakePdf(string $filename = 'document.pdf'): UploadedFile
    {
        $bytes = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n";

        return UploadedFile::fromString($bytes, $filename, 'application/pdf');
    }
}
