<?php

declare(strict_types=1);

namespace Kongono\Chie\Proposal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\InvalidChieProposalException;

/** Holds one explicit exact-line replacement intent; it grants no write authority. */
#[Api(ApiStatus::Experimental)]
final readonly class ChieProposalRequest
{
    public function __construct(
        private ChieProposalAuthority $authority,
        private string $targetPath,
        private string $baseSha256,
        private SafeCandidateText $before,
        private SafeCandidateText $after,
    ) {
        if (!$this->validPath($targetPath)) {
            throw new InvalidChieProposalException(ChieDiagnostic::InvalidProposalRequest, 'target-path');
        }
        if (1 !== preg_match('/^[A-F0-9]{64}$/D', $baseSha256)) {
            throw new InvalidChieProposalException(ChieDiagnostic::InvalidProposalRequest, 'target-sha');
        }
        if ($before->text() === $after->text()) {
            throw new InvalidChieProposalException(ChieDiagnostic::InvalidProposalRequest, 'after-shape');
        }
    }

    public function authority(): ChieProposalAuthority
    {
        return $this->authority;
    }

    public function targetPath(): string
    {
        return $this->targetPath;
    }

    public function baseSha256(): string
    {
        return $this->baseSha256;
    }

    public function before(): SafeCandidateText
    {
        return $this->before;
    }

    public function after(): SafeCandidateText
    {
        return $this->after;
    }

    private function validPath(string $path): bool
    {
        if ('' === $path || strlen($path) > 240 || str_contains($path, '\\') || str_contains($path, ':') || str_contains($path, '%')) {
            return false;
        }
        if (str_starts_with($path, '/') || str_contains($path, "\0") || 1 === preg_match('/[\x00-\x1F\x7F]/', $path)) {
            return false;
        }
        $segments = explode('/', $path);

        return !in_array('', $segments, true) && !in_array('.', $segments, true) && !in_array('..', $segments, true);
    }
}
