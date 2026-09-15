<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Manifest;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterVite\Exception\InvalidViteManifestException;
use Kongono\AssetAdapterVite\Internal\PathGuard;
use Throwable;

/** Strictly duplicate-safely decodes the closed bounded Vite manifest subset. */
#[Api(ApiStatus::Experimental)]
final class ViteManifestDecoder
{
    private const array ALLOWED = ['file', 'src', 'name', 'isEntry', 'isDynamicEntry', 'imports', 'dynamicImports', 'css', 'assets'];

    public function decode(string $bytes, int $maximumBytes = 8_388_608): ViteManifest
    {
        try {
            if ('' === $bytes || strlen($bytes) > $maximumBytes || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\0") || str_contains($bytes, "\r") || 1 !== preg_match('//u', $bytes)) {
                throw InvalidViteManifestException::invalid('framing');
            }
            $model = $this->strictObject($bytes);
            if (count($model) > 2_048) {
                throw InvalidViteManifestException::invalid('entry_count', count($model), 2_048);
            }
            $entries = [];
            foreach ($model as $key => $row) {
                if (!$this->safeString($key, 4_096, false) || !is_array($row) || array_is_list($row)) {
                    throw InvalidViteManifestException::invalid('entry_shape');
                }
                $object = [];
                foreach ($row as $member => $value) {
                    if (!is_string($member)) {
                        throw InvalidViteManifestException::invalid('entry_shape');
                    }
                    if (!in_array($member, self::ALLOWED, true)) {
                        throw InvalidViteManifestException::invalid('unknown_member');
                    }
                    $object[$member] = $value;
                }
                $row = $object;
                if (!array_key_exists('file', $row) || !is_string($row['file'])) {
                    throw InvalidViteManifestException::invalid('entry_shape');
                }
                $entries[] = new ViteManifestEntry(
                    $key,
                    $this->path($row['file']),
                    $this->optionalPath($row, 'src'),
                    $this->optionalString($row, 'name'),
                    $this->optionalBool($row, 'isEntry'),
                    $this->optionalBool($row, 'isDynamicEntry'),
                    $this->list($row, 'imports', false),
                    $this->list($row, 'dynamicImports', false),
                    $this->list($row, 'css', true),
                    $this->list($row, 'assets', true),
                );
            }

            return new ViteManifest($entries);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidViteManifestException) {
                throw $failure;
            }
            throw InvalidViteManifestException::invalid('json', previous: $failure);
        }
    }

    private function path(string $value): string
    {
        try {
            return PathGuard::relative($value);
        } catch (Throwable $failure) {
            throw InvalidViteManifestException::invalid('unsafe_path', previous: $failure);
        }
    }
    /** @param array<string, mixed> $row */
    private function optionalPath(array $row, string $key): ?string
    {
        if (!array_key_exists($key, $row)) {
            return null;
        } if (!is_string($row[$key])) {
            throw InvalidViteManifestException::invalid('entry_shape');
        } return $this->path($row[$key]);
    }
    /** @param array<string, mixed> $row */
    private function optionalString(array $row, string $key): ?string
    {
        if (!array_key_exists($key, $row)) {
            return null;
        } if (!is_string($row[$key]) || !$this->safeString($row[$key], 1_024, true)) {
            throw InvalidViteManifestException::invalid('string_grammar');
        } return $row[$key];
    }
    /** @param array<string, mixed> $row */
    private function optionalBool(array $row, string $key): bool
    {
        if (!array_key_exists($key, $row)) {
            return false;
        } if (!is_bool($row[$key])) {
            throw InvalidViteManifestException::invalid('boolean_type');
        } return $row[$key];
    }
    /**
     * @param array<string, mixed> $row
     *
     * @return list<string>
     */
    private function list(array $row, string $key, bool $paths): array
    {
        if (!array_key_exists($key, $row)) {
            return [];
        }
        $value = $row[$key];
        if (!is_array($value) || !array_is_list($value) || count($value) > 2_048) {
            throw InvalidViteManifestException::invalid('list_shape');
        }
        $seen = [];
        foreach ($value as $entry) {
            if (!is_string($entry) || !$this->safeString($entry, 4_096, false)) {
                throw InvalidViteManifestException::invalid('string_grammar');
            }
            $entry = $paths ? $this->path($entry) : $entry;
            if (isset($seen[$entry])) {
                throw InvalidViteManifestException::invalid('duplicate_list');
            }
            $seen[$entry] = true;
        }
        $result = array_keys($seen);
        sort($result, SORT_STRING);
        return $result;
    }
    private function safeString(string $value, int $maximum, bool $empty): bool
    {
        return ($empty || '' !== $value) && strlen($value) <= $maximum && 1 !== preg_match('/[\x00-\x1F\x7F]/', $value);
    }

    /** @return array<string, mixed> */
    private function strictObject(string $json): array
    {
        $position = 0;
        $this->space($json, $position);
        $this->value($json, $position, 1);
        $this->space($json, $position);
        if ($position !== strlen($json)) {
            throw new JsonException('JSON has a trailing token.');
        }
        $decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw InvalidViteManifestException::invalid('root_shape');
        }
        $result = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                throw InvalidViteManifestException::invalid('root_shape');
            }
            $result[$key] = $value;
        }

        return $result;
    }
    private function value(string $json, int &$position, int $depth): void
    {
        if ($depth > 64 || $position >= strlen($json)) {
            throw new JsonException('JSON depth exceeded.');
        }
        match ($json[$position]) {
            '{' => $this->object($json, $position, $depth), '[' => $this->array($json, $position, $depth), '"' => $this->string($json, $position), 't' => $this->literal($json, $position, 'true'), 'f' => $this->literal($json, $position, 'false'), 'n' => $this->literal($json, $position, 'null'), default => $this->number($json, $position),
        };
    }
    private function object(string $json, int &$position, int $depth): void
    {
        ++$position;
        $this->space($json, $position);
        if (($json[$position] ?? null) === '}') {
            ++$position;
            return;
        } $keys = [];
        while (true) {
            if (($json[$position] ?? null) !== '"') {
                throw new JsonException('JSON key is invalid.');
            }
            $key = $this->string($json, $position);
            if (array_key_exists($key, $keys)) {
                throw new JsonException('JSON member is duplicated.');
            } $keys[$key] = true;
            $this->space($json, $position);
            if (($json[$position] ?? null) !== ':') {
                throw new JsonException('JSON separator is invalid.');
            }
            ++$position;
            $this->space($json, $position);
            $this->value($json, $position, $depth + 1);
            $this->space($json, $position);
            $next = $json[$position] ?? null;
            if ('}' === $next) {
                ++$position;
                return;
            } if (',' !== $next) {
                throw new JsonException('JSON delimiter is invalid.');
            } ++$position;
            $this->space($json, $position);
        }
    }
    private function array(string $json, int &$position, int $depth): void
    {
        ++$position;
        $this->space($json, $position);
        if (($json[$position] ?? null) === ']') {
            ++$position;
            return;
        }
        while (true) {
            $this->value($json, $position, $depth + 1);
            $this->space($json, $position);
            $next = $json[$position] ?? null;
            if (']' === $next) {
                ++$position;
                return;
            } if (',' !== $next) {
                throw new JsonException('JSON delimiter is invalid.');
            } ++$position;
            $this->space($json, $position);
        }
    }
    private function string(string $json, int &$position): string
    {
        $start = $position++;
        $escaped = false;
        while ($position < strlen($json)) {
            $byte = ord($json[$position]);
            if (!$escaped && 0x22 === $byte) {
                ++$position;
                $decoded = json_decode(substr($json, $start, $position - $start), true, 2, JSON_THROW_ON_ERROR);
                if (!is_string($decoded)) {
                    throw new JsonException('JSON string is invalid.');
                } return $decoded;
            } if (!$escaped && $byte < 0x20) {
                throw new JsonException('JSON control byte.');
            } if (!$escaped && 0x5c === $byte) {
                $escaped = true;
                ++$position;
                continue;
            } $escaped = false;
            ++$position;
        }
        throw new JsonException('JSON string is unterminated.');
    }
    private function literal(string $json, int &$position, string $literal): void
    {
        if (substr($json, $position, strlen($literal)) !== $literal) {
            throw new JsonException('JSON literal is invalid.');
        } $position += strlen($literal);
    }
    private function number(string $json, int &$position): void
    {
        if (1 !== preg_match('/\A-?(?:0|[1-9][0-9]*)/', substr($json, $position), $match)) {
            throw new JsonException('JSON number is invalid.');
        } $position += strlen($match[0]);
        if (isset($json[$position]) && in_array($json[$position], ['.', 'e', 'E'], true)) {
            throw new JsonException('JSON float unsupported.');
        }
    }
    private function space(string $json, int &$position): void
    {
        while (isset($json[$position]) && in_array($json[$position], [" ", "\t", "\n"], true)) {
            ++$position;
        }
    }
}
