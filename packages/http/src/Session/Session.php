<?php

declare(strict_types=1);

namespace Kongono\Http\Session;

/** Persistent-runtime-safe session object (never touches PHP $_SESSION superglobal). */
final class Session
{
    public const string FLASH_ERRORS_KEY = '_validation_errors';
    public const string FLASH_OLD_INPUT_KEY = '_old_input';

    /** @var list<string> Field name fragments that must NEVER be flashed into old() input */
    private const array SENSITIVE_INPUT_FRAGMENTS = [
        'password',
        'passwd',
        'passphrase',
        'secret',
        'token',
        'csrf',
        'api_key',
        'apikey',
        'private_key',
        'cvv',
        'cvc',
        'card_number',
        'credit_card',
        'ssn',
        'authorization',
    ];

    private bool $dirty = false;
    private bool $regenerated = false;
    private bool $invalidated = false;
    private bool $idAccessed = false;
    private ?string $previousId = null;

    /** @var list<string> */
    private array $previousIds = [];

    /** @var array<string, mixed> */
    private array $flashIncoming = [];

    /** @var array<string, mixed> */
    private array $flashOutgoing = [];

    /** @param array<string, mixed> $data */
    public function __construct(
        private string $id,
        private array $data = [],
        private readonly bool $persisted = false,
    ) {
        if (isset($this->data['_flash']) && is_array($this->data['_flash'])) {
            foreach ($this->data['_flash'] as $k => $v) {
                $this->flashIncoming[(string) $k] = $v;
            }
            unset($this->data['_flash']);
            $this->dirty = true;
        }
    }

    public function id(): string
    {
        $this->idAccessed = true;

        return $this->id;
    }

    public function previousId(): ?string
    {
        return $this->previousId;
    }

    /** @return list<string> */
    public function previousIds(): array
    {
        return $this->previousIds;
    }

    public function wasRegenerated(): bool
    {
        return $this->regenerated;
    }

    public function isInvalidated(): bool
    {
        return $this->invalidated;
    }

    public function isPersisted(): bool
    {
        return $this->persisted;
    }

    public function wasIdAccessed(): bool
    {
        return $this->idAccessed;
    }

    public function isDirty(): bool
    {
        return $this->dirty || [] !== $this->flashOutgoing;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        $this->dirty = true;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
        $this->dirty = true;
    }

    public function clear(): void
    {
        $this->data = [];
        $this->dirty = true;
    }

    /** Store a one-request flash message (available on the next request only). */
    public function flash(string $key, mixed $value): void
    {
        $this->flashOutgoing[$key] = $value;
        $this->dirty = true;
    }

    /** Read a flash message from the previous request. */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        return $this->flashIncoming[$key] ?? $default;
    }

    /**
     * Flash validation errors and secret-scrubbed form input for the next request
     * (used automatically on HTML form redirect-back).
     *
     * @param array<string, list<string>> $errors
     * @param array<string, mixed>        $rawInput
     */
    public function flashValidation(array $errors, array $rawInput = []): void
    {
        $this->flash(self::FLASH_ERRORS_KEY, $errors);
        $this->flash(self::FLASH_OLD_INPUT_KEY, self::scrubSensitiveInput($rawInput));
    }

    /**
     * Return incoming flashed validation errors from the previous request (`field => list<string>`).
     *
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        $raw = $this->getFlash(self::FLASH_ERRORS_KEY, []);
        if (!is_array($raw)) {
            return [];
        }

        /** @var array<string, list<string>> $normalized */
        $normalized = [];
        foreach ($raw as $field => $messages) {
            if (!is_array($messages)) {
                continue;
            }
            $list = [];
            foreach ($messages as $msg) {
                if (is_string($msg)) {
                    $list[] = $msg;
                }
            }
            if ([] !== $list) {
                $normalized[(string) $field] = $list;
            }
        }

        return $normalized;
    }

    /** Return the first flashed validation error for `$field`, or `null` if none. */
    public function error(string $field): ?string
    {
        $errors = $this->validationErrors();

        return $errors[$field][0] ?? null;
    }

    public function hasError(string $field): bool
    {
        return null !== $this->error($field);
    }

    /**
     * Return all incoming secret-scrubbed flashed input from the previous request.
     *
     * @return array<string, mixed>
     */
    public function oldInput(): array
    {
        $raw = $this->getFlash(self::FLASH_OLD_INPUT_KEY, []);
        if (!is_array($raw)) {
            return [];
        }

        /** @var array<string, mixed> $out */
        $out = [];
        foreach ($raw as $k => $v) {
            $out[(string) $k] = $v;
        }

        return $out;
    }

    /** Retrieve a previously submitted form value (supports dot-notation like `shipping.city`). */
    public function old(string $field, mixed $default = ''): mixed
    {
        $old = $this->oldInput();
        if (array_key_exists($field, $old)) {
            return $old[$field];
        }
        if (!str_contains($field, '.')) {
            return $default;
        }

        $cursor = $old;
        foreach (explode('.', $field) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return $default;
            }
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    /**
     * Return `['errors' => ..., 'old' => ...]` ready to pass into `ViewEngine::render()` if needed.
     *
     * @return array{errors: array<string, list<string>>, old: array<string, mixed>}
     */
    public function viewFlashData(): array
    {
        return [
            'errors' => $this->validationErrors(),
            'old' => $this->oldInput(),
        ];
    }

    /**
     * Recursively remove passwords, CSRF tokens, secrets, and card fields before flashing old input.
     *
     * @param array<mixed> $input
     * @return array<string, mixed>
     */
    public static function scrubSensitiveInput(array $input, int $depth = 0): array
    {
        if ($depth > 8) {
            return [];
        }

        /** @var array<string, mixed> $clean */
        $clean = [];
        foreach ($input as $key => $value) {
            $keyStr = (string) $key;
            $lower = strtolower($keyStr);
            $sensitive = false;
            foreach (self::SENSITIVE_INPUT_FRAGMENTS as $fragment) {
                if (str_contains($lower, $fragment)) {
                    $sensitive = true;
                    break;
                }
            }
            if ($sensitive) {
                continue;
            }

            if (is_array($value)) {
                $clean[$keyStr] = self::scrubSensitiveInput($value, $depth + 1);
            } elseif (is_string($value) || is_int($value) || is_float($value) || is_bool($value) || null === $value) {
                $clean[$keyStr] = $value;
            }
        }

        return $clean;
    }

    /** Rotate session ID (call on login/privilege change to prevent session fixation). */
    public function regenerate(): string
    {
        $this->previousId ??= $this->id;
        $this->previousIds[] = $this->id;
        $this->id = bin2hex(random_bytes(24));
        $this->regenerated = true;
        $this->invalidated = false;
        $this->dirty = true;

        return $this->id;
    }

    /** Destroy the session on logout (destroys stored session & expires the client cookie). */
    public function invalidate(): void
    {
        $this->previousId ??= $this->id;
        $this->previousIds[] = $this->id;
        $this->data = [];
        $this->flashIncoming = [];
        $this->flashOutgoing = [];
        $this->invalidated = true;
        $this->regenerated = false;
        $this->dirty = false;
    }

    /** @return array<string, mixed> */
    public function exportPayload(): array
    {
        $payload = $this->data;
        if ([] !== $this->flashOutgoing) {
            $payload['_flash'] = $this->flashOutgoing;
        }

        return $payload;
    }
}
