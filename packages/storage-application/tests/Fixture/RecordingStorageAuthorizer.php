<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Tests\Fixture;

use Kongono\StorageApplication\Authorization\StorageOperationAuthorizer;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Throwable;

final class RecordingStorageAuthorizer implements StorageOperationAuthorizer
{
    public int $calls = 0;
    public StorageReadRequest|StorageWriteRequest|StorageDeleteRequest|StorageListRequest|null $lastRequest = null;

    public function __construct(public bool $allow, public ?Throwable $failure = null) {}

    public function authorize(StorageReadRequest|StorageWriteRequest|StorageDeleteRequest|StorageListRequest $request): bool
    {
        ++$this->calls;
        $this->lastRequest = $request;
        if (null !== $this->failure) {
            throw $this->failure;
        }
        return $this->allow;
    }
}
