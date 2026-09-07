<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Invocation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Input\InputMessage;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;

/** Aggregates one bounded request value without invoking any provider. */
#[Api(ApiStatus::Experimental)]
final readonly class InvocationRequest
{
    /** @var list<InputMessage> */
    private array $messages;

    /** @param list<InputMessage> $messages */
    public function __construct(
        private ContractVersion $version,
        private InvocationIdentity $identity,
        private ModelProfileReference $profile,
        array $messages,
        private ResultMode $resultMode,
        private bool $streaming,
        private InvocationLimits $limits,
    ) {
        self::validateMessages($messages);
        $required = match ($resultMode) {
            ResultMode::Text => ProviderCapability::TextResult,
            ResultMode::Structured => ProviderCapability::StructuredResult,
            ResultMode::ActionSuggestion => ProviderCapability::ActionSuggestion,
        };
        if (!$profile->capabilities()->supports($required)) {
            throw InvalidModelInvocationContractException::invalidRequest('unsupported_result_mode', mode: $resultMode->value);
        }
        if ($streaming && !$profile->capabilities()->supports(ProviderCapability::Streaming)) {
            throw InvalidModelInvocationContractException::invalidRequest('streaming_capability_required', mode: $resultMode->value);
        }

        $this->messages = [...$messages];
    }

    public function version(): ContractVersion
    {
        return $this->version;
    }

    public function identity(): InvocationIdentity
    {
        return $this->identity;
    }

    public function profile(): ModelProfileReference
    {
        return $this->profile;
    }

    /** @return list<InputMessage> */
    public function messages(): array
    {
        return [...$this->messages];
    }

    public function resultMode(): ResultMode
    {
        return $this->resultMode;
    }

    public function streaming(): bool
    {
        return $this->streaming;
    }

    public function limits(): InvocationLimits
    {
        return $this->limits;
    }

    public function equals(self $other): bool
    {
        return $this->identity->equals($other->identity)
            && $this->profile->equals($other->profile)
            && self::messagesEqual($this->messages, $other->messages)
            && $this->resultMode === $other->resultMode
            && $this->streaming === $other->streaming
            && $this->limits->equals($other->limits);
    }

    /** @param array<array-key, mixed> $messages */
    private static function validateMessages(array $messages): void
    {
        if (!array_is_list($messages)) {
            throw InvalidModelInvocationContractException::invalidRequest('messages_not_list', count($messages));
        }
        if ([] === $messages || count($messages) > 64) {
            throw InvalidModelInvocationContractException::contractLimitExceeded('message_count', 64, count($messages));
        }

        $bytes = 0;
        foreach ($messages as $position => $message) {
            if (!$message instanceof InputMessage) {
                throw InvalidModelInvocationContractException::invalidRequest('invalid_message_entry', count($messages), $position);
            }
            $bytes += strlen($message->content());
            if ($bytes > 262144) {
                throw InvalidModelInvocationContractException::contractLimitExceeded('message_aggregate_bytes', 262144, count($messages));
            }
        }
    }

    /**
     * @param list<InputMessage> $left
     * @param list<InputMessage> $right
     */
    private static function messagesEqual(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }
        foreach ($left as $position => $message) {
            if (!$message->equals($right[$position])) {
                return false;
            }
        }

        return true;
    }
}
