<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports malformed or internally inconsistent artifact data. */
#[Api(ApiStatus::Experimental)]
final class InvalidKnowledgeArtifactException extends KnowledgeException {}
