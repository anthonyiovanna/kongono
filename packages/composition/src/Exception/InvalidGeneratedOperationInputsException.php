<?php

declare(strict_types=1);

namespace Kongono\Composition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports a bounded mismatch in explicit generated operation inputs. */
#[Api(ApiStatus::Experimental)]
final class InvalidGeneratedOperationInputsException extends CompositionException {}
