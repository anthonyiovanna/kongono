<?php

declare(strict_types=1);

namespace Kongono\Chie\Command;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Chie;
use Kongono\Chie\ChieOperation;
use Kongono\Chie\ChieRequest;
use Kongono\Chie\ChieResponse;
use Kongono\Chie\ChieStatus;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\DiagnosticObservation;
use Kongono\Chie\Exception\ArtifactStaleException;
use Kongono\Chie\Exception\ArtifactUnavailableException;
use Kongono\Chie\Exception\InvalidArtifactException;
use Kongono\Chie\Exception\InvalidChieProposalException;
use Kongono\Chie\Exception\InvalidChieRequestException;
use Kongono\Chie\Exception\ProposalSpecUnavailableException;
use Kongono\Chie\Exception\ProposalTargetStaleException;
use Kongono\Chie\Internal\ArtifactFileReader;
use Kongono\Chie\Internal\CanonicalJson;
use Kongono\Chie\Internal\ProposalSpecReader;
use Throwable;

/** Implements the exact non-interactive explain, diagnose, and propose command grammar. */
#[Api(ApiStatus::Internal)]
final class ChieCommand
{
    /**
     * @param list<string>  $arguments
     * @param resource|null $stdout
     * @param resource|null $stderr
     */
    public function run(array $arguments, $stdout = null, $stderr = null): int
    {
        $stdout ??= STDOUT;
        $stderr ??= STDERR;
        if (4 !== count($arguments) || !in_array($arguments[0], ['explain', 'diagnose', 'propose'], true)) {
            fwrite($stderr, CanonicalJson::encodeError(ChieDiagnostic::InvalidRequest, 'invalid-command-usage'));

            return 64;
        }

        if ('propose' === $arguments[0]) {
            return $this->propose($arguments, $stdout, $stderr);
        }

        $operation = 'explain' === $arguments[0] ? ChieOperation::Explain : ChieOperation::Diagnose;
        try {
            $request = ChieOperation::Explain === $operation
                ? ChieRequest::explain($arguments[3])
                : ChieRequest::diagnose(new DiagnosticObservation($arguments[3]));
        } catch (InvalidChieRequestException) {
            fwrite($stderr, CanonicalJson::encodeError(ChieDiagnostic::InvalidRequest, 'invalid-command-usage'));

            return 64;
        }

        try {
            $artifactBytes = (new ArtifactFileReader())->read($arguments[2]);
            $response = (new Chie())->respondCurrent($arguments[1], $artifactBytes, $request);
            fwrite($stdout, CanonicalJson::encodeResponse($response));

            return ChieStatus::Answered === $response->status() ? 0 : 65;
        } catch (ArtifactUnavailableException) {
            return $this->writeFailure($stdout, $operation, ChieStatus::Unavailable, 'artifact-unavailable', 66);
        } catch (ArtifactStaleException) {
            return $this->writeFailure($stdout, $operation, ChieStatus::Stale, 'artifact-stale', 65);
        } catch (InvalidArtifactException|InvalidChieRequestException) {
            return $this->writeFailure($stdout, $operation, ChieStatus::Blocked, 'artifact-invalid', 65);
        } catch (Throwable) {
            fwrite($stderr, CanonicalJson::encodeError(ChieDiagnostic::UnexpectedCommandFailure, 'unexpected-failure'));

            return 70;
        }
    }

    /**
     * @param list<string> $arguments
     * @param resource     $stdout
     * @param resource     $stderr
     */
    private function propose(array $arguments, $stdout, $stderr): int
    {
        try {
            $request = (new ProposalSpecReader())->read($arguments[3]);
        } catch (ProposalSpecUnavailableException) {
            return $this->writeProposalFailure($stdout, 'unavailable', 'proposal-unavailable', 66);
        } catch (InvalidChieProposalException) {
            fwrite($stderr, CanonicalJson::encodeError(ChieDiagnostic::ProposalSpecInvalid, 'spec-shape'));

            return 64;
        }

        try {
            $artifactBytes = (new ArtifactFileReader())->read($arguments[2]);
            $proposal = (new Chie())->proposeCurrent($arguments[1], $artifactBytes, $request);
            fwrite($stdout, CanonicalJson::encodeProposal($proposal));

            return 0;
        } catch (ArtifactUnavailableException) {
            return $this->writeProposalFailure($stdout, 'unavailable', 'proposal-unavailable', 66);
        } catch (ArtifactStaleException|ProposalTargetStaleException) {
            return $this->writeProposalFailure($stdout, 'stale', 'proposal-stale', 65);
        } catch (InvalidArtifactException|InvalidChieRequestException|InvalidChieProposalException) {
            return $this->writeProposalFailure($stdout, 'blocked', 'proposal-blocked', 65);
        } catch (Throwable) {
            fwrite($stderr, CanonicalJson::encodeError(ChieDiagnostic::UnexpectedCommandFailure, 'unexpected-failure'));

            return 70;
        }
    }

    /** @param resource $stdout */
    private function writeFailure($stdout, ChieOperation $operation, ChieStatus $status, string $uncertainty, int $exitCode): int
    {
        $response = ChieResponse::artifactFailure($operation, $status, $uncertainty);
        fwrite($stdout, CanonicalJson::encodeResponse($response));

        return $exitCode;
    }

    /** @param resource $stdout */
    private function writeProposalFailure($stdout, string $status, string $uncertainty, int $exitCode): int
    {
        fwrite($stdout, CanonicalJson::encodeProposalFailure($status, $uncertainty));

        return $exitCode;
    }
}
