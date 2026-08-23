<?php

declare(strict_types=1);

namespace Kongono\Hantei;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Hantei\Exception\InvalidHanteiValueException;
use Kongono\Hantei\Internal\VerdictEvaluator;

/** Represents one immutable verdict bound to an exact profile and framework subject. */
#[Api(ApiStatus::Experimental)]
final readonly class HanteiReport
{
    /** @var list<HanteiCheckEvidence> */
    private array $checks;

    /** @var list<string> */
    private array $uncertainties;

    /**
     * @param array<array-key, mixed> $checks
     * @param array<array-key, mixed> $uncertainties
     */
    public function __construct(
        private HanteiVerdict $verdict,
        private ?string $profileSha256,
        private ?string $subjectSha256,
        private ?string $frameworkArtifactSha256,
        private ?string $sourceSetSha256,
        private string $phpVersion,
        private ?string $composerBinarySha256,
        private ?string $gitVersion,
        array $checks,
        array $uncertainties,
    ) {
        if (!array_is_list($checks) || !array_is_list($uncertainties)) {
            throw InvalidHanteiValueException::report();
        }
        $validatedChecks = [];
        foreach ($checks as $check) {
            if (!$check instanceof HanteiCheckEvidence) {
                throw InvalidHanteiValueException::report();
            }
            $validatedChecks[] = $check;
        }
        if (array_keys(HanteiCheckEvidence::CHECKS) !== array_map(static fn(HanteiCheckEvidence $check): string => $check->checkId(), $validatedChecks)) {
            throw InvalidHanteiValueException::report();
        }
        $validatedUncertainties = [];
        foreach ($uncertainties as $uncertainty) {
            if (!is_string($uncertainty) || !in_array($uncertainty, HanteiCheckEvidence::REASONS, true) || in_array($uncertainty, $validatedUncertainties, true)) {
                throw InvalidHanteiValueException::report();
            }
            $validatedUncertainties[] = $uncertainty;
        }
        $derivedUncertainties = [];
        foreach ($validatedChecks as $check) {
            if (null !== $check->reason() && !in_array($check->reason(), $derivedUncertainties, true)) {
                $derivedUncertainties[] = $check->reason();
            }
        }
        $validIdentity = static fn(?string $digest): bool => null === $digest || 1 === preg_match('/\A[a-f0-9]{64}\z/D', $digest);
        $validPassIdentities = HanteiVerdict::Pass !== $verdict || !in_array(null, [$profileSha256, $subjectSha256, $frameworkArtifactSha256, $sourceSetSha256, $composerBinarySha256, $gitVersion], true);
        if ((new VerdictEvaluator())->evaluate($validatedChecks) !== $verdict
            || $derivedUncertainties !== $validatedUncertainties
            || !$validIdentity($profileSha256)
            || !$validIdentity($subjectSha256)
            || !$validIdentity($frameworkArtifactSha256)
            || !$validIdentity($sourceSetSha256)
            || !$validIdentity($composerBinarySha256)
            || '' === $phpVersion
            || !$validPassIdentities) {
            throw InvalidHanteiValueException::report();
        }
        $this->checks = [...$validatedChecks];
        $this->uncertainties = [...$validatedUncertainties];
    }

    public function verdict(): HanteiVerdict
    {
        return $this->verdict;
    }

    /** @return list<HanteiCheckEvidence> */
    public function checks(): array
    {
        return [...$this->checks];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'kongono.hantei-verdict',
            'version' => 1,
            'role' => 'hantei',
            'verdict' => $this->verdict->value,
            'profile' => 'framework-quality-v1',
            'profile_sha256' => $this->profileSha256,
            'subject_sha256' => $this->subjectSha256,
            'framework_artifact_sha256' => $this->frameworkArtifactSha256,
            'source_set_sha256' => $this->sourceSetSha256,
            'php_version' => $this->phpVersion,
            'composer_binary_sha256' => $this->composerBinarySha256,
            'git_version' => $this->gitVersion,
            'checks' => array_map(static fn(HanteiCheckEvidence $check): array => $check->toArray(), $this->checks),
            'uncertainties' => [...$this->uncertainties],
        ];
    }
}
