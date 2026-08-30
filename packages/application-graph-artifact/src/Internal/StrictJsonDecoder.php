<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Parses strict JSON while rejecting duplicate members at every depth. */
#[Api(ApiStatus::Internal)]
final class StrictJsonDecoder
{
    private int $offset = 0;
    private readonly int $length;
    public function __construct(private readonly string $bytes, private readonly int $maximumDepth)
    {
        if ($maximumDepth < 1) {
            throw new JsonException('Invalid JSON depth.');
        } $this->length = strlen($bytes);
    }
    public function decode(): mixed
    {
        $this->skipWhitespace();
        $value = $this->value(0);
        $this->skipWhitespace();
        if ($this->offset !== $this->length) {
            throw new JsonException('Trailing JSON data.');
        } return $value;
    }
    private function value(int $depth): mixed
    {
        if ($this->offset >= $this->length) {
            throw new JsonException('Unexpected end of JSON.');
        } return match ($this->bytes[$this->offset]) {
            '{' => $this->object($depth + 1), '[' => $this->list($depth + 1), '"' => $this->string(), 't' => $this->literal('true', true), 'f' => $this->literal('false', false), 'n' => $this->literal('null', null), default => $this->number(),
        };
    }
    /** @return array<string, mixed> */
    private function object(int $depth): array
    {
        $this->assertDepth($depth);
        ++$this->offset;
        $this->skipWhitespace();
        if ($this->consume('}')) {
            return [];
        } $result = [];
        $seen = [];
        while (true) {
            if ($this->offset >= $this->length || '"' !== $this->bytes[$this->offset]) {
                throw new JsonException('Expected an object member name.');
            }$key = $this->string();
            if (isset($seen['key:' . $key])) {
                throw new JsonException('Duplicate object member.');
            }$seen['key:' . $key] = true;
            $this->skipWhitespace();
            if (!$this->consume(':')) {
                throw new JsonException('Expected an object member separator.');
            }$this->skipWhitespace();
            $result[$key] = $this->value($depth);
            $this->skipWhitespace();
            if ($this->consume('}')) {
                break;
            }if (!$this->consume(',')) {
                throw new JsonException('Expected an object member delimiter.');
            }$this->skipWhitespace();
        } return $result;
    }
    /** @return list<mixed> */
    private function list(int $depth): array
    {
        $this->assertDepth($depth);
        ++$this->offset;
        $this->skipWhitespace();
        if ($this->consume(']')) {
            return [];
        }$result = [];
        while (true) {
            $result[] = $this->value($depth);
            $this->skipWhitespace();
            if ($this->consume(']')) {
                break;
            }if (!$this->consume(',')) {
                throw new JsonException('Expected an array item delimiter.');
            }$this->skipWhitespace();
        }return $result;
    }
    private function string(): string
    {
        $start = $this->offset;
        ++$this->offset;
        while ($this->offset < $this->length) {
            $byte = ord($this->bytes[$this->offset]);
            if (0x22 === $byte) {
                ++$this->offset;
                $token = substr($this->bytes, $start, $this->offset - $start);
                $decoded = json_decode($token, true, 2, JSON_THROW_ON_ERROR);
                if (!is_string($decoded)) {
                    throw new JsonException('Invalid JSON string.');
                }return $decoded;
            }if ($byte < 0x20) {
                throw new JsonException('Unescaped control byte.');
            }if (0x5C === $byte) {
                ++$this->offset;
                if ($this->offset >= $this->length) {
                    throw new JsonException('Incomplete escape.');
                }if ('u' === $this->bytes[$this->offset]) {
                    for ($i = 1;$i <= 4;++$i) {
                        if ($this->offset + $i >= $this->length || !ctype_xdigit($this->bytes[$this->offset + $i])) {
                            throw new JsonException('Invalid Unicode escape.');
                        }
                    }$this->offset += 4;
                } elseif (!str_contains('"\\/bfnrt', $this->bytes[$this->offset])) {
                    throw new JsonException('Invalid escape.');
                }
            }++$this->offset;
        }throw new JsonException('Unterminated string.');
    }
    private function number(): int|float
    {
        $remaining = substr($this->bytes, $this->offset);
        if (1 !== preg_match('/-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?/A', $remaining, $m)) {
            throw new JsonException('Invalid JSON value.');
        }$token = $m[0];
        $this->offset += strlen($token);
        $decoded = json_decode($token, true, 2, JSON_THROW_ON_ERROR);
        if (!is_int($decoded) && !is_float($decoded)) {
            throw new JsonException('Invalid JSON number.');
        }return $decoded;
    }
    private function literal(string $literal, mixed $value): mixed
    {
        if (substr($this->bytes, $this->offset, strlen($literal)) !== $literal) {
            throw new JsonException('Invalid JSON literal.');
        }$this->offset += strlen($literal);
        return $value;
    }
    private function skipWhitespace(): void
    {
        while ($this->offset < $this->length && str_contains(" \t\n\r", $this->bytes[$this->offset])) {
            ++$this->offset;
        }
    }
    private function consume(string $byte): bool
    {
        if ($this->offset < $this->length && $this->bytes[$this->offset] === $byte) {
            ++$this->offset;
            return true;
        }return false;
    }
    private function assertDepth(int $depth): void
    {
        if ($depth > $this->maximumDepth) {
            throw new JsonException('Maximum JSON depth exceeded.');
        }
    }
}
