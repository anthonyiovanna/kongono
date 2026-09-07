<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Input;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies one immutable input message without assigning authority. */
#[Api(ApiStatus::Experimental)]
enum InputRole: string
{
    case System = 'system';
    case User = 'user';
    case Assistant = 'assistant';
    case Tool = 'tool';
}
