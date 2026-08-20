<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Message;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Internal\HttpMessageValidation;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/** Provides the explicit, transport-neutral PSR-7 server-request value. */
#[Api(ApiStatus::Experimental)]
final class ServerRequest extends AbstractMessage implements ServerRequestInterface
{
    private string $method;
    private UriInterface $uri;
    private ?string $requestTarget = null;
    /** @var array<array-key, mixed> */
    private array $serverParams;
    /** @var array<array-key, mixed> */
    private array $cookieParams = [];
    /** @var array<array-key, mixed> */
    private array $queryParams = [];
    /** @var array<array-key, mixed> */
    private array $uploadedFiles = [];
    /** @var array<array-key, mixed>|object|null */
    private array|object|null $parsedBody = null;
    /** @var array<string, mixed> */
    private array $attributes = [];

    /**
     * @param array<array-key, mixed> $serverParams
     * @param array<array-key, mixed> $headers
     */
    public function __construct(string $method, UriInterface|string $uri, array $serverParams = [], array $headers = [], ?StreamInterface $body = null, string $protocolVersion = '1.1')
    {
        $this->method = HttpMessageValidation::token($method, HttpMessageDiagnostic::InvalidMethod);
        $this->uri = is_string($uri) ? new Uri($uri) : $uri;
        $this->serverParams = self::detachArray($serverParams);
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
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidRequestTarget, ['actual_length' => strlen($requestTarget), 'violation' => $violation]);
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

    /** @return array<array-key, mixed> */
    public function getServerParams(): array
    {
        return self::detachArray($this->serverParams);
    }
    /** @return array<array-key, mixed> */
    public function getCookieParams(): array
    {
        return self::detachArray($this->cookieParams);
    }

    /** @param array<array-key, mixed> $cookies */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->cookieParams = self::detachArray($cookies);
        return $clone;
    }

    /** @return array<array-key, mixed> */
    public function getQueryParams(): array
    {
        return self::detachArray($this->queryParams);
    }

    /** @param array<array-key, mixed> $query */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->queryParams = self::detachArray($query);
        return $clone;
    }

    /** @return array<array-key, mixed> */
    public function getUploadedFiles(): array
    {
        return self::detachArray($this->uploadedFiles);
    }

    /** @param array<array-key, mixed> $uploadedFiles */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        self::validateUploadedTree($uploadedFiles, 0);
        $clone = clone $this;
        $clone->uploadedFiles = self::detachArray($uploadedFiles);
        return $clone;
    }

    /** @return array<array-key, mixed>|object|null */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /** @param mixed $data */
    public function withParsedBody($data): ServerRequestInterface
    {
        if (null !== $data && !is_array($data) && !is_object($data)) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidParsedBody, ['actual_value_kind' => HttpMessageValidation::valueKind($data)]);
        }
        $clone = clone $this;
        $clone->parsedBody = is_array($data) ? self::detachArray($data) : $data;
        return $clone;
    }

    /** @return array<string, mixed> */
    public function getAttributes(): array
    {
        return self::detachArray($this->attributes);
    }

    public function getAttribute(string $name, $default = null)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->attributes = self::detachArray($this->attributes);
        $clone->attributes[$name] = $value;
        return $clone;
    }

    public function withoutAttribute(string $name): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->attributes = self::detachArray($this->attributes);
        unset($clone->attributes[$name]);
        return $clone;
    }

    /**
     * @template TKey of array-key
     * @param array<TKey, mixed> $values
     * @return array<TKey, mixed>
     */
    private static function detachArray(array $values): array
    {
        $detached = [];
        foreach ($values as $key => $value) {
            $detached[$key] = $value;
        }
        return $detached;
    }

    /** @param array<array-key, mixed> $tree */
    private static function validateUploadedTree(array $tree, int $depth): void
    {
        foreach ($tree as $value) {
            if (is_array($value)) {
                self::validateUploadedTree($value, $depth + 1);
            } elseif (!$value instanceof UploadedFileInterface) {
                throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidUploadedFilesTree, ['tree_depth' => $depth, 'actual_value_kind' => HttpMessageValidation::valueKind($value)]);
            }
        }
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
