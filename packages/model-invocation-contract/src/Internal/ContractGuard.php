<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;

/** Centralizes exact local grammar checks without exposing a generic validator. */
#[Api(ApiStatus::Internal)]
final class ContractGuard
{
    public static function identity(string $value, string $violation): string
    {
        if (strlen($value) < 1 || strlen($value) > 128 || 1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $value)) {
            throw InvalidModelInvocationContractException::invalidIdentity($violation);
        }

        return $value;
    }

    public static function inputContent(string $value): string
    {
        return self::text($value, 32768, 'input_content_grammar', 'input_content_bytes', true);
    }

    public static function resultText(string $value): string
    {
        return self::text($value, 262144, 'result_text_grammar', 'result_text_bytes', false);
    }

    public static function streamPayload(string $value): string
    {
        return self::text($value, 8192, 'stream_payload_grammar', 'stream_payload_bytes', null);
    }

    public static function actionArguments(string $value): string
    {
        $length = strlen($value);
        if ($length > 65536) {
            throw InvalidModelInvocationContractException::contractLimitExceeded('action_arguments_bytes', 65536);
        }
        if ($length < 2 || !self::validText($value, true) || !str_starts_with($value, '{') || !json_validate($value) || !is_object(json_decode($value))) {
            throw InvalidModelInvocationContractException::invalidResult('action_arguments_grammar');
        }

        return $value;
    }

    public static function currencyCode(string $value): string
    {
        if (1 !== preg_match('/\A[A-Z]{3}\z/D', $value)) {
            throw InvalidModelInvocationContractException::invalidUsageOrCostEvidence('currency_code_grammar');
        }

        return $value;
    }

    public static function decimalAmount(string $value): string
    {
        if (1 !== preg_match('/\A(?:0|[1-9][0-9]{0,17})(?:\.[0-9]{1,9})?\z/D', $value)) {
            throw InvalidModelInvocationContractException::invalidUsageOrCostEvidence('decimal_amount_grammar');
        }

        return $value;
    }

    private static function text(string $value, int $maximum, string $violation, string $limitViolation, ?bool $input): string
    {
        $length = strlen($value);
        if ($length > $maximum) {
            throw InvalidModelInvocationContractException::contractLimitExceeded($limitViolation, $maximum);
        }
        if ($length < 1 || !self::validText($value, true)) {
            if (true === $input) {
                throw InvalidModelInvocationContractException::invalidInput($violation);
            }
            if (false === $input) {
                throw InvalidModelInvocationContractException::invalidResult($violation);
            }

            throw InvalidModelInvocationContractException::invalidStreamEvent($violation);
        }

        return $value;
    }

    private static function validText(string $value, bool $allowLineFeedAndTab): bool
    {
        if (1 !== preg_match('//u', $value) || $value !== trim($value) || 1 === preg_match('/\A\s|\s\z/u', $value)) {
            return false;
        }

        $controls = $allowLineFeedAndTab
            ? '/[\x{0000}-\x{0008}\x{000B}-\x{001F}\x{007F}-\x{009F}]/u'
            : '/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u';

        return 1 !== preg_match($controls, $value);
    }
}
