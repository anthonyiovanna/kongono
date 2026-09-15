<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;

/** Carries explicit finite limits for one adapter build. */
#[Api(ApiStatus::Experimental)]
final readonly class AdapterBuildLimits
{
    public function __construct(
        private int $timeoutSeconds,
        private int $stdoutBytes,
        private int $stderrBytes,
        private int $manifestBytes,
        private int $emittedFiles,
        private int $emittedFileBytes,
        private int $aggregateEmittedBytes,
        private int $outputDependencyDepth,
    ) {
        $values = [[$timeoutSeconds,1,600],[$stdoutBytes,0,1_048_576],[$stderrBytes,0,1_048_576],[$manifestBytes,1,8_388_608],[$emittedFiles,0,2_048],[$emittedFileBytes,0,67_108_864],[$aggregateEmittedBytes,0,134_217_728],[$outputDependencyDepth,0,256]];
        foreach ($values as [$value,$minimum,$maximum]) {
            if ($value < $minimum || $value > $maximum) {
                throw InvalidAssetAdapterContractException::request('limit_range');
            }
        }
    }
    public function timeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }
    public function stdoutBytes(): int
    {
        return $this->stdoutBytes;
    }
    public function stderrBytes(): int
    {
        return $this->stderrBytes;
    }
    public function manifestBytes(): int
    {
        return $this->manifestBytes;
    }
    public function emittedFiles(): int
    {
        return $this->emittedFiles;
    }
    public function emittedFileBytes(): int
    {
        return $this->emittedFileBytes;
    }
    public function aggregateEmittedBytes(): int
    {
        return $this->aggregateEmittedBytes;
    }
    public function outputDependencyDepth(): int
    {
        return $this->outputDependencyDepth;
    }
}
