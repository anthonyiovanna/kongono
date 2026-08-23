<?php

declare(strict_types=1);

namespace Kongono\Chie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Exception\InvalidChieValueException;

/** Represents one detached canonical response with visible state and bounded evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class ChieResponse
{
    /** @var list<ChieClaim> */
    private array $claims;

    /** @var list<string> */
    private array $uncertainties;

    /**
     * @param array<array-key, mixed> $claims
     * @param array<array-key, mixed> $uncertainties
     */
    private function __construct(
        private ChieStatus $status,
        private ChieOperation $operation,
        private ?string $frameworkArtifactSha256,
        private ?string $sourceSetSha256,
        array $claims,
        array $uncertainties,
    ) {
        $validDigests = null !== $frameworkArtifactSha256
            && null !== $sourceSetSha256
            && 1 === preg_match('/\A[a-f0-9]{64}\z/D', $frameworkArtifactSha256)
            && 1 === preg_match('/\A[a-f0-9]{64}\z/D', $sourceSetSha256);
        if (!array_is_list($claims) || !array_is_list($uncertainties)) {
            throw InvalidChieValueException::response();
        }
        $validatedClaims = [];
        foreach ($claims as $claim) {
            if (!$claim instanceof ChieClaim) {
                throw InvalidChieValueException::response();
            }
            $validatedClaims[] = $claim;
        }
        $validatedUncertainties = [];
        foreach ($uncertainties as $uncertainty) {
            if (!is_string($uncertainty)) {
                throw InvalidChieValueException::response();
            }
            $validatedUncertainties[] = $uncertainty;
        }
        $validState = match ($status) {
            ChieStatus::Answered => $validDigests && count($validatedClaims) >= 1 && count($validatedClaims) <= 9 && [] === $validatedUncertainties,
            ChieStatus::Unsupported => $validDigests
                && [] === $validatedClaims
                && [match ($operation) {
                    ChieOperation::Explain => 'topic-unsupported',
                    ChieOperation::Diagnose => 'diagnostic-not-found',
                }] === $validatedUncertainties,
            ChieStatus::Unavailable => null === $frameworkArtifactSha256 && null === $sourceSetSha256 && [] === $validatedClaims && ['artifact-unavailable'] === $validatedUncertainties,
            ChieStatus::Stale => null === $frameworkArtifactSha256 && null === $sourceSetSha256 && [] === $validatedClaims && ['artifact-stale'] === $validatedUncertainties,
            ChieStatus::Blocked => null === $frameworkArtifactSha256 && null === $sourceSetSha256 && [] === $validatedClaims && ['artifact-invalid'] === $validatedUncertainties,
        };
        if (!$validState) {
            throw InvalidChieValueException::response();
        }
        if (null !== $sourceSetSha256) {
            foreach ($validatedClaims as $claim) {
                foreach ($claim->citations() as $citation) {
                    if (substr($citation->reference(), 14, 64) !== $sourceSetSha256) {
                        throw InvalidChieValueException::response();
                    }
                }
            }
        }
        $this->claims = [...$validatedClaims];
        $this->uncertainties = [...$validatedUncertainties];
    }

    /** @param list<ChieClaim> $claims */
    public static function answered(ChieOperation $operation, string $artifactSha256, string $sourceSetSha256, array $claims): self
    {
        return new self(ChieStatus::Answered, $operation, $artifactSha256, $sourceSetSha256, $claims, []);
    }

    public static function unsupported(ChieOperation $operation, string $artifactSha256, string $sourceSetSha256, string $uncertainty): self
    {
        return new self(ChieStatus::Unsupported, $operation, $artifactSha256, $sourceSetSha256, [], [$uncertainty]);
    }

    public static function artifactFailure(ChieOperation $operation, ChieStatus $status, string $uncertainty): self
    {
        return new self($status, $operation, null, null, [], [$uncertainty]);
    }

    public function status(): ChieStatus
    {
        return $this->status;
    }

    public function operation(): ChieOperation
    {
        return $this->operation;
    }

    public function frameworkArtifactSha256(): ?string
    {
        return $this->frameworkArtifactSha256;
    }

    public function sourceSetSha256(): ?string
    {
        return $this->sourceSetSha256;
    }

    /** @return list<ChieClaim> */
    public function claims(): array
    {
        return [...$this->claims];
    }

    /** @return list<string> */
    public function uncertainties(): array
    {
        return [...$this->uncertainties];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'kongono.chie-response',
            'version' => 1,
            'role' => 'chie',
            'status' => $this->status->value,
            'operation' => $this->operation->value,
            'framework_artifact_sha256' => $this->frameworkArtifactSha256,
            'source_set_sha256' => $this->sourceSetSha256,
            'claims' => array_map(static fn(ChieClaim $claim): array => $claim->toArray(), $this->claims),
            'uncertainties' => [...$this->uncertainties],
        ];
    }
}
