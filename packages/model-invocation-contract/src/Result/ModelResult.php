<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Cost\CostEvidence;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;

/** Carries normalized bounded result evidence without provider or effect truth. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelResult
{
    public function __construct(
        private ContractVersion $version,
        private ResultIdentity $identity,
        private InvocationIdentity $invocationIdentity,
        private ModelProfileReference $profile,
        private ResultMode $mode,
        private FinishReason $finishReason,
        private ?ResultText $text,
        private ?ActionSuggestion $actionSuggestion,
        private ?UsageEvidence $usage,
        private ?CostEvidence $cost,
        private ModelVisibility $visibility,
    ) {
        $actionMode = ResultMode::ActionSuggestion === $mode;
        if (($actionMode && (null === $actionSuggestion || null !== $text)) || (!$actionMode && (null === $text || null !== $actionSuggestion))) {
            throw InvalidModelInvocationContractException::invalidResult('result_shape', $mode->value);
        }
        if ($actionMode !== (FinishReason::ActionSuggested === $finishReason)) {
            throw InvalidModelInvocationContractException::invalidResult('finish_reason_shape', $mode->value);
        }
        if (null !== $usage && !$profile->capabilities()->supports(ProviderCapability::UsageEvidence)) {
            throw InvalidModelInvocationContractException::invalidResult('usage_capability_required', $mode->value);
        }
        if (null !== $cost && !$profile->capabilities()->supports(ProviderCapability::CostEvidence)) {
            throw InvalidModelInvocationContractException::invalidResult('cost_capability_required', $mode->value);
        }
    }

    public function version(): ContractVersion
    {
        return $this->version;
    }

    public function identity(): ResultIdentity
    {
        return $this->identity;
    }

    public function invocationIdentity(): InvocationIdentity
    {
        return $this->invocationIdentity;
    }

    public function profile(): ModelProfileReference
    {
        return $this->profile;
    }

    public function mode(): ResultMode
    {
        return $this->mode;
    }

    public function finishReason(): FinishReason
    {
        return $this->finishReason;
    }

    public function text(): ?ResultText
    {
        return $this->text;
    }

    public function actionSuggestion(): ?ActionSuggestion
    {
        return $this->actionSuggestion;
    }

    public function usage(): ?UsageEvidence
    {
        return $this->usage;
    }

    public function cost(): ?CostEvidence
    {
        return $this->cost;
    }

    public function visibility(): ModelVisibility
    {
        return $this->visibility;
    }

    public function equals(self $other): bool
    {
        return $this->identity->equals($other->identity)
            && $this->invocationIdentity->equals($other->invocationIdentity)
            && $this->profile->equals($other->profile)
            && $this->mode === $other->mode
            && $this->finishReason === $other->finishReason
            && (null === $this->text ? null === $other->text : null !== $other->text && $this->text->equals($other->text))
            && (null === $this->actionSuggestion ? null === $other->actionSuggestion : null !== $other->actionSuggestion && $this->actionSuggestion->equals($other->actionSuggestion))
            && (null === $this->usage ? null === $other->usage : null !== $other->usage && $this->usage->equals($other->usage))
            && (null === $this->cost ? null === $other->cost : null !== $other->cost && $this->cost->equals($other->cost))
            && $this->visibility === $other->visibility;
    }
}
