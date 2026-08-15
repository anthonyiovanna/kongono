<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Developer;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic;
use Kongono\DiagnosticPresentation\Exception\InvalidDiagnosticProjectionException;

/** Stores one detached trusted-developer snapshot without retaining its source. */
#[Api(ApiStatus::Experimental)]
final readonly class TrustedDeveloperDiagnostic
{
    private DeveloperDiagnosticOwner $owner;
    private string $identifier;
    private string $title;
    private string $operation;
    private string $violatedInvariant;
    private string $remediation;

    /** @var list<TrustedDeveloperDiagnosticField> */
    private array $fields;

    /**
     * @param array<array-key, mixed> $fields
     *
     * @throws InvalidDiagnosticProjectionException When the supplied diagnostic structure is invalid.
     */
    public function __construct(
        DeveloperDiagnosticOwner $owner,
        string $identifier,
        string $title,
        string $operation,
        string $violatedInvariant,
        string $remediation,
        array $fields,
    ) {
        $identifierPattern = match ($owner) {
            DeveloperDiagnosticOwner::Kernel => '/^KGN-KERNEL-[0-9]{4}$/D',
            DeveloperDiagnosticOwner::ApplicationGraph => '/^KGN-GRAPH-[0-9]{4}$/D',
            DeveloperDiagnosticOwner::DependencyInjection => '/^KGN-DI-[0-9]{4}$/D',
        };

        if (1 !== preg_match($identifierPattern, $identifier)) {
            throw new InvalidDiagnosticProjectionException(
                DiagnosticPresentationDiagnostic::InvalidDiagnosticIdentifier,
                sprintf('Projected diagnostic identifier must match the exact %s owner prefix and four digits.', $owner->value),
            );
        }

        if ('' === $title || 1 !== preg_match('//u', $title) || strlen($title) > 160) {
            throw new InvalidDiagnosticProjectionException(
                DiagnosticPresentationDiagnostic::InvalidDiagnosticTitle,
                'Projected diagnostic title must be non-empty valid UTF-8 and contain at most 160 bytes.',
            );
        }

        if ('' === $operation || 1 !== preg_match('//u', $operation) || strlen($operation) > 160) {
            throw new InvalidDiagnosticProjectionException(
                DiagnosticPresentationDiagnostic::InvalidDiagnosticOperation,
                'Projected diagnostic operation must be non-empty valid UTF-8 and contain at most 160 bytes.',
            );
        }

        if (
            '' === $violatedInvariant
            || 1 !== preg_match('//u', $violatedInvariant)
            || strlen($violatedInvariant) > 256
        ) {
            throw new InvalidDiagnosticProjectionException(
                DiagnosticPresentationDiagnostic::InvalidDiagnosticInvariant,
                'Projected diagnostic invariant must be non-empty valid UTF-8 and contain at most 256 bytes.',
            );
        }

        if ('' === $remediation || 1 !== preg_match('//u', $remediation) || strlen($remediation) > 256) {
            throw new InvalidDiagnosticProjectionException(
                DiagnosticPresentationDiagnostic::InvalidDiagnosticRemediation,
                'Projected diagnostic remediation must be non-empty valid UTF-8 and contain at most 256 bytes.',
            );
        }

        if (!array_is_list($fields)) {
            throw new InvalidDiagnosticProjectionException(
                DiagnosticPresentationDiagnostic::DiagnosticFieldsNotList,
                'Projected diagnostic fields must be supplied as a zero-based consecutive list.',
            );
        }

        if (count($fields) > 16) {
            throw new InvalidDiagnosticProjectionException(
                DiagnosticPresentationDiagnostic::TooManyDiagnosticFields,
                sprintf('Projected diagnostic may contain at most 16 fields; received %d.', count($fields)),
            );
        }

        $detachedFields = [];
        $keys = [];

        foreach ($fields as $position => $field) {
            if (!$field instanceof TrustedDeveloperDiagnosticField) {
                throw new InvalidDiagnosticProjectionException(
                    DiagnosticPresentationDiagnostic::InvalidDiagnosticFieldEntry,
                    sprintf('Projected diagnostic field at position %d must be a trusted developer field.', $position),
                );
            }

            if (array_key_exists($field->key(), $keys)) {
                throw new InvalidDiagnosticProjectionException(
                    DiagnosticPresentationDiagnostic::DuplicateDiagnosticFieldKey,
                    sprintf('Projected diagnostic contains duplicate field key "%s".', $field->key()),
                );
            }

            $keys[$field->key()] = true;
            $detachedFields[] = $field;
        }

        $this->owner = $owner;
        $this->identifier = $identifier;
        $this->title = $title;
        $this->operation = $operation;
        $this->violatedInvariant = $violatedInvariant;
        $this->remediation = $remediation;
        $this->fields = $detachedFields;
    }

    public function owner(): DeveloperDiagnosticOwner
    {
        return $this->owner;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function violatedInvariant(): string
    {
        return $this->violatedInvariant;
    }

    public function remediation(): string
    {
        return $this->remediation;
    }

    /** @return list<TrustedDeveloperDiagnosticField> */
    public function fields(): array
    {
        return $this->fields;
    }
}
