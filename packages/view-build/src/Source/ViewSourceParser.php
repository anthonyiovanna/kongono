<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Internal\BuildGuard;

/** Parses one explicit source byte sequence without discovery or I/O. */
#[Api(ApiStatus::Experimental)]
final class ViewSourceParser
{
    public function parse(string $sourceBytes): ViewSourceSet
    {
        return BuildGuard::source($sourceBytes);
    }
}
