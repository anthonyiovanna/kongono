<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Message;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\HttpMessageRuntimeException;
use Kongono\Http\Message\Internal\HeaderCollection;
use Kongono\Http\Message\Internal\HttpMessageValidation;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/** Implements common shallow copy-on-change message state. */
#[Api(ApiStatus::Internal)]
abstract class AbstractMessage implements MessageInterface
{
    private string $protocolVersion;
    private HeaderCollection $headers;
    private StreamInterface $body;

    /** @param array<array-key, mixed> $headers */
    protected function initializeMessage(array $headers, ?StreamInterface $body, string $protocolVersion): void
    {
        $this->protocolVersion = HttpMessageValidation::protocolVersion($protocolVersion);
        $this->headers = new HeaderCollection($headers);
        if (null === $body) {
            $resource = @fopen('php://temp', 'w+b');
            if (false === $resource) {
                throw new HttpMessageRuntimeException(HttpMessageDiagnostic::StreamOperationFailed, ['operation' => 'open_temp']);
            }
            $body = new Stream($resource);
        }
        $this->body = $body;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        $clone = clone $this;
        $clone->protocolVersion = HttpMessageValidation::protocolVersion($version);
        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->headers->all();
    }
    public function hasHeader(string $name): bool
    {
        return $this->headers->has($name);
    }
    public function getHeader(string $name): array
    {
        return $this->headers->get($name);
    }
    public function getHeaderLine(string $name): string
    {
        return $this->headers->line($name);
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $clone = clone $this;
        $clone->headers = $this->headers->with($name, $value);
        return $clone;
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $clone = clone $this;
        $clone->headers = $this->headers->withAdded($name, $value);
        return $clone;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $clone = clone $this;
        $clone->headers = $this->headers->without($name);
        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }
}
