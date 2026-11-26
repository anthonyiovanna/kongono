<?php

declare(strict_types=1);

namespace Kongono\Ai;

/**
 * Magic-byte-verified multimodal image or PDF attachment for {@see Message} and {@see AiClient}.
 *
 * Supported across OpenAI, Anthropic Claude, Google Gemini, Groq, and local Ollama (images),
 * enabling native Receipt OCR, damage/inspection photo triage, and PDF extraction with zero extra SDKs.
 */
final readonly class Attachment
{
    public const int DEFAULT_MAX_BYTES = 20_971_520; // 20 MiB

    /** @var list<string> */
    private const array IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /** @var list<string> */
    private const array ALLOWED_DETAILS = ['auto', 'low', 'high'];

    public readonly string $mimeType;
    public readonly string $base64Data;
    public readonly int $byteSize;
    public readonly string $detail;
    public readonly ?string $filename;

    private function __construct(
        string $mimeType,
        string $base64Data,
        int $byteSize,
        string $detail,
        ?string $filename,
    ) {
        $this->mimeType = $mimeType;
        $this->base64Data = $base64Data;
        $this->byteSize = $byteSize;
        $this->detail = $detail;
        $this->filename = $filename;
    }

    /** Create a verified image attachment (JPEG, PNG, WebP, or GIF) from raw binary bytes. */
    public static function image(
        string $binaryBytes,
        string $mimeType,
        string $detail = 'auto',
        int $maxBytes = self::DEFAULT_MAX_BYTES,
    ): self {
        $normalizedMime = self::normalizeMimeType($mimeType);
        if (!in_array($normalizedMime, self::IMAGE_MIME_TYPES, true)) {
            throw new AiException(sprintf(
                'Unsupported AI image MIME type "%s". Supported: %s.',
                $mimeType,
                implode(', ', self::IMAGE_MIME_TYPES),
            ));
        }

        return self::fromBinary($binaryBytes, $normalizedMime, $detail, null, $maxBytes);
    }

    /** Create a verified PDF document attachment (`application/pdf`) from raw binary bytes. */
    public static function pdf(
        string $binaryBytes,
        string $filename = 'document.pdf',
        int $maxBytes = self::DEFAULT_MAX_BYTES,
    ): self {
        $cleanName = self::sanitizeFilename($filename);

        return self::fromBinary($binaryBytes, 'application/pdf', 'auto', $cleanName, $maxBytes);
    }

    /** Create a verified image or PDF attachment from a base64-encoded string. */
    public static function fromBase64(
        string $base64,
        string $mimeType,
        string $detail = 'auto',
        ?string $filename = null,
        int $maxBytes = self::DEFAULT_MAX_BYTES,
    ): self {
        $decoded = base64_decode(trim($base64), true);
        if (false === $decoded) {
            throw new AiException('Attachment payload is not valid base64.');
        }

        $normalizedMime = self::normalizeMimeType($mimeType);
        if ('application/pdf' === $normalizedMime) {
            return self::pdf($decoded, $filename ?? 'document.pdf', $maxBytes);
        }

        return self::image($decoded, $normalizedMime, $detail, $maxBytes);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }

    public function isPdf(): bool
    {
        return 'application/pdf' === $this->mimeType;
    }

    public function dataUri(): string
    {
        return sprintf('data:%s;base64,%s', $this->mimeType, $this->base64Data);
    }

    private static function fromBinary(
        string $binaryBytes,
        string $normalizedMime,
        string $detail,
        ?string $filename,
        int $maxBytes,
    ): self {
        $size = strlen($binaryBytes);
        $limit = max(1, min(self::DEFAULT_MAX_BYTES, $maxBytes));
        if (0 === $size || $size > $limit) {
            throw new AiException(sprintf('Attachment size (%d bytes) must be between 1 and %d bytes.', $size, $limit));
        }

        $cleanDetail = strtolower(trim($detail));
        if (!in_array($cleanDetail, self::ALLOWED_DETAILS, true)) {
            throw new AiException(sprintf('Invalid attachment detail "%s". Allowed: auto, low, high.', $detail));
        }

        self::assertMagicBytes($binaryBytes, $normalizedMime);

        return new self(
            mimeType: $normalizedMime,
            base64Data: base64_encode($binaryBytes),
            byteSize: $size,
            detail: $cleanDetail,
            filename: $filename,
        );
    }

    private static function assertMagicBytes(string $bytes, string $mime): void
    {
        $valid = match ($mime) {
            'image/jpeg' => str_starts_with($bytes, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($bytes, "\x89PNG\r\n\x1A\n"),
            'image/gif' => str_starts_with($bytes, 'GIF87a') || str_starts_with($bytes, 'GIF89a'),
            'image/webp' => strlen($bytes) >= 12 && 'RIFF' === substr($bytes, 0, 4) && 'WEBP' === substr($bytes, 8, 4),
            'application/pdf' => str_starts_with($bytes, '%PDF-'),
            default => false,
        };

        if (!$valid) {
            throw new AiException(sprintf('Attachment binary signature does not match declared MIME type "%s".', $mime));
        }
    }

    private static function normalizeMimeType(string $mimeType): string
    {
        $clean = strtolower(trim(explode(';', $mimeType, 2)[0]));

        return 'image/jpg' === $clean ? 'image/jpeg' : $clean;
    }

    private static function sanitizeFilename(string $filename): string
    {
        $base = basename(str_replace('\\', '/', trim($filename)));
        $clean = preg_replace('/[^A-Za-z0-9._-]+/', '_', $base);
        if (!is_string($clean) || '' === $clean || '.' === $clean || '..' === $clean) {
            return 'document.pdf';
        }

        return substr($clean, 0, 120);
    }
}
