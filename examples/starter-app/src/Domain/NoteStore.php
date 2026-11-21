<?php

declare(strict_types=1);

namespace StarterApp\Domain;

use JsonException;
use Kongono\Storage\StorageInterface;

/** Atomic storage-backed note store (persists notes across HTTP requests via Kongono Storage). */
final class NoteStore
{
    private const string STORAGE_KEY = 'data/notes.json';

    public function __construct(
        private readonly StorageInterface $storage,
    ) {
        if (!$this->storage->exists(self::STORAGE_KEY)) {
            $this->seed();
        }
    }

    /** @return list<Note> */
    public function all(): array
    {
        return array_values($this->loadState()['notes']);
    }

    public function find(int $id): ?Note
    {
        return $this->loadState()['notes'][$id] ?? null;
    }

    public function create(string $title, string $body): Note
    {
        $state = $this->loadState();
        $id = $state['next_id']++;
        $note = new Note($id, $title, $body, gmdate(DATE_ATOM));
        $state['notes'][$id] = $note;
        $this->saveState($state);

        return $note;
    }

    public function delete(int $id): bool
    {
        $state = $this->loadState();
        if (!isset($state['notes'][$id])) {
            return false;
        }
        unset($state['notes'][$id]);
        $this->saveState($state);

        return true;
    }

    private function seed(): void
    {
        $now = gmdate(DATE_ATOM);
        $this->saveState([
            'next_id' => 3,
            'notes' => [
                1 => new Note(1, 'Welcome to Kongono v1', 'Zero legacy PHP 8.5+, zero Symfony, compiled .kgn.html views, and built-in Oshie/Chie/Hantei AI companions.', $now),
                2 => new Note(2, 'Try the JSON API', 'POST /api/notes with {"title": "...", "body": "..."} to see automatic PHP 8.5 DTO validation in action.', $now),
            ],
        ]);
    }

    /** @return array{next_id: int, notes: array<int, Note>} */
    private function loadState(): array
    {
        $raw = $this->storage->get(self::STORAGE_KEY);
        if (null === $raw) {
            return ['next_id' => 1, 'notes' => []];
        }
        try {
            $decoded = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['next_id' => 1, 'notes' => []];
        }
        if (!is_array($decoded)) {
            return ['next_id' => 1, 'notes' => []];
        }

        $nextId = is_int($decoded['next_id'] ?? null) ? $decoded['next_id'] : 1;
        $notes = [];
        if (isset($decoded['notes']) && is_array($decoded['notes'])) {
            foreach ($decoded['notes'] as $item) {
                if (
                    is_array($item)
                    && is_int($item['id'] ?? null)
                    && is_string($item['title'] ?? null)
                    && is_string($item['body'] ?? null)
                    && is_string($item['created_at'] ?? null)
                ) {
                    $notes[$item['id']] = new Note($item['id'], $item['title'], $item['body'], $item['created_at']);
                }
            }
        }

        return ['next_id' => $nextId, 'notes' => $notes];
    }

    /** @param array{next_id: int, notes: array<int, Note>} $state */
    private function saveState(array $state): void
    {
        $payload = [
            'next_id' => $state['next_id'],
            'notes' => array_values(array_map(static fn(Note $n): array => $n->toArray(), $state['notes'])),
        ];
        $this->storage->put(
            self::STORAGE_KEY,
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'application/json',
        );
    }
}
