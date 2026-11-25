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

        if (!$this->allowPrivate && self::isPrivateOrSpecialPurpose($value, blockPrivate: true, blockReserved: !$this->allowReserved)) {
            return $this->defaultMessage($field);
        }
        if ($this->allowPrivate && !$this->allowReserved && self::isPrivateOrSpecialPurpose($value, blockPrivate: false, blockReserved: true)) {
            return $this->defaultMessage($field);
        }

        return null;
    }

    /**
     * Blocks loopback, link-local/cloud IMDS, CGNAT, RFC1918, ULA, multicast, unspecified,
     * non-global IPv6 (outside 2000::/3), documentation ranges (2001:db8::/32, 3fff::/20),
     * 6to4/Teredo tunneling ranges, and IPv4-mapped IPv6 wrappers that PHP's filter flags miss.
     */
    private static function isPrivateOrSpecialPurpose(string $ip, bool $blockPrivate = true, bool $blockReserved = true): bool
    {
        $packed = @inet_pton($ip);
        if (false === $packed) {
            return true;
        }
        if (16 === strlen($packed)) {
            // Unwrap ::ffff:0:0/96 (IPv4-mapped IPv6)
            if (str_starts_with($packed, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff")) {
                $v4 = @inet_ntop(substr($packed, 12, 4));

                return !is_string($v4) || self::isPrivateOrSpecialPurpose($v4, $blockPrivate, $blockReserved);
            }
            $b0 = ord($packed[0]);
            $b1 = ord($packed[1]);
            $b2 = ord($packed[2]);
            $b3 = ord($packed[3]);

            $isUlaOrLinkLocal = 0xfc === ($b0 & 0xfe) || (0xfe === $b0 && 0x80 === ($b1 & 0xc0));
            if ($blockPrivate && $isUlaOrLinkLocal) {
                return true;
            }
            if ($blockReserved) {
                // Only Global Unicast 2000::/3 is globally routable; also reject Teredo (2001::/32),
                // documentation (2001:db8::/32, 3fff::/20), and 6to4 (2002::/16)
                $inGlobalUnicast = 0x20 === ($b0 & 0xe0);
                if (
                    !$inGlobalUnicast
                    || (0x20 === $b0 && 0x01 === $b1 && 0x00 === $b2 && 0x00 === $b3)
                    || (0x20 === $b0 && 0x01 === $b1 && 0x0d === $b2 && 0xb8 === $b3)
                    || (0x3f === $b0 && 0xff === $b1 && 0x00 === ($b2 & 0xf0))
                    || (0x20 === $b0 && 0x02 === $b1)
                ) {
                    if (!$isUlaOrLinkLocal) {
                        return true;
                    }
                }
            }
            if ($blockPrivate && (0x00 === $b0 || 0x01 === $b0 || 0xfe === $b0 || 0xff === $b0 || (0x20 === $b0 && 0x02 === $b1) || (0x20 === $b0 && 0x01 === $b1 && 0x00 === $b2 && 0x00 === $b3))) {
                return true;
            }

            return false;
        }

        if (4 === strlen($packed)) {
            $o0 = ord($packed[0]);
            $o1 = ord($packed[1]);
            if ($blockPrivate && (
                10 === $o0
                || (172 === $o0 && $o1 >= 16 && $o1 <= 31)
                || (192 === $o0 && 168 === $o1)
                || 127 === $o0
                || (169 === $o0 && 254 === $o1)
                || (100 === $o0 && $o1 >= 64 && $o1 <= 127)
                || 0 === $o0
                || (198 === $o0 && (18 === $o1 || 19 === $o1))
                || $o0 >= 224
            )) {
                return true;
            }
            if ($blockReserved && (
                0 === $o0
                || (100 === $o0 && $o1 >= 64 && $o1 <= 127)
                || 127 === $o0
                || (169 === $o0 && 254 === $o1)
                || (198 === $o0 && (18 === $o1 || 19 === $o1))
                || $o0 >= 224
            )) {
                return true;
            }
        }

        return false;
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
