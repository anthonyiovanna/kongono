<?php

declare(strict_types=1);

namespace Kongono\Chie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\InvalidChieRequestException;

/** Stores one syntactically Safe caller-supplied diagnostic identifier. */
#[Api(ApiStatus::Experimental)]
final readonly class DiagnosticObservation
{
    public function __construct(private string $identifier)
    {
        if (strlen($identifier) > 64 || 1 !== preg_match('/^KGN-[A-Z][A-Z0-9-]{0,47}-[0-9]{4}$/D', $identifier)) {
            throw new InvalidChieRequestException(ChieDiagnostic::InvalidRequest, 'diagnostic-grammar');
        }
    }

    public function identifier(): string
    {
        return $this->identifier;
    }
}
