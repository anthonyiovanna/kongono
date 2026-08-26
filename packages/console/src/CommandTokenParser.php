<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Diagnostic\ConsoleDiagnostic;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Normalizes already-tokenized input without I/O, execution, or discovery. */
#[Api(ApiStatus::Experimental)]
final class CommandTokenParser
{
    private const MAX_TOKENS = 128;
    private const MAX_TOKEN_BYTES = 8192;
    private const MAX_TOTAL_TOKEN_BYTES = 65_536;
    private const MAX_SECURE_INPUTS = 64;

    /**
     * @param array<array-key, mixed> $tokens
     * @param array<array-key, mixed> $secureInputs
     */
    public function parse(CommandSpecificationSet $specifications, array $tokens, array $secureInputs = []): CommandParseResult
    {
        if (!$this->validTokens($tokens)) {
            return $this->reject(ConsoleDiagnostic::Parsing);
        }

        $normalizedTokens = [];
        foreach ($tokens as $token) {
            if (!is_string($token)) {
                return $this->reject(ConsoleDiagnostic::Parsing);
            }
            $normalizedTokens[] = $token;
        }
        $tokens = $normalizedTokens;
        try {
            $identifier = new CommandIdentifier($tokens[0]);
        } catch (InvalidConsoleValueException) {
            return $this->reject(ConsoleDiagnostic::UnknownCommand);
        }
        $specification = $specifications->command($identifier);
        if (null === $specification) {
            return $this->reject(ConsoleDiagnostic::UnknownCommand);
        }

        $plan = false;
        $apply = false;
        $noInteraction = false;
        /** @var array<string, CommandInput> $rawInputs */
        $rawInputs = [];
        foreach (array_slice($tokens, 1) as $token) {
            if ('--plan' === $token || '--apply' === $token || '--no-interaction' === $token) {
                if (
                    ('--plan' === $token && $plan)
                    || ('--apply' === $token && $apply)
                    || ('--no-interaction' === $token && $noInteraction)
                ) {
                    return $this->reject(ConsoleDiagnostic::Parsing);
                }
                $plan = $plan || '--plan' === $token;
                $apply = $apply || '--apply' === $token;
                $noInteraction = $noInteraction || '--no-interaction' === $token;
                continue;
            }
            if (!str_starts_with($token, '--') || !str_contains($token, '=')) {
                return $this->reject(ConsoleDiagnostic::Parsing);
            }
            [$rawName, $rawValue] = explode('=', substr($token, 2), 2);
            try {
                $name = new CommandInputName($rawName);
            } catch (InvalidConsoleValueException) {
                return $this->reject(ConsoleDiagnostic::Parsing);
            }
            $definition = $specification->input($name);
            if (null === $definition) {
                return $this->reject(ConsoleDiagnostic::UnknownInput);
            }
            if (CommandInputSensitivity::Sensitive === $definition->sensitivity()) {
                return $this->reject(ConsoleDiagnostic::SensitiveInput);
            }
            if (isset($rawInputs[$rawName])) {
                return $this->reject(ConsoleDiagnostic::Parsing);
            }
            try {
                $value = new CommandInputValue($rawValue);
            } catch (InvalidConsoleValueException) {
                return $this->reject(ConsoleDiagnostic::Parsing);
            }
            $rawInputs[$rawName] = new CommandInput($name, $value, CommandInputSensitivity::Ordinary);
        }

        $intent = $this->intent($specification->definition()->mutationPolicy(), $plan, $apply);
        if (null === $intent) {
            return $this->reject(ConsoleDiagnostic::Parsing);
        }

        if (count($secureInputs) > self::MAX_SECURE_INPUTS) {
            return $this->reject(ConsoleDiagnostic::SensitiveInput);
        }
        /** @var array<string, CommandInput> $acceptedSecureInputs */
        $acceptedSecureInputs = [];
        foreach ($secureInputs as $secureInput) {
            if (!$secureInput instanceof CommandInput || CommandInputSensitivity::Sensitive !== $secureInput->sensitivity()) {
                return $this->reject(ConsoleDiagnostic::SensitiveInput);
            }
            $name = $secureInput->name()->value();
            $definition = $specification->input($secureInput->name());
            if (
                null === $definition
                || CommandInputSensitivity::Sensitive !== $definition->sensitivity()
                || isset($acceptedSecureInputs[$name])
                || isset($rawInputs[$name])
            ) {
                return $this->reject(ConsoleDiagnostic::SensitiveInput);
            }
            $acceptedSecureInputs[$name] = $secureInput;
        }

        $inputs = [];
        foreach ($specification->inputs() as $definition) {
            $name = $definition->name()->value();
            $input = $rawInputs[$name] ?? $acceptedSecureInputs[$name] ?? null;
            if (null === $input) {
                if (CommandInputRequirement::Required === $definition->requirement()) {
                    return $this->reject(ConsoleDiagnostic::MissingInput);
                }
                continue;
            }
            $inputs[] = $input;
        }

        $interaction = CommandInteractionPolicy::Declared === $specification->definition()->interactionPolicy() && !$noInteraction
            ? CommandInteractionMode::Permitted
            : CommandInteractionMode::Disabled;

        return CommandParseResult::accepted(new CommandInvocation($specification->definition(), $inputs, $intent, $interaction));
    }

    /** @param array<array-key, mixed> $tokens */
    private function validTokens(array $tokens): bool
    {
        if ([] === $tokens || count($tokens) > self::MAX_TOKENS) {
            return false;
        }
        $total = 0;
        foreach ($tokens as $token) {
            if (!is_string($token)) {
                return false;
            }
            $length = strlen($token);
            $total += $length;
            if (
                $length < 1
                || $length > self::MAX_TOKEN_BYTES
                || $total > self::MAX_TOTAL_TOKEN_BYTES
                || 1 !== preg_match('//u', $token)
                || 1 === preg_match('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u', $token)
            ) {
                return false;
            }
        }

        return true;
    }

    private function intent(CommandMutationPolicy $policy, bool $plan, bool $apply): ?CommandExecutionIntent
    {
        if (CommandMutationPolicy::ReadOnly === $policy) {
            return $plan || $apply ? null : CommandExecutionIntent::Observe;
        }
        if ($plan === $apply) {
            return null;
        }

        return $plan ? CommandExecutionIntent::Plan : CommandExecutionIntent::Apply;
    }

    private function reject(ConsoleDiagnostic $diagnostic): CommandParseResult
    {
        return CommandParseResult::rejected(new CommandFailure(CommandFailureCategory::InvalidInput, $diagnostic->value));
    }
}
