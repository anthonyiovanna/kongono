<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\AdapterContractVersion;
use Kongono\CacheAdapterContract\Request\CacheAdapterLookupRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterRemoveRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterReplaceRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupResult;
use Kongono\CacheAdapterContract\Result\CacheAdapterMutationResult;

/** Defines one directly supplied replaceable bounded cache adapter. */
#[Api(ApiStatus::Experimental)]
interface CacheAdapter
{
    public function protocolVersion(): AdapterContractVersion;

    public function identity(): CacheAdapterIdentity;

    public function limits(): CacheAdapterLimits;

    public function lookup(CacheAdapterLookupRequest $request): CacheAdapterLookupResult;

    public function store(CacheAdapterStoreRequest $request): CacheAdapterMutationResult;

    public function replace(CacheAdapterReplaceRequest $request): CacheAdapterMutationResult;

    public function remove(CacheAdapterRemoveRequest $request): CacheAdapterMutationResult;
}
