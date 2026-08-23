<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports exact mismatch between an artifact and current admitted sources. */
#[Api(ApiStatus::Experimental)]
final class StaleKnowledgeArtifactException extends KnowledgeException {}
