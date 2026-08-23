<?php

declare(strict_types=1);

namespace Kongono\Chie\Tests\Unit\Proposal;

use Kongono\Chie\Exception\InvalidChieProposalException;
use Kongono\Chie\Exception\InvalidChieValueException;
use Kongono\Chie\Proposal\ChieProposalAuthority;
use Kongono\Chie\Proposal\ChieProposalRequest;
use Kongono\Chie\Proposal\SafeCandidateText;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ChieProposalRequestTest extends TestCase
{
    public function testAdmitsOnlyTheClosedTypedIntent(): void
    {
        $request = new ChieProposalRequest(
            new ChieProposalAuthority(),
            'docs/engineering/php-support-policy.md',
            str_repeat('A', 64),
            new SafeCandidateText("before\n"),
            new SafeCandidateText("after\n"),
        );

        self::assertSame('propose', $request->authority()->operation());
        self::assertSame('exact-line-replacement-v1', $request->authority()->rule());
        self::assertSame('safe', $request->authority()->disclosure());
        self::assertSame('docs/engineering/php-support-policy.md', $request->targetPath());
        self::assertSame(str_repeat('A', 64), $request->baseSha256());
        self::assertSame("before\n", $request->before()->text());
        self::assertSame("after\n", $request->after()->text());
    }

    #[DataProvider('invalidPaths')]
    public function testRejectsNonCanonicalTargetPathsWithoutDisclosingThem(string $path): void
    {
        try {
            new ChieProposalRequest(new ChieProposalAuthority(), $path, str_repeat('A', 64), new SafeCandidateText("before\n"), new SafeCandidateText("after\n"));
            self::fail('Expected path rejection.');
        } catch (InvalidChieProposalException $exception) {
            self::assertStringNotContainsString($path, (string) $exception);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidPaths(): iterable
    {
        yield 'absolute' => ['/docs/file.md'];
        yield 'drive' => ['C:/docs/file.md'];
        yield 'backslash' => ['docs\\file.md'];
        yield 'parent' => ['docs/../file.md'];
        yield 'percent' => ['docs/%2e/file.md'];
        yield 'empty segment' => ['docs//file.md'];
    }

    #[DataProvider('invalidText')]
    public function testSafeCandidateTextIsAnAssertionWithAClosedShape(string $text): void
    {
        $this->expectException(InvalidChieValueException::class);
        new SafeCandidateText($text);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidText(): iterable
    {
        yield 'empty' => [''];
        yield 'unterminated' => ['value'];
        yield 'crlf' => ["value\r\n"];
        yield 'nul' => ["value\0\n"];
        yield 'invalid utf8' => ["\xFF\n"];
        yield 'oversized' => [str_repeat('x', 8192) . "\n"];
    }

    public function testBeforeAndAfterMustDiffer(): void
    {
        $this->expectException(InvalidChieProposalException::class);
        new ChieProposalRequest(
            new ChieProposalAuthority(),
            'docs/file.md',
            str_repeat('A', 64),
            new SafeCandidateText("same\n"),
            new SafeCandidateText("same\n"),
        );
    }
}
