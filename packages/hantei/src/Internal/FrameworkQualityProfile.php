<?php

declare(strict_types=1);

namespace Kongono\Hantei\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Hantei\Diagnostic\HanteiDiagnostic;
use Kongono\Hantei\Exception\InvalidHanteiRequestException;
use Kongono\Hantei\HanteiCheckEvidence;

/** Loads the one package-owned profile and proves the root script graph is exact. */
#[Api(ApiStatus::Internal)]
final readonly class FrameworkQualityProfile
{
    /** @var list<array{check_id: string, composer_script: string}> */
    private array $checks;

    /** @param list<array{check_id: string, composer_script: string}> $checks */
    private function __construct(private string $sha256, array $checks)
    {
        $this->checks = [...$checks];
    }

    public static function load(string $frameworkRoot, string $profile = 'framework-quality-v1'): self
    {
        if ('framework-quality-v1' !== $profile) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::InvalidProfile, 'profile-mismatch');
        }
        $resource = dirname(__DIR__, 2) . '/resources/framework-quality-v1.json';
        $bytes = file_get_contents($resource);
        if (false === $bytes || strlen($bytes) > 65_536 || !str_ends_with($bytes, "\n") || str_contains($bytes, "\r") || str_starts_with($bytes, "\xEF\xBB\xBF")) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::InvalidProfile, 'profile-mismatch');
        }
        try {
            $decoded = json_decode($bytes, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::InvalidProfile, 'profile-mismatch', $exception);
        }
        if (!is_array($decoded) || array_is_list($decoded) || json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n" !== $bytes) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::InvalidProfile, 'profile-mismatch');
        }
        if (array_keys($decoded) !== ['schema', 'version', 'profile', 'checks', 'composer_scripts']
            || 'kongono.hantei-profile' !== $decoded['schema']
            || 1 !== $decoded['version']
            || 'framework-quality-v1' !== $decoded['profile']
            || !is_array($decoded['checks'])
            || !array_is_list($decoded['checks'])
            || !is_array($decoded['composer_scripts'])
            || array_is_list($decoded['composer_scripts'])) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::InvalidProfile, 'profile-mismatch');
        }

        $checks = [];
        foreach ($decoded['checks'] as $record) {
            if (!is_array($record) || array_keys($record) !== ['check_id', 'composer_script'] || !is_string($record['check_id']) || !is_string($record['composer_script'])) {
                throw new InvalidHanteiRequestException(HanteiDiagnostic::InvalidProfile, 'profile-mismatch');
            }
            $checks[] = ['check_id' => $record['check_id'], 'composer_script' => $record['composer_script']];
        }
        if (HanteiCheckEvidence::CHECKS !== array_column($checks, 'composer_script', 'check_id')) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::InvalidProfile, 'profile-mismatch');
        }

        $rootBytes = file_get_contents($frameworkRoot . DIRECTORY_SEPARATOR . 'composer.json');
        if (false === $rootBytes || strlen($rootBytes) > 1_048_576) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::InvalidProfile, 'profile-mismatch');
        }
        try {
            $root = json_decode($rootBytes, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::InvalidProfile, 'profile-mismatch', $exception);
        }
        if (!is_array($root) || !isset($root['scripts']) || !is_array($root['scripts'])) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::InvalidProfile, 'profile-mismatch');
        }
        foreach ($decoded['composer_scripts'] as $name => $definition) {
            if (!is_string($name) || !array_key_exists($name, $root['scripts']) || $root['scripts'][$name] !== $definition) {
                throw new InvalidHanteiRequestException(HanteiDiagnostic::InvalidProfile, 'profile-mismatch');
            }
        }
        if (($root['scripts']['hantei:verify'] ?? null) !== '@php packages/hantei/bin/kongono-hantei verify .') {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::InvalidProfile, 'profile-mismatch');
        }

        return new self(hash('sha256', $bytes), $checks);
    }

    public function sha256(): string
    {
        return $this->sha256;
    }

    /** @return list<array{check_id: string, composer_script: string}> */
    public function checks(): array
    {
        return [...$this->checks];
    }
}
