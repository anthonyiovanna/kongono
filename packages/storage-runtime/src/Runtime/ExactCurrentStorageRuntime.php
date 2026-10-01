<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Runtime;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterRead;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Condition\StorageMutationConditionKind;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Result\StorageListResult;
use Kongono\StorageContract\Result\StorageMutationResult;
use Kongono\StorageContract\Stream\StorageByteSource;
use Kongono\StorageRuntime\Binding\StorageRuntimeBinding;
use Kongono\StorageRuntime\Exception\InvalidStorageRuntimeException;
use Kongono\StorageRuntime\Exception\StorageRuntimeOperationException;
use Kongono\StorageRuntime\Internal\AdapterResultValidator;
use Kongono\StorageRuntime\Internal\LifecycleFailureComposer;
use Kongono\StorageRuntime\Internal\MeteredWriteSource;
use Kongono\StorageRuntime\Internal\OperationScope;
use Kongono\StorageRuntime\Internal\RuntimeGuard;
use Kongono\StorageRuntime\Internal\ValidatedReadSource;
use Kongono\StorageRuntime\Read\StorageRuntimeRead;
use Throwable;

/** Executes exact-current synchronous storage operations through one adapter. */
#[Api(ApiStatus::Experimental)]
final class ExactCurrentStorageRuntime
{
    private readonly RuntimeGuard $guard;

    public function __construct(private readonly StorageRuntimeBinding $binding)
    {
        $this->guard = new RuntimeGuard();
    }

    public function read(StorageReadRequest $request): StorageRuntimeRead
    {
        $this->validateRead($request);
        $scope = $this->guard->enter($request->operationIdentity(), StorageOperation::Read);
        $source = null;
        $returned = null;
        $primary = null;
        $transferred = false;
        try {
            $read = $this->binding->adapter()->read($request);
            $source = $read->source();
            $this->validateReadRequestEcho($request, $read);
            $result = AdapterResultValidator::read($this->profile(), $request, $read->result());
            if (StorageReadOutcome::Completed !== $result->outcome()) {
                if (null !== $read->source()) {
                    throw InvalidStorageRuntimeException::evidence('read_source_relation', 'read', $result->outcome()->value);
                }
                $returned = new StorageRuntimeRead($result);
            } else {
                $evidence = $result->evidence();
                if (!$source instanceof StorageByteSource || null === $evidence || $source->closed()) {
                    throw InvalidStorageRuntimeException::evidence('read_source_relation', 'read', 'completed');
                }

                $returned = new StorageRuntimeRead($result, new ValidatedReadSource($source, $scope, $evidence->byteCount()->value(), $this->profile()->limits()->maxChunkBytes(), $evidence->contentDigest()));
                $transferred = true;
            }
        } catch (Error $failure) {
            $primary = $failure;
        } catch (InvalidStorageRuntimeException|StorageRuntimeOperationException $failure) {
            $primary = $failure;
        } catch (Throwable $failure) {
            $primary = StorageRuntimeOperationException::adapterFailed('read', $failure);
        }
        if ($transferred && $returned instanceof StorageRuntimeRead) {
            return $returned;
        }
        $cleanup = [];
        if (null !== $source) {
            try {
                if (!$source->closed()) {
                    $source->close();
                }
            } catch (Throwable $failure) {
                $cleanup[] = $failure;
            }
        }
        try {
            if (!$scope->retired()) {
                $scope->retire();
            }
        } catch (Throwable $failure) {
            $cleanup[] = $failure;
        }
        LifecycleFailureComposer::finish('read', $primary, $cleanup);
        if (!$returned instanceof StorageRuntimeRead) {
            throw new \LogicException('Storage runtime read did not produce a result.');
        }
        return $returned;
    }

    public function create(StorageWriteRequest $request, StorageByteSource $source): StorageMutationResult
    {
        return $this->write(StorageOperation::Create, $request, $source);
    }

