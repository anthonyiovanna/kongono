<?php

declare(strict_types=1);

namespace Kongono\Oshie\Command;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Oshie\Diagnostic\OshieDiagnostic;
use Kongono\Oshie\Exception\ArtifactStaleException;
use Kongono\Oshie\Exception\ArtifactUnavailableException;
use Kongono\Oshie\Exception\InvalidArtifactException;
use Kongono\Oshie\Exception\InvalidOshieRequestException;
use Kongono\Oshie\Internal\ArtifactFileReader;
use Kongono\Oshie\Internal\CanonicalJson;
use Kongono\Oshie\Oshie;
use Kongono\Oshie\OshieOperation;
use Kongono\Oshie\OshieRequest;
use Kongono\Oshie\OshieResponse;
use Kongono\Oshie\OshieStatus;
use Throwable;

/** Implements the exact non-interactive closed Oshie command grammar. */
#[Api(ApiStatus::Internal)]
final class OshieCommand
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
        try {
            [$request, $root, $path] = $this->parse($arguments);
        } catch (InvalidOshieRequestException) {
            fwrite($stderr, CanonicalJson::encodeError(OshieDiagnostic::InvalidRequest, 'invalid-command-usage'));
            return 64;
        }
        $operation = $request->operation();
        try {
            $response = (new Oshie())->respondCurrent($root, (new ArtifactFileReader())->read($path), $request);
            fwrite($stdout, CanonicalJson::encodeResponse($response));
            return match ($response->status()) {
                OshieStatus::Available, OshieStatus::Taught, OshieStatus::Practice, OshieStatus::Correct => 0,
                OshieStatus::Incorrect => 1,
                default => 65,
            };
        } catch (ArtifactUnavailableException) {
            return $this->failure($stdout, $operation, OshieStatus::Unavailable, 'artifact-unavailable', 66);
        } catch (ArtifactStaleException) {
            return $this->failure($stdout, $operation, OshieStatus::Stale, 'artifact-stale', 65);
        } catch (InvalidArtifactException|InvalidOshieRequestException) {
            return $this->failure($stdout, $operation, OshieStatus::Blocked, 'artifact-invalid', 65);
        } catch (Throwable) {
            fwrite($stderr, CanonicalJson::encodeError(OshieDiagnostic::UnexpectedCommandFailure, 'unexpected-failure'));
            return 70;
        }
    }

    /**
     * @param list<string> $arguments
     * @return array{OshieRequest, string, string}
     */
    private function parse(array $arguments): array
    {
        if ([] === $arguments) {
            throw new InvalidOshieRequestException(OshieDiagnostic::InvalidRequest, 'invalid-command-usage');
        }
        $request = match ($arguments[0]) {
            'catalog' => 3 === count($arguments) ? OshieRequest::catalog() : null,
            'lesson' => 4 === count($arguments) ? OshieRequest::lesson($arguments[3]) : null,
            'exercise' => 4 === count($arguments) ? OshieRequest::exercise($arguments[3]) : null,
            'check' => 5 === count($arguments) ? OshieRequest::check($arguments[3], $arguments[4]) : null,
            default => null,
        };
        if (null === $request) {
            throw new InvalidOshieRequestException(OshieDiagnostic::InvalidRequest, 'invalid-command-usage');
        }
        return [$request, $arguments[1], $arguments[2]];
    }

    /** @param resource $stdout */
    private function failure($stdout, OshieOperation $operation, OshieStatus $status, string $uncertainty, int $exitCode): int
    {
        fwrite($stdout, CanonicalJson::encodeResponse(OshieResponse::artifactFailure($operation, $status, $uncertainty)));
        return $exitCode;
    }
}
