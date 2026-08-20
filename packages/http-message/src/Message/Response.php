<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Message;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Internal\HttpMessageValidation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/** Provides the first-party PSR-7 server response value. */
#[Api(ApiStatus::Experimental)]
final class Response extends AbstractMessage implements ResponseInterface
{
    private int $statusCode;
    private string $reasonPhrase;

    /** @param array<array-key, mixed> $headers */
    public function __construct(int $statusCode = 200, array $headers = [], ?StreamInterface $body = null, string $protocolVersion = '1.1', string $reasonPhrase = '')
    {
        $this->setStatus($statusCode, $reasonPhrase);
        $this->initializeMessage($headers, $body, $protocolVersion);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $clone = clone $this;
        $clone->setStatus($code, $reasonPhrase);
        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    private function setStatus(int $code, string $reasonPhrase): void
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidStatusCode, ['status_code' => $code]);
        }
        $this->statusCode = $code;
        $this->reasonPhrase = HttpMessageValidation::safeReasonPhrase($reasonPhrase);
    }
}
