<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Fixture;

use Closure;
use Kongono\ExternalServiceApplication\Operation\ExternalServiceRequestFactory;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Psr\Http\Message\RequestInterface;
use Throwable;

final class FakeExternalServiceRequestFactory implements ExternalServiceRequestFactory
{
    public int $calls = 0;
    public ?ServiceBinding $binding = null;
    public ?ExternalServiceOperationIdentity $operationIdentity = null;

    /** @param RequestInterface|Throwable|Closure(string): RequestInterface $result */
    public function __construct(private RequestInterface|Throwable|Closure $result, private ?Closure $observer = null) {}

    public function create(
        ServiceBinding $binding,
        ExternalServiceOperationIdentity $operationIdentity,
        #[\SensitiveParameter]
        string $credentialMaterial,
    ): RequestInterface {
        ++$this->calls;
        $this->binding = $binding;
        $this->operationIdentity = $operationIdentity;
        if (null !== $this->observer) {
            ($this->observer)('request_factory.create');
        }
        if ($this->result instanceof Throwable) {
            throw $this->result;
        }
        if ($this->result instanceof Closure) {
            return ($this->result)($credentialMaterial);
        }

        return $this->result;
    }
}
