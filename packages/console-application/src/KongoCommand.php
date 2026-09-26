<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact closed command family exposed by the supported executable. */
#[Api(ApiStatus::Experimental)]
enum KongoCommand: string
{
    case ApplicationGraphInspect = 'application-graph:inspect';
    case ApplicationBuild = 'application:build';
    case AssetBuild = 'asset:build';
    case AssetCheck = 'asset:check';
    case AssetResolve = 'asset:resolve';
    case ExtensionInspect = 'extension:inspect';
    case ObservabilityCheck = 'observability:check';
    case ObservabilityInspect = 'observability:inspect';
    case SecurityCheck = 'security:check';
    case SecurityInspect = 'security:inspect';
    case ViewBuild = 'view:build';
    case ViewCheck = 'view:check';
    case ViewInspect = 'view:inspect';
    case CacheBuild = 'cache:build';
    case CacheCheck = 'cache:check';
    case CachePlan = 'cache:plan';
    case StorageBuild = 'storage:build';
    case StorageCheck = 'storage:check';
    case StoragePlan = 'storage:plan';
}
