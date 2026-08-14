<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Context;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use Kongono\Diagnostics\Exception\InvalidDiagnosticValueException;

/**
 * Defines one presentation-neutral field allowed by a closed context schema.
 *
 * Structural validity does not assign this schema to a diagnostic identifier;
 * that semantic authority remains with the originating package.
 */
#[Api(ApiStatus::Public)]
final readonly class DiagnosticFieldSchema
{
    private string $key;
    private string $label;
    private DiagnosticValueKind $kind;
    private DiagnosticDisclosure $disclosure;
    private bool $required;

    public function __construct(
        string $key,
        string $label,
        DiagnosticValueKind $kind,
        DiagnosticDisclosure $disclosure,
        bool $required = true,
    ) {
        if (
            '' === $key
            || strlen($key) > 64
            || 1 !== preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/D', $key)
        ) {
            throw new InvalidDiagnosticValueException(
                DiagnosticsDiagnostic::InvalidFieldKey,
                'Diagnostic field key must match lowercase ASCII snake case and contain at most 64 bytes.',
            );
        }

        if ('' === $label || 1 !== preg_match('//u', $label) || strlen($label) > 96) {
            throw new InvalidDiagnosticValueException(
                DiagnosticsDiagnostic::InvalidFieldLabel,
                'Diagnostic field label must be non-empty valid UTF-8 and contain at most 96 bytes.',
            );
        }

        $this->key = $key;
        $this->label = $label;
        $this->kind = $kind;
        $this->disclosure = $disclosure;
        $this->required = $required;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function kind(): DiagnosticValueKind
    {
        return $this->kind;
    }

    public function disclosure(): DiagnosticDisclosure
    {
        return $this->disclosure;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
