<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Authorization\StorageOperationAuthorizer;
use Kongono\StorageApplication\Exception\StorageApplicationInvariantException;
use Kongono\StorageApplication\Exception\StorageAuthorizationDeniedException;
use Kongono\StorageApplication\Exception\StorageAuthorizationFailureException;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Result\StorageListResult;
use Kongono\StorageContract\Result\StorageMutationResult;
use Kongono\StorageContract\Stream\StorageByteSource;
use Kongono\StorageRuntime\Read\StorageRuntimeRead;
use Kongono\StorageRuntime\Runtime\ExactCurrentStorageRuntime;
use Throwable;

/** Authorizes each exact request before delegating once to its bound runtime. */
#[Api(ApiStatus::Experimental)]
final class AuthorizedStorageRuntime
{
    private bool $dispatching = false;

    public function __construct(
        private readonly ExactCurrentStorageRuntime $runtime,
        private readonly StorageOperationAuthorizer $authorizer,
    ) {}

    public function read(StorageReadRequest $request): StorageRuntimeRead
    {
        $this->enter();
        try {
            $this->authorize($request);
            return $this->runtime->read($request);
        } finally {
            $this->dispatching = false;
        }
    }

    public function create(StorageWriteRequest $request, StorageByteSource $source): StorageMutationResult
    {
        $this->enter();
        try {
            if (StorageOperation::Create !== $request->operation()) {
                throw StorageApplicationInvariantException::failed('operation_mismatch');
            }
            $this->authorize($request);
            return $this->runtime->create($request, $source);
        } finally {
            $this->dispatching = false;
        }
    }

    public function replace(StorageWriteRequest $request, StorageByteSource $source): StorageMutationResult
    {
        $this->enter();
        try {
            if (StorageOperation::Replace !== $request->operation()) {
                throw StorageApplicationInvariantException::failed('operation_mismatch');
            }
            $this->authorize($request);
            return $this->runtime->replace($request, $source);
        } finally {
            $this->dispatching = false;
        }
    }

    public function delete(StorageDeleteRequest $request): StorageMutationResult
    {
        $this->enter();
        try {
            $this->authorize($request);
            return $this->runtime->delete($request);
        } finally {
            $this->dispatching = false;
        }
    }

    public function list(StorageListRequest $request): StorageListResult
    {
        $this->enter();
        try {
            $this->authorize($request);
            return $this->runtime->list($request);
        } finally {
            $this->dispatching = false;
        }
    }

    private function enter(): void
    {
        if ($this->dispatching) {
            throw StorageApplicationInvariantException::failed('reentrancy');
        }
        $this->dispatching = true;
    }

    private function authorize(StorageReadRequest|StorageWriteRequest|StorageDeleteRequest|StorageListRequest $request): void
    {
        try {
            $allowed = $this->authorizer->authorize($request);
        } catch (Throwable $failure) {
            throw StorageAuthorizationFailureException::failed($failure);
        }
        if (true !== $allowed) {
            throw StorageAuthorizationDeniedException::denied();
        }
    }
}
