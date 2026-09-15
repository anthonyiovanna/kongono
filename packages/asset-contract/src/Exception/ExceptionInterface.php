<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Stringable;
use Throwable;

/** Marks all asset-contract diagnostic exceptions for callers. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable, Stringable
{
    public function diagnosticContext(): DiagnosticContext;

    public function diagnosticId(): string;

    public function diagnosticTitle(): string;

    public function diagnosticRemediation(): string;
}
