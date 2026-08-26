<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Validates and detaches one bounded raw process-argument request. */
#[Api(ApiStatus::Experimental)]
final readonly class KongoProcessRequest
{
    private const MAX_ARGUMENTS = 129;
    private const MAX_ARGUMENT_BYTES = 8192;
    private const MAX_TOTAL_BYTES = 65_536;

    /** @var list<string> */
    private array $commandTokens;

    private bool $valid;

    private bool $machine;

    private bool $fullHelp;

    private bool $commandHelp;

    /** @param array<array-key, mixed> $arguments */
    public function __construct(array $arguments)
    {
        [$this->valid, $this->machine, $this->fullHelp, $this->commandHelp, $this->commandTokens] = self::validate($arguments);
    }

    /**
     * @param array<array-key, mixed> $arguments
     * @return array{bool, bool, bool, bool, list<string>}
     */
    private static function validate(array $arguments): array
    {
        $accepted = [];
        $total = 0;
        if (count($arguments) > self::MAX_ARGUMENTS) {
            return [false, false, false, false, []];
        }
        foreach ($arguments as $argument) {
            if (!is_string($argument)) {
                return [false, false, false, false, []];
            }
            $length = strlen($argument);
            $total += $length;
            if (
                $length < 1
                || $length > self::MAX_ARGUMENT_BYTES
                || $total > self::MAX_TOTAL_BYTES
                || 1 !== preg_match('//u', $argument)
                || 1 === preg_match('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u', $argument)
            ) {
                return [false, false, false, false, []];
            }
            $accepted[] = $argument;
        }

        $machine = '--json' === ($accepted[0] ?? null);
        if ($machine) {
            array_shift($accepted);
        }
        if (in_array('--json', $accepted, true)) {
            return [false, false, false, false, []];
        }
        if ([] === $accepted || ['--help'] === $accepted) {
            return [true, $machine, true, false, []];
        }
        if ([KongoCommand::ApplicationBuild->value, '--help'] === $accepted) {
            return [true, $machine, false, true, []];
        }
        if (
            KongoCommand::ApplicationBuild->value !== $accepted[0]
            || in_array('--help', $accepted, true)
        ) {
            return [false, $machine, false, false, []];
        }

        return [true, $machine, false, false, $accepted];
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function isMachine(): bool
    {
        return $this->machine;
    }

    public function requestsFullHelp(): bool
    {
        return $this->fullHelp;
    }

    public function requestsCommandHelp(): bool
    {
        return $this->commandHelp;
    }

    /** @return list<string> */
    public function commandTokens(): array
    {
        return $this->commandTokens;
    }
}
