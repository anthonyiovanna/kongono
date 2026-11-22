<?php

declare(strict_types=1);

namespace Kongono\Http\Session;

/** Persistent-runtime-safe session object (never touches PHP $_SESSION superglobal). */
final class Session
{
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
