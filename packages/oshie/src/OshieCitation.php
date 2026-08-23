<?php

declare(strict_types=1);

namespace Kongono\Oshie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Knowledge\Source\KnowledgeSourceAuthority;
use Kongono\Oshie\Exception\InvalidOshieValueException;

/** Binds one teaching value to an exact accepted source section. */
#[Api(ApiStatus::Experimental)]
final readonly class OshieCitation
{
    public function __construct(
        private string $reference,
        private string $path,
        private KnowledgeSourceAuthority $authority,
        private string $sha256,
        private int $lineStart,
        private int $lineEnd,
    ) {
        if (1 !== preg_match('/\Akgn-source-v1:[a-f0-9]{64}:[0-9]{4}\z/D', $reference)
            || strlen($path) < 1 || strlen($path) > 240
            || 1 !== preg_match('/\A(?:[A-Za-z0-9._-]+)(?:\/[A-Za-z0-9._-]+)*\z/D', $path)
            || in_array('.', explode('/', $path), true) || in_array('..', explode('/', $path), true)
            || !in_array($authority, [KnowledgeSourceAuthority::GoverningAuthority, KnowledgeSourceAuthority::CurrentExplanatoryDocumentation], true)
            || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $sha256)
            || $lineStart < 1 || $lineEnd < $lineStart) {
            throw InvalidOshieValueException::citation();
        }
    }

    public function reference(): string
    {
        return $this->reference;
    }
    public function path(): string
    {
        return $this->path;
    }
    public function authority(): KnowledgeSourceAuthority
    {
        return $this->authority;
    }
    public function sha256(): string
    {
        return $this->sha256;
    }
    public function lineStart(): int
    {
        return $this->lineStart;
    }
    public function lineEnd(): int
    {
        return $this->lineEnd;
    }

    /** @return array{reference: string, path: string, authority: string, sha256: string, line_start: int, line_end: int} */
    public function toArray(): array
    {
        return ['reference' => $this->reference, 'path' => $this->path, 'authority' => $this->authority->value, 'sha256' => $this->sha256, 'line_start' => $this->lineStart, 'line_end' => $this->lineEnd];
    }
}
