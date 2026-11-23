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

    /**
     * Parse all `Set-Cookie` headers into a `[name => value]` map.
     *
     * @return array<string, string>
     */
    public function cookies(): array
    {
        $parsed = [];
        foreach ($this->response->getHeader('Set-Cookie') as $line) {
            $firstSegment = trim(explode(';', $line, 2)[0]);
            $eq = strpos($firstSegment, '=');
            if (false === $eq) {
                continue;
            }
            $name = trim(substr($firstSegment, 0, $eq));
            $val = rawurldecode(trim(substr($firstSegment, $eq + 1)));
            if ('' !== $name) {
                $parsed[$name] = $val;
            }
        }

        return $parsed;
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

    public function assertUnauthorized(): self
    {
        return $this->assertStatus(401);
    }

    public function assertForbidden(): self
    {
        return $this->assertStatus(403);
    }

    public function assertNotFound(): self
    {
        return $this->assertStatus(404);
    }

    public function assertUnprocessable(): self
    {
        return $this->assertStatus(422);
    }

    public function assertRedirect(?string $expectedLocation = null): self
    {
        if (!in_array($this->status(), [301, 302, 303, 307, 308], true)) {
            throw new CoreException(sprintf('Expected HTTP redirect status (301/302/303/307/308), got %d.', $this->status()));
        }
        if (!$this->response->hasHeader('Location')) {
            throw new CoreException('Expected redirect response to include a Location header.');
        }
        if (null !== $expectedLocation && $this->header('Location') !== $expectedLocation) {
            throw new CoreException(sprintf(
                'Expected redirect Location "%s", got "%s".',
                $expectedLocation,
                $this->header('Location'),
            ));
        }

        return $this;
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

    public function assertHeaderMissing(string $name): self
    {
        if ($this->response->hasHeader($name)) {
            throw new CoreException(sprintf('Expected response header "%s" to be absent, got "%s".', $name, $this->header($name)));
        }

        return $this;
    }

    public function assertCookie(string $name, ?string $expectedValue = null): self
    {
        $cookies = $this->cookies();
        if (!array_key_exists($name, $cookies)) {
            throw new CoreException(sprintf('Expected response to set cookie "%s".', $name));
        }
        if (null !== $expectedValue && $cookies[$name] !== $expectedValue) {
            throw new CoreException(sprintf(
                'Expected cookie "%s" to equal "%s", got "%s".',
                $name,
                $expectedValue,
                $cookies[$name],
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

    /** @param list<string> $needles */
    public function assertSeeInOrder(array $needles): self
    {
        $offset = 0;
        foreach ($needles as $needle) {
            $pos = strpos($this->rawBody, $needle, $offset);
            if (false === $pos) {
                throw new CoreException(sprintf('Expected response body to contain "%s" in order after byte offset %d.', $needle, $offset));
            }
            $offset = $pos + strlen($needle);
        }

        return $this;
    }

    public function assertJsonPath(string $dotPath, mixed $expected): self
    {
        $actual = $this->resolveJsonPath($dotPath);
        if ($actual !== $expected) {
            throw new CoreException(sprintf(
                'JSON path "%s" expected %s, got %s.',
                $dotPath,
                var_export($expected, true),
                var_export($actual, true),
            ));
        }

        return $this;
    }

    public function assertJsonCount(int $expected, ?string $dotPath = null): self
    {
        $target = null === $dotPath ? $this->json() : $this->resolveJsonPath($dotPath);
        if (!is_array($target)) {
            throw new CoreException(sprintf('Expected JSON path "%s" to be an array/object.', $dotPath));
        }
        $actual = count($target);
        if ($actual !== $expected) {
            throw new CoreException(sprintf(
                'Expected JSON %s to have %d item(s), got %d.',
                null === $dotPath ? 'root' : 'path "' . $dotPath . '"',
                $expected,
                $actual,
            ));
        }

        return $this;
    }

    /** @param array<array-key, mixed> $subset */
    public function assertJsonContains(array $subset, ?string $dotPath = null): self
    {
        $target = null === $dotPath ? $this->json() : $this->resolveJsonPath($dotPath);
        if (!is_array($target)) {
            throw new CoreException(sprintf('Expected JSON path "%s" to be an array/object.', $dotPath));
        }
        $this->assertArraySubset($subset, $target, $dotPath ?? '$');

        return $this;
    }

    /**
     * Assert that the JSON payload matches an expected key structure (supports nested arrays
     * and `'*'` wildcards for lists of objects).
     *
     * @param array<array-key, mixed> $structure
     */
    public function assertJsonStructure(array $structure, ?string $dotPath = null): self
    {
        $target = null === $dotPath ? $this->json() : $this->resolveJsonPath($dotPath);
        if (!is_array($target)) {
            throw new CoreException(sprintf('Expected JSON path "%s" to be an array/object.', $dotPath));
        }
        $this->assertStructureRecursive($structure, $target, $dotPath ?? '$');

        return $this;
    }

    /** Assert a 422 JSON validation error payload contains an error entry for `$field`. */
    public function assertValidationError(string $field): self
    {
        $this->assertUnprocessable();
        $json = $this->json();

        $errors = null;
        if (is_array($json['fields'] ?? null)) {
            $errors = $json['fields'];
        } elseif (is_array($json['errors'] ?? null)) {
            $errors = $json['errors'];
        } elseif (is_array($json['error'] ?? null) && is_array($json['error']['details'] ?? null)) {
            $errors = $json['error']['details'];
        } elseif (is_array($json['error'] ?? null) && is_array($json['error']['fields'] ?? null)) {
            $errors = $json['error']['fields'];
        }

        if (!is_array($errors) || !array_key_exists($field, $errors)) {
            throw new CoreException(sprintf(
                'Expected validation error for field "%s" in 422 response. Body: %s',
                $field,
                substr($this->rawBody, 0, 300),
            ));
        }

        return $this;
    }

    /** Assert Kongono Live 2.5 swap mode header (`X-Kongono-Swap: morph|inner|outer|...`). */
    public function assertLiveSwap(string $expectedMode): self
    {
        return $this->assertHeader('X-Kongono-Swap', $expectedMode);
    }

    /** Assert response contains a Kongono Live out-of-band (`kgn-oob`) fragment (optionally matching an element ID). */
    public function assertLiveOob(?string $targetId = null): self
    {
        if (!str_contains($this->rawBody, 'kgn-oob=')) {
            throw new CoreException('Expected response body to include a Kongono Live kgn-oob fragment.');
        }
        if (null !== $targetId && !str_contains($this->rawBody, 'id="' . $targetId . '"') && !str_contains($this->rawBody, "id='" . $targetId . "'")) {
            throw new CoreException(sprintf('Expected Kongono Live kgn-oob fragment targeting id "%s".', $targetId));
        }

        return $this;
    }

    /** Assert response carries a file download `Content-Disposition: attachment` header (and optional filename). */
    public function assertDownload(?string $expectedFilename = null): self
    {
        $disposition = $this->header('Content-Disposition');
        if ('' === $disposition || !str_starts_with(strtolower($disposition), 'attachment')) {
            throw new CoreException(sprintf('Expected Content-Disposition attachment header, got "%s".', $disposition));
        }
        if (null !== $expectedFilename && !str_contains($disposition, $expectedFilename)) {
            throw new CoreException(sprintf(
                'Expected download filename "%s" in Content-Disposition "%s".',
                $expectedFilename,
                $disposition,
            ));
        }

        return $this;
    }

    private function resolveJsonPath(string $dotPath): mixed
    {
        $cursor = $this->json();
        foreach (explode('.', $dotPath) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                throw new CoreException(sprintf('JSON path "%s" does not exist in response.', $dotPath));
            }
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    /**
     * @param array<array-key, mixed> $expected
     * @param array<array-key, mixed> $actual
     */
    private function assertArraySubset(array $expected, array $actual, string $path): void
    {
        foreach ($expected as $key => $value) {
            $childPath = $path . '.' . (string) $key;
            if (!array_key_exists($key, $actual)) {
                throw new CoreException(sprintf('Expected JSON key "%s" to exist.', $childPath));
            }
            if (is_array($value)) {
                if (!is_array($actual[$key])) {
                    throw new CoreException(sprintf('Expected JSON key "%s" to be an array/object.', $childPath));
                }
                $this->assertArraySubset($value, $actual[$key], $childPath);
            } elseif ($actual[$key] !== $value) {
                throw new CoreException(sprintf(
                    'JSON key "%s" expected %s, got %s.',
                    $childPath,
                    var_export($value, true),
                    var_export($actual[$key], true),
                ));
            }
        }
    }

    /**
     * @param array<array-key, mixed> $structure
     * @param array<array-key, mixed> $actual
     */
    private function assertStructureRecursive(array $structure, array $actual, string $path): void
    {
        foreach ($structure as $key => $value) {
            if ('*' === $key && is_array($value)) {
                foreach ($actual as $idx => $item) {
                    if (!is_array($item)) {
                        throw new CoreException(sprintf('Expected JSON list item "%s.%s" to be an object/array.', $path, (string) $idx));
                    }
                    $this->assertStructureRecursive($value, $item, $path . '.' . (string) $idx);
                }
            } elseif (is_array($value)) {
                $k = (string) $key;
                if (!array_key_exists($k, $actual) || !is_array($actual[$k])) {
                    throw new CoreException(sprintf('Expected JSON nested object/array at "%s.%s".', $path, $k));
                }
                $this->assertStructureRecursive($value, $actual[$k], $path . '.' . $k);
            } elseif (is_string($value)) {
                if (!array_key_exists($value, $actual)) {
                    throw new CoreException(sprintf('Expected JSON structure key "%s.%s" to exist.', $path, $value));
                }
            }
        }
    }
}
