<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Process;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;

/** Carries one shell-free bounded process command. */
#[Api(ApiStatus::Experimental)]
final readonly class BuildProcessCommand
{
    /** @var list<string> */
    private array $arguments;
    /** @var array<string, string> */
    private array $environment;

    /**
     * @param array<array-key, mixed> $arguments
     * @param array<array-key, mixed> $environment
     */
    public function __construct(array $arguments, private string $workingDirectory, array $environment, private int $timeoutSeconds, private int $stdoutBytes, private int $stderrBytes)
    {
        if (!array_is_list($arguments)) {
            throw InvalidAssetAdapterContractException::process('arguments_not_list');
        }
        if (count($arguments) < 1 || count($arguments) > 64) {
            throw InvalidAssetAdapterContractException::process('argument_count', count($arguments), 64);
        }
        foreach ($arguments as $argument) {
            if (!is_string($argument) || '' === $argument || strlen($argument) > 2048 || 1 === preg_match('/[\x00-\x1F\x7F]/', $argument)) {
                throw InvalidAssetAdapterContractException::process('argument_grammar');
            }
        }
        $normalized = str_replace('\\', '/', $workingDirectory);
        if ($normalized !== $workingDirectory || 1 !== preg_match('/\A(?:[A-Za-z]:\/|\/)(?!.*(?:^|\/)\.\.?\/)(?:[^\x00-\x1F\x7F\/]+\/)*[^\x00-\x1F\x7F\/]+\z/D', $workingDirectory)) {
            throw InvalidAssetAdapterContractException::process('working_directory_grammar');
        }
        if (count($environment) > 8) {
            throw InvalidAssetAdapterContractException::process('environment_shape', count($environment), 8);
        }
        $env = [];
        foreach ($environment as $key => $value) {
            if (!is_string($key) || !is_string($value) || 1 !== preg_match('/\A[A-Z_][A-Z0-9_]{0,63}\z/D', $key) || strlen($value) > 2048 || 1 === preg_match('/[\x00-\x1F\x7F]/', $value) || isset($env[$key])) {
                throw InvalidAssetAdapterContractException::process('environment_grammar');
            }$env[$key] = $value;
        }
        ksort($env, SORT_STRING);
        if ($timeoutSeconds < 1 || $timeoutSeconds > 600 || $stdoutBytes < 0 || $stdoutBytes > 1_048_576 || $stderrBytes < 0 || $stderrBytes > 1_048_576) {
            throw InvalidAssetAdapterContractException::process('result_relation');
        }
        $this->arguments = $arguments;
        $this->environment = $env;
    }
    /** @return list<string> */ public function arguments(): array
    {
        return [...$this->arguments];
    }
    public function workingDirectory(): string
    {
        return $this->workingDirectory;
    }
    /** @return array<string,string> */ public function environment(): array
    {
        return $this->environment;
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
}
