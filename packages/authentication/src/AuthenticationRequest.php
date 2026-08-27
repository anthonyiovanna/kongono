<?php

declare(strict_types=1);

namespace Kongono\Authentication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;

/** Carries one bounded ordered evidence list and explicit evaluation inputs. */
#[Api(ApiStatus::Experimental)]
final readonly class AuthenticationRequest
{
    /** @var list<AuthenticationEvidence> */
    private array $evidence;

    /** @param array<array-key, mixed> $evidence Expected to contain a bounded evidence list. */
    public function __construct(
        array $evidence,
        private AuthenticationInstant $now,
        private AuthenticationRequirement $requirement,
    ) {
        if (count($evidence) > 8) {
            throw InvalidAuthenticationValueException::evidence();
        }
        $detached = [];
        foreach ($evidence as $entry) {
            if (!$entry instanceof AuthenticationEvidence) {
                throw InvalidAuthenticationValueException::evidence();
            }
            $detached[] = $entry;
        }

        $this->evidence = $detached;
    }

    /** @return list<AuthenticationEvidence> */
    public function evidence(): array
    {
        return $this->evidence;
    }

    public function now(): AuthenticationInstant
    {
        return $this->now;
    }

    public function requirement(): AuthenticationRequirement
    {
        return $this->requirement;
    }
}
