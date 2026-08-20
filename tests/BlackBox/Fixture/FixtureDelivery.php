<?php

declare(strict_types=1);

namespace Kongono\Tests\BlackBox\Fixture;

use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\Web\Runtime\Contract\WebDeliveryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/** Owns one fixture request, response transmission, and exact stream cleanup. */
final class FixtureDelivery implements WebDeliveryInterface
{
    private ?ServerRequestInterface $request;
    private ?StreamInterface $responseBody = null;
    private ?FixtureResponse $result = null;
    private int $permissionEvaluationCount = 0;
    private int $requestCount = 0;
    private int $sendCount = 0;
    private int $closeCount = 0;
    private bool $closed = false;

    public function __construct(
        ServerRequestInterface $request,
        private readonly AdapterControlledDeliveryPermission $permission,
    ) {
        $this->request = $request;
    }

    public function evaluateTrustedPresentationPermission(): AdapterControlledDeliveryPermission
    {
        $this->requireOpen();
        if (0 !== $this->permissionEvaluationCount) {
            throw new RuntimeException('Fixture permission was evaluated more than once.');
        }
        ++$this->permissionEvaluationCount;

        return $this->permission;
    }

    public function request(): ServerRequestInterface
    {
        $this->requireOpen();
        if (0 !== $this->requestCount || null === $this->request) {
            throw new RuntimeException('Fixture request was accessed more than once.');
        }
        ++$this->requestCount;

        return $this->request;
    }

    public function send(ResponseInterface $response): void
    {
        $this->requireOpen();
        if (0 !== $this->sendCount || null !== $this->result || null !== $this->responseBody) {
            throw new RuntimeException('Fixture response was sent more than once.');
        }
        ++$this->sendCount;

        $body = $response->getBody();
        $this->responseBody = $body;
        if (!$body->isReadable() || !$body->isSeekable()) {
            throw new RuntimeException('Fixture response body cannot be read completely.');
        }

        $size = $body->getSize();
        if (null !== $size && $size > FixtureResponse::MAX_BODY_BYTES) {
            throw new RuntimeException('Fixture response body is too large.');
        }

        $body->rewind();
        $contents = '';
        while (!$body->eof()) {
            $remaining = FixtureResponse::MAX_BODY_BYTES + 1 - strlen($contents);
            if ($remaining <= 0) {
                throw new RuntimeException('Fixture response body is too large.');
            }

            $chunk = $body->read(min(8192, $remaining));
            if ('' === $chunk) {
                throw new RuntimeException('Fixture response body read was partial.');
            }
            $contents .= $chunk;
        }

        if (strlen($contents) > FixtureResponse::MAX_BODY_BYTES) {
            throw new RuntimeException('Fixture response body is too large.');
        }

        /** @var array<string, list<string>> $headers */
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            if (!is_string($name)) {
                throw new RuntimeException('Fixture response header name must be a string.');
            }

            $headerValues = [];

            foreach ($values as $value) {
                $headerValues[] = $value;
            }

            $headers[$name] = $headerValues;
        }

        $this->result = new FixtureResponse($response->getStatusCode(), $headers, $contents);
    }

    public function close(): void
    {
        if ($this->closed || 0 !== $this->closeCount) {
            throw new RuntimeException('Fixture delivery was closed more than once.');
        }
        ++$this->closeCount;

        $request = $this->request;
        $responseBody = $this->responseBody;
        $this->request = null;
        $this->responseBody = null;
        $this->closed = true;

        try {
            if (null !== $request) {
                $request->getBody()->close();
            }
        } finally {
            if (null !== $responseBody) {
                $responseBody->close();
            }
        }
    }

    public function result(): FixtureResponse
    {
        if (!$this->closed || 1 !== $this->requestCount || 1 !== $this->sendCount || 1 !== $this->closeCount || null === $this->result) {
            throw new RuntimeException('Fixture result is not complete.');
        }

        return $this->result;
    }

    public function permissionEvaluationCount(): int
    {
        return $this->permissionEvaluationCount;
    }

    public function requestCount(): int
    {
        return $this->requestCount;
    }

    public function sendCount(): int
    {
        return $this->sendCount;
    }

    public function closeCount(): int
    {
        return $this->closeCount;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    private function requireOpen(): void
    {
        if ($this->closed) {
            throw new RuntimeException('Fixture delivery is already closed.');
        }
    }
}
