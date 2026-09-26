<?php

declare(strict_types=1);

namespace StorageMinimalApp;

use Kongono\StorageApplication\Authorization\StorageOperationAuthorizer;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;

/** Example policy for one demo object; this is not authentication. */
final class ExampleStorageAuthorizer implements StorageOperationAuthorizer
{
    public function authorize(StorageReadRequest|StorageWriteRequest|StorageDeleteRequest|StorageListRequest $request): bool
    {
        if ($request instanceof StorageListRequest) {
            return false;
        }
        return 'demo-objects' === $request->objectIdentity()->namespace()->value()
            && 'demo-object' === $request->objectIdentity()->logicalIdentity()->value()
            && in_array($request->operation(), [
                StorageOperation::Read,
                StorageOperation::Create,
                StorageOperation::Replace,
                StorageOperation::Delete,
            ], true);
    }
}
