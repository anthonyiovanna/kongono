<?php

declare(strict_types=1);

namespace Kongono\Http\Testing;

use JsonException;
use Kongono\Core\CoreException;
use Kongono\Http\Response;
use Psr\Http\Message\ResponseInterface;

/** Expressive fluent assertion wrapper around any PSR-7 ResponseInterface. */
final readonly class TestResponse
{
    private string $rawBody;

    public function __construct(
        public ResponseInterface $response,
    ) {
        $this->rawBody = $response instanceof Response
            ? $response->bodyString()
            : (string) $response->getBody();
    }

    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    public function body(): string
    {
        return $this->rawBody;
    }

    public function header(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    /** @return array<string, mixed> */
    public function json(): array
    {
        try {
            $decoded = json_decode($this->rawBody, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new CoreException('Response body is not valid JSON: ' . substr($this->rawBody, 0, 200), previous: $e);
        }
        if (!is_array($decoded)) {
            throw new CoreException('Decoded JSON response is not an array/object.');
        }

        /** @var array<string, mixed> $out */
        $out = [];
        foreach ($decoded as $k => $v) {
            $out[(string) $k] = $v;
        }

        return $out;
    }

    public function assertStatus(int $expected): self
    {
        if ($this->status() !== $expected) {
            throw new CoreException(sprintf(
                'Expected HTTP status %d, got %d. Body: %s',
                $expected,
                $this->status(),
                substr($this->rawBody, 0, 300),
            ));
        }

        return $this;
    }

    public function assertOk(): self
    {
        return $this->assertStatus(200);
    }

    public function assertCreated(): self
    {
        return $this->assertStatus(201);
    }

    public function assertNoContent(): self
    {
        return $this->assertStatus(204);
    }

    public function assertNotFound(): self
    {
        return $this->assertStatus(404);
    }

    public function assertUnprocessable(): self
    {
        return $this->assertStatus(422);
    }

    public function assertHeader(string $name, ?string $expectedValue = null): self
    {
        if (!$this->response->hasHeader($name)) {
            throw new CoreException(sprintf('Expected response header "%s" to be present.', $name));
        }
        if (null !== $expectedValue && $this->header($name) !== $expectedValue) {
            throw new CoreException(sprintf(
                'Expected header "%s" to equal "%s", got "%s".',
                $name,
                $expectedValue,
                $this->header($name),
            ));
        }

        return $this;
    }

    public function assertSee(string $needle): self
    {
        if (!str_contains($this->rawBody, $needle)) {
            throw new CoreException(sprintf('Expected response body to contain "%s".', $needle));
        }

        return $this;
    }

    public function assertDontSee(string $needle): self
    {
        if (str_contains($this->rawBody, $needle)) {
            throw new CoreException(sprintf('Expected response body NOT to contain "%s".', $needle));
        }

        return $this;
    }

    public function assertJsonPath(string $dotPath, mixed $expected): self
    {
        $cursor = $this->json();
        foreach (explode('.', $dotPath) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                throw new CoreException(sprintf('JSON path "%s" does not exist in response.', $dotPath));
            }
            $cursor = $cursor[$segment];
        }
        if ($cursor !== $expected) {
            throw new CoreException(sprintf(
                'JSON path "%s" expected %s, got %s.',
                $dotPath,
                var_export($expected, true),
                var_export($cursor, true),
            ));
        }

        return $this;
    }
}
