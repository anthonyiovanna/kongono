<?php

declare(strict_types=1);

namespace Kongono\Authorization;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authorization\Exception\AuthorizationEvaluationException;
use Kongono\Authorization\Exception\InvalidAuthorizationValueException;
use Throwable;

/** Selects one exact evaluator and returns inert deny-by-default evaluation evidence. */
#[Api(ApiStatus::Experimental)]
final class AuthorizationEngine
{
    /** @var list<array{policy: AuthorizationPolicyReference, evaluator: AuthorizationEvaluator}> */
    private readonly array $registrations;

    /** @param array<array-key, mixed> $evaluators Expected to contain an ordered evaluator list. */
    public function __construct(array $evaluators)
    {
        if (!array_is_list($evaluators) || count($evaluators) < 1 || count($evaluators) > 32) {
            throw InvalidAuthorizationValueException::registration();
        }

        $registrations = [];
        foreach ($evaluators as $evaluator) {
            if (!$evaluator instanceof AuthorizationEvaluator) {
                throw InvalidAuthorizationValueException::registration();
            }

            $reference = $evaluator->policy();
            foreach ($registrations as $registration) {
                if ($registration['policy']->equals($reference)) {
                    throw InvalidAuthorizationValueException::registration();
                }
            }
            $registrations[] = [
                'policy' => $reference,
                'evaluator' => $evaluator,
            ];
        }

        $this->registrations = $registrations;
    }

    public function authorize(AuthorizationRequest $request): AuthorizationEvaluation
    {
        $evidencePolicy = $request->policyEvidence()->reference();
        if (!$evidencePolicy->equals($request->expectedPolicy())) {
            return new AuthorizationEvaluation(AuthorizationDecisionReceipt::policyMismatch($request));
        }

        $selected = null;
        foreach ($this->registrations as $registration) {
            if ($registration['policy']->equals($request->expectedPolicy())) {
                $selected = $registration['evaluator'];
                break;
            }
        }

        if (null === $selected) {
            return new AuthorizationEvaluation(AuthorizationDecisionReceipt::unsupportedPolicy($request));
        }

        try {
            $decision = $selected->evaluate($request);
        } catch (Throwable $failure) {
            throw AuthorizationEvaluationException::from($failure);
        }

        return new AuthorizationEvaluation(AuthorizationDecisionReceipt::evaluated($request, $decision));
    }
}
