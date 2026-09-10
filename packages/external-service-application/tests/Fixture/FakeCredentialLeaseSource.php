<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Fixture;

use Closure;
use Kongono\ExternalServiceContract\Credential\CredentialLease;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseRequest;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseSource;
use Throwable;

final class FakeCredentialLeaseSource implements CredentialLeaseSource
{
    public int $calls = 0;
    public ?CredentialLeaseRequest $request = null;

    public function __construct(private CredentialLease|Throwable $result, private ?Closure $observer = null) {}

    public function acquire(CredentialLeaseRequest $request): CredentialLease
    {
        ++$this->calls;
        $this->request = $request;
        if (null !== $this->observer) {
            ($this->observer)('source.acquire');
        }
        if ($this->result instanceof Throwable) {
            throw $this->result;
        }

        return $this->result;
    }
}
