<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use PHPUnit\Framework\TestCase;

final class ExtensionInspectCommandHandlerTest extends TestCase
{
    public function testSupportedMetadataIsReadOnlyAndRequiresTwoInputs(): void
    {
        $source = json_decode((string) file_get_contents(dirname(__DIR__, 3) . '/resources/kongo.commands.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($source);
        $commands = $source['commands'] ?? null;
        self::assertIsArray($commands);
        $command = $commands[2] ?? null;
        self::assertIsArray($command);
        $inputs = $command['inputs'] ?? null;
        self::assertIsArray($inputs);
        self::assertSame('extension:inspect', $command['identifier']);
        self::assertSame(['declaration-artifact-path', 'generated-wiring-path'], array_column($inputs, 'name'));
        self::assertSame('read_only', $command['mutation']);
    }
}
