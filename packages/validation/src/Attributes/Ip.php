<?php

declare(strict_types=1);

namespace Kongono\Validation\Attributes;

use Attribute;
use Kongono\Core\CoreException;
use Kongono\Validation\RuleInterface;

/**
 * Validate IPv4/IPv6 addresses with optional private-range and reserved-range blocking.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class Ip implements RuleInterface
{
    /** @param string $version Allowed values: 'any', 'v4', 'v6' */
    public function __construct(
        public string $version = 'any',
        public bool $allowPrivate = true,
        public bool $allowReserved = true,
        public ?string $message = null,
    ) {
        if (!in_array($this->version, ['any', 'v4', 'v6'], true)) {
            throw new CoreException(sprintf('Unsupported IP version "%s". Expected any, v4, or v6.', $this->version));
        }
    }

    public function validate(mixed $value, string $field, array $allInput): ?string
    {
        if (!is_string($value) || '' === $value || 1 === preg_match('/[\x00-\x1f\x7f]/', $value)) {
            return $this->defaultMessage($field);
        }

        $flags = match ($this->version) {
            'v4' => FILTER_FLAG_IPV4,
            'v6' => FILTER_FLAG_IPV6,
            default => 0,
        };
        if (!$this->allowPrivate) {
            $flags |= FILTER_FLAG_NO_PRIV_RANGE;
        }
        if (!$this->allowReserved) {
            $flags |= FILTER_FLAG_NO_RES_RANGE;
        }

        if (false === filter_var($value, FILTER_VALIDATE_IP, $flags)) {
            return $this->defaultMessage($field);
        }

        return null;
    }

    private function defaultMessage(string $field): string
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return match ($this->version) {
            'v4' => sprintf('The %s field must be a valid IPv4 address.', $field),
            'v6' => sprintf('The %s field must be a valid IPv6 address.', $field),
            default => sprintf('The %s field must be a valid IP address.', $field),
        };
    }
}
