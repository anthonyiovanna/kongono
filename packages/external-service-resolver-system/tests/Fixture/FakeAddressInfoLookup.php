<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Fixture;

use Error;
use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoLookup;
use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoLookupResult;

final class FakeAddressInfoLookup implements AddressInfoLookup
{
    public int $calls = 0;
    public ?string $host = null;
    public ?string $service = null;
    /** @var array<array-key, mixed>|null */
    public ?array $hints = null;
    public ?int $maximumCandidates = null;

    /**
     * @param non-empty-list<AddressInfoLookupResult|Error> $results
     *
     * @phpstan-param list<AddressInfoLookupResult|Error> $results
     */
    public function __construct(private array $results) {}

    public function lookup(string $host, string $service, array $hints, int $maximumCandidates): AddressInfoLookupResult
    {
        ++$this->calls;
        $this->host = $host;
        $this->service = $service;
        $this->hints = $hints;
        $this->maximumCandidates = $maximumCandidates;
        $result = array_shift($this->results);
        if ($result instanceof Error) {
            throw $result;
        }
        if (null === $result) {
            throw new Error('Unexpected fake lookup exhaustion.');
        }

        return $result;
    }
}
