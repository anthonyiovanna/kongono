<?php

declare(strict_types=1);

namespace Kongono\Chie\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\InvalidChieProposalException;
use Kongono\Chie\Exception\InvalidChieValueException;
use Kongono\Chie\Exception\ProposalSpecUnavailableException;
use Kongono\Chie\Proposal\ChieProposalAuthority;
use Kongono\Chie\Proposal\ChieProposalRequest;
use Kongono\Chie\Proposal\SafeCandidateText;

/** Reads one explicit canonical proposal specification through a bounded stable-file gate. */
#[Api(ApiStatus::Internal)]
final class ProposalSpecReader
{
    private const MAX_SPEC_BYTES = 32768;

    public function read(string $path): ChieProposalRequest
    {
        if ('' === $path || str_contains($path, "\0")) {
            throw new ProposalSpecUnavailableException(ChieDiagnostic::ProposalSpecUnavailable, 'spec-path');
        }
        $withoutDrive = 1 === preg_match('/^[A-Za-z]:/', $path) ? substr($path, 2) : $path;
        if (str_contains($withoutDrive, ':')) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalSpecInvalid, 'spec-path');
        }
        $resolvedParent = realpath(dirname($path));
        $resolved = realpath($path);
        if (false === $resolvedParent || false === $resolved || !is_dir($resolvedParent) || !is_file($path) || is_link($path) || is_link(dirname($path))) {
            throw new ProposalSpecUnavailableException(ChieDiagnostic::ProposalSpecUnavailable, 'spec-path');
        }
        if ($this->normalize($resolved) !== $this->normalize($path) || $this->normalize(dirname($resolved)) !== $this->normalize($resolvedParent)) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalSpecInvalid, 'spec-path');
        }
        $before = @stat($path);
        if (false === $before || $before['size'] > self::MAX_SPEC_BYTES) {
            throw new ProposalSpecUnavailableException(ChieDiagnostic::ProposalSpecUnavailable, 'spec-read');
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw new ProposalSpecUnavailableException(ChieDiagnostic::ProposalSpecUnavailable, 'spec-read');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, self::MAX_SPEC_BYTES + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw new ProposalSpecUnavailableException(ChieDiagnostic::ProposalSpecUnavailable, 'spec-read');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > self::MAX_SPEC_BYTES) {
                    throw new InvalidChieProposalException(ChieDiagnostic::ProposalSpecInvalid, 'spec-shape');
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw new ProposalSpecUnavailableException(ChieDiagnostic::ProposalSpecUnavailable, 'spec-read');
        }

        return $this->decode($bytes);
    }

    private function decode(string $bytes): ChieProposalRequest
    {
        if (!str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n") || str_contains($bytes, "\r") || str_contains($bytes, "\0")) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalSpecInvalid, 'spec-shape');
        }
        try {
            $value = json_decode($bytes, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalSpecInvalid, 'spec-shape', $exception);
        }
        if (!is_array($value) || array_is_list($value)) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalSpecInvalid, 'spec-shape');
        }
        $expectedKeys = ['schema', 'version', 'rule', 'disclosure', 'target_path', 'base_sha256', 'before', 'after'];
        if (array_keys($value) !== $expectedKeys) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalSpecInvalid, 'spec-shape');
        }
        $canonical = json_encode($value, CanonicalJson::FLAGS, 16) . "\n";
        if ($canonical !== $bytes) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalSpecInvalid, 'spec-shape');
        }
        if (
            'kongono.chie-proposal-request' !== $value['schema']
            || 1 !== $value['version']
            || 'exact-line-replacement-v1' !== $value['rule']
            || 'safe' !== $value['disclosure']
            || !is_string($value['target_path'])
            || !is_string($value['base_sha256'])
            || !is_string($value['before'])
            || !is_string($value['after'])
        ) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalSpecInvalid, 'spec-shape');
        }
        try {
            return new ChieProposalRequest(
                new ChieProposalAuthority(),
                $value['target_path'],
                $value['base_sha256'],
                new SafeCandidateText($value['before']),
                new SafeCandidateText($value['after']),
            );
        } catch (InvalidChieValueException $exception) {
            throw new InvalidChieProposalException(ChieDiagnostic::ProposalSpecInvalid, 'spec-shape', $exception);
        }
    }

    /**
     * @param array<string|int, mixed> $before
     * @param array<string|int, mixed> $after
     */
    private function sameMetadata(array $before, array $after): bool
    {
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
