<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Runtime;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;
use Kongono\CacheAdapterContract\Request\CacheAdapterLookupRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterRemoveRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterReplaceRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupResult;
use Kongono\CacheAdapterContract\Result\CacheAdapterMutationResult;
use Kongono\CacheContract\Freshness\CacheFreshnessWindow;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Operation\CacheOperation;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheRuntime\Binding\CacheRuntimeBinding;
use Kongono\CacheRuntime\Exception\CacheRuntimeOperationException;
use Kongono\CacheRuntime\Exception\InvalidCacheRuntimeException;
use Kongono\CacheRuntime\Internal\AdapterResultValidator;
use Kongono\CacheRuntime\Internal\RuntimeGuard;
use Kongono\CacheRuntime\Operation\CacheLookupRequest as RuntimeLookupRequest;
use Kongono\CacheRuntime\Operation\CacheRemoveRequest as RuntimeRemoveRequest;
use Kongono\CacheRuntime\Operation\CacheReplaceRequest as RuntimeReplaceRequest;
use Kongono\CacheRuntime\Operation\CacheStoreRequest as RuntimeStoreRequest;
use Kongono\CacheRuntime\Result\CacheLookupResult;
use Kongono\CacheRuntime\Result\CacheMutationResult;
use Throwable;

/** Executes exact-current synchronous cache operations through one bound adapter. */
#[Api(ApiStatus::Experimental)]
final class ExactCurrentCacheRuntime
{
    private readonly RuntimeGuard $guard;

    public function __construct(private readonly CacheRuntimeBinding $binding)
    {
        $this->guard = new RuntimeGuard();
    }

    public function lookup(RuntimeLookupRequest $request): CacheLookupResult
    {
        $key = $this->key($request->key());
        $result = $this->invokeLookup(new CacheAdapterLookupRequest($key));

        return AdapterResultValidator::lookup($this->binding->profile(), $key, $request->now(), $result);
    }

    public function store(RuntimeStoreRequest $request): CacheMutationResult
    {
        $key = $this->key($request->key());
        $candidate = $this->candidate($key, $request->value(), $request->now(), CacheOperation::Store);
        $result = $this->invokeStore(new CacheAdapterStoreRequest($candidate));

        return AdapterResultValidator::mutation($this->binding->profile(), CacheOperation::Store, $key, $candidate, $result);
    }

    public function replace(RuntimeReplaceRequest $request): CacheMutationResult
    {
        $key = $this->key($request->key());
        $candidate = $this->candidate($key, $request->value(), $request->now(), CacheOperation::Replace);
        $result = $this->invokeReplace(new CacheAdapterReplaceRequest($candidate, $request->expectedVersion()));

        return AdapterResultValidator::mutation($this->binding->profile(), CacheOperation::Replace, $key, $candidate, $result);
    }

    public function remove(RuntimeRemoveRequest $request): CacheMutationResult
    {
        $key = $this->key($request->key());
        $result = $this->invokeRemove(new CacheAdapterRemoveRequest($key, $request->expectedVersion()));

        return AdapterResultValidator::mutation($this->binding->profile(), CacheOperation::Remove, $key, null, $result);
    }

    private function key(\Kongono\CacheContract\Identity\LogicalCacheKey $key): CacheKeyIdentity
    {
        return CacheKeyIdentity::from($this->binding->profile()->namespace(), $key);
    }

    private function candidate(CacheKeyIdentity $key, CacheValueBytes $value, CacheInstant $now, CacheOperation $operation): CacheAdapterEntry
    {
        $profile = $this->binding->profile();
        if ($value->byteCount() > $profile->valueLimit()->bytes()) {
            throw InvalidCacheRuntimeException::invalidOperation(
                'value_limit',
                $operation->value,
                $value->byteCount(),
                $profile->valueLimit()->bytes(),
            );
        }
        $lifetime = $profile->lifetime()->microseconds();
        if ($now->microseconds() > 253_402_300_799_999_999 - $lifetime) {
            throw InvalidCacheRuntimeException::invalidOperation('instant_overflow', $operation->value);
        }

        try {
            $window = new CacheFreshnessWindow($now, new CacheInstant($now->microseconds() + $lifetime));

            return new CacheAdapterEntry(
                $profile->contractVersion(),
                $key,
                $profile->codec(),
                $value,
                $value->contentIdentity(),
                $window,
            );
        } catch (Error $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw InvalidCacheRuntimeException::invalidOperation('request_relation', $operation->value, previous: $failure);
        }
    }

    private function invokeLookup(CacheAdapterLookupRequest $request): CacheAdapterLookupResult
    {
        $this->guard->enter(CacheOperation::Lookup->value);
        try {
            return $this->binding->adapter()->lookup($request);
        } catch (Error $failure) {
            throw $failure;
        } catch (CacheRuntimeOperationException $failure) {
            if ('KGN-CACHE-RUNTIME-2035' === $failure->diagnosticId()) {
                throw $failure;
            }
            throw CacheRuntimeOperationException::adapterFailed(CacheOperation::Lookup->value, $failure);
        } catch (Throwable $failure) {
            throw CacheRuntimeOperationException::adapterFailed(CacheOperation::Lookup->value, $failure);
        } finally {
            $this->guard->leave();
        }
    }

    private function invokeStore(CacheAdapterStoreRequest $request): CacheAdapterMutationResult
    {
        $this->guard->enter(CacheOperation::Store->value);
        try {
            return $this->binding->adapter()->store($request);
        } catch (Error $failure) {
            throw $failure;
        } catch (CacheRuntimeOperationException $failure) {
            if ('KGN-CACHE-RUNTIME-2035' === $failure->diagnosticId()) {
                throw $failure;
            }
            throw CacheRuntimeOperationException::adapterFailed(CacheOperation::Store->value, $failure);
        } catch (Throwable $failure) {
            throw CacheRuntimeOperationException::adapterFailed(CacheOperation::Store->value, $failure);
        } finally {
            $this->guard->leave();
        }
    }

    private function invokeReplace(CacheAdapterReplaceRequest $request): CacheAdapterMutationResult
    {
        $this->guard->enter(CacheOperation::Replace->value);
        try {
            return $this->binding->adapter()->replace($request);
        } catch (Error $failure) {
            throw $failure;
        } catch (CacheRuntimeOperationException $failure) {
            if ('KGN-CACHE-RUNTIME-2035' === $failure->diagnosticId()) {
                throw $failure;
            }
            throw CacheRuntimeOperationException::adapterFailed(CacheOperation::Replace->value, $failure);
        } catch (Throwable $failure) {
            throw CacheRuntimeOperationException::adapterFailed(CacheOperation::Replace->value, $failure);
        } finally {
            $this->guard->leave();
        }
    }

    private function invokeRemove(CacheAdapterRemoveRequest $request): CacheAdapterMutationResult
    {
        $this->guard->enter(CacheOperation::Remove->value);
        try {
            return $this->binding->adapter()->remove($request);
        } catch (Error $failure) {
            throw $failure;
        } catch (CacheRuntimeOperationException $failure) {
            if ('KGN-CACHE-RUNTIME-2035' === $failure->diagnosticId()) {
                throw $failure;
            }
            throw CacheRuntimeOperationException::adapterFailed(CacheOperation::Remove->value, $failure);
        } catch (Throwable $failure) {
            throw CacheRuntimeOperationException::adapterFailed(CacheOperation::Remove->value, $failure);
        } finally {
            $this->guard->leave();
        }
    }
}
