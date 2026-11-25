<?php

declare(strict_types=1);

namespace StarterApp\Domain;

use JsonException;
use Kongono\Security\PasswordHasher;
use Kongono\Storage\StorageInterface;

/** Atomic storage-backed user store with Argon2id password hashing. */
final class UserStore
{
    private const string STORAGE_KEY = 'data/users.json';

    private ?string $dummyHash = null;

    public function __construct(
        private readonly StorageInterface $storage,
        private readonly PasswordHasher $hasher,
    ) {}

    /** @return array{id: string, name: string, email: string, password_hash: string}|null */
    public function findByEmail(string $email): ?array
    {
        $key = strtolower(trim($email));

        return $this->load()[$key] ?? null;
    }

    /** @return array{id: string, name: string, email: string} */
    public function register(string $name, string $email, #[\SensitiveParameter] string $password): array
    {
        $users = $this->load();
        $key = strtolower(trim($email));
        $id = 'usr_' . bin2hex(random_bytes(8));
        $users[$key] = [
            'id' => $id,
            'name' => $name,
            'email' => $key,
            'password_hash' => $this->hasher->hash($password),
        ];
        $this->storage->put(self::STORAGE_KEY, json_encode($users, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 'application/json');

        return ['id' => $id, 'name' => $name, 'email' => $key];
    }

    /** @return array{id: string, name: string, email: string}|null */
    public function verifyCredentials(string $email, #[\SensitiveParameter] string $password): ?array
    {
        $user = $this->findByEmail($email);
        if (null === $user) {
            // Perform a single Argon2id verify against a cached dummy hash (matches real-user cost 1:1)
            $this->dummyHash ??= $this->hasher->hash('kongono-dummy-timing-guard');
            $this->hasher->verify($password, $this->dummyHash);

            return null;
        }
        if (!$this->hasher->verify($password, $user['password_hash'])) {
            return null;
        }

        return ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']];
    }

    /** @return array<string, array{id: string, name: string, email: string, password_hash: string}> */
    private function load(): array
    {
        $raw = $this->storage->get(self::STORAGE_KEY);
        if (null === $raw) {
            return [];
        }
        try {
            $decoded = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
        if (!is_array($decoded)) {
            return [];
        }

        $users = [];
        foreach ($decoded as $k => $v) {
            if (
                is_string($k)
                && is_array($v)
                && is_string($v['id'] ?? null)
                && is_string($v['name'] ?? null)
                && is_string($v['email'] ?? null)
                && is_string($v['password_hash'] ?? null)
            ) {
                $users[$k] = [
                    'id' => $v['id'],
                    'name' => $v['name'],
                    'email' => $v['email'],
                    'password_hash' => $v['password_hash'],
                ];
            }
        }

        return $users;
    }
}
