<?php

declare(strict_types=1);

namespace Kongono\Cli\Upgrade;

/**
 * Contract for deterministic, AST/parser-verified Kongono upgrade codemods (`kongo upgrade`).
 */
interface CodemodInterface
{
    public function id(): string;

    public function title(): string;

    public function targetVersion(): string;

    public function supportsFile(string $relativePath): bool;

    /**
     * @return array{changed: bool, updated: string, summary: string}
     */
    public function transform(string $relativePath, string $content): array;
}
