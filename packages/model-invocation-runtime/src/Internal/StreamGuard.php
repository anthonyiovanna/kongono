<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Budget\InvocationExecutionBudget;
use Kongono\ModelInvocationContract\Cost\CostEvidence;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationContract\Stream\ModelStreamEvent;
use Kongono\ModelInvocationContract\Stream\StreamEventKind;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;
use Kongono\ModelInvocationRuntime\Exception\InvalidInvocationRuntimeException;

/** Enforces the exact normalized stream, cumulative usage, and cost relations. */
#[Api(ApiStatus::Internal)]
final class StreamGuard
{
    private int $sequence = 0;
    private bool $terminal = false;
    private bool $completed = false;
    /** @var array<string, int> */
    private array $usageSnapshot = [];

    public function acceptEvent(ModelStreamEvent $event, InvocationRequest $request, InvocationExecutionBudget $budget): int
    {
        if ($this->terminal || !$event->invocationIdentity()->equals($request->identity())) {
            throw InvalidInvocationRuntimeException::invalid('stream_protocol');
        }
        $sequence = $event->sequence()->value();
        if ($this->sequence === PHP_INT_MAX || $sequence !== $this->sequence + 1) {
            throw InvalidInvocationRuntimeException::invalid('stream_protocol');
        }
        if (0 === $this->sequence && StreamEventKind::Started !== $event->kind()) {
            throw InvalidInvocationRuntimeException::invalid('stream_protocol');
        }
        if (0 !== $this->sequence && StreamEventKind::Started === $event->kind()) {
            throw InvalidInvocationRuntimeException::invalid('stream_protocol');
        }

        $payloadBytes = match ($event->kind()) {
            StreamEventKind::ContentDelta => $this->payloadForMode($event, $request, false),
            StreamEventKind::ActionName, StreamEventKind::ActionArgumentsDelta => $this->payloadForMode($event, $request, true),
            StreamEventKind::Usage => $this->usageEvent($event, $request, $budget),
            StreamEventKind::Completed, StreamEventKind::Failed => $this->terminal($event),
            StreamEventKind::Started => 0,
        };
        $this->sequence = $sequence;

        return $payloadBytes;
    }

    public function validateFinalEvidence(ModelResult $result, InvocationRequest $request, InvocationExecutionBudget $budget): void
    {
        $usage = $result->usage();
        if (null !== $usage) {
            $this->validateUsage($usage, $request, $budget, true);
        } elseif ([] !== $this->usageSnapshot) {
            throw InvalidInvocationRuntimeException::invalid('usage_protocol');
        }
        $cost = $result->cost();
        if (null !== $cost) {
            $this->validateCost($cost, $request, $budget);
        }
    }

    public function isTerminal(): bool
    {
        return $this->terminal;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    private function payloadForMode(ModelStreamEvent $event, InvocationRequest $request, bool $action): int
    {
        $actionMode = ResultMode::ActionSuggestion === $request->resultMode();
        if ($action !== $actionMode || null === $event->payload()) {
            throw InvalidInvocationRuntimeException::invalid('stream_protocol');
        }

        return strlen($event->payload());
    }

    private function usageEvent(ModelStreamEvent $event, InvocationRequest $request, InvocationExecutionBudget $budget): int
    {
        $usage = $event->usage();
        if (null === $usage) {
            throw InvalidInvocationRuntimeException::invalid('usage_protocol');
        }
        $this->validateUsage($usage, $request, $budget, false);

        return 0;
    }

    private function terminal(ModelStreamEvent $event): int
    {
        $this->terminal = true;
        $this->completed = StreamEventKind::Completed === $event->kind();

        return 0;
    }

    private function validateUsage(UsageEvidence $usage, InvocationRequest $request, InvocationExecutionBudget $budget, bool $final): void
    {
        if (!$request->profile()->capabilities()->supports(ProviderCapability::UsageEvidence)) {
            throw InvalidInvocationRuntimeException::invalid('usage_protocol');
        }
        $quantities = $usage->quantities();
        if (count($quantities) > $request->limits()->maxUsageEntries()) {
            throw InvalidInvocationRuntimeException::invalid('budget_exceeded');
        }
        $limits = [];
        foreach ($budget->usageLimits() as $limit) {
            $limits[$limit->metric()->value() . '|' . $limit->unit()->value()] = $limit->maximumQuantity();
        }
        $current = [];
        foreach ($quantities as $quantity) {
            $key = $quantity->metric()->value() . '|' . $quantity->unit()->value();
            if (!array_key_exists($key, $limits) || $quantity->quantity() > $limits[$key]) {
                throw InvalidInvocationRuntimeException::invalid('budget_exceeded');
            }
            if (array_key_exists($key, $this->usageSnapshot) && $quantity->quantity() < $this->usageSnapshot[$key]) {
                throw InvalidInvocationRuntimeException::invalid('usage_protocol');
            }
            $current[$key] = $quantity->quantity();
        }
        if ([] !== $this->usageSnapshot && array_keys($current) !== array_keys($this->usageSnapshot)) {
            throw InvalidInvocationRuntimeException::invalid('usage_protocol');
        }
        if (!$final) {
            $this->usageSnapshot = $current;
        }
    }

    private function validateCost(CostEvidence $cost, InvocationRequest $request, InvocationExecutionBudget $budget): void
    {
        $limit = $budget->costLimit();
        if (
            !$request->profile()->capabilities()->supports(ProviderCapability::CostEvidence)
            || null === $limit
            || !$cost->currency()->equals($limit->currency())
            || self::decimalGreaterThan($cost->amount()->value(), $limit->maximumAmount()->value())
        ) {
            throw InvalidInvocationRuntimeException::invalid('cost_protocol');
        }
    }

    private static function decimalGreaterThan(string $left, string $right): bool
    {
        [$leftInteger, $leftFraction] = self::decimalParts($left);
        [$rightInteger, $rightFraction] = self::decimalParts($right);
        if (strlen($leftInteger) !== strlen($rightInteger)) {
            return strlen($leftInteger) > strlen($rightInteger);
        }
        $integerComparison = strcmp($leftInteger, $rightInteger);
        if (0 !== $integerComparison) {
            return $integerComparison > 0;
        }
        $width = max(strlen($leftFraction), strlen($rightFraction));

        return strcmp(str_pad($leftFraction, $width, '0'), str_pad($rightFraction, $width, '0')) > 0;
    }

    /** @return array{string, string} */
    private static function decimalParts(string $amount): array
    {
        $parts = explode('.', $amount, 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
