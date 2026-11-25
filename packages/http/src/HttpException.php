<?php

declare(strict_types=1);

namespace Kongono\Http;

use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\Diagnostic;
use Throwable;

final class HttpException extends CoreException
{
    /** @param array<string, string|list<string>> $headers */
    public function __construct(
        public readonly int $statusCode,
        string $message = '',
        public readonly array $headers = [],
        ?Diagnostic $diagnostic = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $diagnostic, $previous);
    }

    public function headerLine(string $name): string
    {
        $needle = strtolower($name);
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === $needle) {
                return is_array($v) ? implode(', ', $v) : $v;
            }
        }

        return '';
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $needle = strtolower($name);
        foreach (array_keys($headers) as $k) {
            if (strtolower($k) === $needle) {
                unset($headers[$k]);
            }
        }
        $headers[$name] = $value;

        return new self($this->statusCode, $this->getMessage(), $headers, $this->diagnostic, $this);
    }

    public function withAddedHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $needle = strtolower($name);
        $existingKey = null;
        foreach (array_keys($headers) as $k) {
            if (strtolower($k) === $needle) {
                $existingKey = $k;
                break;
            }
        }

        if (null === $existingKey) {
            $headers[$name] = $value;
        } else {
            $existing = $headers[$existingKey];
            $headers[$existingKey] = is_array($existing) ? [...$existing, $value] : [$existing, $value];
        }

        return new self($this->statusCode, $this->getMessage(), $headers, $this->diagnostic, $this);
    }
}
