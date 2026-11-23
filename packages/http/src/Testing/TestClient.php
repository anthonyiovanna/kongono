<?php

declare(strict_types=1);

namespace Kongono\Http\Testing;

use Kongono\Core\CoreException;
use Kongono\Http\App;
use Kongono\Http\Request;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Zero-dependency fluent HTTP test client for Kongono applications.
 *
 * Supports immutable request builders, `actingAs(Principal)` scoped auth attributes,
 * Live 2.5 / Bridge helpers, multipart uploads (`postMultipart`), optional per-client
 * stateful cookie + CSRF token tracking (`withStatefulCookies`), and `followRedirects()`.
 */
final class TestClient
{
    /** @var array<string, string> */
    private array $defaultHeaders = [];

    /** @var array<string, string> */
    private array $cookies = [];

    /** @var array<string, mixed> */
    private array $attributes = [];

    private bool $statefulCookies = false;

    private ?string $capturedCsrfToken = null;

    public function __construct(
        private readonly App $app,
    ) {}

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->defaultHeaders[$name] = $value;

        return $clone;
    }

    /** @param array<string, string> $headers */
    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        foreach ($headers as $name => $value) {
            $clone->defaultHeaders[$name] = $value;
        }

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

    public function withAttribute(string $name, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    /**
     * Attach an authenticated principal (e.g. `Kongono\Security\Principal`) as a scoped
     * PSR-7 request attribute so `AuthorizeMiddleware`, `#[Can]`, and typed `Principal`
     * controller parameters resolve cleanly without mutating global state.
     */
    public function actingAs(object $principal): self
    {
        return $this->withAttribute($principal::class, $principal);
    }

    /** Send Kongono Live 2.5 fragment header (`X-Kongono-Live: true`). */
    public function withLive(bool $enabled = true): self
    {
        $clone = clone $this;
        if ($enabled) {
            $clone->defaultHeaders['X-Kongono-Live'] = 'true';
        } else {
            unset($clone->defaultHeaders['X-Kongono-Live']);
        }

        return $clone;
    }

    /** Send Kongono Bridge SPA headers (`X-Kongono-Bridge: true` and optional asset version). */
    public function withBridge(?string $version = null): self
    {
        $clone = $this->withHeader('X-Kongono-Bridge', 'true');
        if (null !== $version && '' !== $version) {
            $clone = $clone->withHeader('X-Kongono-Bridge-Version', $version);
        }

        return $clone;
    }

    /** Send an explicit `X-CSRF-Token` header on subsequent requests. */
    public function withCsrf(string $token): self
    {
        return $this->withHeader('X-CSRF-Token', $token);
    }

    /**
     * Enable per-client stateful cookie jar & automatic CSRF token capture from HTML
     * `<meta name="csrf-token" content="...">` or hidden `_csrf` inputs across chained requests.
     */
    public function withStatefulCookies(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->statefulCookies = $enabled;

        return $clone;
    }

    /** @return array<string, string> */
    public function cookies(): array
    {
        return $this->cookies;
    }

    public function csrfToken(): ?string
    {
        return $this->capturedCsrfToken;
    }

    /**
     * Execute an arbitrary HTTP method request (also used by Dev Studio `TestExporter`).
     *
     * @param array<string, string>                $headers
     * @param array<string, mixed>                 $query
     * @param array<string, mixed>|null            $parsedBody
     * @param array<string, UploadedFileInterface> $files
     */
    public function request(
        string $method,
        string $uri,
        string $body = '',
        array $headers = [],
        array $query = [],
        ?array $parsedBody = null,
        array $files = [],
    ): TestResponse {
        return $this->send(Request::create(
            method: $method,
            uri: $uri,
            headers: $this->mergeHeaders($method, $headers),
            body: $body,
            query: $query,
            files: $files,
            parsedBody: $parsedBody,
        ));
    }

    /** @param array<string, string> $headers */
    public function get(string $uri, array $headers = []): TestResponse
    {
        return $this->send(Request::create('GET', $uri, $this->mergeHeaders('GET', $headers)));
    }

    /**
     * @param array<string, mixed>  $fields
     * @param array<string, string> $headers
     */
    public function postForm(string $uri, array $fields, array $headers = []): TestResponse
    {
        return $this->send(Request::create('POST', $uri, $this->mergeHeaders('POST', $headers), parsedBody: $fields));
    }

    /**
     * Send a multipart form POST with PSR-7 uploaded files (e.g. from {@see TestUpload}).
     *
     * @param array<string, UploadedFileInterface> $files
     * @param array<string, mixed>                 $fields
     * @param array<string, string>                $headers
     */
    public function postMultipart(string $uri, array $files, array $fields = [], array $headers = []): TestResponse
    {
        return $this->send(Request::create(
            method: 'POST',
            uri: $uri,
            headers: $this->mergeHeaders('POST', $headers),
            files: $files,
            parsedBody: $fields,
        ));
    }

    /** @param array<string, string> $headers */
    public function postJson(string $uri, mixed $payload, array $headers = []): TestResponse
    {
        return $this->send(Request::json('POST', $uri, $payload, $this->mergeHeaders('POST', $headers)));
    }

    /** @param array<string, string> $headers */
    public function putJson(string $uri, mixed $payload, array $headers = []): TestResponse
    {
        return $this->send(Request::json('PUT', $uri, $payload, $this->mergeHeaders('PUT', $headers)));
    }

    /** @param array<string, string> $headers */
    public function patchJson(string $uri, mixed $payload, array $headers = []): TestResponse
    {
        return $this->send(Request::json('PATCH', $uri, $payload, $this->mergeHeaders('PATCH', $headers)));
    }

    /** @param array<string, string> $headers */
    public function delete(string $uri, array $headers = []): TestResponse
    {
        return $this->send(Request::create('DELETE', $uri, $this->mergeHeaders('DELETE', $headers)));
    }

    /**
     * Follow HTTP redirect responses (301/302/303/307/308) up to `$maxHops` times.
     * Pairs cleanly with `withStatefulCookies()` for PRG form validation & login flows.
     */
    public function followRedirects(TestResponse $response, int $maxHops = 5): TestResponse
    {
        $current = $response;
        $hops = 0;

        while (in_array($current->status(), [301, 302, 303, 307, 308], true)) {
            $location = trim($current->header('Location'));
            if ('' === $location) {
                break;
            }
            if ($hops >= $maxHops) {
                throw new CoreException(sprintf('Redirect chain exceeded maximum of %d hop(s) (last Location: "%s").', $maxHops, $location));
            }
            ++$hops;
            $current = $this->get($location);
        }

        return $current;
    }

    public function send(Request $request): TestResponse
    {
        if ([] !== $this->cookies) {
            /** @var Request $request */
            $request = $request->withCookieParams([...$this->cookies, ...$request->getCookieParams()]);
        }
        foreach ($this->attributes as $attrName => $attrVal) {
            if (null === $request->getAttribute($attrName)) {
                /** @var Request $request */
                $request = $request->withAttribute($attrName, $attrVal);
            }
        }

        $testResponse = new TestResponse($this->app->handle($request));

        if ($this->statefulCookies) {
            $this->absorbResponseState($testResponse);
        }

        return $testResponse;
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private function mergeHeaders(string $method, array $headers): array
    {
        $merged = [...$this->defaultHeaders, ...$headers];
        if (
            $this->statefulCookies
            && null !== $this->capturedCsrfToken
            && !in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true)
        ) {
            $hasCsrf = false;
            foreach (array_keys($merged) as $k) {
                if ('x-csrf-token' === strtolower($k)) {
                    $hasCsrf = true;
                    break;
                }
            }
            if (!$hasCsrf) {
                $merged['X-CSRF-Token'] = $this->capturedCsrfToken;
            }
        }

        return $merged;
    }

    private function absorbResponseState(TestResponse $response): void
    {
        foreach ($response->response->getHeader('Set-Cookie') as $headerLine) {
            $parts = explode(';', $headerLine);
            $pair = trim($parts[0]);
            $eq = strpos($pair, '=');
            if (false === $eq) {
                continue;
            }
            $name = trim(substr($pair, 0, $eq));
            $value = rawurldecode(trim(substr($pair, $eq + 1)));
            if ('' === $name) {
                continue;
            }

            $expired = '' === $value;
            foreach (array_slice($parts, 1) as $attr) {
                if (1 === preg_match('/\A\s*max-age\s*=\s*(-?\d+)\s*\z/i', $attr, $m) && (int) $m[1] <= 0) {
                    $expired = true;
                    break;
                }
            }

            if ($expired) {
                unset($this->cookies[$name]);
            } else {
                $this->cookies[$name] = $value;
            }
        }

        $body = $response->body();
        if (1 === preg_match('/<meta\s+name=["\']csrf-token["\']\s+content=["\']([^"\']+)["\']/i', $body, $metaMatch)) {
            $this->capturedCsrfToken = $metaMatch[1];
        } elseif (1 === preg_match('/<input[^>]+name=["\']_csrf["\'][^>]+value=["\']([^"\']+)["\']/i', $body, $inputMatch)) {
            $this->capturedCsrfToken = $inputMatch[1];
        }
    }
}
