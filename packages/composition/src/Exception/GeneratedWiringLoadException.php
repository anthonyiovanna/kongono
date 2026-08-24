<?php

declare(strict_types=1);

namespace Kongono\Composition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Throwable;

/** Reports a fixed, non-disclosing generated-assembly admission failure. */
#[Api(ApiStatus::Experimental)]
final class GeneratedWiringLoadException extends CompositionException
{
    /** @var list<string> */
    private const VIOLATIONS = [
        'class_collision',
        'generated_file_invalid',
        'generated_file_name',
        'generated_shape',
        'input_count',
        'input_type',
        'start_result',
        'wiring_already_started',
    ];

    public function __construct(string $violation, ?Throwable $previous = null)
    {
        parent::__construct(
            CompositionDiagnostic::GeneratedWiringLoadFailed,
            in_array($violation, self::VIOLATIONS, true) ? $violation : 'generated_file_invalid',
            $previous,
        );
    }
}
