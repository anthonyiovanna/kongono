<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Fixture;

use Closure;
use Kongono\ExternalServiceContract\Credential\CredentialLease;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseRequest;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseSource;
use Throwable;

final class FakeCredentialLeaseSource implements CredentialLeaseSource
{
    public int $calls = 0;
    public ?CredentialLeaseRequest $receivedRequest = null;

    /**
     * @param CredentialLease|Throwable|Closure(CredentialLeaseRequest): CredentialLease $result
     * @param null|Closure(string): void $observer
     */
    public function __construct(private CredentialLease|Throwable|Closure $result, private ?Closure $observer = null) {}

    public function acquire(CredentialLeaseRequest $request): CredentialLease
    {
        ++$this->calls;
        if (null !== $this->observer) {
            ($this->observer)('source.acquire');
        }
        $this->receivedRequest = $request;
        if ($this->result instanceof Throwable) {
            throw $this->result;
        }
        if ($this->result instanceof Closure) {
            return ($this->result)($request);
        }

        return $this->result;
    }
}
