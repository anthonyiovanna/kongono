<?php

declare(strict_types=1);

namespace Kongono\Chie\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\InvalidArtifactException;

/** Loads and proves the exact package-owned six-topic catalog without discovery. */
#[Api(ApiStatus::Internal)]
final class TopicCatalog
{
    private const RESOURCE = __DIR__ . '/../../resources/topic-catalog.json';

    /** @var list<array{token: string, path: string, heading: string}> */
    private const EXPECTED = [
        ['token' => 'developer-intelligence', 'path' => 'docs/architecture/developer-intelligence.md', 'heading' => '## Roles and promises'],
        ['token' => 'error-handling', 'path' => 'docs/engineering/error-handling.md', 'heading' => '## Purpose and current status'],
        ['token' => 'knowledge-artifact', 'path' => 'docs/architecture/framework-knowledge-artifact.md', 'heading' => '## Purpose and status'],
        ['token' => 'knowledge-foundation', 'path' => 'docs/architecture/knowledge-foundation.md', 'heading' => '## Purpose and status'],
        ['token' => 'phase-4-roadmap', 'path' => 'docs/architecture/phase-4-roadmap.md', 'heading' => '## Fixed A-H roadmap'],
        ['token' => 'php-support', 'path' => 'docs/engineering/php-support-policy.md', 'heading' => '## Accepted rolling one-minor policy'],
    ];

    /** @var list<array{token: string, path: string, heading: string}>|null */
    private ?array $records = null;

    /** @return array{path: string, heading: string}|null */
    public function record(string $token): ?array
    {
        foreach ($this->records() as $record) {
            if ($record['token'] === $token) {
                return ['path' => $record['path'], 'heading' => $record['heading']];
            }
        }

        return null;
    }

    /** @return list<array{token: string, path: string, heading: string}> */
    public function records(): array
    {
        if (null !== $this->records) {
            return [...$this->records];
        }
        if (!is_file(self::RESOURCE) || is_link(self::RESOURCE)) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'topic-catalog');
        }
        $bytes = file_get_contents(self::RESOURCE);
        if (false === $bytes || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'topic-catalog');
        }
        try {
            $data = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'topic-catalog', $exception);
        }
        if (!is_array($data) || array_is_list($data) || array_keys($data) !== ['schema', 'version', 'topics']) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'topic-catalog');
        }
        if ('kongono.chie-topic-catalog' !== $data['schema'] || 1 !== $data['version'] || !is_array($data['topics']) || !array_is_list($data['topics'])) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'topic-catalog');
        }

        $records = [];
        foreach ($data['topics'] as $record) {
            if (!is_array($record) || array_is_list($record) || array_keys($record) !== ['token', 'path', 'heading']) {
                throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'topic-catalog');
            }
            if (!is_string($record['token']) || !is_string($record['path']) || !is_string($record['heading'])) {
                throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'topic-catalog');
            }
            $records[] = ['token' => $record['token'], 'path' => $record['path'], 'heading' => $record['heading']];
        }
        if ($records !== self::EXPECTED) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'topic-catalog');
        }
        if (json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n" !== $bytes) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'topic-catalog');
        }
        $this->records = $records;

        return [...$this->records];
    }
}
