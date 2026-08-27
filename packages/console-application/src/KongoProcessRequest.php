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

    private ?string $helpCommandIdentifier;

    /** @param array<array-key, mixed> $arguments */
    public function __construct(array $arguments)
    {
        [$this->valid, $this->machine, $this->fullHelp, $this->commandHelp, $this->helpCommandIdentifier, $this->commandTokens] = self::validate($arguments);
    }

    /**
     * @param array<array-key, mixed> $arguments
     * @return array{bool, bool, bool, bool, ?string, list<string>}
     */
    private static function validate(array $arguments): array
    {
        $accepted = [];
        $total = 0;
        if (count($arguments) > self::MAX_ARGUMENTS) {
            return [false, false, false, false, null, []];
        }
        foreach ($arguments as $argument) {
            if (!is_string($argument)) {
                return [false, false, false, false, null, []];
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
                return [false, false, false, false, null, []];
            }
            $accepted[] = $argument;
        }

        $machine = '--json' === ($accepted[0] ?? null);
        if ($machine) {
            array_shift($accepted);
        }
        if (in_array('--json', $accepted, true)) {
            return [false, false, false, false, null, []];
        }
        if ([] === $accepted || ['--help'] === $accepted) {
            return [true, $machine, true, false, null, []];
        }
        $commandIdentifiers = array_map(
            static fn(KongoCommand $command): string => $command->value,
            KongoCommand::cases(),
        );
        if (2 === count($accepted) && '--help' === $accepted[1] && in_array($accepted[0], $commandIdentifiers, true)) {
            return [true, $machine, false, true, $accepted[0], []];
        }
        if (
            !in_array($accepted[0], $commandIdentifiers, true)
            || in_array('--help', $accepted, true)
        ) {
            return [false, $machine, false, false, null, []];
        }

        return [true, $machine, false, false, null, $accepted];
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

    public function helpCommandIdentifier(): ?string
    {
        return $this->helpCommandIdentifier;
    }

    /** @return list<string> */
    public function commandTokens(): array
    {
        return $this->commandTokens;
    }
}
