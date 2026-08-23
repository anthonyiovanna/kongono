<?php

declare(strict_types=1);

namespace Kongono\Oshie\Tests\Unit;

use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Oshie\Exception\InvalidArtifactException;
use Kongono\Oshie\Oshie;
use Kongono\Oshie\OshieAnswerOutcome;
use Kongono\Oshie\OshieRequest;
use Kongono\Oshie\OshieStatus;
use PHPUnit\Framework\TestCase;

final class OshieTest extends TestCase
{
    public function testCatalogAndEveryLessonReturnCurrentExactCitations(): void
    {
        $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->root());
        $oshie = new Oshie();
        $catalog = $oshie->respondCurrent($this->root(), $artifact->canonicalBytes(), OshieRequest::catalog());
        self::assertSame(OshieStatus::Available, $catalog->status());
        self::assertCount(6, $catalog->curriculum());
        foreach ($catalog->curriculum() as $summary) {
            $response = $oshie->respondCurrent($this->root(), $artifact->canonicalBytes(), OshieRequest::lesson($summary->lessonId()));
            self::assertSame(OshieStatus::Taught, $response->status());
            self::assertNotNull($response->lesson());
            self::assertStringStartsWith('## ', $response->lesson()->text());
            self::assertSame($artifact->sourceSetSha256(), substr($response->lesson()->citation()->reference(), 14, 64));
        }
    }

    public function testExercisesHideAnswerFieldAndChecksAreExactAndStateless(): void
    {
        $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->root());
        $oshie = new Oshie();
        $practice = $oshie->respondCurrent($this->root(), $artifact->canonicalBytes(), OshieRequest::exercise('role-verifier'));
        self::assertSame(OshieStatus::Practice, $practice->status());
        self::assertArrayNotHasKey('accepted_answer', $practice->exercise()?->toArray() ?? []);
        $correct = $oshie->respondCurrent($this->root(), $artifact->canonicalBytes(), OshieRequest::check('role-verifier', 'hantei'));
        $incorrect = $oshie->respondCurrent($this->root(), $artifact->canonicalBytes(), OshieRequest::check('role-verifier', 'chie'));
        self::assertSame(OshieAnswerOutcome::Correct, $correct->attempt()?->outcome());
        self::assertSame(OshieAnswerOutcome::Incorrect, $incorrect->attempt()?->outcome());
        self::assertStringNotContainsString('chie', json_encode($incorrect->attempt()->toArray(), JSON_THROW_ON_ERROR));
        self::assertSame(OshieStatus::Practice, $oshie->respondCurrent($this->root(), $artifact->canonicalBytes(), OshieRequest::exercise('role-verifier'))->status());
    }

    public function testUnsupportedAndMalformedInputFailClosed(): void
    {
        $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->root());
        $response = (new Oshie())->respondCurrent($this->root(), $artifact->canonicalBytes(), OshieRequest::lesson('unknown-lesson'));
        self::assertSame(OshieStatus::Unsupported, $response->status());
        self::assertSame(['lesson-unsupported'], $response->uncertainties());
        $this->expectException(InvalidArtifactException::class);
        (new Oshie())->respondCurrent($this->root(), "{}\n", OshieRequest::catalog());
    }

    private function root(): string
    {
        return dirname(__DIR__, 4);
    }
}
