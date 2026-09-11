<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Message;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Internal\HttpMessageValidation;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/** Provides the explicit, transport-neutral PSR-7 outbound request value. */
#[Api(ApiStatus::Experimental)]
final class Request extends AbstractMessage implements RequestInterface
{
    private string $method;
    private UriInterface $uri;
    private ?string $requestTarget = null;

    /** @param array<array-key, mixed> $headers */
    public function __construct(
        string $method,
        UriInterface|string $uri,
        array $headers = [],
        ?StreamInterface $body = null,
        string $protocolVersion = '1.1',
    ) {
        $this->method = HttpMessageValidation::token($method, HttpMessageDiagnostic::InvalidMethod);
        $this->uri = is_string($uri) ? new Uri($uri) : $uri;
        if (!self::headersContain($headers, 'Host') && '' !== $this->uri->getHost()) {
            $headers['Host'] = self::hostFromUri($this->uri);
        }
        $this->initializeMessage($headers, $body, $protocolVersion);
    }

    public function getRequestTarget(): string
    {
        if (null !== $this->requestTarget) {
            return $this->requestTarget;
        }
        $target = '' === $this->uri->getPath() ? '/' : $this->uri->getPath();

        return '' === $this->uri->getQuery() ? $target : $target . '?' . $this->uri->getQuery();
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        $violation = '' === $requestTarget ? 'empty' : (1 === preg_match('/[\x00-\x20\x7F]/', $requestTarget) ? 'whitespace_or_control' : null);
        if (null !== $violation) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidRequestTarget, [
                'actual_length' => strlen($requestTarget),
                'violation' => $violation,
            ]);
        }
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): RequestInterface
    {
        $clone = clone $this;
        $clone->method = HttpMessageValidation::token($method, HttpMessageDiagnostic::InvalidMethod);

        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $clone = clone $this;
        $clone->uri = $uri;
        $hostValues = $this->getHeader('Host');
        if ($preserveHost) {
            if ('' !== ($hostValues[0] ?? '') || '' === $uri->getHost()) {
                return $clone;
            }
        } elseif ('' === $uri->getHost()) {
            return $clone;
        }

        return $clone->withHeader('Host', self::hostFromUri($uri));
    }

    /** @param array<array-key, mixed> $headers */
    private static function headersContain(array $headers, string $name): bool
    {
        foreach (array_keys($headers) as $header) {
            if (is_string($header) && 0 === strcasecmp($header, $name)) {
                return true;
            }
        }

        return false;
    }

    private static function hostFromUri(UriInterface $uri): string
    {
        return $uri->getHost() . (null === $uri->getPort() ? '' : ':' . $uri->getPort());
    }
}
