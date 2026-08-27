<?php

declare(strict_types=1);

namespace Kongono\Authentication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\Exception\AuthenticationCleanupException;
use Kongono\Authentication\Exception\AuthenticationOperationAndCleanupException;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;
use Kongono\IdentityAccess\AuthenticationOutcomeKind;
use Throwable;

/** Selects one exact verifier and owns one attempt's verify/retire sequence. */
#[Api(ApiStatus::Experimental)]
final class AuthenticationOrchestrator
{
    /** @var array<string, AuthenticationVerifier> */
    private readonly array $verifiers;

    /** @param array<array-key, mixed> $verifiers Expected to contain an ordered verifier list. */
    public function __construct(array $verifiers)
    {
        if (count($verifiers) < 1 || count($verifiers) > 32) {
            throw InvalidAuthenticationValueException::registration();
        }

        $registered = [];
        foreach ($verifiers as $verifier) {
            if (!$verifier instanceof AuthenticationVerifier) {
                throw InvalidAuthenticationValueException::registration();
            }

            $mechanism = $verifier->mechanism()->value();
            if (array_key_exists($mechanism, $registered)) {
                throw InvalidAuthenticationValueException::registration();
            }
            $registered[$mechanism] = $verifier;
        }

        $this->verifiers = $registered;
    }

    public function authenticate(AuthenticationRequest $request): AuthenticationVerification
    {
        $evidence = $request->evidence();
        if ([] === $evidence) {
            return AuthenticationVerification::absent();
        }
        if (count($evidence) > 1) {
            return AuthenticationVerification::ambiguous();
        }

        $selectedEvidence = $evidence[0];
        $mechanism = $selectedEvidence->mechanism();
        $verifier = $this->verifiers[$mechanism->value()] ?? null;
        if (null === $verifier) {
            return AuthenticationVerification::unsupported();
        }

        $operation = $verifier->begin($selectedEvidence, $request->now());
        $verification = null;
        $operationFailure = null;
        try {
            $verification = $operation->verify();
        } catch (Throwable $failure) {
            $operationFailure = $failure;
        }

        try {
            $operation->retire();
        } catch (Throwable $cleanupFailure) {
            if (null !== $operationFailure) {
                throw AuthenticationOperationAndCleanupException::from($operationFailure, $cleanupFailure);
            }

            throw AuthenticationCleanupException::from($cleanupFailure);
        }

        if (null !== $operationFailure) {
            throw $operationFailure;
        }

        if (AuthenticationOutcomeKind::Authenticated !== $verification->outcome()->kind()) {
            return $verification;
        }

        $facts = $verification->facts();
        if (null === $facts || !$facts->mechanism()->equals($mechanism)) {
            return AuthenticationVerification::invalid();
        }
        if ($facts->authenticatedAt()->isAfter($request->now())) {
            return AuthenticationVerification::invalid();
        }
        if ($request->now()->isAfter($facts->validUntil())) {
            return AuthenticationVerification::expired();
        }
        if ($request->now()->elapsedSince($facts->authenticatedAt()) > $request->requirement()->maximumAgeSeconds()) {
            return AuthenticationVerification::expired();
        }
        if (!$facts->strength()->satisfies($request->requirement()->minimumStrength())) {
            return AuthenticationVerification::invalid();
        }

        return $verification;
    }
}
