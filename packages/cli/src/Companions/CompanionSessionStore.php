<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\SecretScrubber;

/**
 * Local, secret-scrubbed, gitignored session store under `var/kongono/companions/`.
 * Zero mutable static state (safe on persistent runtimes) and strict symlink containment.
 */
final readonly class CompanionSessionStore
{
    private const int MAX_HISTORY = 25;

    private string $dir;

    public function __construct(string $projectRoot)
    {
        $resolved = realpath($projectRoot);
        if (false === $resolved || !is_dir($resolved)) {
            throw new CoreException(sprintf('Project root directory does not exist: "%s".', $projectRoot));
        }
        $this->dir = $resolved . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'kongono' . DIRECTORY_SEPARATOR . 'companions';
    }

    public function recordOshieLesson(string $lessonId): void
    {
        $clean = trim($lessonId);
        if ('' === $clean) {
            return;
        }
        $data = $this->readJson('oshie.json');
        $completed = is_array($data['completed_lessons'] ?? null)
            ? array_values(array_filter($data['completed_lessons'], 'is_string'))
            : [];
        if (!in_array($clean, $completed, true)) {
            $completed[] = $clean;
        }
        $exercises = is_array($data['completed_exercises'] ?? null)
            ? array_values(array_filter($data['completed_exercises'], 'is_string'))
            : [];

        $this->writeJson('oshie.json', [
            'completed_lessons' => $completed,
            'completed_exercises' => $exercises,
            'updated_at' => gmdate('c'),
        ]);
    }

    public function recordOshieExercise(string $exerciseId): void
    {
        $clean = trim($exerciseId);
        if ('' === $clean) {
            return;
        }
        $data = $this->readJson('oshie.json');
        $completed = is_array($data['completed_lessons'] ?? null)
            ? array_values(array_filter($data['completed_lessons'], 'is_string'))
            : [];
        $exercises = is_array($data['completed_exercises'] ?? null)
            ? array_values(array_filter($data['completed_exercises'], 'is_string'))
            : [];
        if (!in_array($clean, $exercises, true)) {
            $exercises[] = $clean;
        }
        if (!in_array($clean, $completed, true)) {
            $completed[] = $clean;
        }

        $this->writeJson('oshie.json', [
            'completed_lessons' => $completed,
            'completed_exercises' => $exercises,
            'updated_at' => gmdate('c'),
        ]);
    }

    /** @return array{completed_lessons: list<string>, completed_exercises: list<string>, updated_at: ?string} */
    public function oshieProgress(): array
    {
        $data = $this->readJson('oshie.json');
        $completed = is_array($data['completed_lessons'] ?? null)
            ? array_values(array_filter($data['completed_lessons'], 'is_string'))
            : [];
        $exercises = is_array($data['completed_exercises'] ?? null)
            ? array_values(array_filter($data['completed_exercises'], 'is_string'))
            : [];
        $updated = is_string($data['updated_at'] ?? null) ? $data['updated_at'] : null;

        return [
            'completed_lessons' => $completed,
            'completed_exercises' => $exercises,
            'updated_at' => $updated,
        ];
    }

    /**
     * Persist the latest Chie build plan summary so `kongo chie --continue "..."` can iterate on it.
     *
     * @param list<array{path: string, action: string, explanation: string, content: string}> $changes
     * @param list<string>                                                                    $applied
     */
    public function saveChiePlan(string $instruction, string $summary, array $changes, array $applied = []): void
    {
        $files = [];
        $explanations = [];
        foreach ($changes as $change) {
            $files[] = $change['path'];
            $explanations[] = sprintf('- (%s) %s: %s', $change['action'], $change['path'], SecretScrubber::scrub($change['explanation']));
        }

        $entry = [
            'timestamp' => gmdate('c'),
            'instruction' => substr(SecretScrubber::scrub($instruction), 0, 1000),
            'summary' => substr(SecretScrubber::scrub($summary), 0, 1000),
            'files' => $files,
            'applied' => $applied,
            'context_prompt' => implode("\n", [
                'Prior instruction: ' . SecretScrubber::scrub($instruction),
                'Prior summary: ' . SecretScrubber::scrub($summary),
                'Files touched/proposed:',
                ...$explanations,
            ]),
        ];

        $data = $this->readJson('chie.json');
        $sessions = is_array($data['sessions'] ?? null) ? array_values($data['sessions']) : [];
        $sessions[] = $entry;
        if (count($sessions) > self::MAX_HISTORY) {
            $sessions = array_slice($sessions, -self::MAX_HISTORY);
        }

        $this->writeJson('chie.json', ['sessions' => $sessions]);
    }

    /**
     * @return array{
     *     timestamp: string,
     *     instruction: string,
     *     summary: string,
     *     files: list<string>,
     *     applied: list<string>,
     *     context_prompt: string
     * }|null
     */
    public function lastChieSession(): ?array
    {
        $data = $this->readJson('chie.json');
        $sessions = is_array($data['sessions'] ?? null) ? array_values($data['sessions']) : [];
        if ([] === $sessions) {
            return null;
        }
        $last = $sessions[count($sessions) - 1];
        if (!is_array($last)) {
            return null;
        }

        return [
            'timestamp' => is_string($last['timestamp'] ?? null) ? $last['timestamp'] : '',
            'instruction' => is_string($last['instruction'] ?? null) ? $last['instruction'] : '',
            'summary' => is_string($last['summary'] ?? null) ? $last['summary'] : '',
            'files' => is_array($last['files'] ?? null) ? array_values(array_filter($last['files'], 'is_string')) : [],
            'applied' => is_array($last['applied'] ?? null) ? array_values(array_filter($last['applied'], 'is_string')) : [],
            'context_prompt' => is_string($last['context_prompt'] ?? null) ? $last['context_prompt'] : '',
        ];
    }

    public function recordHanteiDiagnosis(string $kind, string $subject, string $diagnosis, ?string $provider = null): void
    {
        $data = $this->readJson('hantei.json');
        $entries = is_array($data['history'] ?? null) ? array_values($data['history']) : [];
        $entries[] = [
            'timestamp' => gmdate('c'),
            'kind' => substr(trim($kind), 0, 32),
            'subject' => substr(SecretScrubber::scrub(trim($subject)), 0, 240),
            'diagnosis_excerpt' => substr(SecretScrubber::scrub(trim($diagnosis)), 0, 600),
            'provider' => null !== $provider ? substr($provider, 0, 64) : null,
        ];
        if (count($entries) > self::MAX_HISTORY) {
            $entries = array_slice($entries, -self::MAX_HISTORY);
        }

        $this->writeJson('hantei.json', ['history' => $entries]);
    }

    /**
     * Return recent Hantei diagnoses (most recent first).
     *
     * @return list<array{timestamp: string, kind: string, subject: string, diagnosis_excerpt: string, provider: ?string}>
     */
    public function hanteiHistory(int $limit = 10): array
    {
        $data = $this->readJson('hantei.json');
        $entries = is_array($data['history'] ?? null) ? array_values($data['history']) : [];
        $entries = array_reverse($entries);
        $out = [];

        foreach (array_slice($entries, 0, max(1, $limit)) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $out[] = [
                'timestamp' => is_string($item['timestamp'] ?? null) ? $item['timestamp'] : '',
                'kind' => is_string($item['kind'] ?? null) ? $item['kind'] : 'debug',
                'subject' => is_string($item['subject'] ?? null) ? $item['subject'] : '',
                'diagnosis_excerpt' => is_string($item['diagnosis_excerpt'] ?? null) ? $item['diagnosis_excerpt'] : '',
                'provider' => is_string($item['provider'] ?? null) ? $item['provider'] : null,
            ];
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private function readJson(string $fileName): array
    {
        $path = $this->dir . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($path) || is_link($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if (false === $raw || '' === trim($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        /** @var array<string, mixed> $out */
        $out = [];
        foreach ($decoded as $k => $v) {
            $out[(string) $k] = $v;
        }

        return $out;
    }

    /** @param array<string, mixed> $payload */
    private function writeJson(string $fileName, array $payload): void
    {
        $this->ensureDirectory();
        $path = $this->dir . DIRECTORY_SEPARATOR . $fileName;
        if (is_link($path)) {
            throw new CoreException(sprintf('Refusing to write companion session through symlink "%s".', $fileName));
        }

        $scrubbed = SecretScrubber::scrubArray($payload);
        $json = json_encode($scrubbed, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($path, $json . "\n", LOCK_EX);
    }

    private function ensureDirectory(): void
    {
        if (is_link($this->dir)) {
            throw new CoreException('Companion session directory cannot be a symbolic link.');
        }
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0o775, true) && !is_dir($this->dir)) {
            throw new CoreException(sprintf('Unable to create companion session directory "%s".', $this->dir));
        }
        $gitignore = $this->dir . DIRECTORY_SEPARATOR . '.gitignore';
        if (!is_file($gitignore) && !is_link($gitignore)) {
            @file_put_contents($gitignore, "*\n!.gitignore\n", LOCK_EX);
        }
    }
}
