<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit;

use Kongono\ConsoleApplication\KongoProcessRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class KongoProcessRequestTest extends TestCase
{
    /**
     * @param list<string> $arguments
     * @param list<string> $tokens
     */
    #[DataProvider('acceptedRequests')]
    public function testAcceptedGrammar(array $arguments, bool $machine, bool $fullHelp, bool $commandHelp, array $tokens): void
    {
        $request = new KongoProcessRequest($arguments);

        self::assertTrue($request->isValid());
        self::assertSame($machine, $request->isMachine());
        self::assertSame($fullHelp, $request->requestsFullHelp());
        self::assertSame($commandHelp, $request->requestsCommandHelp());
        self::assertSame($tokens, $request->commandTokens());
    }

    /** @return iterable<string, array{list<string>, bool, bool, bool, list<string>}> */
    public static function acceptedRequests(): iterable
    {
        yield 'empty help' => [[], false, true, false, []];
        yield 'explicit help' => [['--help'], false, true, false, []];
        yield 'machine help' => [['--json'], true, true, false, []];
        yield 'machine explicit help' => [['--json', '--help'], true, true, false, []];
        yield 'command help' => [['application:build', '--help'], false, false, true, []];
        yield 'machine command help' => [['--json', 'application:build', '--help'], true, false, true, []];
        yield 'security check help' => [['security:check', '--help'], false, false, true, []];
        yield 'security inspect help' => [['--json', 'security:inspect', '--help'], true, false, true, []];
        yield 'plan' => [['application:build', '--plan', '--application-root=A', '--output-directory=B'], false, false, false, ['application:build', '--plan', '--application-root=A', '--output-directory=B']];
        yield 'machine apply' => [['--json', 'application:build', '--apply', '--application-root=A', '--output-directory=B', '--no-interaction'], true, false, false, ['application:build', '--apply', '--application-root=A', '--output-directory=B', '--no-interaction']];
        yield 'security check' => [['security:check', '--application-root=A', '--output-directory=B'], false, false, false, ['security:check', '--application-root=A', '--output-directory=B']];
        yield 'machine security inspect' => [['--json', 'security:inspect', '--application-root=A', '--output-directory=B', '--no-interaction'], true, false, false, ['security:inspect', '--application-root=A', '--output-directory=B', '--no-interaction']];
    }

    /** @param array<array-key, mixed> $arguments */
    #[DataProvider('rejectedRequests')]
    public function testRejectedGrammarAndBoundsDoNotRetainRawValues(array $arguments): void
    {
        $request = new KongoProcessRequest($arguments);

        self::assertFalse($request->isValid());
        self::assertSame([], $request->commandTokens());
        self::assertFalse($request->requestsFullHelp());
        self::assertFalse($request->requestsCommandHelp());
    }

    /** @return iterable<string, array{array<array-key, mixed>}> */
    public static function rejectedRequests(): iterable
    {
        yield 'misplaced json' => [['application:build', '--json']];
        yield 'duplicate json' => [['--json', '--json']];
        yield 'duplicate command json' => [['--json', 'application:build', '--json']];
        yield 'misplaced help' => [['application:build', '--plan', '--help']];
        yield 'duplicate help' => [['--help', '--help']];
        yield 'unknown command' => [['unknown']];
        yield 'empty token' => [['']];
        yield 'control byte' => [["application:build\nsecret"]];
        yield 'invalid utf8' => [["\xFF"]];
        yield 'non-string' => [[new \stdClass()]];
        yield 'token bytes' => [[str_repeat('a', 8193)]];
        yield 'count' => [array_fill(0, 130, 'a')];
        yield 'total bytes' => [array_fill(0, 9, str_repeat('a', 8192))];
    }
}
