<?php

declare(strict_types=1);

namespace Kongono\Chie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the exact six explanation topics implemented by Phase 4D. */
#[Api(ApiStatus::Experimental)]
enum ChieTopic: string
{
    case DeveloperIntelligence = 'developer-intelligence';
    case ErrorHandling = 'error-handling';
    case KnowledgeArtifact = 'knowledge-artifact';
    case KnowledgeFoundation = 'knowledge-foundation';
    case Phase4Roadmap = 'phase-4-roadmap';
    case PhpSupport = 'php-support';
}
