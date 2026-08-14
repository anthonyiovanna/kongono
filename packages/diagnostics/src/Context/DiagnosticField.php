<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Context;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use Kongono\Diagnostics\Exception\InvalidDiagnosticValueException;

/** Stores one detached, bounded scalar value with copied schema metadata. */
#[Api(ApiStatus::Public)]
final readonly class DiagnosticField
{
    private string $key;
    private string $label;
    private DiagnosticValueKind $kind;
    private string|int|bool $value;
    private DiagnosticDisclosure $disclosure;
    private ?bool $textWasTruncated;

    public function __construct(
        DiagnosticFieldSchema $schema,
        string|int|bool $value,
    ) {
        if (!$this->valueMatchesKind($schema->kind(), $value)) {
            throw new InvalidDiagnosticValueException(
                DiagnosticsDiagnostic::ContextValueKindMismatch,
                sprintf(
                    'Diagnostic field "%s" requires %s; received %s.',
                    $schema->key(),
                    $schema->kind()->value,
                    get_debug_type($value),
                ),
            );
        }

        $storedValue = $value;
        $wasTruncated = null;

        if (DiagnosticValueKind::Text === $schema->kind()) {
            /** @var string $value */
            [$storedValue, $wasTruncated] = $this->representText($value);
        }

        $this->key = $schema->key();
        $this->label = $schema->label();
        $this->kind = $schema->kind();
        $this->value = $storedValue;
        $this->disclosure = $schema->disclosure();
        $this->textWasTruncated = $wasTruncated;
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

    private function valueMatchesKind(DiagnosticValueKind $kind, string|int|bool $value): bool
    {
        return match ($kind) {
            DiagnosticValueKind::Text => is_string($value),
            DiagnosticValueKind::Integer => is_int($value),
            DiagnosticValueKind::Boolean => is_bool($value),
        };
    }

    /** @return array{string, bool} */
    private function representText(string $value): array
    {
        $tokens = [];
        $representedLength = 0;
        $inputLength = strlen($value);

        for ($position = 0; $position < $inputLength; ++$position) {
            $byte = ord($value[$position]);
            $token = match (true) {
                $byte >= 0x20 && $byte <= 0x7E && 0x5C !== $byte => $value[$position],
                0x5C === $byte => '\\\\',
                0x09 === $byte => '\\t',
                0x0A === $byte => '\\n',
                0x0D === $byte => '\\r',
                default => sprintf('\\x%02X', $byte),
            };
            $tokenLength = strlen($token);

            if ($representedLength + $tokenLength > 512) {
                while ($representedLength + 14 > 512) {
                    $removed = array_pop($tokens);

                    if (null === $removed) {
                        break;
                    }

                    $representedLength -= strlen($removed);
                }

                return [implode('', $tokens) . '...[truncated]', true];
            }

            $tokens[] = $token;
            $representedLength += $tokenLength;
        }

        return [implode('', $tokens), false];
    }
}