    public function replace(StorageWriteRequest $request, StorageByteSource $source): StorageMutationResult
    {
        return $this->write(StorageOperation::Replace, $request, $source);
    }

    public function delete(StorageDeleteRequest $request): StorageMutationResult
    {
        $this->validateDelete($request);
        $scope = $this->guard->enter($request->operationIdentity(), StorageOperation::Delete);
        try {
            $result = AdapterResultValidator::mutation($this->profile(), StorageOperation::Delete, $request, $this->binding->adapter()->delete($request));
            $scope->retire();

            return $result;
        } catch (Error $failure) {
            $this->retireScope($scope, $failure);
        } catch (InvalidStorageRuntimeException|StorageRuntimeOperationException $failure) {
            $this->retireScope($scope, $failure);
        } catch (Throwable $failure) {
            $this->retireScope($scope, StorageRuntimeOperationException::adapterFailed('delete', $failure));
        }
    }

    public function list(StorageListRequest $request): StorageListResult
    {
        $this->validateList($request);
        $scope = $this->guard->enter($request->operationIdentity(), StorageOperation::List);
        try {
            $result = AdapterResultValidator::list($this->profile(), $request, $this->binding->adapter()->list($request));
            $scope->retire();

            return $result;
        } catch (Error $failure) {
            $this->retireScope($scope, $failure);
        } catch (InvalidStorageRuntimeException|StorageRuntimeOperationException $failure) {
            $this->retireScope($scope, $failure);
        } catch (Throwable $failure) {
            $this->retireScope($scope, StorageRuntimeOperationException::adapterFailed('list', $failure));
        }
    }

    private function write(StorageOperation $operation, StorageWriteRequest $request, StorageByteSource $source): StorageMutationResult
    {
        $this->validateWrite($operation, $request, $source);
        $scope = $this->guard->enter($request->operationIdentity(), $operation);
        $metered = new MeteredWriteSource($source, $operation->value, $request->declaredByteCount()->value(), $this->profile()->limits()->maxChunkBytes(), $request->contentDigest());
        $result = null;
        $primary = null;
        try {
            $result = $this->binding->adapter()->write($request, $metered);
            if (StorageMutationOutcome::Completed === $result->outcome()) {
                $metered->validateCompleted();
            }
            $result = AdapterResultValidator::mutation($this->profile(), $operation, $request, $result);
        } catch (Error $failure) {
            $primary = $failure;
        } catch (InvalidStorageRuntimeException|StorageRuntimeOperationException $failure) {
            $primary = $failure;
        } catch (Throwable $failure) {
            $primary = StorageRuntimeOperationException::adapterFailed($operation->value, $failure);
        }
        $cleanup = [];
        try {
            $metered->close();
        } catch (Throwable $failure) {
            $cleanup[] = $failure;
        }
        try {
            if (!$scope->retired()) {
                $scope->retire();
            }
        } catch (Throwable $failure) {
            $cleanup[] = $failure;
        }
        LifecycleFailureComposer::finish($operation->value, $primary, $cleanup);

        if (!$result instanceof StorageMutationResult) {
            throw new \LogicException('Storage runtime mutation did not produce a result.');
        }

        return $result;
    }

    private function validateRead(StorageReadRequest $request): void
    {
        $this->common($request->contractVersion(), $request->objectIdentity()->namespace(), StorageCapability::Read, StorageOperation::Read);
        if ($request->maximumByteCount()->value() > $this->profile()->limits()->maxObjectBytes()) {
            throw InvalidStorageRuntimeException::operation('object_byte_limit', 'read', context: ['byte_count' => $request->maximumByteCount()->value(), 'limit' => $this->profile()->limits()->maxObjectBytes()]);
        }
    }

