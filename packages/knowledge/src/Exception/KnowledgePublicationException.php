<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports an atomic no-clobber publication failure. */
#[Api(ApiStatus::Experimental)]
final class KnowledgePublicationException extends KnowledgeException {}
