<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Fixture;

use Closure;
use Kongono\ExternalServiceCredentialRuntime\Clock\CredentialMonotonicClock;
use Throwable;

final class FakeCredentialMonotonicClock implements CredentialMonotonicClock
{
    /** @var list<int|Throwable|Closure(): int> */
    private array $observations;
    public int $calls = 0;

    /**
     * @param list<int|Throwable|Closure(): int> $observations
     * @param null|Closure(string): void $observer
     */
    public function __construct(array $observations, private ?Closure $observer = null)
    {
        $this->observations = [...$observations];
    }

    public function milliseconds(): int
    {
        ++$this->calls;
        if (null !== $this->observer) {
            ($this->observer)('clock');
        }
        $value = array_shift($this->observations);
        if ($value instanceof Throwable) {
            throw $value;
        }
        if ($value instanceof Closure) {
            return $value();
        }

        return $value ?? 0;
    }
}
