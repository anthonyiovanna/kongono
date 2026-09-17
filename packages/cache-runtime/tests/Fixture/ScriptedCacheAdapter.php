<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Fixture;

use Closure;
use Kongono\CacheAdapterContract\Adapter\CacheAdapter;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterLimits;
use Kongono\CacheAdapterContract\AdapterContractVersion;
use Kongono\CacheAdapterContract\Request\CacheAdapterLookupRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterRemoveRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterReplaceRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupResult;
use Kongono\CacheAdapterContract\Result\CacheAdapterMutationResult;
use Kongono\CacheContract\Entry\CacheEntryEvidence;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use Kongono\CacheContract\Version\CacheEntryVersion;
use LogicException;
use Throwable;

final class ScriptedCacheAdapter implements CacheAdapter
{
    /** @var list<CacheAdapterLookupResult|Throwable|Closure> */
    private array $lookup = [];

    /** @var array<string, list<CacheAdapterMutationResult|Throwable|Closure>> */
    private array $mutations = ['store' => [], 'replace' => [], 'remove' => []];

    /** @var list<string> */
    private array $invocations = [];

    /** @var list<object> */
    private array $requests = [];

    private ?Closure $reentry = null;

    public function __construct(
        private readonly CacheAdapterIdentity $adapterIdentity = new CacheAdapterIdentity('scripted', 1),
        private readonly CacheAdapterLimits $adapterLimits = new CacheAdapterLimits(16, 1_048_576, 1_048_576),
    ) {}

    public function protocolVersion(): AdapterContractVersion
    {
        return AdapterContractVersion::Version1;
    }

    public function identity(): CacheAdapterIdentity
    {
        return $this->adapterIdentity;
    }

    public function limits(): CacheAdapterLimits
    {
        return $this->adapterLimits;
    }

    public function lookup(CacheAdapterLookupRequest $request): CacheAdapterLookupResult
    {
        $this->capture('lookup', $request);
        $script = array_shift($this->lookup);
        if (null === $script) {
            return CacheAdapterLookupResult::miss($request->keyIdentity());
        }

        /** @var CacheAdapterLookupResult $result */
        $result = $this->resolve($script, $request);

        return $result;
    }

    public function store(CacheAdapterStoreRequest $request): CacheAdapterMutationResult
    {
        return $this->mutation('store', $request, $request->entry());
    }

    public function replace(CacheAdapterReplaceRequest $request): CacheAdapterMutationResult
    {
        return $this->mutation('replace', $request, $request->entry());
    }

    public function remove(CacheAdapterRemoveRequest $request): CacheAdapterMutationResult
    {
        return $this->mutation('remove', $request);
    }

    public function queueLookup(CacheAdapterLookupResult|Throwable|Closure $script): self
    {
        $this->lookup[] = $script;

        return $this;
    }

    public function queueMutation(string $operation, CacheAdapterMutationResult|Throwable|Closure $script): self
    {
        if (!array_key_exists($operation, $this->mutations)) {
            throw new LogicException('Unsupported scripted mutation operation.');
        }
        $this->mutations[$operation][] = $script;

        return $this;
    }

    public function reenterOnce(Closure $hook): self
    {
        $this->reentry = $hook;

        return $this;
    }

    /** @return list<string> */
    public function invocations(): array
    {
        return [...$this->invocations];
    }

    /** @return list<object> */
    public function requests(): array
    {
        return [...$this->requests];
    }

    public static function stored(CacheAdapterEntry $entry, string $versionSeed = 'scripted-version'): CacheAdapterMutationResult
    {
        return CacheAdapterMutationResult::stored($entry->keyIdentity(), self::evidence($entry, $versionSeed));
    }

    public static function evidence(CacheAdapterEntry $entry, string $versionSeed = 'scripted-version'): CacheEntryEvidence
    {
        return new CacheEntryEvidence(
            $entry->contractVersion(),
            $entry->keyIdentity(),
            $entry->codecIdentity(),
            $entry->valueBytes(),
            $entry->contentIdentity(),
            $entry->freshnessWindow(),
            new CacheEntryVersion(hash('sha256', $versionSeed)),
        );
    }

    private function mutation(string $operation, object $request, ?CacheAdapterEntry $entry = null): CacheAdapterMutationResult
    {
        $this->capture($operation, $request);
        $script = array_shift($this->mutations[$operation]);
        if (null !== $script) {
            /** @var CacheAdapterMutationResult $result */
            $result = $this->resolve($script, $request);

            return $result;
        }
        $key = $entry?->keyIdentity();
        if (null === $key && $request instanceof CacheAdapterRemoveRequest) {
            $key = $request->keyIdentity();
        }
        if (null === $key) {
            throw new LogicException('Scripted request has no key.');
        }

        return CacheAdapterMutationResult::withoutEvidence(CacheMutationOutcome::Unavailable, $key);
    }

    private function capture(string $operation, object $request): void
    {
        $this->invocations[] = $operation;
        $this->requests[] = $request;
        $hook = $this->reentry;
        $this->reentry = null;
        if ($hook instanceof Closure) {
            $hook();
        }
    }

    private function resolve(object $script, object $request): object
    {
        if ($script instanceof Throwable) {
            throw $script;
        }
        if ($script instanceof Closure) {
            $result = $script($request);
            if (!is_object($result)) {
                throw new LogicException('Scripted closure must return an object.');
            }

            return $result;
        }

        return $script;
    }
}
