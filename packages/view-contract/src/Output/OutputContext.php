<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Output;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the only initially supported compiler output contexts. */
#[Api(ApiStatus::Experimental)]
enum OutputContext: string
{
    case HtmlText = 'html_text';
    case HtmlQuotedAttribute = 'html_quoted_attribute';
}
