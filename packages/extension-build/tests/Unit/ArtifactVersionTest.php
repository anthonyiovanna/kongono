<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Tests\Unit;

use Kongono\ExtensionBuild\ArtifactVersion;
use PHPUnit\Framework\TestCase;

final class ArtifactVersionTest extends TestCase
{
    public function testVersionVocabularyIsClosed(): void
    {
        self::assertSame([ArtifactVersion::Version1], ArtifactVersion::cases());
        self::assertSame(1, ArtifactVersion::Version1->value);
    }
}
