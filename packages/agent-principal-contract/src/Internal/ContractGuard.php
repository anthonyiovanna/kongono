<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Internal;

use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Centralizes exact local grammar checks without exposing a generic validator. */
#[Api(ApiStatus::Internal)]
final class ContractGuard
{
    public static function identity(string $value, string $violation): string
    {
        if (!self::isIdentity($value)) {
            throw InvalidAgentPrincipalContractException::invalidIdentity($violation);
        }

        return $value;
    }

    public static function purpose(string $value, string $violation): string
    {
        if (
            strlen($value) < 1
            || strlen($value) > 512
            || 1 !== preg_match('//u', $value)
            || 1 === preg_match('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u', $value)
            || 1 === preg_match('/\A\s|\s\z/u', $value)
        ) {
            throw InvalidAgentPrincipalContractException::invalidPurpose($violation);
        }

        return $value;
    }

    public static function evidenceDigest(string $value): string
    {
        if (!self::isDigest($value)) {
            throw InvalidAgentPrincipalContractException::invalidEvidence('evidence_digest_grammar');
        }

        return $value;
    }

    public static function provenanceIdentity(string $value): string
    {
        if (!self::isIdentity($value)) {
            throw InvalidAgentPrincipalContractException::invalidEvidence('provenance_source_identity_grammar');
        }

        return $value;
    }

    public static function provenanceDigest(string $value): string
    {
        if (!self::isDigest($value)) {
            throw InvalidAgentPrincipalContractException::invalidEvidence('provenance_digest_grammar');
        }

        return $value;
    }

    public static function receiptDigest(string $value): string
    {
        if (!self::isDigest($value)) {
            throw InvalidAgentPrincipalContractException::invalidReceipt('receipt_evidence_digest_grammar');
        }

        return $value;
    }

    private static function isIdentity(string $value): bool
    {
        return strlen($value) >= 1
            && strlen($value) <= 128
            && 1 === preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $value);
    }

    private static function isDigest(string $value): bool
    {
        return 64 === strlen($value) && 1 === preg_match('/\A[a-f0-9]{64}\z/D', $value);
    }
}
