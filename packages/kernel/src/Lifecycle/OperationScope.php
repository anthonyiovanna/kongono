<?php

declare(strict_types=1);

namespace Kongono\Kernel\Lifecycle;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/**
 * Acts solely as an opaque, object-identity token for one active operation.
 *
 * The creating Kernel owns lifecycle completion. This Experimental scope contains
 * no identifier, data, callback, ambient state, or serialization contract; future
 * scoped-data and propagation mechanisms remain intentionally unresolved.
 */
#[Api(ApiStatus::Experimental)]
final class OperationScope {}
