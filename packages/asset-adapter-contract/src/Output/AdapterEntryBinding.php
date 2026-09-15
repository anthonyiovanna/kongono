<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Output;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Identity\AdapterOutputIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;

/** Binds one application-owned logical entry point to one opaque output. */
#[Api(ApiStatus::Experimental)]
final readonly class AdapterEntryBinding
{
    public function __construct(private LogicalAssetIdentity $entryPoint, private AdapterOutputIdentity $output) {}
    public function entryPoint(): LogicalAssetIdentity
    {
        return $this->entryPoint;
    } public function output(): AdapterOutputIdentity
    {
        return $this->output;
    } public function orderingKey(): string
    {
        return $this->entryPoint->value() . "\0" . $this->output->value();
    }
}
