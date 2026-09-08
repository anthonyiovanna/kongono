<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressRuntime\Address\AddressCategory;
use Kongono\NetworkEgressRuntime\Address\AddressFamily;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;

/** Parses and conservatively classifies canonical literal addresses without resolution. */
#[Api(ApiStatus::Internal)]
final class AddressClassifier
{
    /** @return array{AddressFamily, AddressCategory} */
    public static function classify(string $value): array
    {
        if ('' === $value || strtolower($value) !== $value || str_contains($value, '%') || str_contains($value, '[') || str_contains($value, ']')) {
            throw InvalidEgressRuntimeException::invalid('address_grammar');
        }
        $binary = @inet_pton($value);
        if (false === $binary || $value !== @inet_ntop($binary)) {
            throw InvalidEgressRuntimeException::invalid('address_grammar');
        }
        if (16 === strlen($binary) && self::prefix($binary, hex2bin('00000000000000000000ffff'), 96)) {
            throw InvalidEgressRuntimeException::invalid('address_grammar');
        }

        return 4 === strlen($binary)
            ? [AddressFamily::Ipv4, self::ipv4($binary, $value)]
            : [AddressFamily::Ipv6, self::ipv6($binary, $value)];
    }

    private static function ipv4(string $binary, string $value): AddressCategory
    {
        if ("\0\0\0\0" === $binary) {
            return AddressCategory::Unspecified;
        }
        if (self::prefix($binary, "\0", 8)) {
            return AddressCategory::Reserved;
        }
        if (self::prefix($binary, "\x0a", 8) || self::prefix($binary, "\xac\x10", 12) || self::prefix($binary, "\xc0\xa8", 16)) {
            return AddressCategory::Private;
        }
        if (self::prefix($binary, "\x7f", 8)) {
            return AddressCategory::Loopback;
        }
        if (self::prefix($binary, "\xa9\xfe", 16)) {
            return AddressCategory::LinkLocal;
        }
        if (self::prefix($binary, "\xe0", 4)) {
            return AddressCategory::Multicast;
        }
        if (self::prefix($binary, "\xc0\x00\x02", 24) || self::prefix($binary, "\xc6\x33\x64", 24) || self::prefix($binary, "\xcb\x00\x71", 24)) {
            return AddressCategory::Documentation;
        }
        if (
            self::prefix($binary, "\x64\x40", 10)
            || self::prefix($binary, "\xc0\x00\x00", 24)
            || self::prefix($binary, "\xc0\x58\x63", 24)
            || self::prefix($binary, "\xc6\x12", 15)
            || self::prefix($binary, "\xf0", 4)
            || false === filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
        ) {
            return AddressCategory::Reserved;
        }

        return AddressCategory::Public;
    }

    private static function ipv6(string $binary, string $value): AddressCategory
    {
        if (str_repeat("\0", 16) === $binary) {
            return AddressCategory::Unspecified;
        }
        if (str_repeat("\0", 15) . "\x01" === $binary) {
            return AddressCategory::Loopback;
        }
        if (self::prefix($binary, "\xfc", 7)) {
            return AddressCategory::Private;
        }
        if (self::prefix($binary, "\xfe\x80", 10)) {
            return AddressCategory::LinkLocal;
        }
        if (self::prefix($binary, "\xff", 8)) {
            return AddressCategory::Multicast;
        }
        if (self::prefix($binary, hex2bin('20010db8'), 32) || self::prefix($binary, hex2bin('3fff'), 20)) {
            return AddressCategory::Documentation;
        }
        if (
            self::prefix($binary, str_repeat("\0", 12), 96)
            || self::prefix($binary, hex2bin('0064ff9b'), 96)
            || self::prefix($binary, hex2bin('0064ff9b0001'), 48)
            || self::prefix($binary, hex2bin('0100000000000000'), 64)
            || self::prefix($binary, hex2bin('20010000'), 32)
            || self::prefix($binary, hex2bin('20010002'), 48)
            || self::prefix($binary, hex2bin('20010010'), 28)
            || self::prefix($binary, hex2bin('20010020'), 28)
            || self::prefix($binary, hex2bin('2002'), 16)
            || self::prefix($binary, hex2bin('fec0'), 10)
            || false === filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
        ) {
            return AddressCategory::Reserved;
        }

        return AddressCategory::Public;
    }

    private static function prefix(string $address, string|false $prefix, int $bits): bool
    {
        if (false === $prefix) {
            return false;
        }
        $prefix = str_pad($prefix, strlen($address), "\0");
        $bytes = intdiv($bits, 8);
        $remaining = $bits % 8;
        if (substr($address, 0, $bytes) !== substr($prefix, 0, $bytes)) {
            return false;
        }
        if (0 === $remaining) {
            return true;
        }
        $mask = (0xff << (8 - $remaining)) & 0xff;

        return (ord($address[$bytes]) & $mask) === (ord($prefix[$bytes]) & $mask);
    }

    private function __construct() {}
}
