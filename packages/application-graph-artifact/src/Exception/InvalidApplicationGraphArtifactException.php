<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Diagnostic\ApplicationGraphArtifactDiagnostic;
use Throwable;

/** Reports malformed Graph input, artifact structure, or fixed bounds. */
#[Api(ApiStatus::Experimental)]
final class InvalidApplicationGraphArtifactException extends ApplicationGraphArtifactException
{
    /** @param array<string, string|int|bool> $extra */
    public static function graph(string $violation, string $recordKind = 'graph', array $extra = [], ?Throwable $previous = null): self
    {
        return new self(ApplicationGraphArtifactDiagnostic::InvalidGraphInput, self::context($violation, 'build', $recordKind, $extra), $previous);
    }

    /** @param array<string, string|int|bool> $extra */
    public static function artifact(string $violation, string $recordKind = 'artifact', array $extra = [], ?Throwable $previous = null): self
    {
        return new self(ApplicationGraphArtifactDiagnostic::InvalidArtifact, self::context($violation, 'validate', $recordKind, $extra), $previous);
    }

    public static function limit(string $violation, string $recordKind, int $count): self
    {
        return new self(ApplicationGraphArtifactDiagnostic::ArtifactLimitExceeded, self::context($violation, 'validate', $recordKind, ['count' => max(0, $count)]));
    }

    /** @param array<string, string|int|bool> $extra
     *  @return array<string, string|int|bool>
     */
    private static function context(string $violation, string $operation, string $recordKind, array $extra): array
    {
        $allowedOperations = ['build', 'validate', 'currentness', 'publish', 'load'];
        $allowedKinds = ['artifact', 'builder', 'graph', 'node', 'relation', 'field', 'provenance', 'path', 'target', 'temporary'];

        return ['violation' => 1 === preg_match('/\A[a-z0-9_]+\z/D', $violation) ? $violation : 'unsupported_violation', 'operation' => in_array($operation, $allowedOperations, true) ? $operation : 'validate', 'record_kind' => in_array($recordKind, $allowedKinds, true) ? $recordKind : 'artifact', ...$extra];
    }
}
