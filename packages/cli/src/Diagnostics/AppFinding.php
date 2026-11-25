<?php

declare(strict_types=1);

namespace Kongono\Cli\Diagnostics;

use Kongono\Core\Diagnostics\SecretScrubber;

/**
 * Single framework-native 5W1H diagnostic finding produced by `KongonoAppInspector`.
 */
final readonly class AppFinding
{
    /**
     * @param 'error'|'warning' $severity
     * @param list<string>      $howToFix
     * @param list<string>      $didYouMean
     */
    public function __construct(
        public string $code,
        public string $severity,
        public string $who,
        public string $what,
        public string $why,
        public string $where,
        public array $howToFix,
        public ?string $codeFix = null,
        public ?string $command = null,
        public array $didYouMean = [],
        public ?string $file = null,
        public ?int $line = null,
        public bool $fixable = false,
    ) {}

    /**
     * @return array{
     *     code: string,
     *     severity: 'error'|'warning',
     *     who: string,
     *     what: string,
     *     why: string,
     *     where: string,
     *     file: ?string,
     *     line: ?int,
     *     fixable: bool,
     *     how_to_fix: list<string>,
     *     code_fix: ?string,
     *     command: ?string,
     *     did_you_mean: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity,
            'who' => SecretScrubber::scrub($this->who),
            'what' => SecretScrubber::scrub($this->what),
            'why' => SecretScrubber::scrub($this->why),
            'where' => SecretScrubber::scrub($this->where),
            'file' => null !== $this->file ? SecretScrubber::scrub($this->file) : null,
            'line' => $this->line,
            'fixable' => $this->fixable,
            'how_to_fix' => array_map(static fn(string $s): string => SecretScrubber::scrub($s), $this->howToFix),
            'code_fix' => null !== $this->codeFix ? SecretScrubber::scrub($this->codeFix) : null,
            'command' => null !== $this->command ? SecretScrubber::scrub($this->command) : null,
            'did_you_mean' => array_map(static fn(string $s): string => SecretScrubber::scrub($s), $this->didYouMean),
        ];
    }
}
