<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphComposition\Diagnostic\CompositionGraphDiagnostic;
use Throwable;

/** Reports one bounded all-or-nothing Composition projection failure. */
#[Api(ApiStatus::Experimental)]
final class InvalidCompositionGraphProjectionException extends CompositionGraphException
{
    private const array RECORD_KINDS = ['artifact', 'input', 'service', 'binding', 'root', 'argument', 'node', 'relation'];

    public static function invalidArtifact(string $violation, ?Throwable $previous = null): self
    {
        return new self(CompositionGraphDiagnostic::InvalidArtifact, self::context($violation, 'artifact'), $previous);
    }

    public static function unsupportedVersion(string $violation = 'composition_version'): self
    {
        return new self(CompositionGraphDiagnostic::UnsupportedVersion, self::context($violation, 'artifact'));
    }

    public static function invalidModel(
        string $violation,
        string $recordKind,
        ?int $count = null,
        ?int $position = null,
        ?Throwable $previous = null,
    ): self {
        return new self(
            CompositionGraphDiagnostic::InvalidModel,
            self::context($violation, $recordKind, $count, $position),
            $previous,
        );
    }

    public static function conflict(
        string $violation,
        string $recordKind,
        ?int $count = null,
        ?int $position = null,
        ?Throwable $previous = null,
    ): self {
        return new self(
            CompositionGraphDiagnostic::ProjectionConflict,
            self::context($violation, $recordKind, $count, $position),
            $previous,
        );
    }

    public static function limit(string $violation, string $recordKind, int $count): self
    {
        return new self(CompositionGraphDiagnostic::LimitExceeded, self::context($violation, $recordKind, $count));
    }

    /** @return array<string, string|int|bool> */
    private static function context(string $violation, string $recordKind, ?int $count = null, ?int $position = null): array
    {
        $context = [
            'violation' => 1 === preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $violation) ? $violation : 'unsupported_violation',
            'record_kind' => in_array($recordKind, self::RECORD_KINDS, true) ? $recordKind : 'artifact',
        ];
        if (null !== $count) {
            $context['count'] = max(0, $count);
        }
        if (null !== $position) {
            $context['position'] = max(0, $position);
        }

        return $context;
    }
}
