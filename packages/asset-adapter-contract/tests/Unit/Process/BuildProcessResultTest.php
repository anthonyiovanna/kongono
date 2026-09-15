<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Process;

use Kongono\AssetAdapterContract\Process\BuildProcessResult;
use PHPUnit\Framework\TestCase;

final class BuildProcessResultTest extends TestCase
{
    public function testResultCarriesRawBoundedOperationEvidenceAndDigests(): void
    {
        $result = new BuildProcessResult(true, 0, 'out', 'err', false, false, false, false, true, true, 'none');
        self::assertTrue($result->succeeded());
        self::assertSame(hash('sha256', 'out'), $result->stdoutSha256());
        self::assertSame(3, $result->stderrByteCount());
    }
}
