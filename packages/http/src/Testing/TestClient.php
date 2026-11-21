<?php

declare(strict_types=1);

namespace Kongono\Http\Testing;

use Kongono\Http\App;
use Kongono\Http\Request;

/** Zero-dependency fluent HTTP test client for Kongono applications. */
final class TestClient
{
    /** @var array<string, string> */
    private array $defaultHeaders = [];

    /** @var array<string, string> */
    private array $cookies = [];

    public function __construct(
        private readonly App $app,
    ) {}

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->defaultHeaders[$name] = $value;

        return $clone;
    }

    public function withBearerToken(string $token): self
    {
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function withCookie(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->cookies[$name] = $value;

        return $clone;
    }

    /** @param array<string, string> $headers */
    public function get(string $uri, array $headers = []): TestResponse
    {
        return $this->send(Request::create('GET', $uri, [...$this->defaultHeaders, ...$headers]));
    }

    /**
     * @param array<string, mixed>  $fields
     * @param array<string, string> $headers
     */
    public function postForm(string $uri, array $fields, array $headers = []): TestResponse
    {
        return $this->send(Request::create('POST', $uri, [...$this->defaultHeaders, ...$headers], parsedBody: $fields));
    }

    /** @param array<string, string> $headers */
    public function postJson(string $uri, mixed $payload, array $headers = []): TestResponse
    {
        return $this->send(Request::json('POST', $uri, $payload, [...$this->defaultHeaders, ...$headers]));
    }

    /** @param array<string, string> $headers */
    public function putJson(string $uri, mixed $payload, array $headers = []): TestResponse
    {
        return $this->send(Request::json('PUT', $uri, $payload, [...$this->defaultHeaders, ...$headers]));
    }

    /** @param array<string, string> $headers */
    public function patchJson(string $uri, mixed $payload, array $headers = []): TestResponse
    {
        return $this->send(Request::json('PATCH', $uri, $payload, [...$this->defaultHeaders, ...$headers]));
    }

    /** @param array<string, string> $headers */
    public function delete(string $uri, array $headers = []): TestResponse
    {
        return $this->send(Request::create('DELETE', $uri, [...$this->defaultHeaders, ...$headers]));
    }

    public function send(Request $request): TestResponse
    {
        if ([] !== $this->cookies) {
            /** @var Request $request */
            $request = $request->withCookieParams([...$this->cookies, ...$request->getCookieParams()]);
        }

        return new TestResponse($this->app->handle($request));
    }
}
