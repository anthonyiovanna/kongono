<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports rejected inventory, path, source, encoding, or bound input. */
#[Api(ApiStatus::Experimental)]
final class InvalidKnowledgeSourceException extends KnowledgeException {}
