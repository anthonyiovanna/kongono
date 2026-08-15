<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Developer;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic;
use Kongono\DiagnosticPresentation\Exception\InvalidDiagnosticProjectionException;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Stores one immutable field copied from an already bounded diagnostic context. */
#[Api(ApiStatus::Experimental)]
final readonly class TrustedDeveloperDiagnosticField
{
    private string $key;
    private string $label;
    private DiagnosticValueKind $kind;
    private string|int|bool $value;
    private DiagnosticDisclosure $disclosure;
    private ?bool $textWasTruncated;

    /**
     * @throws InvalidDiagnosticProjectionException When any field value violates the trusted projection structure.
     */
    public function __construct(
        string $key,
        string $label,
        DiagnosticValueKind $kind,
        string|int|bool $value,
        DiagnosticDisclosure $disclosure,
        ?bool $textWasTruncated,
    ) {
        if (
            '' === $key
            || strlen($key) > 64
            || 1 !== preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/D', $key)
        ) {
            throw new InvalidDiagnosticProjectionException(
                DiagnosticPresentationDiagnostic::InvalidDiagnosticFieldKey,
                'Projected diagnostic field key must use lowercase ASCII snake case and contain at most 64 bytes.',
            );
        }

        if ('' === $label || 1 !== preg_match('//u', $label) || strlen($label) > 96) {
            throw new InvalidDiagnosticProjectionException(
                DiagnosticPresentationDiagnostic::InvalidDiagnosticFieldLabel,
                'Projected diagnostic field label must be non-empty valid UTF-8 and contain at most 96 bytes.',
            );
        }

        if (DiagnosticValueKind::Text === $kind) {
            if (!is_string($value)) {
                throw new InvalidDiagnosticProjectionException(
                    DiagnosticPresentationDiagnostic::DiagnosticFieldValueKindMismatch,
                    sprintf('Projected diagnostic field "%s" value does not match kind %s.', $key, $kind->value),
                );
            }

            if (!is_bool($textWasTruncated)) {
                throw new InvalidDiagnosticProjectionException(
                    DiagnosticPresentationDiagnostic::InvalidDiagnosticTruncationState,
                    sprintf('Projected diagnostic field "%s" has an invalid truncation-state shape.', $key),
                );
            }

            if (strlen($value) > 512 || 1 !== preg_match('/^[\x20-\x7E]*$/D', $value)) {
                throw new InvalidDiagnosticProjectionException(
                    DiagnosticPresentationDiagnostic::InvalidDiagnosticTextRepresentation,
                    sprintf('Projected diagnostic field "%s" text must be printable ASCII containing at most 512 bytes.', $key),
                );
            }

            if ($textWasTruncated && !str_ends_with($value, '...[truncated]')) {
                throw new InvalidDiagnosticProjectionException(
                    DiagnosticPresentationDiagnostic::InvalidDiagnosticTruncationState,
                    sprintf('Projected diagnostic field "%s" marked truncated must end with the truncation suffix.', $key),
                );
            }
        } elseif (DiagnosticValueKind::Integer === $kind) {
            if (!is_int($value)) {
                throw new InvalidDiagnosticProjectionException(
                    DiagnosticPresentationDiagnostic::DiagnosticFieldValueKindMismatch,
                    sprintf('Projected diagnostic field "%s" value does not match kind %s.', $key, $kind->value),
                );
            }

            if (null !== $textWasTruncated) {
                throw new InvalidDiagnosticProjectionException(
                    DiagnosticPresentationDiagnostic::InvalidDiagnosticTruncationState,
                    sprintf('Projected diagnostic field "%s" has an invalid truncation-state shape.', $key),
                );
            }
        } else {
            if (!is_bool($value)) {
                throw new InvalidDiagnosticProjectionException(
                    DiagnosticPresentationDiagnostic::DiagnosticFieldValueKindMismatch,
                    sprintf('Projected diagnostic field "%s" value does not match kind %s.', $key, $kind->value),
                );
            }

            if (null !== $textWasTruncated) {
                throw new InvalidDiagnosticProjectionException(
                    DiagnosticPresentationDiagnostic::InvalidDiagnosticTruncationState,
                    sprintf('Projected diagnostic field "%s" has an invalid truncation-state shape.', $key),
                );
            }
        }

        $this->key = $key;
        $this->label = $label;
        $this->kind = $kind;
        $this->value = $value;
        $this->disclosure = $disclosure;
        $this->textWasTruncated = $textWasTruncated;
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

    public function value(): string|int|bool
    {
        return $this->value;
    }

    public function disclosure(): DiagnosticDisclosure
    {
        return $this->disclosure;
    }

    public function textWasTruncated(): ?bool
    {
        return $this->textWasTruncated;
    }

}
