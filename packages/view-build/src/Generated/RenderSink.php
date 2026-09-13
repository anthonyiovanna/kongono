<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Output\OutputContext;
use Kongono\ViewContract\Value\PresentationValue;

/** Defines the future runtime sink protocol without implementing rendering. */
#[Api(ApiStatus::Internal)]
interface RenderSink
{
    public function step(int $operationIndex): void;

    public function literal(string $bytes, int $operationIndex): void;

    public function value(PresentationValue $value, OutputContext $context, int $operationIndex): void;
}
