<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Output\OutputContext;

/** Escapes dynamic strings in the two compile-approved HTML contexts. */
#[Api(ApiStatus::Internal)]
final class ContextualEscaper
{
    public function escape(string $value, OutputContext $context, int $operationIndex): string
    {
        return match ($context) {
            OutputContext::HtmlText => htmlspecialchars($value, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', true),
            OutputContext::HtmlQuotedAttribute => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', true),
        };
    }
}