    private function validateWrite(StorageOperation $operation, StorageWriteRequest $request, StorageByteSource $source): void
    {
        if ($request->operation() !== $operation) {
            throw InvalidStorageRuntimeException::operation('operation_mismatch', $operation->value);
        }
        $capability = StorageOperation::Create === $operation ? StorageCapability::Create : StorageCapability::Replace;
        $this->common($request->contractVersion(), $request->objectIdentity()->namespace(), $capability, $operation);
        $expectedCondition = StorageOperation::Create === $operation ? StorageMutationConditionKind::IfAbsent : StorageMutationConditionKind::IfVersion;
        if ($request->condition()->kind() !== $expectedCondition
            || (StorageOperation::Replace === $operation && !$this->profile()->capabilities()->contains(StorageCapability::ConditionalMutation))) {
            throw InvalidStorageRuntimeException::operation('mutation_condition', $operation->value);
        }
        if ($request->declaredByteCount()->value() > $this->profile()->limits()->maxObjectBytes()) {
            throw InvalidStorageRuntimeException::operation('object_byte_limit', $operation->value, context: ['byte_count' => $request->declaredByteCount()->value(), 'limit' => $this->profile()->limits()->maxObjectBytes()]);
        }
        if (!$this->profile()->capabilities()->contains(StorageCapability::ContentDigest) || $request->contentDigest()->algorithm() !== $this->profile()->digestAlgorithm()) {
            throw InvalidStorageRuntimeException::operation('digest_relation', $operation->value);
        }
        if ([] !== $request->metadata()->entries() && !$this->profile()->capabilities()->contains(StorageCapability::Metadata)) {
            throw InvalidStorageRuntimeException::operation('metadata_capability', $operation->value);
        }
        if ($source->closed()) {
            throw InvalidStorageRuntimeException::operation('source_closed', $operation->value);
        }
    }

    private function validateDelete(StorageDeleteRequest $request): void
    {
        $this->common($request->contractVersion(), $request->objectIdentity()->namespace(), StorageCapability::Delete, StorageOperation::Delete);
        if (StorageMutationConditionKind::IfVersion !== $request->condition()->kind() || !$this->profile()->capabilities()->contains(StorageCapability::ConditionalMutation)) {
            throw InvalidStorageRuntimeException::operation('mutation_condition', 'delete');
        }
    }

    private function validateList(StorageListRequest $request): void
    {
        $this->common($request->contractVersion(), $request->namespace(), StorageCapability::List, StorageOperation::List);
        if ($request->pageSize() > $this->profile()->limits()->maxListPageSize()) {
            throw InvalidStorageRuntimeException::operation('list_page_limit', 'list', context: ['byte_count' => $request->pageSize(), 'limit' => $this->profile()->limits()->maxListPageSize()]);
        }
    }

    private function common(\Kongono\StorageContract\ContractVersion $version, \Kongono\StorageContract\Identity\StorageNamespace $namespace, StorageCapability $capability, StorageOperation $operation): void
    {
        if (!$namespace->equals($this->profile()->namespace()) || !$this->profile()->capabilities()->contains($capability)) {
            throw InvalidStorageRuntimeException::operation('request_profile_relation', $operation->value);
        }
    }

    private function validateReadRequestEcho(StorageReadRequest $request, StorageAdapterRead $read): void
    {
        $echo = $read->request();
        if (!$echo->operationIdentity()->equals($request->operationIdentity())
            || !$echo->objectIdentity()->equals($request->objectIdentity())
            || !$echo->maximumByteCount()->equals($request->maximumByteCount())) {
            throw InvalidStorageRuntimeException::evidence('read_request_mismatch', 'read');
        }
    }

    private function profile(): StorageProfileDeclaration
    {
        return $this->binding->profile();
    }

    private function retireScope(OperationScope $scope, Throwable $primary): never
    {
        $cleanup = [];
        try {
            if (!$scope->retired()) {
                $scope->retire();
            }
        } catch (Throwable $failure) {
            $cleanup[] = $failure;
        }
        LifecycleFailureComposer::finish($scope->operation()->value, $primary, $cleanup);
        throw $primary;
    }
}
